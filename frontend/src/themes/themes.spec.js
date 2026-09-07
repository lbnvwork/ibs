import { describe, it, expect } from 'vitest'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'
import { resolveTheme, themeName, isBakulevo, FEATURES } from './index.js'

const here = dirname(fileURLToPath(import.meta.url))
const almazovo = readFileSync(join(here, 'almazovo.css'), 'utf8')
const bakulevo = readFileSync(join(here, 'bakulevo.css'), 'utf8')

const token = (css, name) => {
  const m = css.match(new RegExp(`${name}:\\s*([^;]+);`))
  return m ? m[1].trim() : null
}

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

  it('index.js: дефолт Алмазово и флаги по умолчанию', () => {
    expect(themeName).toBe('almazovo')
    expect(isBakulevo).toBe(false)
    expect(FEATURES).toEqual({
      pharmacogenetics: true,
      patientListPanel: true,
      riskScale: false
    })
  })
})
