import { test, expect, request, type Page } from '@playwright/test';

// Генерация скриншотов для инструкции-путеводителя «Демо-гайд Бакулево» (3.58).
// Прогоняется на bakulevo-сборке. Скриншоты → tests/e2e/screenshots/bakulevo/.
// Использует демо-данные (3.77): пациент 40001 (Иван Иванов) с активным лечением.

const THEME = process.env.E2E_THEME;
const BASE_URL = process.env.E2E_BASE_URL || 'http://nginx';
const ADMIN_LOGIN = process.env.ADMIN_LOGIN || 'demo';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'demo12345';
const DOCTOR_LOGIN = process.env.DOCTOR_LOGIN || 'demo.doctor';
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || 'demo12345';
const DEMO_PATIENT_ID = '40001';
const OUT = 'screenshots/bakulevo';

// Временный пациент с «разнобойным» МНО (выше/ниже целевого) — для скрина графика.
const chartDemo = {
  adminToken: null as string | null,
  hospitalIri: null as string | null,
  patientIri: null as string | null,
  patientId: null as string | null,
  treatmentIri: null as string | null,
  historyIris: [] as string[],
  appointmentIris: [] as string[],
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
  const ctx = await request.newContext({ baseURL: BASE_URL, extraHTTPHeaders: { Authorization: `Bearer ${token}` } });
  const resp = await ctx.post(path, { data });
  expect(resp.ok(), `POST ${path} → ${resp.status()}: ${await resp.text()}`).toBeTruthy();
  const body = await resp.json();
  await ctx.dispose();
  return body as T;
}

