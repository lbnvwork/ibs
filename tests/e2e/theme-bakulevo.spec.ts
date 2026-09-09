import { test, expect, type Page } from '@playwright/test';

// E2E-регрессия 3.58 «Темизация Бакулево».
// Прогоняется на bakulevo-сборке (`docker compose exec -e VITE_THEME=bakulevo node npm run build`).
// Guard `E2E_THEME` позволяет пропустить спеки темы при полном прогоне на другой сборке.
// Использует демо-данные (3.77): пациенты id 40001–40005 с активным лечением.

const THEME = process.env.E2E_THEME;
const DOCTOR_LOGIN = process.env.DOCTOR_LOGIN || 'demo.doctor';
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || 'demo12345';
const DEMO_PATIENT_ID = '40001';

async function loginAsDoctor(page: Page): Promise<void> {
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);
  await page.getByLabel('Логин').fill(DOCTOR_LOGIN);
  await page.getByLabel('Пароль').fill(DOCTOR_PASSWORD);
  await page.getByRole('button', { name: 'Войти' }).click();
  await page.waitForURL('/');
}

test.describe('3.58 Тема Бакулево (дизайн-токены + layout)', () => {
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('СЦ-3.58.1: тема рендерится — светлый контент + тёмный сайдбар', async ({ page }) => {
    await page.goto('/');

    const vars = await page.evaluate(() => {
      const cs = getComputedStyle(document.documentElement);
      return {
        sidebarBg: cs.getPropertyValue('--color-sidebar-bg').trim(),
        contentBg: cs.getPropertyValue('--color-content-bg').trim(),
        sidebarWidth: cs.getPropertyValue('--sidebar-width').trim(),
      };
    });

    expect(vars.sidebarBg).toBe('#1b2b4b'); // тёмный сайдбар
    expect(vars.contentBg).toBe('#f4f6f9'); // светлый контент
    expect(vars.sidebarWidth).toBe('240px'); // широкий (текстовый) сайдбар
  });

  test('СЦ-3.58.13: текстовый сайдбар (подписи, логотип, поддержка)', async ({ page }) => {
    await loginAsDoctor(page);
    const sidebar = page.locator('.sidebar--text');
    await expect(sidebar).toBeVisible();
    await expect(sidebar.locator('.logo-text')).toHaveText('Coag Analyzer');

    for (const label of ['Пациенты', 'Список пациентов', 'Новый пациент', 'Рекомендации', 'Назначение', 'Анализ']) {
      await expect(sidebar.getByText(label)).toBeVisible();
    }
    await expect(sidebar.getByText('Служба поддержки')).toBeVisible();
  });

  test('СЦ-3.58.17: сайдбар — только рабочие пункты (заглушек нет)', async ({ page }) => {
    await loginAsDoctor(page);
    const sidebar = page.locator('.sidebar--text');

    await expect(sidebar.getByText('Список пациентов')).toBeVisible();
    await expect(sidebar.getByText('Новый пациент')).toBeVisible();
    await expect(sidebar.getByText('Назначение')).toBeVisible();
    await expect(sidebar.getByText('Анализ')).toBeVisible();

    for (const stub of ['Сообщения', 'Статистика', 'Календарь', 'Печать', 'Сохранённые формы']) {
      await expect(sidebar.getByText(stub)).toHaveCount(0);
    }
  });

  test('СЦ-3.58.18: навигация сайдбара («Новый пациент» → /patient/add, «Список пациентов» → /)', async ({ page }) => {
    await loginAsDoctor(page);
    const sidebar = page.locator('.sidebar--text');

    await sidebar.getByText('Новый пациент').click();
    await expect(page).toHaveURL(/\/patient\/add$/);

    await sidebar.getByText('Список пациентов').click();
    await expect(page).toHaveURL(/\/$/);
  });

  test('СЦ-3.58.19: «Назначение»/«Анализ» disabled вне карточки пациента', async ({ page }) => {
    await loginAsDoctor(page);

    await expect(page.locator('.sidebar-group__item', { hasText: 'Назначение' })).toHaveClass(/disabled-button/);
    await expect(page.locator('.sidebar-group__item', { hasText: 'Анализ' })).toHaveClass(/disabled-button/);
  });

  test('СЦ-3.58.21: «Выход» → /login', async ({ page }) => {
    await loginAsDoctor(page);
    await page.locator('.sidebar__logout').click();
    await expect(page).toHaveURL(/\/login/);
  });

  test('СЦ-3.58.11: боковой список пациентов скрыт (таблица на всю ширину)', async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page.locator('.left-panel')).toHaveCount(0);
    await expect(page.locator('.patient-table')).toBeVisible();
  });

  test('СЦ-3.58.15: триаж-бейджи + счётчик + легенда статусов', async ({ page }) => {
    await loginAsDoctor(page);

    await expect(page.locator('.triage-badge').first()).toBeVisible();
    await expect(page.getByText(/Всего пациентов:/)).toBeVisible();
    await expect(page.getByText('Статусы пациентов')).toBeVisible();
    for (const label of ['Угроза кровотечения', 'Риск тромбоза', 'В норме']) {
      await expect(page.getByText(label).first()).toBeVisible();
    }
  });

  test('СЦ-3.58.3: фармакогенетика скрыта в карточке Бакулево', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Фармакогенетика')).toHaveCount(0);
  });

  test('СЦ-3.58.16: плитки показателей + риск-блок на истории', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);

    await expect(page.getByRole('tab', { name: 'Обзор' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'История наблюдений' })).toBeVisible();

    await expect(page.getByText('Текущие показатели')).toBeVisible();
    await expect(page.locator('.metric-card').first()).toBeVisible();
    await expect(page.getByText('МНО (INR)')).toBeVisible();

    await expect(page.getByText('Риск осложнений')).toBeVisible();
  });

  test('СЦ-3.58.20: «Анализ» открывает форму добавления анализа', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);

    const analysis = page.locator('.sidebar-group__item', { hasText: 'Анализ' });
    await expect(analysis).not.toHaveClass(/disabled-button/);
    await analysis.click();

    await expect(page.locator('.modal-content', { hasText: 'Добавить анализ' })).toBeVisible();
  });
});
