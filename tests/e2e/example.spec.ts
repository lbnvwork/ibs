import { test, expect } from '@playwright/test';

// Плейсхолдер-тест: проверяет, что стенд поднят и nginx отдаёт приложение.
// Будет заменён реальными E2E-сценариями критических путей (задача 3.21).
test('плейсхолдер: приложение доступно через nginx', async ({ page }) => {
  const response = await page.goto('/');
  expect(response?.ok()).toBeTruthy();
  await expect(page.locator('#app')).toBeAttached();
  await expect(page).toHaveTitle('Warfarin manager');
});
