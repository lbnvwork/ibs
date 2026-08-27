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
-- Data for Name: sms_templates; Type: TABLE DATA; Schema: public; Owner: symfony
--

INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (1, 1, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (2, 2, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G. $COMMENT.', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (3, 4, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (4, 6, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (5, 8, '79207074400', '$CONTROL_DATE необходимо провести контрольный замер МНО у пациента $CODE.', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (6, 16, '79207074400', '$CONTROL_DATE необходимо провести контрольный замер МНО всех пациентов', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (7, 32, '79207074400', 'Ваше МНО - $MNO. Дозировку $DRUG_G оставьте прежней', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (8, 64, '79207074400', 'Смс не принята с ошибкой: $DESCRIPTION', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (9, 128, '79207074400', 'Принято для $CODE: МНО $MNO, Доза: $DOSE', NULL);
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (10, 256, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ЧЕРЕДОВАТЬ $DOSE и $SDOSE $DRUG_G. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (11, 512, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ЧЕРЕДОВАТЬ $DOSE и $SDOSE $DRUG_G. $COMMENT.', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (12, 1024, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ЧЕРЕДОВАТЬ $DOSE и $SDOSE $DRUG_G. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (13, 1536, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ЧЕРЕДОВАТЬ $DOSE и $SDOSE $DRUG_G. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (14, 4096, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G утром и $SDOSE $DRUG_G вечером. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (15, 8192, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G утром и $SDOSE $DRUG_G вечером. $COMMENT.', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (16, 16384, '79207074400', 'Ваше МНО - $MNO. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G утром и $SDOSE $DRUG_G вечером. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (17, 24576, '79207074400', 'Ваше МНО - $MNO. До $DATE от приема $DRUG_G воздержитесь. С $DATE ВАМ НУЖНО ПРИНИМАТЬ $DOSE $DRUG_G утром и $SDOSE $DRUG_G вечером. $CONTROL_DATE необходимо прийти для контрольного измерения МНО. $COMMENT', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (18, 9, '79207074400', 'Пациент $PATIENT включен в систему мониторинга с кодом $CODE. $CONTROL_DATE необходимо провести контрольный замер МНО у пациента.', '');
INSERT INTO public.sms_templates (id, sms_type, sms_source, sms_template, comment) VALUES (19, 10, '79207074400', 'Вы включены в систему централизованного мониторинга МНО. Ваш код $CODE. Явка для сдачи МНО - $CONTROL_DATE', '');


--
-- Name: sms_templates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: symfony
--

SELECT pg_catalog.setval('public.sms_templates_id_seq', 19, true);


--
-- PostgreSQL database dump complete
--


