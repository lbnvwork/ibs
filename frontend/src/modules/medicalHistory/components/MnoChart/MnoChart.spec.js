import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MnoChart from './MnoChart.vue'

function mountMnoChart(props = {}) {
  return mount(MnoChart, {
    props: { data: [], ...props },
    global: { stubs: { Line: true } }
  })
}

describe('MnoChart.vue', () => {
  it('returns null chart data when there is no history', () => {
    const wrapper = mountMnoChart({ data: [] })
    expect(wrapper.vm.chartData).toBeNull()
  })

  it('ignores entries without a numeric МНО value', () => {
    const wrapper = mountMnoChart({
      data: [
        { date: '2024-01-01', inr: '—' },
        { date: '2024-01-02', inr: 'n/a' },
      ]
    })
    expect(wrapper.vm.chartData).toBeNull()
  })

  it('builds labels/values sorted by date and a main МНО dataset', () => {
    const wrapper = mountMnoChart({
      data: [
        { date: '2024-02-01', inr: '2.5' },
        { date: '2024-01-01', inr: '2.0' },
      ]
    })
    const chartData = wrapper.vm.chartData
    expect(chartData.datasets[0].label).toBe('МНО')
    expect(chartData.datasets[0].data).toEqual([2.0, 2.5])
  })

  it('adds threshold datasets for mnoFrom/mnoTo when provided', () => {
    const wrapper = mountMnoChart({
      data: [{ date: '2024-01-01', inr: '2.5' }],
      mnoFrom: 2,
      mnoTo: 3,
    })
    const labels = wrapper.vm.chartData.datasets.map(d => d.label)
    expect(labels).toContain('Нижняя граница (2)')
    expect(labels).toContain('Верхняя граница (3)')
  })

  it('does not add threshold datasets when mnoFrom/mnoTo are null', () => {
    const wrapper = mountMnoChart({ data: [{ date: '2024-01-01', inr: '2.5' }] })
    expect(wrapper.vm.chartData.datasets).toHaveLength(1)
  })

  it('changeRange updates the selected range', () => {
    const wrapper = mountMnoChart({ data: [] })
    wrapper.vm.changeRange('1m')
    expect(wrapper.vm.selectedRange).toBe('1m')
  })

  it('filters out entries older than the selected range, relative to the latest entry', () => {
    const wrapper = mountMnoChart({
      data: [
        { date: '2024-01-01', inr: '2.0' },
        { date: '2024-06-01', inr: '2.5' },
      ]
    })
    wrapper.vm.changeRange('1m')

    const chartData = wrapper.vm.chartData
    expect(chartData.datasets[0].data).toEqual([2.5])
  })

  it('includes every entry when the range is "all"', () => {
    const wrapper = mountMnoChart({
      data: [
        { date: '2024-01-01', inr: '2.0' },
        { date: '2024-06-01', inr: '2.5' },
      ]
    })
    wrapper.vm.changeRange('all')

    expect(wrapper.vm.chartData.datasets[0].data).toEqual([2.0, 2.5])
  })
})
