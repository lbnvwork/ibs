import { test, expect } from '@playwright/test';

// E2E-регрессия 3.58 «Темизация Бакулево».
// Прогоняется на bakulevo-сборке (`docker compose exec -e VITE_THEME=bakulevo node npm run build`).
// Guard `E2E_THEME` позволяет пропустить спеки темы при полном прогоне на другой сборке.

const THEME = process.env.E2E_THEME;

test.describe('3.58 Тема Бакулево (дизайн-токены)', () => {
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
});
