-- Демо-данные (3.77): лечения (5). mkb10: 3998=I48, 4162=I80. drugs: 1=варфарин, 4=ривароксабан.
INSERT INTO treatments (id, patient_id, drug_id, mkb10_id, mod_dt, code, diagnosis, comorbidities, mno_from, mno_to, beg_dt, plan_end_dt, real_end_dt, stopping_reason, comment, hemorrhages, flags, diagnosis_code, pin, antiplatelet_drug_id, antiplatelet_doze) VALUES
(50001, 40001, 1, 3998, NULL, 10001, 'Фибрилляция и трепетание предсердий', NULL, 2.0, 3.0, '2026-01-10 00:00:00', NULL, NULL, NULL, NULL, 0, 0, 'I48', NULL, NULL, NULL),
(50002, 40002, 1, 3998, NULL, 10002, 'Фибрилляция и трепетание предсердий', NULL, 2.0, 3.0, '2026-01-15 00:00:00', NULL, NULL, NULL, NULL, 0, 0, 'I48', NULL, NULL, NULL),
(50003, 40003, 1, 4162, NULL, 10003, 'Флебит и тромбофлебит', NULL, 2.0, 3.0, '2026-02-01 00:00:00', NULL, NULL, NULL, NULL, 0, 0, 'I80', NULL, NULL, NULL),
(50004, 40004, 1, 3998, NULL, 10004, 'Фибрилляция и трепетание предсердий', NULL, 2.0, 3.0, '2026-01-20 00:00:00', NULL, NULL, NULL, 'Чередование доз 2,5/2,75', 0, 0, 'I48', NULL, NULL, NULL),
(50005, 40005, 4, 3998, NULL, 10005, 'Фибрилляция и трепетание предсердий', NULL, 2.0, 3.0, '2026-02-10 00:00:00', NULL, NULL, NULL, 'ПОАК (ривароксабан)', 0, 0, 'I48', NULL, NULL, NULL)
ON CONFLICT (id) DO NOTHING;
