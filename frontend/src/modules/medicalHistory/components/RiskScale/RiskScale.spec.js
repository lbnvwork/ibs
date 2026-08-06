import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RiskScale from './RiskScale.vue'

function mountRiskScale(riskScores) {
  return mount(RiskScale, { props: { riskScores } })
}

describe('RiskScale.vue', () => {
  it('calculates progress percentage as a CSS width string', () => {
    const wrapper = mountRiskScale({ cha2ds2Vasc: 2, hasBled: 1, score: 3 })
    expect(wrapper.vm.calculateProgress(2, 9)).toBe(`${(2 / 9) * 100}%`)
    expect(wrapper.vm.calculateProgress(0, 9)).toBe('0%')
  })

  describe('CHA2DS2-VASc', () => {
    it.each([
      [0, '#2ecc71', 'Низкий риск инсульта'],
      [1, '#f39c12', 'Умеренный риск инсульта'],
      [2, '#e74c3c', 'Высокий риск инсульта'],
      [6, '#e74c3c', 'Высокий риск инсульта'],
    ])('score %i -> color %s / %s', (score, color, text) => {
      const wrapper = mountRiskScale({ cha2ds2Vasc: score, hasBled: 0, score: 0 })
      expect(wrapper.vm.getCha2ds2VascColor(score)).toBe(color)
      expect(wrapper.vm.getCha2ds2VascInterpretation(score)).toBe(text)
    })
  })

  describe('HAS-BLED', () => {
    it.each([
      [0, '#2ecc71', 'Низкий риск кровотечения'],
      [1, '#2ecc71', 'Низкий риск кровотечения'],
      [2, '#f39c12', 'Умеренный риск кровотечения'],
      [3, '#e74c3c', 'Высокий риск кровотечения'],
    ])('score %i -> color %s / %s', (score, color, text) => {
      const wrapper = mountRiskScale({ cha2ds2Vasc: 0, hasBled: score, score: 0 })
      expect(wrapper.vm.getHasBledColor(score)).toBe(color)
      expect(wrapper.vm.getHasBledInterpretation(score)).toBe(text)
    })
  })

  describe('SCORE (cardiovascular risk)', () => {
    it.each([
      [0, '#2ecc71', 'Низкий риск СС осложнений'],
      [2, '#2ecc71', 'Низкий риск СС осложнений'],
      [3, '#f39c12', 'Умеренный риск СС осложнений'],
      [4, '#f39c12', 'Умеренный риск СС осложнений'],
      [5, '#e74c3c', 'Высокий риск СС осложнений'],
    ])('score %i -> color %s / %s', (score, color, text) => {
      const wrapper = mountRiskScale({ cha2ds2Vasc: 0, hasBled: 0, score })
      expect(wrapper.vm.getScoreColor(score)).toBe(color)
      expect(wrapper.vm.getScoreInterpretation(score)).toBe(text)
    })
  })

  it('rejects a riskScores object missing required keys via the prop validator', () => {
    const validator = RiskScale.props.riskScores.validator
    expect(validator({ cha2ds2Vasc: 1, hasBled: 1 })).toBe(false)
    expect(validator({ cha2ds2Vasc: 1, hasBled: 1, score: 1 })).toBe(true)
  })
})
