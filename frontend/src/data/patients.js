// Данные пациентов
export const patients = [
  {
    id: 1,
    name: "Иванов Иван Николаевич",
    age: "66 лет",
    birthDate: "07.04.1958",
    address: "г. Курск, ул. Ломакина, д. 5, кв. 7",
    phone: "8 (919) 275-88-19",
    email: "ivanov@mail.ru",
    passport: "3820 290865",
    insurance: "4655140842000261",
    snils: "20969171795",
    hospital: "Курская больница №1 им. Н.С. Короткова",
    diagnosis: "ФП постоянная форма. ОНМК 2017 г.",
    consentSigned: true,
    additionalConditions: [
      "Язвенная болезнь 12ПК",
      "Мочекаменная болезнь",
      "Целевой диапазон МНО: 2.0-3.0"
    ],
    inrRange: "2.0-3.0",
      riskScores: {
      cha2ds2Vasc: 4,
      hasBled: 1,
      score: 2
    },
    pharmacogenetics: [
      "VKORC1 GG - нормальный метаболизм варфарина",
      "CYP2C9 *1/*1 - нормальная активность"
    ],
    mutations: [
      "MTHFR (677) CC - нормальный",
      "Faktor V Leiden - не выявлено"
    ],
    medicalHistory: [
      {
        date: "03.10.2023",
        inr: "1.36",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.0",
        prescribedDose: "2.5",
        recommendations: "С 03.10.23 принимать 2.5 таблетки варфарина. Контроль МНО 10.10.23",
        comment: "Ваcильева Юлия Ивановна"
      },
      {
        date: "10.10.2023",
        inr: "1.44",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.5",
        prescribedDose: "3.0",
        recommendations: "С 10.10.23 принимать 3 таблетки варфарина. Контроль МНО 17.10.23",
        comment: "Ваcильева Юлия Ивановна"
      },
      {
        date: "18.10.2023",
        inr: "1.44",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.0",
        prescribedDose: "3.5",
        recommendations: "С 18.10.23 принимать 3.5 таблетки варфарина. Контроль МНО 25.10.23",
        comment: "Сорокина Анна Сергеевна"
      },
      {
        date: "25.10.2023",
        inr: "2.11",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "15.11.2023",
        inr: "2.99",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "04.01.2024",
        inr: "3.24",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "19.01.2024",
        inr: "2.11",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "09.03.2024",
        inr: "2.46",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "23.03.2024",
        inr: "2.25",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "20.04.2024",
        inr: "2.3",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "18.05.2024",
        inr: "1.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.75",
        recommendations: "С 18.05.24 принимать 3.75 таблетки варфарина. Контроль МНО 25.05.24",
        comment: "Асеев Илья Александрович"
      },
      {
        date: "21.06.2024",
        inr: "2.23",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75",
        prescribedDose: "3.5",
        recommendations: "С 21.06.24 принимать 3.5 таблетки варфарина",
        comment: "Ларина Инна Валерьевна"
      },
      {
        date: "10.08.2024",
        inr: "1.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5/3.75",
        recommendations: "Чередовать 3.5 и 3.75 таблетки варфарина. Контроль МНО 17.08.24",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "17.08.2024",
        inr: "1.96",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5/3.75",
        prescribedDose: "3.5",
        recommendations: "Принимать 3.5 таблетки варфарина",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "21.09.2024",
        inr: "3.69",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.25",
        recommendations: "С 21.09.24 принимать 3.25 таблетки варфарина. Контроль МНО 28.09.24",
        comment: "Ларина Инна Валерьевна"
      },
      {
        date: "19.10.2024",
        inr: "1.74",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.25",
        prescribedDose: "3.25/3.5",
        recommendations: "Чередовать 3.25 и 3.5 таблетки варфарина. Контроль МНО 26.10.24",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "07.11.2024",
        inr: "2.24",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.25/3.5",
        prescribedDose: "3.25/3.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Сорокина Анна Сергеевна"
      },
      {
        date: "03.02.2025",
        inr: "2.25",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.25/3.5",
        prescribedDose: "3.5/3.25",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "21.03.2025",
        inr: "1.78",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5/3.25",
        prescribedDose: "3.5",
        recommendations: "С 21.03.25 принимать 3.5 таблетки варфарина. Контроль МНО 28.03.25",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "28.03.2025",
        inr: "1.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5",
        prescribedDose: "3.5/3.75",
        recommendations: "Чередовать 3.5 и 3.75 таблетки варфарина. Контроль МНО 04.04.25",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "11.04.2025",
        inr: "1.96",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.5/3.75",
        prescribedDose: "3.75",
        recommendations: "С 12.04.25 принимать 3.75 таблетки варфарина",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "02.05.2025",
        inr: "2.55",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75",
        prescribedDose: "3.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "23.05.2025",
        inr: "2.38",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75",
        prescribedDose: "3.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "20.06.2025",
        inr: "1.86",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75",
        prescribedDose: "3.75/4.0",
        recommendations: "Чередовать 3.75 и 4.0 таблетки варфарина. Контроль МНО 27.06.25",
        comment: "Ваcильева Юлия Ивановна"
      },
      {
        date: "01.08.2025",
        inr: "2.71",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75/4.0",
        prescribedDose: "3.75/4.0",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "12.09.2025",
        inr: "3.0",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75/4.0",
        prescribedDose: "3.75/4.0",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "07.11.2025",
        inr: "2.52",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "3.75/4.0",
        prescribedDose: "3.75/4.0",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      }
    ]
  },
  // Обновленные данные для второго пациента
  {
    id: 2,
    name: "Петров Петр Петрович",
    age: "58 лет",
    birthDate: "15.07.1965",
    address: "Курск, улица Ленина 15 - 7",
    phone: "+7 910 123 45 67",
    email: "petrov@mail.ru",
    passport: "4528 654321",
    insurance: "461320987654",
    snils: "173-83-46-3",
    hospital: "Поликлиника № 4",
    diagnosis: "Фибрилляция предсердий",
    consentSigned: false,
    additionalConditions: [
      "Артериальная гипертензия",
      "Целевой диапазон МНО: 2.0-3.0"
    ],
    inrRange: "2.0-3.0",
      riskScores: {
      cha2ds2Vasc: 2,
      hasBled: 3,
      score: 5
    },
    pharmacogenetics: [
      "VKORC1 AG - умеренная чувствительность",
      "CYP2C9 *1/*3 - сниженная активность"
    ],
    mutations: [
      "MTHFR (677) CT - гетерозигота",
      "Prothrombin G20210A - не выявлено"
    ],
    medicalHistory: [
      {
        date: "30.03.2025",
        inr: "1.7",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.0",
        prescribedDose: "2.5",
        recommendations: "С 30.03.25 принимать 2.5 таблетки варфарина. Контроль МНО 07.04.25",
        comment: "Ваcильева Юлия Ивановна"
      },
      {
        date: "07.04.2025",
        inr: "2.6",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.5",
        prescribedDose: "2.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "12.04.2025",
        inr: "3.1",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.5",
        prescribedDose: "2.25",
        recommendations: "С 12.04.25 принимать 2.25 таблетки варфарина. Контроль МНО 15.04.25",
        comment: "Сорокина Анна Сергеевна"
      },
      {
        date: "15.04.2025",
        inr: "3.4",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.25",
        prescribedDose: "2.0",
        recommendations: "С 15.04.25 принимать 2.0 таблетки варфарина. Контроль МНО 19.04.25",
        comment: "Ларина Инна Валерьевна"
      },
      {
        date: "19.04.2025",
        inr: "2.6",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.0",
        prescribedDose: "2.0",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "22.04.2025",
        inr: "3.8",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.0",
        prescribedDose: "1.75",
        recommendations: "С 22.04.25 принимать 1.75 таблетки варфарина. Контроль МНО 13.05.25",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "13.05.2025",
        inr: "2.7",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.75",
        prescribedDose: "1.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "25.05.2025",
        inr: "2.7",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.75",
        prescribedDose: "1.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "10.06.2025",
        inr: "3.9",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.75",
        prescribedDose: "1.5",
        recommendations: "С 10.06.25 принимать 1.5 таблетки варфарина. Контроль МНО 18.06.25",
        comment: "Ваcильева Юлия Ивановна"
      },
      {
        date: "18.06.2025",
        inr: "2.9",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.5",
        prescribedDose: "1.5",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "08.07.2025",
        inr: "1.4",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.5",
        prescribedDose: "2.0",
        recommendations: "С 08.07.25 принимать 2.0 таблетки варфарина. Контроль МНО 03.08.25",
        comment: "Асеев Илья Александрович"
      },
      {
        date: "03.08.2025",
        inr: "3.8",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "2.0",
        prescribedDose: "1.75",
        recommendations: "С 03.08.25 принимать 1.75 таблетки варфарина. Контроль МНО 06.08.25",
        comment: "Ларина Инна Валерьевна"
      },
      {
        date: "06.08.2025",
        inr: "2.8",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.75",
        prescribedDose: "1.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "23.08.2025",
        inr: "6.2",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.75",
        prescribedDose: "1.0",
        recommendations: "С 23.08.25 принимать 1.0 таблетки варфарина. Контроль МНО 26.08.25",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "26.08.2025",
        inr: "3.8",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "1.0",
        prescribedDose: "0.75",
        recommendations: "С 26.08.25 принимать 0.75 таблетки варфарина. Контроль МНО 30.08.25",
        comment: "Пономарёва Ирина Владимировна"
      },
      {
        date: "30.08.2025",
        inr: "2.7",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "0.75",
        prescribedDose: "0.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "14.09.2025",
        inr: "3.1",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "0.75",
        prescribedDose: "0.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      },
      {
        date: "16.09.2025",
        inr: "2.3",
        analysis1: "-",
        analysis2: "-",
        heartRate: "-",
        bloodPressure: "-",
        currentDose: "0.75",
        prescribedDose: "0.75",
        recommendations: "Дозировку варфарина оставьте прежней",
        comment: "Автоматическое назначение"
      }
    ]
  },
  {
    id: 3,
    name: "Ефремова Светлана Николаевна",
    age: "34 лет",
    birthDate: "02.02.1991",
    address: "г. Курск, ул. Еремина, д. 17/2, кв. 3",
    phone: "+7 (988) 756-31-99",
    email: "stepanos@mail.ru",
    passport: "4527 538834",
    insurance: "461320967509",
    snils: "172-82-45-2",
    hospital: "Женская консультация № 2",
    diagnosis: "Протезирование аортального клапана механическим протезом от мая 2023 г.",
    consentSigned: true,
    additionalConditions: [
      "Механический протез аортального клапана",
      "Целевой диапазон МНО: 2.5-3.5"
    ],
    inrRange: "2.5-3.5",
    riskScores: {
      cha2ds2Vasc: 1,
      hasBled: 1,
      score: 1
    },
    pharmacogenetics: [
      "VKORC1 GG - Чувствительность к варфарину",
      "Не выявлено Чувствительность к ПОАК",
      "ITGB3 - полиморфизм выявлен"
    ],
    mutations: [
      "MTHFR (677) AT; РАН 5G4G",
      "MTRR AG"
    ],
    medicalHistory: [
      {
        date: "22.05.2023",
        inr: "1.15",
        analysis1: "-",
        analysis2: "-",
        heartRate: "78",
        bloodPressure: "120/80",
        currentDose: "0",
        prescribedDose: "5.0",
        recommendations: "Начало терапии варфарином после протезирования клапана. Принимать 5 мг ежедневно. Контроль МНО 25.05.2023",
        comment: "Кардиохирург Петров А.В."
      },
      {
        date: "25.05.2023",
        inr: "1.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "76",
        bloodPressure: "118/78",
        currentDose: "5.0",
        prescribedDose: "6.0",
        recommendations: "Увеличить дозу варфарина до 6 мг ежедневно. Контроль МНО 29.05.2023",
        comment: "Кардиохирург Петров А.В."
      },
      {
        date: "29.05.2023",
        inr: "2.45",
        analysis1: "-",
        analysis2: "-",
        heartRate: "74",
        bloodPressure: "116/76",
        currentDose: "6.0",
        prescribedDose: "5.5",
        recommendations: "Снизить дозу варфарина до 5.5 мг ежедневно. Контроль МНО 02.06.2023",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "02.06.2023",
        inr: "2.95",
        analysis1: "-",
        analysis2: "-",
        heartRate: "72",
        bloodPressure: "115/75",
        currentDose: "5.5",
        prescribedDose: "5.0",
        recommendations: "Снизить дозу варфарина до 5 мг ежедневно. Целевой диапазон достигнут. Контроль МНО 09.06.2023",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "09.06.2023",
        inr: "3.15",
        analysis1: "-",
        analysis2: "-",
        heartRate: "70",
        bloodPressure: "114/74",
        currentDose: "5.0",
        prescribedDose: "4.5",
        recommendations: "Снизить дозу варфарина до 4.5 мг ежедневно. Контроль МНО 16.06.2023",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "16.06.2023",
        inr: "2.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "68",
        bloodPressure: "112/72",
        currentDose: "4.5",
        prescribedDose: "4.5",
        recommendations: "Дозировку варфарина оставить прежней. МНО в целевом диапазоне. Контроль через 2 недели",
        comment: "Автоматическое назначение"
      },
      {
        date: "30.06.2023",
        inr: "3.45",
        analysis1: "-",
        analysis2: "-",
        heartRate: "66",
        bloodPressure: "110/70",
        currentDose: "4.5",
        prescribedDose: "4.0",
        recommendations: "Снизить дозу варфарина до 4 мг ежедневно. Контроль МНО 07.07.2023",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "07.07.2023",
        inr: "2.65",
        analysis1: "-",
        analysis2: "-",
        heartRate: "65",
        bloodPressure: "108/68",
        currentDose: "4.0",
        prescribedDose: "4.0",
        recommendations: "Дозировку варфарина оставить прежней. МНО в целевом диапазоне. Контроль через 4 недели",
        comment: "Автоматическое назначение"
      },
      {
        date: "04.08.2023",
        inr: "2.55",
        analysis1: "-",
        analysis2: "-",
        heartRate: "64",
        bloodPressure: "106/66",
        currentDose: "4.0",
        prescribedDose: "4.0",
        recommendations: "Дозировку варфарина оставить прежней. Стабильное состояние. Контроль через 6 недель",
        comment: "Автоматическое назначение"
      },
      {
        date: "15.09.2023",
        inr: "2.35",
        analysis1: "-",
        analysis2: "-",
        heartRate: "63",
        bloodPressure: "104/64",
        currentDose: "4.0",
        prescribedDose: "4.25",
        recommendations: "Увеличить дозу варфарина до 4.25 мг ежедневно. Контроль МНО 22.09.2023",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "22.09.2023",
        inr: "2.95",
        analysis1: "-",
        analysis2: "-",
        heartRate: "62",
        bloodPressure: "102/62",
        currentDose: "4.25",
        prescribedDose: "4.25",
        recommendations: "Дозировку варфарина оставить прежней. МНО в целевом диапазоне. Контроль через 8 недель",
        comment: "Автоматическое назначение"
      },
      {
        date: "17.11.2023",
        inr: "2.85",
        analysis1: "-",
        analysis2: "-",
        heartRate: "61",
        bloodPressure: "100/60",
        currentDose: "4.25",
        prescribedDose: "4.25",
        recommendations: "Дозировку варфарина оставить прежней. Стабильное состояние. Контроль через 12 недель",
        comment: "Автоматическое назначение"
      },
      {
        date: "09.02.2024",
        inr: "2.75",
        analysis1: "-",
        analysis2: "-",
        heartRate: "60",
        bloodPressure: "98/58",
        currentDose: "4.25",
        prescribedDose: "4.25",
        recommendations: "Дозировку варфарина оставить прежней. Продолжать наблюдение. Следующий контроль 03.05.2024",
        comment: "Кардиолог Сидорова Е.Л."
      },
      {
        date: "03.05.2024",
        inr: "2.65",
        analysis1: "-",
        analysis2: "-",
        heartRate: "59",
        bloodPressure: "96/56",
        currentDose: "4.25",
        prescribedDose: "4.25",
        recommendations: "Дозировку варфарина оставить прежней. Стабильное течение. Контроль через 6 месяцев",
        comment: "Автоматическое назначение"
      },
      {
        date: "02.11.2024",
        inr: "2.55",
        analysis1: "-",
        analysis2: "-",
        heartRate: "58",
        bloodPressure: "94/54",
        currentDose: "4.25",
        prescribedDose: "4.25",
        recommendations: "Дозировку варфарина оставить прежней. Состояние стабильное. Продолжать терапию",
        comment: "Автоматическое назначение"
      }
    ]
  }
];

// Данные для мониторинга пациентов
export const monitoringPatients = [
  {
    id: 1,
    name: "Иванов Иван Николаевич",
    age: "66 лет",
    diagnosis: "ФП постоянная форма. ОНМК 2017 г.",
    smsStatus: "📱✓",
    indicators: '<span style="color:#2a5c98;">↓&nbsp;MHO - 2,52</span>, Целевой диапазон: 2.0-3.0',
    comment: "Последний контроль: 07.11.2025",
    highlightRed: false,
    highlightBlue: false
  },
  {
    id: 2,
    name: "Петров Петр Петрович",
    age: "58 лет",
    diagnosis: "Фибрилляция предсердий",
    smsStatus: "📱✔",
    indicators: '<span style="color:#2a5c98;">↓&nbsp;MHO - 2,3</span>, Целевой диапазон: 2.0-3.0',
    comment: "Последний контроль: 16.09.2025",
    highlightRed: false,
    highlightBlue: false
  },
  {
    id: 3,
    name: "Ефремова Светлана Николаевна",
    age: "34 лет",
    diagnosis: "Протезирование аортального клапана",
    smsStatus: "📱✓",
    indicators: '<span style="color:#2a5c98;">MHO - 2,8</span>, Целевой диапазон: 2.5-3.5',
    comment: "Последний контроль: 15.12.2023",
    highlightRed: false,
    highlightBlue: false
  }
];

// Данные для левой панели списка пациентов
export const panelPatients = [
  {
    id: 1,
    name: "Иванов Иван Николаевич",
    info: "66 лет, ФП постоянная форма. ОНМК 2017 г., МНО: 2.52 (целевой диапазон 2.0-3.0)",
    diagnosis: "ФП постоянная форма. ОНМК 2017 г.",
    status: "активный",
    inr: "2.52",
    targetRange: "2.0-3.0"
  },
  {
    id: 2,
    name: "Петров Петр Петрович",
    info: "58 лет, Фибрилляция предсердий, МНО: 2.3 (целевой диапазон 2.0-3.0)",
    diagnosis: "Фибрилляция предсердий",
    status: "активный",
    inr: "2.3",
    targetRange: "2.0-3.0"
  },
  {
    id: 3,
    name: "Ефремова Светлана Николаевна",
    info: "34 лет, Протезирование аортального клапана, МНО: 2.8 (целевой диапазон 2.5-3.5)",
    diagnosis: "Протезирование аортального клапана",
    status: "активный",
    inr: "2.8",
    targetRange: "2.5-3.5"
  }
];