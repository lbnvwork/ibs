import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.68 «Отправка MAX-уведомления при добавлении анализа (МНО)».
// Покрывает UI-часть: автозаполнение «Сообщения пациенту» по шаблону analysis_result
// (СЦ-1) и редактирование/сохранение итогового текста в TestHistory.comment (СЦ-2).
// Сама асинхронная отправка в MAX (СЦ-3…СЦ-8) покрыта юнит-тестами бэкенда
// (TestHistorySaveProcessorTest) и здесь не проверяется (внешний канал).

const BASE_URL = process.env.E2E_BASE_URL || 'http://nginx';
const ADMIN_LOGIN = process.env.ADMIN_LOGIN || 'demo';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'demo12345';
const DOCTOR_LOGIN = process.env.DOCTOR_LOGIN || 'demo.doctor';
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || 'demo12345';

const demo = {
  adminToken: null as string | null,
  hospitalIri: null as string | null,
  patientIri: null as string | null,
  patientId: null as string | null,
  treatmentIri: null as string | null,
  testHistoryIris: [] as string[],
};

async function apiLogin(login: string, password: string): Promise<string> {
  const ctx = await request.newContext({ baseURL: BASE_URL });
  const resp = await ctx.post('/api/login', { data: { login, password } });
  expect(resp.ok(), `login ${login} → ${resp.status()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body.token as string;
}

async function apiPost<T>(token: string, path: string, data: object): Promise<T> {
  const ctx = await request.newContext({
    baseURL: BASE_URL,
    extraHTTPHeaders: { Authorization: `Bearer ${token}` },
  });
  const resp = await ctx.post(path, { data });
  expect(resp.ok(), `POST ${path} → ${resp.status()}: ${await resp.text()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body as T;
}

async function apiDelete(token: string, path: string): Promise<void> {
  const ctx = await request.newContext({
    baseURL: BASE_URL,
    extraHTTPHeaders: { Authorization: `Bearer ${token}` },
  });
  const resp = await ctx.delete(path);
  expect(resp.ok(), `DELETE ${path} → ${resp.status()}`).toBeTruthy();
  await ctx.dispose();
}

async function loginAsDoctor(page: Page): Promise<void> {
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);
  await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
  await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
  await page.getByRole('button', { name: 'Войти' }).click();
  await page.waitForURL('/');
}

test.describe.serial('3.68 MAX-уведомление при добавлении анализа', () => {
  test('setup: больница + пациент + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;

    const suffix = Date.now();
    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Анализ-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Анна',
      lastname: `Анализ${suffix}`,
      birthday: '1975-04-04',
      sex: 1,
      smsPhone: '8(900)888-00-03',
      address: 'г. Москва, ул. Тестовая',
      passport: '8888 000003',
      snils: '888-888-888 03',
    });
    demo.patientId = String(patient.id);
    demo.patientIri = `/api/patients/${patient.id}`;

    const treatment = await apiPost<{ id: number }>(token, '/api/treatments', {
      patient: demo.patientIri,
      drug: '/api/drugs/1',
      diagnosis: 'Фибрилляция и трепетание предсердий',
      diagnosisCode: 'I48',
      mnoFrom: 2.0,
      mnoTo: 3.0,
      begDt: '2026-08-01T08:00:00+00:00',
    });
    demo.treatmentIri = `/api/treatments/${treatment.id}`;

    console.log('[3.68 setup]', demo);
  });

  /**
   * СЦ-1: автозаполнение «Сообщения пациенту» при вводе МНО.
   */
  test('СЦ-1: автозаполнение сообщения по шаблону analysis_result', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить анализ' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Добавить анализ' });
    await expect(modal).toBeVisible();

    const comment = modal.locator('textarea');
    await expect(comment).toHaveValue(''); // изначально пусто

    // Ввод МНО → debounce 300 мс → превью-эндпоинт → автозаполнение.
    await modal.locator('input[type="number"][step="0.1"]').fill('2.3');

    await expect(comment).toHaveValue(/Ваше МНО - 2\.3/);
    await expect(comment).toHaveValue(/оставьте прежней/);

    console.log('[3.68] автозаполнение работает');
  });

  /**
   * СЦ-2: редактирование сообщения врачом + сохранение итогового текста в comment.
   */
  test('СЦ-2: редактирование сообщения + сохранение comment', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить анализ' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Добавить анализ' });
    await expect(modal).toBeVisible();

    await modal.locator('input[type="number"][step="0.1"]').fill('2.3');
    const comment = modal.locator('textarea');
    await expect(comment).toHaveValue(/Ваше МНО - 2\.3/);

    // Обязательная доза.
    await modal.locator('input[type="number"][step="0.25"]').fill('2.5');

    // Врач правит текст (dirty-флаг).
    const edited = 'Ваше МНО - 2.3. Дозировку оставьте прежней (уточнено врачом)';
    await comment.fill(edited);

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/test_histories') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const created = await resp.json();
    expect(created.comment).toBe(edited); // сохранён итоговый текст
    demo.testHistoryIris.push(`/api/test_histories/${created.id}`);

    await expect(modal).toBeHidden();

    console.log('[3.68] comment сохранён:', created.comment);
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    for (const iri of demo.testHistoryIris) await apiDelete(token, iri);
    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.68 teardown] демо-данные удалены');
  });
});

