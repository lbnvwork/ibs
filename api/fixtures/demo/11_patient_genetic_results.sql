-- Демо-данные (3.77): фармакогенетика (CYP2C9, VKORC1).
-- marker 14=CYP2C9_2 (26=CT), 15=CYP2C9_3 (29=AC), 7=VKORC1_3673 (31=GG, 32=GA).
INSERT INTO patient_genetic_results (id, patient_id, marker_id, marker_value_id, created_at, updated_at, created_by, updated_by) VALUES
(90001, 40004, 14, 26, '2026-01-20 00:00:00', '2026-01-20 00:00:00', NULL, NULL),
(90002, 40004, 15, 29, '2026-01-20 00:00:00', '2026-01-20 00:00:00', NULL, NULL),
(90003, 40004, 7, 32, '2026-01-20 00:00:00', '2026-01-20 00:00:00', NULL, NULL),
(90004, 40001, 7, 31, '2026-01-10 00:00:00', '2026-01-10 00:00:00', NULL, NULL),
(90005, 40002, 14, 25, '2026-01-15 00:00:00', '2026-01-15 00:00:00', NULL, NULL)
ON CONFLICT (id) DO NOTHING;

