import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  outputDir: 'test-results',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    // Внутри сети docker-compose приложение доступно по имени сервиса nginx.
    baseURL: 'http://nginx',
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
