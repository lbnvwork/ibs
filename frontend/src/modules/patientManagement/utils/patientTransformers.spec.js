import { describe, it, expect } from 'vitest'
import { transformForListPanel, transformForMonitoring } from './patientTransformers'

describe('transformForListPanel', () => {
  it('returns null for a falsy patient', () => {
    expect(transformForListPanel(null)).toBeNull()
  })

  it('builds "Lastname F.S." from lastname/firstname/secondName initials', () => {
    const result = transformForListPanel({ id: 1, lastname: 'Иванов', firstname: 'Пётр', secondName: 'Сергеевич' })
    expect(result).toEqual({ id: 1, name: 'Иванов П. С.', status: undefined })
  })

  it('omits initials that are missing', () => {
    const result = transformForListPanel({ id: 2, lastname: 'Сидоров', firstname: '', secondName: '' })
    expect(result.name).toBe('Сидоров')
  })

  it('falls back to "Без имени" when there is no name data at all', () => {
    const result = transformForListPanel({ id: 3, lastname: '', firstname: '', secondName: '' })
    expect(result.name).toBe('Без имени')
  })

  it('passes the status field through', () => {
    const result = transformForListPanel({ id: 4, lastname: 'Петров', status: 'активный' })
    expect(result.status).toBe('активный')
  })
})

describe('transformForMonitoring', () => {
  it('returns null for a falsy patient', () => {
    expect(transformForMonitoring(null)).toBeNull()
  })

  it('joins the full name and formats the age', () => {
    const birthday = new Date()
    birthday.setFullYear(birthday.getFullYear() - 45)
    const result = transformForMonitoring({
      id: 1,
      lastname: 'Иванов',
      firstname: 'Пётр',
      secondName: 'Сергеевич',
      birthday: birthday.toISOString()
    })
    expect(result.name).toBe('Иванов Пётр Сергеевич')
    expect(result.age).toBe('45 лет')
  })

  it('uses provided fallback fields when present, and defaults otherwise', () => {
    const result = transformForMonitoring({ id: 2, lastname: 'Петров', comment: 'аллергия', highlightRed: true })
    expect(result.comment).toBe('аллергия')
    expect(result.highlightRed).toBe(true)
    expect(result.highlightBlue).toBe(false)
    expect(result.diagnosis).toBe('Диагноз не указан')
  })
})
