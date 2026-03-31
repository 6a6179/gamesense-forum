UPDATE gs_config
SET conf_value = 'http://localhost:8080/forums'
WHERE conf_name = 'o_base_url';

UPDATE gs_config
SET conf_value = '0'
WHERE conf_name = 'recaptcha_enabled';

UPDATE gs_config
SET conf_value = ''
WHERE conf_name = 'o_mailing_list';

UPDATE gs_config
SET conf_value = 'dev@localhost'
WHERE conf_name IN ('o_admin_email', 'o_webmaster_email');
