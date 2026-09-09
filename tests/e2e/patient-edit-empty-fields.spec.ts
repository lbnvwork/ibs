import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.78 «Фикс BUG-3.58-01: редактирование персональных данных (пустые поля)».
// Покрывает: частичное редактирование (только телефон) при пустом необязательном поле
// (email) — сохранение проходит без «Неверный формат email».

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

test.describe.serial('3.78 Фикс пустых полей в редактировании персональных данных', () => {
  test('setup: больница + пациент (обязательные заполнены, email пуст) + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;
    const suffix = Date.now();

    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Пустые-поля-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    // Обязательные поля заполнены; email НЕ задаём (пустой).
    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Елена',
      lastname: `Пустая${suffix}`,
      birthday: '1978-02-02',
      sex: 0,
      smsPhone: '8(900)333-00-07',
      address: 'г. Москва, ул. Тестовая',
      passport: '3333 000007',
      snils: '333-333-333 07',
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

    console.log('[3.78 setup]', demo);
  });

  test('СЦ-3.78.1/2: частичное редактирование (только телефон) без ошибок', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть «Персональные данные» и войти в редактирование.
    await page.locator('.section-title', { hasText: 'Персональные данные' }).click();
    await page.locator('.btn-edit-treatment[title="Редактировать данные"]').click();

    // Изменить только телефон (email/комментарий остаются пустыми).
    await page.getByPlaceholder('8(XXX)XXX-XX-XX').fill('8(900)111-22-33');

    // Сохранить → PATCH успешен, без ошибки формата пустого email.
    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/patients') && r.request().method() === 'PATCH',
      ),
      page.locator('.btn-save-treatment').click(),
    ]);
    expect(resp.status()).toBe(200);

    // Нет ошибки валидации («Неверный формат email» / «Формат: XXXX XXXXXX»).
    await expect(page.getByText(/Неверный формат email|Формат:/)).toHaveCount(0);

    // Форма вышла из режима редактирования (кнопка ✎ снова видна).
    await expect(page.locator('.btn-edit-treatment[title="Редактировать данные"]')).toBeVisible();

    console.log('[3.78] частичное редактирование прошло без ошибок');
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.78 teardown] демо-данные удалены');
  });
});
