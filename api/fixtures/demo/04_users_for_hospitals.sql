-- Демо-данные (3.77): привязка доктора к обеим больницам.
INSERT INTO users_for_hospitals (id, user_id, hospital_id, permissions) VALUES
(30001, 30001, 10001, NULL),
(30002, 30001, 10002, NULL)
ON CONFLICT (id) DO NOTHING;