async function apiDelete(token: string, path: string): Promise<void> {
  const ctx = await request.newContext({ baseURL: BASE_URL, extraHTTPHeaders: { Authorization: `Bearer ${token}` } });
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

test.describe('guide: Бакулево (десктоп) — скриншоты путеводителя', () => {
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('снимки позитивных сценариев', async ({ page }) => {
    // 1. Вход
    await page.goto('/');
    await expect(page).toHaveURL(/\/login/);
    await page.screenshot({ path: `${OUT}/01-login.png` });

    // 2. Логин + список пациентов (триаж)
    await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
    await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
    await page.getByRole('button', { name: 'Войти' }).click();
    await page.waitForURL('/');
    await expect(page.locator('.triage-badge').first()).toBeVisible(); // ждём загрузку списка
    await page.screenshot({ path: `${OUT}/02-patient-list.png`, fullPage: true });

    // 3. Карточка пациента — дашборд «Обзор»
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Текущие показатели')).toBeVisible();
    await page.screenshot({ path: `${OUT}/03-patient-card-overview.png`, fullPage: true });

    // 3а. График МНО с выходами за диапазон (временный пациент: МНО выше и ниже целевого).
    chartDemo.adminToken = await apiLogin(ADMIN_LOGIN, ADMIN_PASSWORD);
    const t = chartDemo.adminToken;
    const suffix = Date.now();
    const h = await apiPost<{ id: number }>(t, '/api/hospitals', { name: `График-ЛПУ-${suffix}`, region: 'Москва' });
    chartDemo.hospitalIri = `/api/hospitals/${h.id}`;
    const p = await apiPost<{ id: number }>(t, '/api/patients', {
      hospital: chartDemo.hospitalIri, firstname: 'График', lastname: `Мно${suffix}`,
      birthday: '1960-01-01', sex: 1, smsPhone: '8(900)000-00-09', address: 'г. Москва',
      passport: '9999 000099', snils: '999-999-999 09',
    });
    chartDemo.patientIri = `/api/patients/${p.id}`;
    chartDemo.patientId = String(p.id);
    const tr = await apiPost<{ id: number }>(t, '/api/treatments', {
      patient: chartDemo.patientIri, drug: '/api/drugs/1',
      diagnosis: 'Фибрилляция и трепетание предсердий', diagnosisCode: 'I48',
      mnoFrom: 2.0, mnoTo: 3.0, begDt: '2026-08-01T08:00:00+00:00',
    });
    chartDemo.treatmentIri = `/api/treatments/${tr.id}`;
    for (const [i, mno] of [4.5, 3.4, 2.5, 1.6, 1.4].entries()) {
      const th = await apiPost<{ id: number }>(t, '/api/test_histories', {
        treatment: chartDemo.treatmentIri, creationDt: `2026-09-0${i + 1}T10:00:00+00:00`,
        mno, doze: 2.5, doze2: -1, drug: '/api/drugs/1',
      });
      chartDemo.historyIris.push(`/api/test_histories/${th.id}`);
      // Назначение в тот же день — чтобы на графике отображалась доза.
      const appt = await apiPost<{ id: number }>(t, '/api/appointments', {
        treatment: chartDemo.treatmentIri, appointmentDt: `2026-09-0${i + 1}T12:00:00+00:00`,
        doze: 3.0, doze2: -1, drug: '/api/drugs/1',
      });
      chartDemo.appointmentIris.push(`/api/appointments/${appt.id}`);
    }
    await page.goto(`/patient/${chartDemo.patientId}`);
    await expect(page.getByText('Динамика МНО')).toBeVisible();
    await page.locator('.dash-card', { hasText: 'Динамика МНО' }).screenshot({ path: `${OUT}/chart-mno.png` });
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Текущие показатели')).toBeVisible();

    // 4. Вкладка «История наблюдений»
    await page.getByRole('tab', { name: 'История наблюдений' }).click();
    await expect(page.locator('.medical-data')).toBeVisible();
    await page.screenshot({ path: `${OUT}/04-history-tab.png`, fullPage: true });

    // 5. Форма анализа — автозаполнение «Сообщение пациенту» (3.68)
    await page.getByRole('tab', { name: 'Обзор' }).click();
    await page.getByRole('button', { name: 'Добавить анализ' }).click();
    const analysisModal = page.locator('.modal-content', { hasText: 'Добавить анализ' });
    await expect(analysisModal).toBeVisible();
    await analysisModal.locator('input[type="number"][step="0.1"]').fill('2.4');
    await expect(analysisModal.locator('textarea')).toHaveValue(/Ваше МНО/);
    await page.screenshot({ path: `${OUT}/05-analysis-form.png` });
    await analysisModal.locator('.btn-cancel').click();

    // 6. Форма назначения — автозаполнение (3.69)
    await page.getByRole('button', { name: 'Добавить назначение' }).click();
    const apptModal = page.locator('.modal-content', { hasText: 'Новое назначение' });
    await expect(apptModal).toBeVisible();
    await apptModal.locator('input[type="number"][step="0.25"]').fill('2.5');
    await expect(apptModal.locator('textarea')).toHaveValue(/ВАМ НУЖНО ПРИНИМАТЬ/);
    await page.screenshot({ path: `${OUT}/06-appointment-form.png` });
    await apptModal.locator('.btn-cancel').click();

    // 7. Ссылка для мессенджера MAX (3.70)
    await page.locator('.max-deeplink').getByRole('button', { name: 'Получить ссылку' }).click();
    await expect(page.locator('a.max-deeplink-url')).toBeVisible();
    await page.screenshot({ path: `${OUT}/07-max-deeplink.png` });

    // 8. Редактирование персональных данных (PatientCard)
    await page.locator('.icon-more[aria-label="Редактировать данные"]').click();
    await expect(page.locator('.modal-panel')).toBeVisible();
    await page.locator('.modal-panel').screenshot({ path: `${OUT}/edit-patient.png` });
    await page.locator('.modal-panel .modal-close').click();

    // 9. Редактирование витальных показателей (VitalsCard)
    // 3.79: «Изменить» сразу открывает форму редактирования (autoEdit), без плиток + ✎.
    await page.getByRole('button', { name: 'Изменить' }).click();
    await expect(page.locator('.modal-panel')).toBeVisible();
    await expect(page.locator('.modal-panel .vitals-form')).toBeVisible();
    await page.locator('.modal-panel').screenshot({ path: `${OUT}/edit-vitals.png` });
    await page.locator('.modal-panel .modal-close').click();

    // 10. Редактирование лечения (TreatmentCard)
    await page.locator('.icon-more[aria-label="Редактировать лечение"]').click();
    await expect(page.locator('.modal-panel')).toBeVisible();
    await page.locator('.modal-panel').screenshot({ path: `${OUT}/edit-treatment.png` });
    await page.locator('.modal-panel .modal-close').click();

    // 11. Создание пациента (PatientAdd)
    await page.goto('/patient/add');
    await expect(page.getByRole('button', { name: 'Сохранить', exact: true })).toBeVisible();
    await page.screenshot({ path: `${OUT}/patient-add.png`, fullPage: true });

    // 12. Создание лечения (TreatmentAdd)
    await page.goto(`/patient/${DEMO_PATIENT_ID}/treatment/add`);
    await expect(page.getByRole('button', { name: /Сохранить/ }).first()).toBeVisible();
    await page.screenshot({ path: `${OUT}/treatment-add.png`, fullPage: true });

    // Чистка временного пациента (график МНО).
    const tok = chartDemo.adminToken;
    if (tok) {
      for (const iri of chartDemo.historyIris) await apiDelete(tok, iri);
      for (const iri of chartDemo.appointmentIris) await apiDelete(tok, iri);
      if (chartDemo.treatmentIri) await apiDelete(tok, chartDemo.treatmentIri);
      if (chartDemo.patientIri) await apiDelete(tok, chartDemo.patientIri);
      if (chartDemo.hospitalIri) await apiDelete(tok, chartDemo.hospitalIri);
    }
  });
});

test.describe('guide: Бакулево (планшет 1080×810) — скриншоты', () => {
  test.use({ viewport: { width: 1080, height: 810 } });
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('снимки списка и карточки', async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page.locator('.patient-table')).toBeVisible();
    await page.screenshot({ path: `${OUT}/tablet-01-patient-list.png`, fullPage: true });

    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Текущие показатели')).toBeVisible();
    await page.screenshot({ path: `${OUT}/tablet-02-patient-card.png`, fullPage: true });
  });
});
