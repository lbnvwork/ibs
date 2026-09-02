import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  outputDir: 'test-results',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    // Внутри docker-сети приложение доступно по имени сервиса nginx.
    // Для демо-прогона на тест-сервере: E2E_BASE_URL=https://test.bloodcontrol.ru
    baseURL: process.env.E2E_BASE_URL || 'http://nginx',
    headless: true,
    screenshot: 'on',
    trace: 'on',
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
  ],
});
