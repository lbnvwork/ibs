import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import VitalsCard from './VitalsCard.vue'
import { vitalsApi } from '@/modules/shared/api/vitals'

vi.mock('@/modules/shared/api/vitals', () => ({
  vitalsApi: { getLatest: vi.fn(), create: vi.fn() }
}))

function mountVitalsCard(props = {}, latestMembers = []) {
  setActivePinia(createPinia())
  vitalsApi.getLatest.mockResolvedValueOnce({ data: { member: latestMembers } })
  return mount(VitalsCard, { props: { patientId: '7', ...props } })
}

describe('VitalsCard.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches the latest vitals when mounted with a patientId', async () => {
    mountVitalsCard()
    await flushPromises()
    expect(vitalsApi.getLatest).toHaveBeenCalledWith('7')
  })

  it('re-fetches when patientId changes', async () => {
    const wrapper = mountVitalsCard()
    await flushPromises()
    vi.clearAllMocks()
    vitalsApi.getLatest.mockResolvedValue({ data: { member: [] } })

    await wrapper.setProps({ patientId: '9' })
    await flushPromises()

    expect(vitalsApi.getLatest).toHaveBeenCalledWith('9')
  })

  it('reports hasAnyData as false when every vital is null', async () => {
    const wrapper = mountVitalsCard({}, [{ hb: null, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null }])
    await flushPromises()

    expect(wrapper.vm.hasAnyData).toBe(false)
  })

  it('reports hasAnyData as true when at least one vital is present', async () => {
    const wrapper = mountVitalsCard({}, [{ hb: 140, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null }])
    await flushPromises()

    expect(wrapper.vm.hasAnyData).toBe(true)
  })

  describe('editing flow', () => {
    it('seeds the form from the latest values when starting to edit', async () => {
      const wrapper = mountVitalsCard({}, [{ hb: 140, heartRate: 70, systolicPressure: 120, diastolicPressure: 80, saturation: 98, weight: 80 }])
      await flushPromises()

      wrapper.vm.startEditing()

      expect(wrapper.vm.editing).toBe(true)
      expect(wrapper.vm.form.hb).toBe(140)
      expect(wrapper.vm.form.heartRate).toBe(70)
      expect(wrapper.vm.form.comment).toBe('')
    })

    it('restores the original snapshot when cancelling', async () => {
      const wrapper = mountVitalsCard({}, [{ hb: 140 }])
      await flushPromises()
      wrapper.vm.startEditing()
      wrapper.vm.form.hb = 999

      wrapper.vm.cancelEditing()

      expect(wrapper.vm.editing).toBe(false)
      expect(wrapper.vm.form.hb).toBe(140)
    })

    it('resets editing state when patientId changes', async () => {
      const wrapper = mountVitalsCard({}, [{ hb: 140 }])
      await flushPromises()
      wrapper.vm.startEditing()
      wrapper.vm.form.hb = 999

      vitalsApi.getLatest.mockResolvedValue({ data: { member: [{ hb: 150 }] } })
      await wrapper.setProps({ patientId: '9' })
      await flushPromises()

      expect(wrapper.vm.editing).toBe(false)
      expect(wrapper.vm.form.hb).toBeNull()
    })
  })

  describe('validateForm / save', () => {
    it('rejects saving when every field is empty', async () => {
      const wrapper = mountVitalsCard()
      await flushPromises()
      wrapper.vm.startEditing()

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toBe('Укажите хотя бы один показатель.')
      expect(vitalsApi.create).not.toHaveBeenCalled()
    })

    it('saves the measurement and refreshes the latest data', async () => {
      vitalsApi.create.mockResolvedValue({ data: {} })
      const wrapper = mountVitalsCard({ treatmentId: '3' })
      await flushPromises()
      wrapper.vm.startEditing()
      wrapper.vm.form.hb = 140

      await wrapper.vm.save()

      expect(vitalsApi.create).toHaveBeenCalledWith(expect.objectContaining({
        patient: '/api/patients/7',
        treatment: '/api/treatments/3',
        hb: 140,
      }))
      expect(wrapper.vm.editing).toBe(false)
      expect(vitalsApi.getLatest).toHaveBeenCalledTimes(2)
    })

    it('sends a null treatment IRI when no treatmentId prop is given', async () => {
      vitalsApi.create.mockResolvedValue({ data: {} })
      const wrapper = mountVitalsCard()
      await flushPromises()
      wrapper.vm.startEditing()
      wrapper.vm.form.hb = 140

      await wrapper.vm.save()

      expect(vitalsApi.create).toHaveBeenCalledWith(expect.objectContaining({ treatment: null }))
    })

    it('surfaces the server error message on save failure', async () => {
      vitalsApi.create.mockRejectedValue({ response: { data: { detail: 'Некорректные данные' } } })
      const wrapper = mountVitalsCard()
      await flushPromises()
      wrapper.vm.startEditing()
      wrapper.vm.form.hb = 140

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toBe('Некорректные данные')
      expect(wrapper.vm.editing).toBe(true)
    })
  })

  describe('formatValue', () => {
    it('formats a present value with its unit, and a dash otherwise', async () => {
      const wrapper = mountVitalsCard()
      await flushPromises()
      expect(wrapper.vm.formatValue(120, 'мм рт.ст.')).toBe('120 мм рт.ст.')
      expect(wrapper.vm.formatValue(null, 'мм рт.ст.')).toBe('—')
    })
  })
})
