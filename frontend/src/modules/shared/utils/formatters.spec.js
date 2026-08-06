import { describe, it, expect } from 'vitest'
import { calculateAge, formatPhone, formatDate, formatAge, formatPassport, formatSnils } from './formatters'

describe('calculateAge', () => {
  it('returns null for falsy input', () => {
    expect(calculateAge(null)).toBeNull()
    expect(calculateAge(undefined)).toBeNull()
    expect(calculateAge('')).toBeNull()
  })

  it('computes full years elapsed, accounting for birthday not yet reached this year', () => {
    const today = new Date()
    const notYetBirthday = new Date(today.getFullYear() - 30, today.getMonth() + 1, today.getDate())
    expect(calculateAge(notYetBirthday)).toBe(29)
  })

  it('computes full years elapsed when birthday already passed this year', () => {
    const today = new Date()
    const alreadyBirthday = new Date(today.getFullYear() - 30, today.getMonth() === 0 ? 11 : today.getMonth() - 1, today.getDate())
    expect(calculateAge(alreadyBirthday)).toBe(30)
  })

  it('returns NaN for an unparsable date (invalid Date arithmetic does not throw)', () => {
    expect(calculateAge('not-a-date')).toBeNaN()
  })
})

describe('formatPhone', () => {
  it('returns dash for empty input', () => {
    expect(formatPhone('')).toBe('-')
    expect(formatPhone(null)).toBe('-')
  })

  it('progressively formats digits as they are typed', () => {
    expect(formatPhone('8')).toBe('8')
    expect(formatPhone('8900')).toBe('8(900')
    expect(formatPhone('8900123')).toBe('8(900)123')
    expect(formatPhone('890012345')).toBe('8(900)123-45')
    expect(formatPhone('89001234567')).toBe('8(900)123-45-67')
  })

  it('strips non-digit characters before formatting', () => {
    expect(formatPhone('8(900)123-45-67')).toBe('8(900)123-45-67')
  })
})

describe('formatDate', () => {
  it('returns an em dash for falsy input', () => {
    expect(formatDate(null)).toBe('—')
    expect(formatDate('')).toBe('—')
  })

  it('formats an ISO date using ru-RU locale', () => {
    expect(formatDate('2026-01-15')).toBe('15.01.2026')
  })
})

describe('formatAge', () => {
  it('returns an em dash when age is null or undefined', () => {
    expect(formatAge(null)).toBe('—')
    expect(formatAge(undefined)).toBe('—')
  })

  it('pluralizes according to Russian grammar rules', () => {
    expect(formatAge(1)).toBe('1 год')
    expect(formatAge(21)).toBe('21 год')
    expect(formatAge(2)).toBe('2 года')
    expect(formatAge(4)).toBe('4 года')
    expect(formatAge(5)).toBe('5 лет')
    expect(formatAge(11)).toBe('11 лет')
    expect(formatAge(12)).toBe('12 лет')
    expect(formatAge(14)).toBe('14 лет')
    expect(formatAge(0)).toBe('0 лет')
  })
})

describe('formatPassport', () => {
  it('returns empty string for falsy input', () => {
    expect(formatPassport('')).toBe('')
  })

  it('inserts a space after the 4-digit series', () => {
    expect(formatPassport('1234')).toBe('1234')
    expect(formatPassport('1234567890')).toBe('1234 567890')
  })
})

describe('formatSnils', () => {
  it('returns empty string for falsy input', () => {
    expect(formatSnils('')).toBe('')
  })

  it('progressively inserts separators as digits are entered', () => {
    expect(formatSnils('123')).toBe('123')
    expect(formatSnils('123456')).toBe('123-456')
    expect(formatSnils('123456789')).toBe('123-456-789')
    expect(formatSnils('12345678995')).toBe('123-456-789 95')
  })
})
