import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './authStore'
import apiClient from '@/modules/shared/api/client'
import router from '@/router'

vi.mock('@/modules/shared/api/client', () => ({
  default: { get: vi.fn(), post: vi.fn() }
}))

vi.mock('@/router', () => ({
  default: { push: vi.fn() }
}))

function fakeJwt(payload) {
  const base64url = (obj) => btoa(JSON.stringify(obj)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
  return `${base64url({ alg: 'RS256' })}.${base64url(payload)}.signature`
}

describe('authStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('decodeToken', () => {
    it('decodes the JWT payload', () => {
      const store = useAuthStore()
      const token = fakeJwt({ login: 'ivanov', id: 1 })
      expect(store.decodeToken(token)).toEqual({ login: 'ivanov', id: 1 })
    })

    it('returns null for a malformed token', () => {
      const store = useAuthStore()
      expect(store.decodeToken('not-a-jwt')).toBeNull()
    })
  })

  describe('login', () => {
    it('stores the token, decodes the user and redirects home on success', async () => {
      const token = fakeJwt({ login: 'ivanov', id: 1 })
      apiClient.post.mockResolvedValue({ data: { token } })
      apiClient.get.mockResolvedValue({ data: { userName: 'Иван Иванов' } })

      const store = useAuthStore()
      await store.login({ login: 'ivanov', password: 'secret' })

      expect(store.token).toBe(token)
      expect(localStorage.getItem('token')).toBe(token)
      expect(store.isAuthenticated).toBe(true)
      expect(store.user.login).toBe('ivanov')
      expect(router.push).toHaveBeenCalledWith('/')
      expect(store.error).toBeNull()
      expect(store.loading).toBe(false)
    })

    it('sets an error message and does not redirect on failure', async () => {
      apiClient.post.mockRejectedValue({ response: { data: { message: 'Неверный логин или пароль' } } })

      const store = useAuthStore()
      await store.login({ login: 'ivanov', password: 'wrong' })

      expect(store.error).toBe('Неверный логин или пароль')
      expect(store.token).toBeNull()
      expect(router.push).not.toHaveBeenCalled()
    })
  })

  describe('logout', () => {
    it('clears state, localStorage and redirects to login', () => {
      const store = useAuthStore()
      store.token = 'abc'
      store.user = { login: 'ivanov' }
      localStorage.setItem('token', 'abc')

      store.logout()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(localStorage.getItem('token')).toBeNull()
      expect(router.push).toHaveBeenCalledWith('/login')
    })
  })

  describe('initAuth', () => {
    it('restores the session from a valid stored token', async () => {
      const token = fakeJwt({ login: 'ivanov', id: 1 })
      localStorage.setItem('token', token)
      apiClient.get.mockResolvedValue({ data: { userName: 'Иван Иванов' } })

      const store = useAuthStore()
      await store.initAuth()

      expect(store.token).toBe(token)
      expect(store.user.login).toBe('ivanov')
    })

    it('logs out when the stored token cannot be decoded', async () => {
      localStorage.setItem('token', 'not-a-jwt')

      const store = useAuthStore()
      await store.initAuth()

      expect(localStorage.getItem('token')).toBeNull()
      expect(router.push).toHaveBeenCalledWith('/login')
    })

    it('does nothing when there is no stored token', async () => {
      const store = useAuthStore()
      await store.initAuth()

      expect(store.token).toBeNull()
      expect(apiClient.get).not.toHaveBeenCalled()
    })
  })

  describe('getters', () => {
    it('userDisplayName falls back through userName, login, then a default', () => {
      const store = useAuthStore()
      expect(store.userDisplayName).toBe('')

      store.user = { login: 'ivanov' }
      expect(store.userDisplayName).toBe('ivanov')

      store.user = { login: 'ivanov', userName: 'Иван Иванов' }
      expect(store.userDisplayName).toBe('Иван Иванов')

      store.user = {}
      expect(store.userDisplayName).toBe('Пользователь')
    })
  })
})
