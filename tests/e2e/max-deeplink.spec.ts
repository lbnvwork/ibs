import { test, expect, request, type Page } from '@playwright/test';
import { IS_BAKULEVO } from './support/theme';

// E2E-регрессия 3.70 «Отображение референтной ссылки MAX (диплинк) в карточке пациента».
// Покрывает: блок «Ссылка для мессенджера MAX», получение ссылки, статус привязки,
// стабильность токена, копирование (+fallback).

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

async function apiGet<T>(token: string, path: string): Promise<T> {
  const ctx = await request.newContext({
    baseURL: BASE_URL,
    extraHTTPHeaders: { Authorization: `Bearer ${token}` },
  });
  const resp = await ctx.get(path);
  expect(resp.ok(), `GET ${path} → ${resp.status()}`).toBeTruthy();
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

test.describe.serial('3.70 Ссылка MAX (диплинк) в карточке пациента', () => {
  test('setup: больница + пациент + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;

    const suffix = Date.now();
    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `MAX-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Сергей',
      lastname: `Макс${suffix}`,
      birthday: '1972-09-09',
      sex: 0,
      smsPhone: '8(900)777-00-02',
      address: 'г. Москва, ул. Тестовая',
      passport: '7777 000002',
      snils: '777-777-777 02',
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

    console.log('[3.70 setup]', demo);
  });

  /**
   * СЦ-1/2/4: блок ссылки MAX виден; «Получить ссылку» → url + статус «Чат не привязан».
   */
  test('СЦ-1/2/4: получение ссылки + статус', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть секцию «Ссылка для мессенджера MAX» (в Бакулево блок виден в дашборде сразу).
    if (!IS_BAKULEVO) {
      await page.locator('.section-title', { hasText: 'Ссылка для мессенджера MAX' }).click();
    }
    const block = page.locator('.max-deeplink');
    await expect(block).toBeVisible();
    await expect(block.getByText(/Ссылка для привязки чата MAX/)).toBeVisible();

    // Получить ссылку.
    await block.getByRole('button', { name: 'Получить ссылку' }).click();

    // Ссылка показана + статус «Чат не привязан» (контакта max нет).
    const urlLink = block.locator('a.max-deeplink-url');
    await expect(urlLink).toBeVisible();
    await expect(urlLink).toHaveAttribute('href', /^https:\/\//);
    await expect(block.getByText('Чат не привязан')).toBeVisible();

    // Кнопка «Копировать» доступна.
    await expect(block.getByRole('button', { name: 'Копировать' })).toBeVisible();

    console.log('[3.70] ссылка получена + статус');
  });

  /**
   * СЦ-3: стабильность токена — повторный запрос API возвращает тот же url.
   */
  test('СЦ-3: стабильность токена (повторный запрос)', async () => {
    const first = await apiGet<{ url: string; bound: boolean }>(
      demo.adminToken!,
      `/api/patients/${demo.patientId}/max-deeplink`,
    );
    const second = await apiGet<{ url: string; bound: boolean }>(
      demo.adminToken!,
      `/api/patients/${demo.patientId}/max-deeplink`,
    );

    expect(first.url).toBeTruthy();
    expect(first.url).toBe(second.url); // токен стабилен
    expect(first.bound).toBe(false); // контакта max нет

    console.log('[3.70] токен стабилен');
  });

  /**
   * СЦ-8: копирование ссылки (clipboard или fallback «Скопируйте вручную»).
   */
  test('СЦ-8: копирование ссылки', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    if (!IS_BAKULEVO) {
      await page.locator('.section-title', { hasText: 'Ссылка для мессенджера MAX' }).click();
    }
    const block = page.locator('.max-deeplink');
    await block.getByRole('button', { name: 'Получить ссылку' }).click();
    await expect(block.locator('a.max-deeplink-url')).toBeVisible();

    await block.getByRole('button', { name: 'Копировать' }).click();

    // Либо «Скопировано», либо fallback «Скопируйте вручную» (headless без clipboard).
    const feedback = block.locator('.max-deeplink-hint');
    await expect(feedback).toBeVisible();
    await expect(feedback).toHaveText(/Скопировано|Скопируйте вручную/);

    console.log('[3.70] копирование');
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.70 teardown] демо-данные удалены');
  });
});

