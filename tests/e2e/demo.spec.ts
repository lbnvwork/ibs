import { test, expect, request } from '@playwright/test';

// ===== Конфиг демо-прогона (env) =====
// E2E_BASE_URL — базовый URL (локально http://nginx, демо https://test.bloodcontrol.ru).
// DEMO=1 — данные остаются после прогона (режим демо); иначе teardown чистит за собой.
const BASE_URL = process.env.E2E_BASE_URL || 'http://nginx';
const DEMO = process.env.DEMO === '1';

// Креды админа для setup (на тест-сервере demo/demo12345 с ROLE_ADMIN).
const ADMIN_LOGIN = process.env.ADMIN_LOGIN || 'demo';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'demo12345';

// Креды врача. ВАЖНО: врач создаётся ЗАРАНЕЕ командой app:create-user (3.43),
// т.к. password/roles НЕ пишутся через POST /api/users (нет в user:write).
const DOCTOR_LOGIN = process.env.DOCTOR_LOGIN || 'demo.doctor';
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || 'demo12345';

// Общее состояние сценария (заполняется в шагах, переиспользуется далее).
const demo = {
  hospitalIri: null as string | null,
  supervisorIri: null as string | null,
  doctorPersonnelIri: null as string | null,
  doctorUserIri: null as string | null,
  adminToken: null as string | null,
  doctorToken: null as string | null,
};

/**
 * Логин через API → JWT-токен.
 */
async function apiLogin(login: string, password: string): Promise<string> {
  const ctx = await request.newContext({ baseURL: BASE_URL });
  const resp = await ctx.post('/api/login', { data: { login, password } });
  expect(resp.ok(), `login ${login} → ${resp.status()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body.token as string;
}

/**
 * POST-запрос к API с Bearer-токеном. Возвращает созданный ресурс.
 */
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

/**
 * GET-запрос к API с Bearer-токеном.
 */
async function apiGet<T>(token: string, path: string): Promise<T> {
  const ctx = await request.newContext({
    baseURL: BASE_URL,
    extraHTTPHeaders: { Authorization: `Bearer ${token}` },
  });
  const resp = await ctx.get(path);
  expect(resp.ok(), `GET ${path} → ${resp.status()}: ${await resp.text()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body as T;
}

/**
 * PATCH-запрос к API с Bearer-токеном. Возвращает обновлённый ресурс.
 */
async function apiPatch<T>(token: string, path: string, data: object): Promise<T> {
  const ctx = await request.newContext({
    baseURL: BASE_URL,
    extraHTTPHeaders: { Authorization: `Bearer ${token}` },
  });
  const resp = await ctx.patch(path, { data });
  expect(resp.ok(), `PATCH ${path} → ${resp.status()}: ${await resp.text()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body as T;
}

test.describe.serial('3.35 Демо-сценарий (куратор)', () => {
  /**
   * Шаг 0 — Setup через API: тестовый Hospital и Supervisor.
   * Врач (User + MedicalPersonnel) создаётся ЗАРАНЕЕ командой:
   *   php bin/console app:create-user --login demo.doctor --password demo12345 --role ROLE_ADMIN --name "Демо-врач"
   * здесь мы только находим его и привязываем к больнице.
   */
  test('Шаг 0 — setup: Hospital + Supervisor + врач', async () => {
    demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);

    // 1. Тестовая больница.
    const hospital = await apiPost<{ id: number }>(demo.adminToken, '/api/hospitals', {
      name: 'Демо-больница',
      region: 'Москва',
    });
    demo.hospitalIri = `/api/hospitals/${hospital.id}`;

    // 2. Находим заранее созданного врача (app:create-user) по логину.
    // GET /api/users (без Accept: ld+json) отдаёт плоский JSON-массив.
    const users = await apiGet<Array<{ id: number; login: string; medicalPersonnel?: unknown }>>(
      demo.adminToken,
      '/api/users',
    );
    const doctor = users.find((u) => u.login === DOCTOR_LOGIN);
    expect(doctor, `врач «${DOCTOR_LOGIN}» не найден — выполните app:create-user`).toBeTruthy();
    demo.doctorUserIri = `/api/users/${doctor!.id}`;

    // medicalPersonnel может прийти IRI-строкой или объектом.
    const mp = doctor!.medicalPersonnel;
    if (typeof mp === 'string') {
      demo.doctorPersonnelIri = mp;
    } else if (mp && typeof mp === 'object') {
      demo.doctorPersonnelIri =
        (mp as { '@id'?: string; id?: number })['@id'] ??
        `/api/medical_personnels/${(mp as { id: number }).id}`;
    }

    // 3. Привязываем профиль врача к больнице.
    if (demo.doctorPersonnelIri) {
      await apiPatch(demo.adminToken, demo.doctorPersonnelIri, { hospital: demo.hospitalIri });
    }

    // 4. Тестовый Supervisor, привязка к врачу.
    const supervisor = await apiPost<{ id: number }>(demo.adminToken, '/api/supervisors', {
      user: demo.doctorUserIri,
    });
    demo.supervisorIri = `/api/supervisors/${supervisor.id}`;

    console.log('[setup]', demo);
  });

  /**
   * Шаг 1 — СЦ-1 Авторизация врача.
   * Неавторизованный переход на / → редирект /login; логин → / + JWT в localStorage.
   */
  test('Шаг 1 — СЦ-1 Авторизация врача', async ({ page }) => {
    // Неавторизованный переход на рабочий стол → редирект на логин.
    await page.goto('/');
    await expect(page).toHaveURL(/\/login/);

    // Логин врача.
    await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
    await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
    await page.getByRole('button', { name: 'Войти' }).click();
    await page.waitForURL('/');

    // JWT сохранён в localStorage.
    const token = await page.evaluate(() => localStorage.getItem('token'));
    expect(token, 'JWT не сохранён после логина').toBeTruthy();
    demo.doctorToken = token;
  });
});
