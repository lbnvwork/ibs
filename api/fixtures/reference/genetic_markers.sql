--
-- PostgreSQL database dump
--


-- Dumped from database version 15.14 (Debian 15.14-1.pgdg13+1)
-- Dumped by pg_dump version 15.14 (Debian 15.14-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: genetic_markers; Type: TABLE DATA; Schema: public; Owner: symfony
--

INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (7, 'VKORC1_3673', 'Витамин К эпоксидредуктаза, G3673A', 'rs9923231', 'Аллель A повышает чувствительность к варфарину (требуется меньшая доза)');
INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (8, 'VKORC1_3730', 'Витамин К эпоксидредуктаза, G3730A', 'rs7294', 'Аллель A понижает чувствительность (резистентность), требуется более высокая доза варфарина');
INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (9, 'ABCB1', 'АВС-транспортёр 1', NULL, 'Влияет на транспорт ПОАК (ривароксабан, апиксабан, дабигатран)');
INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (10, 'CYP3A5', 'Цитохром P450 3A5', NULL, 'Метаболизм апиксабана и ривароксабана');
INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (14, 'CYP2C9_2', 'Цитохром P450 2C9, аллель *2', 'rs1799853', 'Генотип CYP2C9*2');
INSERT INTO public.genetic_markers (id, gene_symbol, full_name, rs_id, description) VALUES (15, 'CYP2C9_3', 'Цитохром P450 2C9, аллель *3', 'rs1057910', 'Генотип CYP2C9*3');


--
-- Name: genetic_markers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: symfony
--

SELECT pg_catalog.setval('public.genetic_markers_id_seq', 15, true);


--
-- PostgreSQL database dump complete
--


