import { test, expect, request, type Page } from '@playwright/test';
import { IS_BAKULEVO } from './support/theme';

// E2E-регрессия 3.81 «Справочник диагнозов: поиск МКБ-10 в редактировании лечения».
// Покрывает: поиск по коду → подстановка диагноза + кода → сохранение diagnosisCode.
// Прогоняется на almazovo-сборке (фича общая, ветка от develop).

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

test.describe.serial('3.81 Поиск МКБ-10 в редактировании лечения', () => {
  // В теме Бакулево лечение редактируется в модалке (секции «Лечение» .section-title нет).
  test.skip(IS_BAKULEVO, 'almazovo-сборка: секция «Лечение» (.section-title) отсутствует в модальной теме Бакулево');

  test('setup: больница + пациент + лечение (I48)', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;
    const suffix = Date.now();

    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `МКБ-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Диана',
      lastname: `Мкб${suffix}`,
      birthday: '1974-09-09',
      sex: 0,
      smsPhone: '8(900)444-00-06',
      address: 'г. Москва, ул. Тестовая',
      passport: '4444 000006',
      snils: '444-444-444 06',
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

    console.log('[3.81 setup]', demo);
  });

  test('СЦ-3.81.2/3/4: поиск кода → подстановка → сохранение diagnosisCode', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть секцию «Лечение» и войти в редактирование.
    await page.locator('.section-title', { hasText: 'Лечение' }).click();
    await page.locator('.btn-edit-treatment[title="Редактировать лечение"]').click();

    // Поиск кода I82.4 в «Код диагноза (МКБ-10)».
    const searchInput = page.locator('.multi-diagnosis-select .search-input-inline');
    await searchInput.fill('I82.4');
    const option = page.locator('.options-dropdown .option', { hasText: 'I82.4' });
    await expect(option).toBeVisible();
    await option.click();

    // Подстановка: поле «Диагноз» заполнено названием кода I82.4 (содержит «тромбоз»).
    const diagnosisInput = page.locator('.section-content input.edit-input').first();
    await expect(diagnosisInput).toHaveValue(/тромбоз/);

    // Сохранить → diagnosisCode уходит в PATCH.
    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/treatments') && r.request().method() === 'PATCH',
      ),
      page.locator('.btn-save-treatment').click(),
    ]);
    const updated = await resp.json();
    expect(updated.diagnosisCode).toBe('I82.4');

    console.log('[3.81] diagnosisCode сохранён:', updated.diagnosisCode);
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.81 teardown] демо-данные удалены');
  });
});
