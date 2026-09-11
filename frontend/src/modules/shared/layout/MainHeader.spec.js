import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MainHeader from './MainHeader.vue'
import { useAuthStore } from '@/modules/shared/stores/authStore'

// Базовый спек — тема Алмазово (в шапке остаётся кнопка «Выход»-дверь).
// Поведение Бакулево (дверь скрыта) проверяется в MainHeader.bakulevo.spec.js.
vi.mock('@/themes', () => ({ isBakulevo: false }))

function mountMainHeader(user, { routeName = 'Home' } = {}) {
  setActivePinia(createPinia())
  const store = useAuthStore()
  store.user = user
  const wrapper = mount(MainHeader, {
    global: {
      mocks: {
        $route: { name: routeName, meta: {} }
      }
    }
  })
  return { wrapper, store }
}

describe('MainHeader.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('displays the user display name from the auth store', () => {
    const { wrapper } = mountMainHeader({ userName: 'Хрусталёв А.И.' })
    expect(wrapper.text()).toContain('Хрусталёв А.И.')
  })

  it('falls back to a generic label when there is no userName/login', () => {
    const { wrapper } = mountMainHeader({})
    expect(wrapper.text()).toContain('Пользователь')
  })

  it('calls authStore.logout() when the logout button is clicked', async () => {
    const { wrapper, store } = mountMainHeader({ userName: 'Хрусталёв А.И.' })
    store.logout = vi.fn()

    await wrapper.find('.logout-button').trigger('click')

    expect(store.logout).toHaveBeenCalled()
  })

  describe('page title (3.58)', () => {
    it('показывает заголовок текущей страницы: MedicalHistory', () => {
      const { wrapper } = mountMainHeader({ userName: 'Хрусталёв А.И.' }, { routeName: 'MedicalHistory' })
      expect(wrapper.find('.page-title').text()).toBe('История пациента')
    })

    it('показывает заголовок текущей страницы: Home', () => {
      const { wrapper } = mountMainHeader({}, { routeName: 'Home' })
      expect(wrapper.find('.page-title').text()).toBe('Пациенты')
    })

    it('fallback для неизвестного route — имя приложения', () => {
      const { wrapper } = mountMainHeader({}, { routeName: 'Unknown' })
      expect(wrapper.find('.page-title').text()).toBe('Warfarin manager')
    })
  })
})

