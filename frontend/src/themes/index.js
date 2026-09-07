const THEMES = ['almazovo', 'bakulevo']

/**
 * Резолвит имя темы: только известные значения, иначе fallback `almazovo`
 * (СЦ-3.58.6: неизвестная тема не ломает сборку).
 */
export function resolveTheme(name) {
  return THEMES.includes(name) ? name : 'almazovo'
}

// build-time выбор темы через VITE_THEME (runtime-переключение — вне 3.58).
export const themeName = resolveTheme(import.meta.env.VITE_THEME)

export const isBakulevo = themeName === 'bakulevo'

// Тема-зависимые фичи: в Бакулево скрыты фармакогенетика и боковой список,
// показан риск-блок. Снимаются в модульном рефакторинге (3.52/3.57).
export const FEATURES = {
  pharmacogenetics: !isBakulevo,
  patientListPanel: !isBakulevo,
  riskScale: isBakulevo
}
