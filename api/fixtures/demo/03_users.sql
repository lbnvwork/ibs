-- Демо-данные (3.77): пользователь-доктор (вход demo.doctor / demo12345).
-- Пароль хранится открытым текстом — хэшируется командой app:hash-passwords.
INSERT INTO users (id, medical_personnel_id, login, password, user_name, roles, comment) VALUES
(30001, 20001, 'demo.doctor', 'demo12345', 'Демо Врач', '["ROLE_ADMIN"]'::json, NULL)
ON CONFLICT (id) DO NOTHING;
