import { test, expect, request, type Page } from '@playwright/test';

// Регрессионный тест на баг BUG-3.35-01:
// форма редактирования карточки (витальные/персональные/лечение) не сбрасывается
// при переключении пациента в боковой панели. Сейчас тест ПАДАЕТ (баг есть),
// поэтому помечен test.fixme — после фикса снять пометку.

const BASE_URL = process.env.E2E_BASE_URL || 'http://nginx';
const ADMIN_LOGIN = process.env.ADMIN_LOGIN || 'demo';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'demo12345';
const DOCTOR_LOGIN = process.env.DOCTOR_LOGIN || 'demo.doctor';
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || 'demo12345';

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

async function loginAsDoctor(page: Page): Promise<void> {
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);
  await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
  await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
  await page.getByRole('button', { name: 'Войти' }).click();
  await page.waitForURL('/');
}

// setup-данные: 2 пациента с лечением; у пациента A — витальные АД 122/80.
async function seedTwoPatients(): Promise<{ aId: number; bId: number; lastNameB: string }> {
  const token = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
  const suffix = Date.now();
  const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
    name: `Баг-больница-${suffix}`,
    region: 'Москва',
  });
  const mkPatient = (lastname: string, firstname: string, phone: string, passport: string, snils: string) =>
    apiPost<{ id: number }>(token, '/api/patients', {
      lastname,
      firstname,
      birthday: '1970-01-01',
      sex: 0,
      smsPhone: phone,
      address: 'г. Москва, ул. Тестовая',
      passport,
      snils,
      hospital: `/api/hospitals/${hospital.id}`,
    });

  const a = await mkPatient(`Смит${suffix}`, 'Анна', '8(900)111-11-11', '1111 111111', '111-111-111 11');
  const b = await mkPatient(`Джонс${suffix}`, 'Борис', '8(900)222-22-22', '2222 222222', '222-222-222 22');

  // Оба пациента с лечением (иначе карточка не рендерится).
  for (const id of [a.id, b.id]) {
    await apiPost(token, '/api/treatments', {
      patient: `/api/patients/${id}`,
      drug: '/api/drugs/1',
      begDt: '2026-09-02',
      mnoFrom: 2,
      mnoTo: 3,
      diagnosis: 'Фибрилляция и трепетание предсердий',
      diagnosisCode: 'I48',
    });
  }

  // Витальные только у A.
  await apiPost(token, '/api/patient_vitals', {
    patient: `/api/patients/${a.id}`,
    treatment: null,
    recordDt: new Date().toISOString(),
    systolicPressure: 122,
    diastolicPressure: 80,
  });

  return { aId: a.id, bId: b.id, lastNameB: `Джонс${suffix}` };
}

test.fail('BUG-3.35-01: форма редактирования сбрасывается при переключении пациента', async ({ page }) => {
  const { aId, bId, lastNameB } = await seedTwoPatients();

  await loginAsDoctor(page);
  await page.goto(`/patient/${aId}`);

  // Раскрыть «Витальные показатели» и войти в редактирование (данные A).
  await page.locator('.section-title', { hasText: 'Витальные показатели' }).click();
  const vitalsCard = page.locator('.vitals-card');
  await vitalsCard.locator('.btn-icon-edit').click();
  await expect(
    vitalsCard.locator('.form-row', { hasText: 'Систолическое АД' }).locator('input'),
  ).toHaveValue('122');

  // Переключение на пациента B через боковую панель (клиентская навигация).
  await page.locator('.patient-item', { hasText: lastNameB }).first().click();
  await page.waitForURL(new RegExp(`/patient/${bId}`));

  // ОЖИДАНИЕ: режим редактирования сброшен — карандаш ✎ снова виден (форма закрыта).
  // Сейчас (баг) editing остаётся true, ✎ скрыт → тест падает.
  await expect(vitalsCard.locator('.btn-icon-edit')).toBeVisible();
});
