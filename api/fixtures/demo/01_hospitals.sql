-- Демо-данные (3.77): больницы (ЛПУ). Идемпотентно: ON CONFLICT DO NOTHING.
INSERT INTO hospitals (id, mod_dt, name, region, sms_phone, address, comment) VALUES
(10001, NULL, 'Клиника «Северная»', 'Санкт-Петербург', NULL, 'пр. Медиков, 1', 'Демо ЛПУ 1'),
(10002, NULL, 'Клиника «Центральная»', 'Москва', NULL, 'ул. Кардиологов, 10', 'Демо ЛПУ 2')
ON CONFLICT (id) DO NOTHING;
