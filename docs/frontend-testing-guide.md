# Как писать тесты во frontend (Vue 3 + Pinia + Vitest)

См. также: [запуск тестов](./frontend-testing-run.md) · [обзор покрытия](./frontend-testing-overview.md)

Тесты лежат **рядом** с тестируемым файлом, а не в отдельном каталоге: `formatters.js` + `formatters.spec.js` в одной папке, `Login.vue` + `Login.spec.js` — в одной папке компонента. Стек: `vitest`, `@vue/test-utils`, `jsdom`.

Структура компонента в проекте — 4 файла: `Name.vue` (тонкая обёртка, подключает остальные через `<template src>`/`<script src>`/`<style scoped src>`), `Name.script.js` (вся логика), `Name.template.html`, `Name.style.css`. Тестировать нужно `Name.vue` целиком через `mount()` — это автоматически подтягивает script и template.

## Три вида тестов — что когда выбирать

| Что тестируем | Инструмент |
|---|---|
| Чистая функция (`utils/*.js`) | `describe`/`it`/`expect` напрямую, без Vue |
| Pinia-стор (`stores/*.js`) | `setActivePinia(createPinia())` + `vi.mock` API-модуля, который вызывает стор |
| Vue-компонент (`components/*.vue`) | `@vue/test-utils`'s `mount()`, плюс Pinia/`$route`/`$router`-моки при необходимости |

## Пример: тест чистой функции
Из `formatters.spec.js`:
```js
import { describe, it, expect } from 'vitest'
import { calculateAge } from './formatters'

describe('calculateAge', () => {
  it('returns null for falsy input', () => {
    expect(calculateAge(null)).toBeNull()
  })

  it('computes full years elapsed, accounting for birthday not yet reached this year', () => {
    const today = new Date()
    const notYetBirthday = new Date(today.getFullYear() - 30, today.getMonth() + 1, today.getDate())
    expect(calculateAge(notYetBirthday)).toBe(29)
  })
})
```

## Пример: тест Pinia-стора
Мокаем именно API-слой (`@/modules/shared/api/...`), а не сам стор — стор выполняется по-настоящему, поэтому проверяется реальная логика внутри `actions`/`getters`. Из `pharmacogeneticsStore.spec.js`:
```js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePharmacogeneticsStore } from './pharmacogeneticsStore'
import { pharmacogeneticsApi } from '@/modules/shared/api/pharmacogenetics'

vi.mock('@/modules/shared/api/pharmacogenetics', () => ({
  pharmacogeneticsApi: { getForPatient: vi.fn(), createResult: vi.fn(), updateResult: vi.fn(), deleteResult: vi.fn() }
}))

describe('pharmacogeneticsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchPharmacogenetics loads markers and seeds editingValueId from currentValueId', async () => {
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [{ markerId: 1, currentValueId: 10 }] } })

    const store = usePharmacogeneticsStore()
    await store.fetchPharmacogenetics(1, '/api/drugs/1')

    expect(pharmacogeneticsApi.getForPatient).toHaveBeenCalledWith(1, { params: { drug: '/api/drugs/1' } })
    expect(store.markers[0].editingValueId).toBe(10)
  })
})
```
> В проекте **не используется** `@pinia/testing` — она требует Pinia ≥4, а проект зафиксирован на `^3.0.4` (конфликт peer-зависимостей). Настоящий стор через `setActivePinia()`/`useXStore()` полностью закрывает эту потребность.

## Пример: тест простого компонента (без стора)
Из `CollapsibleSection.spec.js`:
```js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CollapsibleSection from './CollapsibleSection.vue'

function mountSection(props = {}) {
  return mount(CollapsibleSection, { props: { title: 'Персональные данные', ...props } })
}

describe('CollapsibleSection.vue', () => {
  it('toggle() flips the expanded state', () => {
    const wrapper = mountSection()
    wrapper.vm.toggle()
    expect(wrapper.vm.isExpanded).toBe(true)
  })

  it('forces expansion when forceExpand becomes true', async () => {
    const wrapper = mountSection({ forceExpand: false })
    await wrapper.setProps({ forceExpand: true })
    expect(wrapper.vm.isExpanded).toBe(true)
  })
})
```

## Пример: компонент со «своим» Pinia-стором внутри
Когда компонент сам вызывает `useXStore()` (в `setup()` или в `computed`), поднимаем настоящую Pinia и подставляем нужное состояние/экшены прямо в стор — не мокаем сам компонент:
```js
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { vi, it, expect } from 'vitest'
import PatientCard from './PatientCard.vue'
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore'

function mountPatientCard(patient) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const store = usePatientCardStore()
  store.patient = patient
  const wrapper = mount(PatientCard, {
    global: { plugins: [pinia], mocks: { $route: { params: { patientId: '7' } } } }
  })
  return { wrapper, store }
}

it('entering edit mode emits edit-start and calls the store', async () => {
  const { wrapper, store } = mountPatientCard({ name: 'Иванов Пётр' })
  store.startEditingPatient = vi.fn()

  await wrapper.find('.btn-edit-treatment').trigger('click')

  expect(store.startEditingPatient).toHaveBeenCalled()
  expect(wrapper.emitted('edit-start')).toBeTruthy()
})
```

## Частые ловушки (проверено на практике в этом проекте)
- **Getters Pinia — read-only.** `store.someGetter = value` не сработает (тихий `[Vue warn] Set operation on key "..." failed: target is readonly`, значение не меняется). Меняйте состояние, от которого зависит getter (например, вместо `treatmentStore.isActive = false` ставьте `treatment.realEndDt = '2024-01-01'`).
- **`mapState`/`mapActions` (Options API).** Этим хелперам нужен реально установленный Pinia-плагин — `global: { plugins: [pinia] }` в `mount()`, а не только `setActivePinia()` (иначе появляются шумные, хоть и не фатальные, предупреждения `$pinia accessed during render but not defined`).
- **`immediate: true`-watcher, который сам дёргает async-экшен на монтировании.** Если после монтирования вы ещё раз меняете состояние и вызываете тот же метод вручную — сначала дождитесь `await flushPromises()`, иначе два вызова racing друг друга непредсказуемо перезапишут состояние.
- **`lodash/debounce` в методах компонента.** Используйте `vi.useFakeTimers()` + `vi.advanceTimersByTime(ms)` вместо ожидания реального времени.
- **`chart.js`/`vue-chartjs` в компоненте.** Стабьте сам компонент графика (`global: { stubs: { Line: true } }`) и тестируйте только вычисляемые данные (`chartData` и т.п.) через `wrapper.vm` — не пытайтесь проверять реальный рендер canvas в jsdom.
- **`$route`/`$router`/`<router-link>` без реального роутера.** Пробрасывайте `global: { mocks: { $route: {...}, $router: { push: vi.fn() } }, stubs: { 'router-link': true } }`.
- **Мок, который тестовый хелпер сбрасывает уже после того, как вы его настроили.** Если `mountXxx()` сам делает `apiMock.mockResolvedValue(...)` «по умолчанию», а тест до его вызова уже настроил другое значение — хелпер молча затрёт настройку теста. Передавайте нужные данные ответа параметром в сам хелпер, не полагайтесь на порядок вызовов `mockResolvedValue`.

## Запуск нового теста во время разработки
```bash
docker exec -w /app ibs-node npx vitest run путь/до/Файл.spec.js
```
или в watch-режиме:
```bash
docker exec -it -w /app ibs-node npx vitest путь/до/Файл.spec.js
```
