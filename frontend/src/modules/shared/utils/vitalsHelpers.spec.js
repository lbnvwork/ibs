import { describe, it, expect } from 'vitest'
import { buildIndicators, buildIndicatorsFromRow } from './vitalsHelpers'

describe('buildIndicators', () => {
  it('returns an em dash when there is no data at all', () => {
    expect(buildIndicators(null, null)).toBe('—')
  })

  it('returns an em dash when vitals fields are explicitly null (the real API shape)', () => {
    const emptyVitals = { hb: null, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null }
    expect(buildIndicators(null, emptyVitals)).toBe('—')
  })

  it('includes the MNO chip when testHistory.mno is present', () => {
    const html = buildIndicators({ mno: 2.5 }, null)
    expect(html).toContain('indicator-mno')
    expect(html).toContain('2.5')
  })

  it('includes vitals chips (Hb, heart rate, blood pressure, saturation)', () => {
    const html = buildIndicators(null, {
      hb: 120,
      heartRate: 72,
      systolicPressure: 120,
      diastolicPressure: 80,
      saturation: 98
    })
    expect(html).toContain('Hb')
    expect(html).toContain('120')
    expect(html).toContain('ЧСС')
    expect(html).toContain('72')
    expect(html).toContain('120/80')
    expect(html).toContain('98%')
  })

  it('omits blood pressure chip when either systolic or diastolic is missing', () => {
    const html = buildIndicators(null, { systolicPressure: 120, diastolicPressure: null })
    expect(html).toBe('—')
  })

  it('combines MNO and vitals into a single wrapper', () => {
    const html = buildIndicators({ mno: 2.1 }, { hb: 130 })
    expect(html).toContain('indicator-wrapper')
    expect(html).toContain('indicator-mno')
    expect(html).toContain('Hb')
  })
})

describe('buildIndicatorsFromRow', () => {
  it('maps a flat row object into the same chip markup as buildIndicators', () => {
    const html = buildIndicatorsFromRow({ mno: 2.5, hb: 120, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null })
    expect(html).toContain('indicator-mno')
    expect(html).toContain('Hb')
  })

  it('returns an em dash for a row with no measurements', () => {
    const html = buildIndicatorsFromRow({ mno: null, hb: null, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null })
    expect(html).toBe('—')
  })
})
