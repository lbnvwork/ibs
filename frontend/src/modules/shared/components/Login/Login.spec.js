import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Login from './Login.vue'
import { useAuthStore } from '@/modules/shared/stores/authStore'

describe('Login.vue', () => {
  let pinia
  let authStore

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    authStore = useAuthStore()
    authStore.login = vi.fn().mockResolvedValue(undefined)
  })

  function mountLogin() {
    return mount(Login, { global: { plugins: [pinia] } })
  }

  it('renders the login form', () => {
    const wrapper = mountLogin()
    expect(wrapper.find('#login').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toBe('Войти')
  })

  it('submits the entered credentials to authStore.login', async () => {
    const wrapper = mountLogin()
    await wrapper.find('#login').setValue('ivanov')
    await wrapper.find('#password').setValue('secret')
    await wrapper.find('form').trigger('submit')

    expect(authStore.login).toHaveBeenCalledWith({ login: 'ivanov', password: 'secret' })
  })

  it('displays the store error message when present', async () => {
    authStore.error = 'Неверный логин или пароль'
    const wrapper = mountLogin()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.error-message').text()).toBe('Неверный логин или пароль')
  })

  it('shows no error message by default', () => {
    const wrapper = mountLogin()
    expect(wrapper.find('.error-message').exists()).toBe(false)
  })

  it('disables the submit button and shows a loading label while logging in', async () => {
    authStore.loading = true
    const wrapper = mountLogin()
    await wrapper.vm.$nextTick()

    const button = wrapper.find('button[type="submit"]')
    expect(button.text()).toBe('Вход...')
    expect(button.attributes('disabled')).toBeDefined()
  })
})
