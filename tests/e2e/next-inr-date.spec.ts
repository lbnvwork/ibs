import { test, expect, request, type Page } from '@playwright/test';

// E2E-регрессия 3.66 «Дата следующей сдачи МНО при назначении».
// Покрывает: ввод даты следующей сдачи в форме назначения, сохранение nextTestDt,
// отображение в «Медицинские данные» (колонка «Следующая сдача МНО»),
// валидацию даты в прошлом.

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
  appointmentIris: [] as string[],
};

function dateDaysAgo(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

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

test.describe.serial('3.66 Дата следующей сдачи МНО', () => {
  test('setup: больница + пациент + лечение', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const token = demo.adminToken;

    const suffix = Date.now();
    const hospital = await apiPost<{ id: number }>(token, '/api/hospitals', {
      name: `Дата-МНО-больница-${suffix}`,
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    const patient = await apiPost<{ id: number }>(token, '/api/patients', {
      hospital: demo.hospitalIri,
      firstname: 'Пётр',
      lastname: `ДатаМно${suffix}`,
      birthday: '1968-07-07',
      sex: 0,
      smsPhone: '8(900)999-00-01',
      address: 'г. Москва, ул. Тестовая',
      passport: '9999 000001',
      snils: '999-999-999 01',
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

    console.log('[3.66 setup]', demo);
  });

  /**
   * СЦ-3.66.1/2: дата следующей сдачи МНО сохраняется и отображается.
   */
  test('СЦ-3.66.1/2: дата сохраняется и отображается', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    // Доза 2.5.
    await modal.locator('input[type="number"][step="0.25"]').first().fill('2.5');

    // Дата следующей сдачи МНО = через 7 дней (будущее).
    const futureDate = dateDaysAgo(-7);
    await modal.locator('input[type="date"]').nth(1).fill(futureDate);

    // Сохранить → захватить POST /api/appointments.
    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/appointments') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const appt = await resp.json();
    expect(appt.nextTestDt).toBeTruthy(); // дата сохранена
    demo.appointmentIris.push(`/api/appointments/${appt.id}`);
    await expect(modal).toBeHidden();

    // СЦ-3.66.2: в «Медицинские данные» колонка «Следующая сдача МНО» не пустая.
    const row = page.locator('.medical-data tr.appointment-row').first();
    await expect(row.locator('td').nth(4)).not.toHaveText('—');

    console.log('[3.66] дата сохраняется + отображается');
  });

  /**
   * СЦ-3.66.3: без даты → назначение сохраняется, дата пустая.
   */
  test('СЦ-3.66.3: без даты → пусто', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    await modal.locator('input[type="number"][step="0.25"]').first().fill('3.0');
    // nextTestDt не заполняем.

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/appointments') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const appt = await resp.json();
    expect(appt.nextTestDt ?? null).toBeNull();
    demo.appointmentIris.push(`/api/appointments/${appt.id}`);
    await expect(modal).toBeHidden();

    console.log('[3.66] без даты → null');
  });

  /**
   * СЦ-3.66.4: дата в прошлом → ошибка, POST не уходит.
   */
  test('СЦ-3.66.4: дата в прошлом → ошибка', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    await modal.locator('input[type="number"][step="0.25"]').first().fill('3.0');

    // Дата следующей сдачи МНО раньше даты назначения (7 дней назад).
    await modal.locator('input[type="date"]').nth(1).fill(dateDaysAgo(7));

    await modal.locator('.btn-save').click();

    await expect(
      modal.getByText(/Дата следующей сдачи не может быть раньше даты назначения/),
    ).toBeVisible();
    await expect(modal).toBeVisible(); // модалка не закрылась

    console.log('[3.66] дата в прошлом → ошибка');
  });

  test.afterAll(async () => {
    const token = demo.adminToken;
    if (!token) return;

    for (const iri of demo.appointmentIris) await apiDelete(token, iri);
    if (demo.treatmentIri) await apiDelete(token, demo.treatmentIri);
    if (demo.patientIri) await apiDelete(token, demo.patientIri);
    if (demo.hospitalIri) await apiDelete(token, demo.hospitalIri);

    console.log('[3.66 teardown] демо-данные удалены');
  });
});

