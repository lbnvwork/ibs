import { test, expect } from '@playwright/test';

const E2E_LOGIN = process.env.E2E_LOGIN ?? '';
const E2E_PASSWORD = process.env.E2E_PASSWORD ?? '';

async function loginAsDoctor(page) {
  if (!E2E_LOGIN || !E2E_PASSWORD) {
    throw new Error('E2E_LOGIN / E2E_PASSWORD не заданы — передайте через docker compose run -e ...');
  }
  await page.goto('/login');
  await page.getByLabel('Логин').fill(E2E_LOGIN);
  await page.getByLabel('Пароль').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Войти' }).click();
  await page.waitForURL('/');
}

async function fillPatientForm(page, lastname) {
  const hospital = page.getByLabel('Больница', { exact: false });
  await expect(hospital.locator('option')).not.toHaveCount(0);
  await hospital.selectOption({ index: 0 });

  await page.getByLabel('Фамилия', { exact: false }).fill(lastname);
  await page.getByLabel('Имя', { exact: false }).fill('Мария');
  await page.getByLabel('Дата рождения', { exact: false }).fill('1985-04-12');
  await page.getByLabel('Пол', { exact: true }).selectOption({ label: 'Женский' });
  await page.getByLabel('Телефон', { exact: false }).fill('8(901)234-56-78');
  await page.getByLabel('Адрес', { exact: false }).fill('г. Москва, ул. Тестовая, д. 1');
  await page.getByLabel('Паспорт', { exact: false }).fill('1234 567890');
  await page.getByLabel('СНИЛС', { exact: false }).fill('123-456-789 00');
}

test('создание пациента: позитивный путь + проверка в списке', async ({ page }) => {
  await loginAsDoctor(page);

  const lastname = `Тестова${Date.now()}`;

  await page.goto('/patient/add');
  await fillPatientForm(page, lastname);
  await page.getByRole('button', { name: 'Сохранить', exact: true }).click();

  // Пациент создан → редирект на добавление лечения
  await page.waitForURL(/\/patient\/\d+\/treatment\/add/);

  // Проверяем появление в списке (левая панель PatientListPanel)
  await page.goto('/');
  await page.getByPlaceholder('Поиск пациентов...').fill(lastname);

  await expect(page.getByText(lastname, { exact: false }).first()).toBeVisible();
});

test('создание пациента: негатив — пустая форма не отправляется', async ({ page }) => {
  await loginAsDoctor(page);

  await page.goto('/patient/add');
  await page.getByRole('button', { name: 'Сохранить', exact: true }).click();

  // Браузерная валидация required блокирует отправку — остаёмся на форме
  await expect(page).toHaveURL(/\/patient\/add/);
});
