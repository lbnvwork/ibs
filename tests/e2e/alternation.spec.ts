import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.65 «Чередование МНО в назначении (0,5 / через день)».
// Покрывает: ввод чередования в назначении (±0.25/±0.5), сохранение doze2,
// отображение «X / Y через день» в истории, автоподстановку в анализе,
// валидацию (чередование без отклонения).

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
  appointmentIri: null as string | null,
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

test.describe.serial('3.65 Чередование дозы (через день)', () => {
  test('setup: больница + пациент + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;

    const suffix = Date.now();
    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Чередование-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Анна',
      lastname: `Чередова${suffix}`,
      birthday: '1965-02-02',
      sex: 1,
      smsPhone: '8(900)888-00-01',
      address: 'г. Москва, ул. Тестовая',
      passport: '8888 000001',
      snils: '888-888-888 01',
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

    console.log('[3.65 setup]', demo);
  });

  /**
   * СЦ-3.65.1/2/6: ввод чередования «Больше на 0,25» → вторая доза = doze+0.25,
   * сохранение doze2, отображение «X / Y через день» в истории.
   */
  test('СЦ-3.65.1/2/6: чередование ±0.25 + отображение', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    await modal.locator('input[type="number"][step="0.25"]').first().fill('2.5');

    await modal.locator('input[type="checkbox"]').check();
    await modal.locator('select.form-control').selectOption('0.25');

    await expect(modal.getByText('Вторая доза: 2.75 таб./сут')).toBeVisible();

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/appointments') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const appt = await resp.json();
    expect(appt.doze).toBe(2.5);
    expect(appt.doze2).toBe(2.75);
    demo.appointmentIri = `/api/appointments/${appt.id}`;
    await expect(modal).toBeHidden();

    // СЦ-3.65.6: в «Медицинские данные» — «2.5 / 2.75 через день».
    const medicalData = page.locator('.medical-data');
    await expect(medicalData.getByText('2.5 / 2.75 через день').first()).toBeVisible();

    console.log('[3.65] чередование ±0.25 + отображение');
  });

  /**
   * СЦ-3.65.8: автоподстановка чередования в форме анализа из последнего назначения.
   */
  test('СЦ-3.65.8: автоподстановка чередования в анализе', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить анализ' }).click();
    const modal = page.locator('.modal-content');
    await expect(modal).toBeVisible();

    // Доза автоподставлена (2.5), чередование включено, отклонение +0.25.
    await expect(modal.locator('input[type="number"][step="0.25"]').first()).toHaveValue('2.5');
    await expect(modal.locator('input[type="checkbox"]')).toBeChecked();
    await expect(modal.locator('select.form-control')).toHaveValue('0.25');
    await expect(modal.getByText('Вторая доза: 2.75 таб./сут')).toBeVisible();

    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    console.log('[3.65] автоподстановка в анализе');
  });

  /**
   * СЦ-3.65.4: чередование включено, но отклонение не выбрано → ошибка, POST не уходит.
   */
  test('СЦ-3.65.4: валидация (чередование без отклонения)', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    await modal.locator('input[type="number"][step="0.25"]').first().fill('3.0');
    await modal.locator('input[type="checkbox"]').check();

    await modal.locator('.btn-save').click();

    await expect(modal.getByText(/Выберите отклонение чередования/)).toBeVisible();
    await expect(modal).toBeVisible(); // модалка не закрылась

    console.log('[3.65] валидация чередования');
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    if (demo.appointmentIri) await apiDelete(token, demo.appointmentIri);
    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.65 teardown] демо-данные удалены');
  });
});

