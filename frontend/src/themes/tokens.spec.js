import { describe, it, expect } from 'vitest'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const srcRoot = join(dirname(fileURLToPath(import.meta.url)), '..')
const modulesDir = join(srcRoot, 'modules')

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry)
    if (statSync(full).isDirectory()) walk(full, out)
    else if (entry.endsWith('.style.css')) out.push(full)
  }
  return out
}

function cssFiles() {
  return [...walk(modulesDir), join(srcRoot, 'assets', 'style.css')]
}

const HEX = /#[0-9a-fA-F]{3,8}\b/g

describe('token-only CSS (3.58)', () => {
  it('в компонентных CSS нет жёстких hex-цветов (СЦ-3.58.5)', () => {
    for (const file of cssFiles()) {
      const hex = readFileSync(file, 'utf8').match(HEX)
      expect(hex, `жёсткие hex в ${file}`).toBeNull()
    }
  })
})
