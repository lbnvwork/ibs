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
-- Data for Name: phone_types; Type: TABLE DATA; Schema: public; Owner: symfony
--

INSERT INTO public.phone_types (id, mod_dt, name, mask, comment) VALUES (1, NULL, 'Мобильный', '8(999)999-99-99', 'Номер мобильного телефона (Россия)');
INSERT INTO public.phone_types (id, mod_dt, name, mask, comment) VALUES (2, NULL, 'Домашний - 5 цифр', '9-99-99', 'Домашний телефон из 5 цифр');
INSERT INTO public.phone_types (id, mod_dt, name, mask, comment) VALUES (3, NULL, 'Домашний - 6 цифр', '99-99-99', 'Домашний телефон из 6 цифр');
INSERT INTO public.phone_types (id, mod_dt, name, mask, comment) VALUES (4, NULL, 'Домашний - 5 цифр с кодом района', '8(49999)9-99-99', 'Домашний телефон из 5 цифр с кодом района');
INSERT INTO public.phone_types (id, mod_dt, name, mask, comment) VALUES (5, NULL, 'Домашний - 6 цифр с кодом района', '8(4999)99-99-99', 'Домашний телефон из 6 цифр с кодом района');


--
-- Name: phone_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: symfony
--

SELECT pg_catalog.setval('public.phone_types_id_seq', 5, true);


--
-- PostgreSQL database dump complete
--


