import { describe, it, expect } from 'vitest'
import { isValidPhone, isValidSnils, isValidPassport, isValidEmail } from './validators'

describe('isValidPhone', () => {
  it('accepts 11 digits starting with 8', () => {
    expect(isValidPhone('8(900)123-45-67')).toBe(true)
  })

  it('accepts 11 digits starting with 7', () => {
    expect(isValidPhone('79001234567')).toBe(true)
  })

  it('rejects wrong length', () => {
    expect(isValidPhone('890012345')).toBe(false)
  })

  it('rejects a valid-length number not starting with 7 or 8', () => {
    expect(isValidPhone('91234567890')).toBe(false)
  })
})

describe('isValidSnils', () => {
  it('accepts exactly 11 digits', () => {
    expect(isValidSnils('123-456-789 95')).toBe(true)
  })

  it('rejects fewer than 11 digits', () => {
    expect(isValidSnils('123-456-789')).toBe(false)
  })
})

describe('isValidPassport', () => {
  it('accepts "XXXX XXXXXX" format', () => {
    expect(isValidPassport('1234 567890')).toBe(true)
  })

  it('rejects missing space separator', () => {
    expect(isValidPassport('1234567890')).toBe(false)
  })

  it('rejects wrong series/number lengths', () => {
    expect(isValidPassport('123 4567890')).toBe(false)
    expect(isValidPassport('1234 56789')).toBe(false)
  })
})

describe('isValidEmail', () => {
  it('treats empty value as valid (optional field)', () => {
    expect(isValidEmail('')).toBe(true)
    expect(isValidEmail(null)).toBe(true)
  })

  it('accepts a well-formed email', () => {
    expect(isValidEmail('doctor@hospital.ru')).toBe(true)
  })

  it('rejects malformed emails', () => {
    expect(isValidEmail('not-an-email')).toBe(false)
    expect(isValidEmail('missing@domain')).toBe(false)
    expect(isValidEmail('@nodomain.com')).toBe(false)
  })
})
