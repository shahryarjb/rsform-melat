INSERT IGNORE INTO `#__rsform_config` (`SettingName`, `SettingValue`) VALUES
('trangellmellat.terminalid', ''),
('trangellmellat.username', ''),
('trangellmellat.userpassword', ''),
('trangellmellat.tax.value', '');

INSERT IGNORE INTO `#__rsform_component_types` (`ComponentTypeId`, `ComponentTypeName`) VALUES (201, 'trangellmellat');

DELETE FROM #__rsform_component_type_fields WHERE ComponentTypeId = 201;
INSERT IGNORE INTO `#__rsform_component_type_fields` (`ComponentTypeId`, `FieldName`, `FieldType`, `FieldValues`, `Ordering`) VALUES
(201, 'NAME', 'textbox', '', 0),
(201, 'LABEL', 'textbox', '', 1),
(201, 'COMPONENTTYPE', 'hidden', '201', 2),
(201, 'LAYOUTHIDDEN', 'hiddenparam', 'YES', 7);