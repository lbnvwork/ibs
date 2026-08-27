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
-- Data for Name: drugs; Type: TABLE DATA; Schema: public; Owner: symfony
--

INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (1, '2014-11-30 12:10:13', 'варфарин', 'варфарина', 1);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (2, '2014-11-30 12:10:13', 'фенилин', 'фенилина', 1);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (3, NULL, 'дабигатран', 'дабигатрана', 2);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (4, NULL, 'ривароксабан', 'ривароксабана', 2);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (5, NULL, 'апиксабан', 'апиксабана', 2);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (6, NULL, 'эдоксабан', 'эдоксабана', 2);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (7, NULL, 'НМГ', 'НМГ', 3);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (8, NULL, 'НФГ', 'НФГ', 3);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (9, NULL, 'аспирин', 'аспирина', 4);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (10, NULL, 'клопидогрел', 'клопидогрела', 4);
INSERT INTO public.drugs (id, mod_dt, nominative, genitive, group_id) VALUES (11, NULL, 'тикагрелор', 'тикагрелора', 4);


--
-- Name: drugs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: symfony
--

SELECT pg_catalog.setval('public.drugs_id_seq', 11, true);


--
-- PostgreSQL database dump complete
--


