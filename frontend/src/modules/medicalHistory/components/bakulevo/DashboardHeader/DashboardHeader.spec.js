import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardHeader from './DashboardHeader.vue'

const patient = {
  id: 7,
  name: 'Иванов Пётр Сергеевич',
  age: '67 лет',
  birthday: '1958-01-01',
  phone: '8(912)345-67-89',
}

const treatment = {
  begDt: '2024-03-12',
  diagnosis: 'Протезирование аортального клапана',
  realEndDt: null,
}

function mountHeader(props = {}) {
  return mount(DashboardHeader, {
    props: { patient, treatment, doctorName: 'Петров А. В.', sex: 1, ...props },
  })
}

describe('DashboardHeader.vue', () => {
  it('renders patient identity and treatment info', () => {
    const wrapper = mountHeader()
    expect(wrapper.text()).toContain('Иванов Пётр Сергеевич')
    expect(wrapper.text()).toContain('Активное наблюдение')
    expect(wrapper.text()).toContain('Петров А. В.')
    expect(wrapper.text()).toContain('Протезирование аортального клапана')
    expect(wrapper.text()).toContain('Начало лечения')
    expect(wrapper.text()).toContain('Диагноз')
  })

  it('builds initials from first and last name parts', () => {
    const wrapper = mountHeader()
    expect(wrapper.vm.initials).toBe('ИС')
  })

  it('labels gender and avatar from sex', () => {
    const wrapper = mountHeader()
    expect(wrapper.vm.genderLabel).toBe('муж')
    expect(wrapper.vm.avatarClass).toBe('avatar--male')
  })

  it('labels female gender when sex is 0', () => {
    const wrapper = mountHeader({ sex: 0 })
    expect(wrapper.vm.genderLabel).toBe('жен')
    expect(wrapper.vm.avatarClass).toBe('avatar--female')
  })

  it('shows «Лечение завершено» for a finished treatment', () => {
    const wrapper = mountHeader({ treatment: { ...treatment, realEndDt: '2024-06-01' } })
    expect(wrapper.vm.statusLabel).toBe('Лечение завершено')
    expect(wrapper.vm.statusClass).toBe('status-badge--inactive')
  })

  it('emits edit-patient and edit-treatment from the two «⋯» buttons', async () => {
    const wrapper = mountHeader()
    const buttons = wrapper.findAll('.icon-more')
    await buttons[0].trigger('click')
    await buttons[1].trigger('click')
    expect(wrapper.emitted('edit-patient')).toBeTruthy()
    expect(wrapper.emitted('edit-treatment')).toBeTruthy()
  })
})
