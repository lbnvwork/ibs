import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/themes', () => ({ isBakulevo: true }))

import MainHeader from './MainHeader.vue'
import { useAuthStore } from '@/modules/shared/stores/authStore'

function mountHeader() {
  setActivePinia(createPinia())
  const authStore = useAuthStore()
  authStore.user = { userName: 'Хрусталёв А.И.' }
  return mount(MainHeader, {
    global: {
      mocks: {
        $route: { name: 'Home', meta: {} }
      }
    }
  })
}

describe('MainHeader.vue (Бакулево, 3.58)', () => {
  it('скрывает кнопку «Выход»-дверь и заголовок страницы (выход — в сайдбаре, заголовок — в контенте)', () => {
    const wrapper = mountHeader()
    expect(wrapper.find('.logout-button').exists()).toBe(false)
    expect(wrapper.find('.page-title').exists()).toBe(false)
  })

  it('показывает блок врача с именем и раскрывашкой', () => {
    const wrapper = mountHeader()
    expect(wrapper.find('.doctor-block').exists()).toBe(true)
    expect(wrapper.find('.doctor-name').text()).toBe('Хрусталёв А.И.')
  })

  it('раскрывашка содержит «Выйти» и вызывает logout', async () => {
    const wrapper = mountHeader()
    const authStore = useAuthStore()
    authStore.logout = vi.fn()

    await wrapper.find('.doctor-toggle').trigger('click')
    expect(wrapper.find('.doctor-menu').exists()).toBe(true)
    expect(wrapper.find('.doctor-menu__item').text()).toBe('Выйти')

    await wrapper.find('.doctor-menu__item').trigger('click')
    expect(authStore.logout).toHaveBeenCalled()
  })
})
