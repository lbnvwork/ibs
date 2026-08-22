# 4.7 — Подготовка материалов для регистрации программы для ЭВМ

Рабочая область задачи 4.7. Одна кодовая база готовится к подаче **двух
разных регистрационных объектов** — значит, будет два комплекта документов.
Общий код распределяется между объектами по правилу «один файл принадлежит
только одному объекту».

## Статус выполнения

- [x] Пункт 1 — зафиксированы рабочие названия и границы двух регистрационных объектов
- [x] Пункт 2 — созданы манифесты исходного кода (`object_a/manifest.yaml`, `object_b/manifest.yaml`)
- [x] Пункт 3 — написан генератор PDF-распечатки (`scripts/registration_4_7/`)
- [x] Пункт 4 — подготовлены два реферата (`object_a/abstract.md`, `object_b/abstract.md`)
- [x] Пункт 5 — подготовлены два набора данных для куратора (`data_for_application.md`)
- [x] Пункт 6 — прогнаны тест-кейсы для каждого объекта (верификация PDF и рефератов)
- [ ] Пункт 7 — коммит и проверка комплекта

---

## Регистрационный объект A

- **Рабочее название:** «Модуль поддержки принятия врачебных решений по дозированию варфарина»
- **Официальное название:** ожидается от куратора проекта
- **Фокус:** CDSS-функции, расчёт дозировки, фармакогенетика, витальные показатели

### Состав исходного кода

**Backend**

```text
api/src/Ibs/Context/AICDSS/
api/src/Ibs/Context/LabIoTGateway/
```

**Frontend**

```text
frontend/src/modules/medicalHistory/components/Pharmacogenetics/
frontend/src/modules/medicalHistory/components/MnoChart/
frontend/src/modules/medicalHistory/components/VitalsCard/
frontend/src/modules/shared/api/pharmacogenetics.js
frontend/src/modules/shared/api/vitals.js
frontend/src/modules/shared/utils/vitalsHelpers.js
```

---

## Регистрационный объект B

- **Рабочее название:** «Медицинская информационная система ведения терапии варфарином»
- **Официальное название:** ожидается от куратора проекта
- **Фокус:** ведение пациентов, терапия, приёмы, коммуникации, аутентификация и основной UI

### Состав исходного кода

**Backend**

```text
api/src/Ibs/Context/PatientManagement/
api/src/Ibs/Context/TreatmentTherapy/
api/src/Ibs/Context/Communication/
api/src/Ibs/Context/SecurityIdentity/
api/src/Ibs/Shared/
```

**Frontend**

```text
frontend/src/modules/patientManagement/
frontend/src/modules/medicalHistory/components/PatientCard/
frontend/src/modules/medicalHistory/components/TreatmentCard/
frontend/src/modules/medicalHistory/components/MedicalTable/
frontend/src/modules/medicalHistory/components/AppointmentAdd/
frontend/src/modules/medicalHistory/components/TestAddModal/
frontend/src/modules/medicalHistory/components/RiskScale/
frontend/src/modules/medicalHistory/components/CollapsibleSection/
frontend/src/modules/shared/layout/
frontend/src/modules/shared/stores/
frontend/src/modules/shared/api/client.js
frontend/src/modules/shared/api/patients.js
frontend/src/modules/shared/api/treatments.js
frontend/src/modules/shared/api/drug.js
frontend/src/modules/shared/api/hospitals.js
frontend/src/modules/shared/api/mkb10.js
frontend/src/modules/shared/api/testHistory.js
```

---

## Правило распределения общего кода

Каждый файл исходного кода включается только в один регистрационный объект.
Служебные общие модули относим к тому объекту, чью основную функциональность
они обеспечивают:

- `SecurityIdentity`, `Shared`, базовый `api/client.js`, справочные API пациентов
  и лечения — в объект B (инфраструктура клинической части);
- `pharmacogenetics.js`, `vitals.js`, `vitalsHelpers.js` — в объект A
  (витрина CDSS-функций).

---

## Целевая структура каталога

```text
docs/registration_4_7/
├── README.md                      # этот файл
├── object_a/
│   ├── manifest.yaml              # пункт 2
│   ├── abstract.md                # пункт 4
│   ├── abstract.pdf               # пункт 4
│   ├── data_for_application.md    # пункт 5 — данные для куратора
│   └── printout/
│       └── printout.pdf           # пункт 3
└── object_b/
    ├── manifest.yaml              # пункт 2
    ├── abstract.md                # пункт 4
    ├── abstract.pdf               # пункт 4
    ├── data_for_application.md    # пункт 5 — данные для куратора
    └── printout/
        └── printout.pdf           # пункт 3
```

Скрипты генерации и проверки будут размещены в `scripts/registration_4_7/`.

---

## Ответственность сторон

- **Мы (разработка) готовим:**
  - PDF-распечатки исходного кода по обоим объектам;
  - два реферата;
  - два структурированных набора данных для официальной формы.

- **Куратор проекта готовит:**
  - заполнение официальной формы заявки, включая титульный лист;
  - окончательную сверку юридических и контактных данных.

---

## Открытые вопросы для куратора

1. Официальные названия обоих регистрационных объектов.
2. Правообладатель, авторы, дата создания, контактные данные для каждого объекта.
3. Совпадают ли правообладатель и авторы для объектов A и B.
4. Кто назначен ответственным за подачу заявки (патентовед).

---

## Данные проекта, используемые в документах

- Backend: PHP 8.2, Symfony 7.3, API Platform
- Frontend: Vue 3, Vite, Pinia
- БД: PostgreSQL 15
- Запуск: Docker, PHP-FPM, nginx
- Целевая ОС: Ubuntu 22.04 LTS

---

## Следующий шаг

Пункт 7 — коммит и финальная проверка комплекта. Перед подачей необходимо
запросить у куратора официальные названия, правообладателя, авторов, дату
создания и контакты — эти сведения заполняются в `data_for_application.md`
и переносятся в официальную форму заявки куратором/патентоведом.