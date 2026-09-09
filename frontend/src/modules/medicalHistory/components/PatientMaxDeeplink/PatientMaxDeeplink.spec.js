import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PatientMaxDeeplink from './PatientMaxDeeplink.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { get: vi.fn() } }))

function mountDeeplink() {
    return mount(PatientMaxDeeplink, { props: { patientId: 7 } })
}

describe('PatientMaxDeeplink.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    afterEach(() => {
        vi.unstubAllGlobals()
        vi.restoreAllMocks()
    })

    it('renders the block with "Получить ссылку" button', () => {
        const wrapper = mountDeeplink()
        expect(wrapper.text()).toContain('Получить ссылку')
    })

    it('fetches deeplink on click and shows url + bound status', async () => {
        apiClient.get.mockResolvedValue({ data: { url: 'https://max.ru/bot?start=abc', bound: true } })
        const wrapper = mountDeeplink()

        await wrapper.vm.fetchDeeplink()

        expect(apiClient.get).toHaveBeenCalledWith('/patients/7/max-deeplink')
        expect(wrapper.vm.url).toBe('https://max.ru/bot?start=abc')
        expect(wrapper.vm.bound).toBe(true)
        expect(wrapper.text()).toContain('Чат привязан')
    })

    it('copies link to clipboard', async () => {
        apiClient.get.mockResolvedValue({ data: { url: 'https://max.ru/bot?start=abc', bound: false } })
        const writeText = vi.fn().mockResolvedValue(undefined)
        vi.stubGlobal('navigator', { clipboard: { writeText } })

        const wrapper = mountDeeplink()
        await wrapper.vm.fetchDeeplink()
        await wrapper.vm.copyLink()

        expect(writeText).toHaveBeenCalledWith('https://max.ru/bot?start=abc')
        expect(wrapper.vm.copyFeedback).toBe('copied')
    })

    it('falls back when clipboard is unavailable', async () => {
        apiClient.get.mockResolvedValue({ data: { url: 'https://max.ru/bot?start=abc', bound: false } })
        vi.stubGlobal('navigator', { clipboard: { writeText: vi.fn().mockRejectedValue(new Error('nope')) } })

        const wrapper = mountDeeplink()
        await wrapper.vm.fetchDeeplink()
        await wrapper.vm.copyLink()

        expect(wrapper.vm.copyFeedback).toBe('fallback')
    })

    it('shows error on 404', async () => {
        apiClient.get.mockRejectedValue({ response: { status: 404 } })
        const wrapper = mountDeeplink()

        await wrapper.vm.fetchDeeplink()

        expect(wrapper.vm.error).toBe('Пациент не найден.')
        expect(wrapper.vm.url).toBeNull()
    })

    it('handles network error', async () => {
        apiClient.get.mockRejectedValue(new Error('network down'))
        const wrapper = mountDeeplink()

        await wrapper.vm.fetchDeeplink()

        expect(wrapper.vm.error).toBe('Не удалось получить ссылку. Попробуйте ещё раз.')
    })

    it('keeps the same url on repeat fetch (stable token)', async () => {
        apiClient.get.mockResolvedValue({ data: { url: 'https://max.ru/bot?start=abc', bound: true } })
        const wrapper = mountDeeplink()

        await wrapper.vm.fetchDeeplink()
        await wrapper.vm.fetchDeeplink()

        expect(wrapper.vm.url).toBe('https://max.ru/bot?start=abc')
    })
})
