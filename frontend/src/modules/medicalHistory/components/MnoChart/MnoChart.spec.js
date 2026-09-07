import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MnoChart from './MnoChart.vue'
import { hasDoseValue } from './MnoChart.script.js'

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

  it('chartOptions exposes doses by point index (СЦ-3.64.1/2)', () => {
    const wrapper = mountMnoChart({
      data: [
        { date: '2024-01-01', inr: '1.5', dose: 5 },
        { date: '2024-01-02', inr: '2.5', dose: '—' },
      ]
    })

    expect(wrapper.vm.chartOptions.doses).toEqual([5, '—'])
  })

  it('hasDoseValue distinguishes a real dose from placeholders (СЦ-3.64.3/4/5)', () => {
    expect(hasDoseValue(5)).toBe(true)
    expect(hasDoseValue(2.5)).toBe(true)
    expect(hasDoseValue('—')).toBe(false)
    expect(hasDoseValue(null)).toBe(false)
    expect(hasDoseValue(undefined)).toBe(false)
    expect(hasDoseValue('')).toBe(false)
    expect(hasDoseValue(NaN)).toBe(false)
    expect(hasDoseValue(0)).toBe(false)
  })

  it('draws МНО and dose on two lines for a point below range (СЦ-3.64.1)', () => {
    const wrapper = mountMnoChart({ data: [] })
    const fillText = vi.fn()
    const chart = {
      getDatasetMeta: () => ({ data: [{ x: 10, y: 20 }] }),
      options: { mnoFrom: 2, mnoTo: 3, doses: [5] },
      data: { datasets: [{ data: [1.5] }] },
      ctx: { font: '', fillStyle: '', textAlign: '', fillText },
    }

    wrapper.vm.customLabelsPlugin.afterDatasetsDraw(chart)

    expect(fillText).toHaveBeenCalledWith('1.5', 10, 35)
    expect(fillText).toHaveBeenCalledWith('5', 10, 47)
  })

  it('draws МНО and dose on two lines for a point above range (СЦ-3.64.2)', () => {
    const wrapper = mountMnoChart({ data: [] })
    const fillText = vi.fn()
    const chart = {
      getDatasetMeta: () => ({ data: [{ x: 10, y: 20 }] }),
      options: { mnoFrom: 2, mnoTo: 3, doses: [2.5] },
      data: { datasets: [{ data: [3.8] }] },
      ctx: { font: '', fillStyle: '', textAlign: '', fillText },
    }

    wrapper.vm.customLabelsPlugin.afterDatasetsDraw(chart)

    expect(fillText).toHaveBeenCalledWith('3.8', 10, -7)
    expect(fillText).toHaveBeenCalledWith('2.5', 10, 5)
  })

  it('draws only МНО when dose is missing (СЦ-3.64.4/5)', () => {
    const wrapper = mountMnoChart({ data: [] })
    const fillText = vi.fn()
    const chart = {
      getDatasetMeta: () => ({ data: [{ x: 10, y: 20 }] }),
      options: { mnoFrom: 2, mnoTo: 3, doses: ['—'] },
      data: { datasets: [{ data: [1.5] }] },
      ctx: { font: '', fillStyle: '', textAlign: '', fillText },
    }

    wrapper.vm.customLabelsPlugin.afterDatasetsDraw(chart)

    expect(fillText).toHaveBeenCalledTimes(1)
    expect(fillText).toHaveBeenCalledWith('1.5', 10, 35)
  })

  it('tooltip shows МНО and dose (СЦ-3.64.1/2)', () => {
    const wrapper = mountMnoChart({
      data: [{ date: '2024-01-01', inr: '1.5', dose: 5 }],
      mnoFrom: 2,
      mnoTo: 3,
    })

    const label = wrapper.vm.chartOptions.plugins.tooltip.callbacks.label

    expect(label({
      datasetIndex: 0,
      dataIndex: 0,
      parsed: { y: 1.5 },
      dataset: { label: 'МНО' },
      chart: { options: { doses: [5] } },
    })).toBe('МНО: 1.5 · доза: 5')

    expect(label({
      datasetIndex: 0,
      dataIndex: 0,
      parsed: { y: 1.5 },
      dataset: { label: 'МНО' },
      chart: { options: { doses: ['—'] } },
    })).toBe('МНО: 1.5')
  })

  it('adds top padding so labels above top points stay visible (СЦ-3.64.2)', () => {
    const wrapper = mountMnoChart({ data: [] })

    expect(wrapper.vm.chartOptions.layout.padding.top).toBe(40)
    expect(wrapper.vm.chartOptions.layout.padding.bottom).toBe(30)
  })

  it('computes chart data once and reuses it for options (кэш, ревью №3)', () => {
    const wrapper = mountMnoChart({ data: [{ date: '2024-01-01', inr: '1.5', dose: 5 }] })
    const spy = vi.spyOn(wrapper.vm, 'prepareChartData')

    // Форсируем пересчёт (после монтирования computed уже закэширован).
    wrapper.vm.changeRange('1m')

    wrapper.vm.chartData
    wrapper.vm.chartOptions

    expect(spy).toHaveBeenCalledTimes(1)
  })
})
