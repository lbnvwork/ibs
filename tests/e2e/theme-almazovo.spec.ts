import { test, expect, type Page } from '@playwright/test';

// E2E-регрессия 3.58 «Тема Алмазово не затронута».
// Прогоняется на almazovo-сборке (`docker compose exec -e VITE_THEME=almazovo node npm run build`).
// Guard `E2E_THEME` позволяет пропустить при полном прогоне на bakulevo-сборке.
// Демо-данные (3.77): пациент 40001 с активным лечением.

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

test.describe('3.58 Тема Алмазово (регрессия — не затронута)', () => {
  test.skip(THEME === 'bakulevo', 'almazovo-сборка не активна');

  test('СЦ-3.58.2: тема Алмазово рендерится (текущая палитра)', async ({ page }) => {
    await page.goto('/');

    const vars = await page.evaluate(() => {
      const cs = getComputedStyle(document.documentElement);
      return {
        sidebarBg: cs.getPropertyValue('--color-sidebar-bg').trim(),
        contentBg: cs.getPropertyValue('--color-content-bg').trim(),
        sidebarWidth: cs.getPropertyValue('--sidebar-width').trim(),
      };
    });

    expect(vars.sidebarBg).toBe('#5291e9'); // текущая палитра (голубой сайдбар)
    expect(vars.contentBg).toBe('#fff'); // белый контент (миниифицированный hex)
    expect(vars.sidebarWidth).toBe('60px'); // узкий (иконочный) сайдбар
  });

  test('СЦ-3.58.14: иконочный сайдбар (без текстовых подписей)', async ({ page }) => {
    await loginAsDoctor(page);
    const sidebar = page.locator('.sidebar');
    await expect(sidebar).toBeVisible();

    // Пунктов с текстовыми подписями нет — только иконки.
    await expect(sidebar.getByText('Список пациентов')).toHaveCount(0);
    await expect(sidebar.getByText('Служба поддержки')).toHaveCount(0);
    await expect(sidebar.getByText('Выход')).toHaveCount(0);
  });

  test('СЦ-3.58.12: боковой список пациентов виден', async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page.locator('.left-panel')).toBeVisible();
  });

  test('СЦ-3.58.4: фармакогенетика видна в карточке Алмазово', async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/patient/${DEMO_PATIENT_ID}`);
    await expect(page.getByText('Фармакогенетика').first()).toBeVisible();
  });
});
