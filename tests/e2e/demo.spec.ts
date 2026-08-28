import { test, expect, request } from '@playwright/test';

// ===== Конфиг демо-прогона (env) =====
// E2E_BASE_URL — базовый URL (локально http://nginx, демо https://test.bloodcontrol.ru).
// DEMO=1 — данные остаются после прогона (режим демо); иначе teardown чистит за собой.
const BASE_URL = process.env.E2E_BASE_URL || 'http://nginx';
const DEMO = process.env.DEMO === '1';

// Креды админа для setup (на тест-сервере demo/demo12345 с ROLE_ADMIN).
const ADMIN_LOGIN = process.env.ADMIN_LOGIN || 'demo';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'demo12345';

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
 * Шаг 0 — Setup: тестовый Hospital, Supervisor и врач (MedicalPersonnel + User).
 * Админ-токен нужен для POST /api/users (ROLE_ADMIN).
 */
test('Шаг 0 — setup демо-данных (Hospital, Supervisor, врач)', async () => {
  demo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);

  // 1. Тестовая больница.
  const hospital = await apiPost<{ id: number }>(demo.adminToken, '/api/hospitals', {
    name: 'Демо-больница',
    region: 'Москва',
  });
  demo.hospitalIri = `/api/hospitals/${hospital.id}`;

  // 2. Врач — профиль (MedicalPersonnel), привязка к больнице.
  const personnel = await apiPost<{ id: number }>(demo.adminToken, '/api/medical_personnels', {
    name: 'Демо-врач',
    post: 'Кардиолог',
    hospital: demo.hospitalIri,
  });
  demo.doctorPersonnelIri = `/api/medical_personnels/${personnel.id}`;

  // 3. Врач — учётная запись (User).
  // ВАЖНО: password/roles/medicalPersonnel НЕ пишутся через API (нет в user:write) —
  // пароль докручиваем отдельно (DB + app:hash-passwords), см. следующий шаг/заметку.
  const user = await apiPost<{ id: number }>(demo.adminToken, '/api/users', {
    login: 'demo.doctor',
    userName: 'Демо-врач',
  });
  demo.doctorUserIri = `/api/users/${user.id}`;

  // 4. Тестовый Supervisor, привязка к врачу.
  const supervisor = await apiPost<{ id: number }>(demo.adminToken, '/api/supervisors', {
    user: demo.doctorUserIri,
  });
  demo.supervisorIri = `/api/supervisors/${supervisor.id}`;

  console.log('[setup]', demo);
});
