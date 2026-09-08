import { describe, it, expect, vi, afterEach } from 'vitest'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'
import { resolveTheme } from './index.js'

const here = dirname(fileURLToPath(import.meta.url))
const almazovo = readFileSync(join(here, 'almazovo.css'), 'utf8')
const bakulevo = readFileSync(join(here, 'bakulevo.css'), 'utf8')

const token = (css, name) => {
  const m = css.match(new RegExp(`${name}:\\s*([^;]+);`))
  return m ? m[1].trim() : null
}

// themeName/isBakulevo/FEATURES вычисляются на импорте из import.meta.env.VITE_THEME,
// поэтому проверяем их детерминированно — динамическим импортом с моком env (ревью PR #124).
afterEach(() => {
  vi.unstubAllEnvs()
  vi.resetModules()
})

describe('themes (3.58)', () => {
  it('Бакулево: тёмный сайдбар + светлый контент (СЦ-3.58.1)', () => {
    expect(token(bakulevo, '--color-sidebar-bg')).toBe('#1b2b4b')
    expect(token(bakulevo, '--color-content-bg')).toBe('#f4f6f9')
    expect(token(bakulevo, '--color-accent')).toBe('#2563eb')
  })

  it('Алмазово: текущая палитра (СЦ-3.58.2)', () => {
    expect(token(almazovo, '--color-sidebar-bg')).toBe('#5291e9')
    expect(token(almazovo, '--color-accent')).toBe('#3498db')
  })

  it('resolveTheme: неизвестная/пустая тема → fallback Алмазово (СЦ-3.58.6)', () => {
    expect(resolveTheme(undefined)).toBe('almazovo')
    expect(resolveTheme('')).toBe('almazovo')
    expect(resolveTheme('unknown')).toBe('almazovo')
    expect(resolveTheme('bakulevo')).toBe('bakulevo')
    expect(resolveTheme('almazovo')).toBe('almazovo')
  })

  it('index.js: дефолт Алмазово и флаги по умолчанию (мок env)', async () => {
    vi.stubEnv('VITE_THEME', 'almazovo')
    vi.resetModules()
    const { themeName, isBakulevo, FEATURES } = await import('./index.js')
    expect(themeName).toBe('almazovo')
    expect(isBakulevo).toBe(false)
    expect(FEATURES).toEqual({
      pharmacogenetics: true,
      patientListPanel: true,
      riskScale: false
    })
  })

  it('index.js: Бакулево — флаги инвертируются (мок env)', async () => {
    vi.stubEnv('VITE_THEME', 'bakulevo')
    vi.resetModules()
    const { themeName, isBakulevo, FEATURES } = await import('./index.js')
    expect(themeName).toBe('bakulevo')
    expect(isBakulevo).toBe(true)
    expect(FEATURES).toEqual({
      pharmacogenetics: false,
      patientListPanel: false,
      riskScale: true
    })
  })
})
