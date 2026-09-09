import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.80 «Бакулево: редактирование пола в карточке пациента».
// Прогоняется на bakulevo-сборке. Пол: 0=женский, 1=мужской (конвенция приложения).
// Покрывает: изменение пола в карточке + обновление пола в шапке дашборда.

const THEME = process.env.E2E_THEME;
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

test.describe.serial('3.80 Редактирование пола в карточке пациента', () => {
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('setup: больница + пациент (мужской) + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;
    const suffix = Date.now();

    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Пол-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Валентин',
      lastname: `Пол${suffix}`,
      birthday: '1968-06-06',
      sex: 1, // мужской
      smsPhone: '8(900)555-00-05',
      address: 'г. Москва, ул. Тестовая',
      passport: '5555 000005',
      snils: '555-555-555 05',
      email: 'test@example.com',
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

    console.log('[3.80 setup]', demo);
  });

  test('СЦ-3.80: изменение пола (мужской → женский) + обновление шапки', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Шапка показывает «муж» (sex=1).
    const headerMeta = page.locator('.dash-header .patient-meta').first();
    await expect(headerMeta).toContainText('муж');

    // Открыть редактирование персональных данных.
    await page.locator('.icon-more[aria-label="Редактировать данные"]').click();
    await expect(page.locator('.modal-panel')).toBeVisible();

    // Войти в режим редактирования внутри PatientCard.
    await page.locator('.modal-panel .btn-edit-treatment').click();
    const sexSelect = page.locator('.modal-panel select');
    await expect(sexSelect).toHaveValue('1'); // мужской

    // Изменить пол на «Женский».
    await sexSelect.selectOption('0');
    await page.locator('.modal-panel .btn-save-treatment').click();

    // Модалка закрылась, шапка обновилась → «жен».
    await expect(page.locator('.modal-panel')).toBeHidden();
    await expect(page.locator('.dash-header .patient-meta').first()).toContainText('жен');

    console.log('[3.80] пол изменён на женский');
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.80 teardown] демо-данные удалены');
  });
});
