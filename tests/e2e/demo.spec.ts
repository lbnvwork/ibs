import { test, expect, request, type Page } from '@playwright/test';

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

// ФИО демо-пациента (СЦ-5). Для демо — фиксированное имя.
const PATIENT_LASTNAME = process.env.PATIENT_LASTNAME || 'Демо';
const PATIENT_FIRSTNAME = process.env.PATIENT_FIRSTNAME || 'Пациент';

// Общее состояние сценария (заполняется в шагах, переиспользуется далее).
const demo = {
  hospitalIri: null as string | null,
  hospitalId: null as number | null,
  supervisorIri: null as string | null,
  doctorPersonnelIri: null as string | null,
  doctorUserIri: null as string | null,
  adminToken: null as string | null,
  doctorToken: null as string | null,
  patientIri: null as string | null,
  patientId: null as string | null,
  treatmentIri: null as string | null,
  treatmentId: null as string | null,
  appointmentIri: null as string | null,
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

/**
 * Дата N дней назад (локальная, формат YYYY-MM-DD для <input type="date">).
 */
function dateDaysAgo(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

/**
 * Логин врача через UI. Каждый UI-шаг получает свежий `page`,
 * поэтому авторизация повторяется в начале шага.
 */
async function loginAsDoctor(page: Page): Promise<void> {
  // Неавторизованный переход на рабочий стол → редирект на логин.
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);

  await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
  await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
  await page.getByRole('button', { name: 'Войти' }).click();
  await page.waitForURL('/');

  // JWT сохранён в localStorage.
  const token = await page.evaluate(() => localStorage.getItem('token'));
  expect(token, 'JWT не сохранён после логина').toBeTruthy();
  demo.doctorToken = token;
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
    demo.hospitalId = hospital.id;

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
    await loginAsDoctor(page);
  });

  /**
   * Шаг 2 — СЦ-5 Создание пациента.
   * /patient/add → заполнение формы → «Сохранить» → редирект /patient/{id}/treatment/add.
   */
  test('Шаг 2 — СЦ-5 Создание пациента', async ({ page }) => {
    await loginAsDoctor(page);

    await page.goto('/patient/add');

    // Больница — демо-больница из шага 0 (по id).
    const hospital = page.getByLabel('Больница', { exact: false });
    await expect(hospital.locator('option')).not.toHaveCount(0);
    await hospital.selectOption(String(demo.hospitalId));

    await page.getByLabel('Фамилия', { exact: false }).fill(PATIENT_LASTNAME);
    await page.getByLabel('Имя', { exact: false }).fill(PATIENT_FIRSTNAME);
    await page.getByLabel('Отчество', { exact: false }).fill('Демонстрационный');
    await page.getByLabel('Дата рождения', { exact: false }).fill('1958-03-15');
    await page.getByLabel('Пол', { exact: true }).selectOption({ label: 'Мужской' });
    await page.getByLabel('Телефон', { exact: false }).fill('8(911)222-33-44');
    await page.getByLabel('Адрес', { exact: false }).fill('г. Москва, ул. Демо, д. 5');
    await page.getByLabel('Паспорт', { exact: false }).fill('4509 123456');
    await page.getByLabel('СНИЛС', { exact: false }).fill('112-233-445 95');
    await page.getByLabel('Полис', { exact: false }).fill('7711 2233445566');
    await page.locator('input[type="email"]').fill('demo.patient@example.com');
    await page.getByLabel('Комментарий', { exact: false }).fill('Демо-пациент для куратора');

    await page.getByRole('button', { name: 'Сохранить', exact: true }).click();

    // Пациент создан → редирект на добавление лечения.
    await page.waitForURL(/\/patient\/\d+\/treatment\/add/);
    const patientId = page.url().match(/\/patient\/(\d+)\//)?.[1];
    expect(patientId, 'не удалось извлечь id пациента из URL').toBeTruthy();
    demo.patientId = patientId!;
    demo.patientIri = `/api/patients/${patientId}`;

    console.log('[шаг2]', { patientId, patientIri: demo.patientIri });
  });

  /**
   * Шаг 3 — СЦ-6 Назначение лечения.
   * /patient/{id}/treatment/add → препарат (варфарин) + МКБ-10 + целевой МНО → «Сохранить лечение».
   */
  test('Шаг 3 — СЦ-6 Назначение лечения', async ({ page }) => {
    await loginAsDoctor(page);

    await page.goto(`/patient/${demo.patientId}/treatment/add`);

    // Препарат — варфарин (дождаться загрузки справочника).
    const drug = page.getByLabel('Препарат', { exact: false });
    await expect(drug.locator('option')).not.toHaveCount(0);
    await drug.selectOption({ label: 'варфарин' });

    // Диагноз по МКБ-10: поиск «фибрилляция» → выбор I48.
    await page.getByPlaceholder('Поиск или выберите...').fill('фибрилляция');
    const mkbOption = page.locator('input[type="checkbox"][value="I48"]');
    await mkbOption.waitFor({ state: 'visible', timeout: 10000 });
    await mkbOption.check();
    // Ждём автозаполнение диагноза из справочника.
    await expect(page.getByLabel('Диагноз (текст)', { exact: false })).toHaveValue(/.+/);

    // Целевой диапазон МНО.
    await page.getByLabel('Целевой МНО от', { exact: true }).fill('2');
    await page.getByLabel('Целевой МНО до', { exact: true }).fill('3');

    await page.getByLabel('Комментарий к лечению', { exact: false }).fill('Старт терапии варфарином (демо)');

    // «Сохранить лечение» → захватываем id созданного лечения из ответа API.
    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/treatments') && r.request().method() === 'POST',
      ),
      page.getByRole('button', { name: 'Сохранить лечение' }).click(),
    ]);
    const created = await resp.json();
    demo.treatmentId = String(created.id);
    demo.treatmentIri = `/api/treatments/${created.id}`;

    // Лечение создано → редирект на карточку пациента.
    await page.waitForURL(new RegExp(`/patient/${demo.patientId}(?:$|[/?#])`));

    console.log('[шаг3]', { treatmentId: demo.treatmentId, treatmentIri: demo.treatmentIri });
  });

  /**
   * Шаг 4 — СЦ-8 Анализы (МНО, ≥3 точки).
   * Карточка пациента → «Добавить анализ» → дата + МНО + доза → «Сохранить».
   */
  test('Шаг 4 — СЦ-8 Анализы (МНО, ≥3 точки)', async ({ page }) => {
    await loginAsDoctor(page);

    await page.goto(`/patient/${demo.patientId}`);
    const addTestBtn = page.getByRole('button', { name: 'Добавить анализ' });
    await expect(addTestBtn).toBeVisible();

    // Три точки МНО: ниже целевого (1.8) / в диапазоне (2.6) / выше (3.5) — для светофора.
    const points = [
      { daysAgo: 3, mno: '1.8', doze: '5' },
      { daysAgo: 2, mno: '2.6', doze: '5' },
      { daysAgo: 1, mno: '3.5', doze: '5' },
    ];

    for (const p of points) {
      await addTestBtn.click();
      const modal = page.locator('.modal-content');
      await modal.locator('input[type="date"]').fill(dateDaysAgo(p.daysAgo));
      await modal.locator('input[type="number"][step="0.1"]').fill(p.mno);
      await modal.locator('input[type="number"][step="0.25"]').fill(p.doze);
      await modal.getByRole('button', { name: 'Сохранить' }).click();
      await expect(modal).toBeHidden();
    }

    // Проверка: 3 анализа сохранены.
    const histories = await apiGet<Array<{ id: number }>>(
      demo.adminToken,
      `/api/test_histories?treatment=${demo.treatmentIri}`,
    );
    expect(histories.length, 'ожидается 3 анализа МНО').toBe(3);

    console.log('[шаг4]', { анализов: histories.length });
  });

  /**
   * Шаг 5 — СЦ-9 Витальные показатели.
   * Ручной ввод через UI: раздел «Витальные показатели» → карандаш ✎ → форма → «Сохранить».
   */
  test('Шаг 5 — СЦ-9 Витальные показатели', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрываем раздел «Витальные показатели» (заголовок секции, не VitalsCard).
    await page.locator('.section-title', { hasText: 'Витальные показатели' }).click();

    const vitalsCard = page.locator('.vitals-card');

    // Переход в режим редактирования (карандаш ✎).
    await vitalsCard.locator('.btn-icon-edit').click();

    // Заполняем форму (поля привязаны по тексту label в .form-row).
    const form = vitalsCard.locator('.vitals-form');
    await form.locator('.form-row', { hasText: 'Hb' }).locator('input').fill('132');
    await form.locator('.form-row', { hasText: 'ЧСС' }).locator('input').fill('76');
    await form.locator('.form-row', { hasText: 'Систолическое АД' }).locator('input').fill('122');
    await form.locator('.form-row', { hasText: 'Диастолическое АД' }).locator('input').fill('80');
    await form.locator('.form-row', { hasText: 'SpO₂' }).locator('input').fill('97');
    await form.locator('.form-row', { hasText: 'Вес' }).locator('input').fill('82');

    await vitalsCard.locator('.btn-save').click();

    // После сохранения форма закрывается, показатели отображаются.
    await expect(page.getByText(/122\/80 мм рт. ст./)).toBeVisible();
    await expect(page.getByText(/Последнее измерение/)).toBeVisible();
  });

  /**
   * Шаг 6 — СЦ-10 Назначение дозы (СППВР) + контрольная явка.
   * Карточка → «Добавить назначение» → «Рассчитать дозу (AI)» → «Сохранить».
   */
  test('Шаг 6 — СЦ-10 Доза СППВР (назначение + явка)', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Ждём загрузки карточки (кнопка «Добавить назначение»).
    const addAppointmentBtn = page.getByRole('button', { name: 'Добавить назначение' });
    await expect(addAppointmentBtn).toBeVisible();
    await addAppointmentBtn.click();

    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });

    // ИИ-подсказка: расчёт дозы.
    await page.getByRole('button', { name: /Рассчитать дозу/ }).click();
    const doseInput = modal.locator('input[type="number"][step="0.25"]').first();
    await expect(doseInput).toHaveValue(/.+/); // доза рассчитана и подставлена

    // Сохраняем назначение (захватываем id из ответа POST /api/appointments).
    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/appointments') && r.request().method() === 'POST',
      ),
      modal.locator('.btn-save').click(),
    ]);
    const appointment = await resp.json();
    demo.appointmentIri = `/api/appointments/${appointment.id}`;

    // Модалка закрылась.
    await expect(modal).toBeHidden();

    console.log('[шаг6]', { appointmentIri: demo.appointmentIri });
  });

  /**
   * Шаг 7 — СЦ-11 Фармакогенетика (CYP2C9*2, CYP2C9*3, VKORC1).
   * Раздел «Фармакогенетика» → ✎ → выбор генотипов → «Сохранить».
   */
  test('Шаг 7 — СЦ-11 Фармакогенетика', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть раздел «Фармакогенетика».
    await page.locator('.section-title', { hasText: 'Фармакогенетика' }).click();

    const pharm = page.locator('.pharmacogenetics-section');

    // Дождаться загрузки 4 маркеров для варфарина.
    await expect(pharm.locator('.marker-item')).toHaveCount(4);

    // Редактирование.
    await pharm.locator('.btn-icon-edit').click();

    // Выбор генотипов.
    await pharm.locator('.marker-item', { hasText: 'CYP2C9_2' }).locator('select').selectOption({ label: 'Гетерозигота' });
    await pharm.locator('.marker-item', { hasText: 'CYP2C9_3' }).locator('select').selectOption({ label: 'Норма' });
    await pharm.locator('.marker-item', { hasText: 'VKORC1_3673' }).locator('select').selectOption({ label: 'Гетерозигота' });
    await pharm.locator('.marker-item', { hasText: 'VKORC1_3730' }).locator('select').selectOption({ label: 'Норма' });

    await pharm.locator('.btn-save').click();

    // После сохранения маркеры отображают выбранные генотипы.
    await expect(pharm.locator('.marker-item', { hasText: 'CYP2C9_2' }).locator('.genotype')).toContainText('Гетерозигота');
    await expect(pharm.locator('.marker-item', { hasText: 'CYP2C9_3' }).locator('.genotype')).toContainText('Норма');

    console.log('[шаг7] фармакогенетика сохранена');
  });

  /**
   * Шаг 7а — СЦ-7 Персональные данные (inline edit).
   * Раздел «Персональные данные» → ✎ → изменить поля → «Сохранить» (PATCH /api/patients/{id}).
   */
  test('Шаг 7а — СЦ-7 Персональные данные (inline edit)', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть раздел «Персональные данные».
    await page.locator('.section-title', { hasText: 'Персональные данные' }).click();

    const patientCard = page.locator('.patient-info-compact');
    await expect(patientCard).toBeVisible();

    // Режим редактирования (карандаш ✎).
    await patientCard.locator('.btn-edit-treatment').click();

    // Изменить адрес и комментарий.
    await patientCard.locator('.info-group', { hasText: 'Проживание' }).locator('input').fill('г. Москва, ул. Обновлённая, д. 9');
    await patientCard.locator('.info-group', { hasText: 'Комментарий' }).locator('input').fill('Демо-пациент (отредактировано)');

    // Сохранить (захватываем PATCH).
    const [resp] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/patients/') && r.request().method() === 'PATCH'),
      patientCard.locator('.btn-save-treatment').click(),
    ]);
    expect(resp.ok()).toBeTruthy();

    // Изменения отобразились в режиме просмотра.
    await expect(patientCard.getByText('г. Москва, ул. Обновлённая, д. 9')).toBeVisible();
    await expect(patientCard.getByText('Демо-пациент (отредактировано)')).toBeVisible();

    console.log('[шаг7а] персональные данные отредактированы');
  });

  /**
   * Шаг 7б — СЦ-7 Лечение (inline edit).
   * Раздел «Лечение» → ✎ → изменить осложнения → «Сохранить» (PATCH /api/treatments/{id}).
   */
  test('Шаг 7б — СЦ-7 Лечение (inline edit)', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Раскрыть раздел «Лечение».
    await page.locator('.section-title', { hasText: 'Лечение' }).click();

    const treatmentSection = page.locator('.collapsible-section', { hasText: 'Лечение' });

    // Редактирование (карандаш ✎ с title «Редактировать лечение»).
    await treatmentSection.getByTitle('Редактировать лечение').click();

    // Изменить осложнения (уникальное поле, без маски).
    await treatmentSection.locator('.info-group', { hasText: 'Осложнения' }).locator('input').fill('Артериальная гипертензия');

    // Сохранить (захватываем PATCH).
    const [resp] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/treatments/') && r.request().method() === 'PATCH'),
      treatmentSection.locator('.btn-save-treatment').click(),
    ]);
    expect(resp.ok()).toBeTruthy();

    // Изменение отобразилось.
    await expect(treatmentSection.getByText('Артериальная гипертензия')).toBeVisible();

    console.log('[шаг7б] лечение отредактировано');
  });

  /**
   * Шаг 7в — Назначение из боковой панели (сайдбар «Сформировать рекомендации пациенту»).
   * Отличается от шага 6 точкой входа: полная модалка AppointmentAdd (showAppointmentModal).
   */
  test('Шаг 7в — Назначение из боковой панели', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Кнопка сайдбара «Сформировать рекомендации пациенту» (иконка, без текста).
    // Дождаться, пока лечение загрузится и кнопка станет активной (isTreatmentActive).
    const recommendBtn = page.getByTitle('Сформировать рекомендации пациенту');
    await expect(recommendBtn).not.toHaveClass(/disabled-button/);
    await recommendBtn.click();

    const modal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(modal).toBeVisible();

    // Рассчитать дозу.
    await page.getByRole('button', { name: /Рассчитать дозу/ }).click();
    const doseInput = modal.locator('input[type="number"][step="0.25"]').first();
    await expect(doseInput).toHaveValue(/.+/);

    // Сохранить.
    const [resp] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/appointments') && r.request().method() === 'POST'),
      modal.locator('.btn-save').click(),
    ]);
    const appointment = await resp.json();

    // Модалка закрылась.
    await expect(modal).toBeHidden();

    console.log('[шаг7в] назначение из сайдбара, id:', appointment.id);
  });

  /**
   * Шаг 8 — СЦ-2 Мониторинг (светофор/триаж).
   * Рабочий стол → вкладка «Варфарин» → строка демо-пациента подсвечена красным (МНО 3.5 > 3.0).
   */
  test('Шаг 8 — СЦ-2 Мониторинг (светофор/триаж)', async ({ page }) => {
    await loginAsDoctor(page);

    // Выбрать вкладку препарата «Варфарин».
    await page.getByRole('button', { name: 'Варфарин' }).click();

    // Дождаться загрузки таблицы и найти строку демо-пациента.
    const patientRow = page.locator('tr', { hasText: `${PATIENT_LASTNAME} ${PATIENT_FIRSTNAME}` });
    await expect(patientRow).toBeVisible();

    // Красная подсветка: последний МНО 3.5 выше целевого диапазона (2–3).
    await expect(patientRow).toHaveClass(/highlight-red/);

    // Диагноз отображается.
    await expect(patientRow.getByText('Фибрилляция и трепетание предсердий')).toBeVisible();

    console.log('[шаг8] светофор: красная подсветка демо-пациента');
  });

  /**
   * Шаг 9 — СЦ-7 Карточка: полная история + график МНО.
   * Таблица «Медицинские данные» (анализы + назначения) + график МНО (canvas).
   */
  test('Шаг 9 — СЦ-7 Карточка: история + график МНО', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${demo.patientId}`);

    // Ждём загрузки «Медицинские данные» (история).
    const medicalData = page.locator('.medical-data');
    await expect(medicalData).toBeVisible();

    // График МНО отрисован (canvas).
    await expect(page.locator('.mno-chart-container canvas')).toBeVisible();

    // 3 анализа МНО в таблице.
    await expect(page.locator('.indicator-mno')).toHaveCount(3);

    console.log('[шаг9] карточка: история + график МНО');
  });
});
