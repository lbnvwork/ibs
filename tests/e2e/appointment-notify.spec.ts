import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.69 «Отправка MAX-уведомления при добавлении назначения».
// Покрывает UI-часть: автозаполнение «Сообщения пациенту» (шаблоны appointment_dose /
// appointment_alternate по модели дозы) и сохранение итогового текста в
// Appointment.comment. Асинхронная отправка в MAX (СЦ-4…СЦ-9) покрыта phpunit
// (AppointmentSaveProcessorTest) и здесь не проверяется (внешний канал).

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
  appointmentIris: [] as string[],
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

test.describe.serial('3.69 MAX-уведомление при добавлении назначения', () => {
  test('setup: больница + пациент + лечение + анализ (МНО)', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;

    const suffix = Date.now();
    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Назначение-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Михаил',
      lastname: `Назначение${suffix}`,
      birthday: '1970-03-03',
      sex: 0,
      smsPhone: '8(900)666-00-04',
      address: 'г. Москва, ул. Тестовая',
      passport: '6666 000004',
      snils: '666-666-666 04',
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

    // Анализ с МНО=2.3 — предусловие СЦ-1 («известен последний МНО»).
    const th = await apiPost<{ id: number }>(token, '/api/test_histories', {
      treatment: demo.treatmentIri,
      creationDt: '2026-09-08T12:00:00+00:00',
      mno: 2.3,
      doze: 2.5,
      doze2: -1,
      drug: '/api/drugs/1',
    });
    demo.testHistoryIris.push(`/api/test_histories/${th.id}`);

    console.log('[3.69 setup]', demo);
  });

  /**
   * СЦ-1: автозаполнение «обычная доза» → appointment_dose.
   */
  test('СЦ-1: автозаполнение «обычная доза»', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    const comment = modal.locator('textarea');
    await modal.locator('input[type="number"][step="0.25"]').fill('2.5');

    await expect(comment).toHaveValue(/ВАМ НУЖНО ПРИНИМАТЬ 2\.5/);
    await expect(comment).toHaveValue(/Ваше МНО - 2\.3/);

    console.log('[3.69] автозаполнение «обычная доза» работает');
  });

  /**
   * СЦ-2: автозаполнение «чередование» → appointment_alternate.
   */
  test('СЦ-2: автозаполнение «чередование»', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    const comment = modal.locator('textarea');
    await modal.locator('input[type="number"][step="0.25"]').fill('2.5');
    await modal.getByRole('checkbox').check(); // Чередование (через день)
    await modal.locator('select').selectOption('0.25'); // doze2 = 2.75

    await expect(comment).toHaveValue(/ВАМ НУЖНО ЧЕРЕДОВАТЬ 2\.5 и 2\.75/);

    console.log('[3.69] автозаполнение «чередование» работает');
  });

  /**
   * СЦ-3: редактирование сообщения врачом + сохранение итогового текста в comment.
   */
  test('СЦ-3: редактирование сообщения + сохранение comment', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    const comment = modal.locator('textarea');
    await modal.locator('input[type="number"][step="0.25"]').fill('2.5');
    await expect(comment).toHaveValue(/ВАМ НУЖНО ПРИНИМАТЬ 2\.5/);

    // Врач правит текст (dirty-флаг).
    const edited = 'Принимайте 2.5 мг варфарина ежедневно (уточнено врачом).';
    await comment.fill(edited);

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/appointments') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const created = await resp.json();
    expect(created.comment).toBe(edited); // сохранён итоговый текст
    demo.appointmentIris.push(`/api/appointments/${created.id}`);

    await expect(modal).toBeHidden();

    console.log('[3.69] comment сохранён:', created.comment);
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    for (const iri of demo.appointmentIris) await apiDelete(token, iri);
    for (const iri of demo.testHistoryIris) await apiDelete(token, iri);
    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.69 teardown] демо-данные удалены');
  });
});

