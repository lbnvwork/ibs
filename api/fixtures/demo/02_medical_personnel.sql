-- Демо-данные (3.77): врачи (medical_personnel).
INSERT INTO medical_personnel (id, hospital_id, mod_dt, name, post, address, comment) VALUES
(20001, 10001, NULL, 'Демо Врач', 'Кардиолог', NULL, NULL),
(20002, 10002, NULL, 'Демо Врач 2', 'Терапевт', NULL, NULL)
ON CONFLICT (id) DO NOTHING;
