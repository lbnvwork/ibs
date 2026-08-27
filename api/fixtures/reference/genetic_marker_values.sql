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
-- Data for Name: genetic_marker_values; Type: TABLE DATA; Schema: public; Owner: symfony
--

INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (13, 9, 'CC', 'CC (норма)', 'Нормальный транспорт ПОАК');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (14, 9, 'CT', 'CT (гетерозигота)', 'Возможно изменение транспорта ПОАК');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (15, 9, 'TT', 'TT (мутантная гомозигота)', 'Изменённый транспорт ПОАК');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (16, 10, '*1/*1', '*1/*1 (норма)', 'Нормальный метаболизм');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (17, 10, '*1/*3', '*1/*3 (гетерозигота)', 'Сниженный метаболизм апиксабана и ривароксабана');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (18, 10, '*3/*3', '*3/*3 (мутантная гомозигота)', 'Значительно сниженный метаболизм апиксабана и ривароксабана');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (25, 14, 'CC', 'Норма', 'Нормальная активность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (26, 14, 'CT', 'Гетерозигота', 'Снижение активности');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (27, 14, 'TT', 'Мутантная гомозигота', 'Значительное снижение активности');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (28, 15, 'AA', 'Норма', 'Нормальная активность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (29, 15, 'AC', 'Гетерозигота', 'Снижение активности');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (30, 15, 'CC', 'Мутантная гомозигота', 'Значительное снижение активности');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (31, 7, 'GG', 'Норма', 'Нормальная чувствительность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (32, 7, 'GA', 'Гетерозигота', 'Умеренная чувствительность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (33, 7, 'AA', 'Мутантная гомозигота', 'Высокая чувствительность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (34, 8, 'GG', 'Норма', 'Нормальная чувствительность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (35, 8, 'GA', 'Гетерозигота', 'Умеренная чувствительность');
INSERT INTO public.genetic_marker_values (id, marker_id, value, label, description) VALUES (36, 8, 'AA', 'Мутантная гомозигота', 'Высокая чувствительность');


--
-- Name: genetic_marker_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: symfony
--

SELECT pg_catalog.setval('public.genetic_marker_values_id_seq', 36, true);


--
-- PostgreSQL database dump complete
--


