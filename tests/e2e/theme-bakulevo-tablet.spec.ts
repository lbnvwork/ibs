import { test, expect, type Page } from '@playwright/test';

// E2E-регрессия 3.58: адаптивность темы Бакулево под планшет (smoke-респонсив).
// Прогоняется на bakulevo-сборке. Проверяет: нет горизонтального скролла,
// ключевые блоки (сайдбар/таблица/дашборд) видимы на планшетном viewport.
// Скриншоты — авто (screenshot: 'on') для путеводителя.

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

async function expectNoHorizontalOverflow(page: Page): Promise<void> {
  const { scrollWidth, clientWidth } = await page.evaluate(() => {
    const doc = document.documentElement;
    return { scrollWidth: doc.scrollWidth, clientWidth: doc.clientWidth };
  });
  expect(
    scrollWidth - clientWidth,
    `горизонтальный overflow: ${scrollWidth}px > viewport ${clientWidth}px`,
  ).toBeLessThanOrEqual(1);
}

test.describe('3.58 Бакулево на планшете (landscape 1080×810)', () => {
  test.use({ viewport: { width: 1080, height: 810 } });
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('список пациентов: без скролла, сайдбар + таблица видимы', async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page.locator('.sidebar--text')).toBeVisible();
    await expect(page.locator('.patient-table')).toBeVisible();
    await expectNoHorizontalOverflow(page);
  });

  test('карточка пациента (дашборд): без скролла, плитки видимы', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Текущие показатели')).toBeVisible();
    await expectNoHorizontalOverflow(page);
  });
});

test.describe('3.58 Бакулево на планшете (portrait 810×1080)', () => {
  test.use({ viewport: { width: 810, height: 1080 } });
  test.skip(THEME === 'almazovo', 'bakulevo-сборка не активна');

  test('список пациентов: без скролла, сайдбар + таблица видимы', async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page.locator('.sidebar--text')).toBeVisible();
    await expect(page.locator('.patient-table')).toBeVisible();
    await expectNoHorizontalOverflow(page);
  });

  test('карточка пациента (дашборд): без скролла, плитки видимы', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Текущие показатели')).toBeVisible();
    await expectNoHorizontalOverflow(page);
  });
});
