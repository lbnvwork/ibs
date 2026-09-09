import type { Page } from '@playwright/test';

// Хелперы для двухтемных E2E (3.58). Тема активной сборки задаётся env `E2E_THEME`
// при запуске (`-e E2E_THEME=bakulevo` / `-e E2E_THEME=almazovo`).

export const IS_BAKULEVO = process.env.E2E_THEME === 'bakulevo';

/**
 * В карточке пациента темы Бакулево таблица «Медицинские данные» (MedicalTable)
 * находится на вкладке «История наблюдений»; в Алмазово она видна сразу.
 * Перед ассертами по `.medical-data` вызвать этот хелпер.
 */
export async function openHistoryTabIfBakulevo(page: Page): Promise<void> {
  if (!IS_BAKULEVO) return;
  await page.getByRole('tab', { name: 'История наблюдений' }).click();
}
