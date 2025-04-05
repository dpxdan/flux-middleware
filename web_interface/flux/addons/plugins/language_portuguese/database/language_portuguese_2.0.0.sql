INSERT INTO `languages` ( `code`, `name`, `locale`) VALUES ('pt', 'Portuguese', 'pt_BR');
ALTER TABLE `translations` ADD `pt_BR` TEXT NOT NULL;
INSERT INTO `system` ( `name`, `value`, `field_type`, `reseller_id`, `is_display`) VALUES ('default_language', 'Portuguese', 'default_system_input', '0', '1');
