--
-- Default Data for Travis CI
--

INSERT INTO gibbonPerson SET gibbonPersonID=1, title='Mr.', surname='Mc Test', firstName='Test', preferredName='Test', officialName='Test McTest', username='admin', password='travis', passwordStrong='', passwordStrongSalt='', status='Full', canLogin='Y', passwordForceReset='N', gibbonRoleIDPrimary='001', gibbonRoleIDAll='001', email='';

UPDATE gibbonSetting SET value='http://127.0.0.1:8888' WHERE scope='System' AND name='absoluteURL';
UPDATE gibbonSetting SET value='/home/travis/build/SKuipers/core' WHERE scope='System' AND name='absolutePath';
UPDATE gibbonSetting SET value='Travis CI' WHERE scope='System' AND name='systemName';
UPDATE gibbonSetting SET value='Travis CI' WHERE scope='System' AND name='organisationName';
UPDATE gibbonSetting SET value='TCI' WHERE scope='System' AND name='organisationNameShort';
UPDATE gibbonSetting SET value='sandra.kuipers@tis.edu.mo' WHERE scope='System' AND name='organisationEmail';
UPDATE gibbonSetting SET value='HKD $' WHERE scope='System' AND name='currency';
UPDATE gibbonSetting SET value='1' WHERE scope='System' AND name='organisationAdministrator';
UPDATE gibbonSetting SET value='1' WHERE scope='System' AND name='organisationDBA';
UPDATE gibbonSetting SET value='1' WHERE scope='System' AND name='organisationHR';
UPDATE gibbonSetting SET value='1' WHERE scope='System' AND name='organisationAdmissions';
UPDATE gibbonSetting SET value='Hong Kong' WHERE scope='System' AND name='country';
UPDATE gibbonSetting SET value='' WHERE scope='System' AND name='gibboneduComOrganisationName';
UPDATE gibbonSetting SET value='' WHERE scope='System' AND name='gibboneduComOrganisationKey';
UPDATE gibbonSetting SET value='Asia/Hong_Kong' WHERE scope='System' AND name='timezone';
UPDATE gibbonSetting SET value='Testing' WHERE scope='System' AND name='installType';
UPDATE gibbonSetting SET value='N' WHERE scope='System' AND name='statsCollection';
UPDATE gibbonSetting SET value='Y' WHERE scope='System' AND name='cuttingEdgeCode';

UPDATE gibboni18n SET code='ka_GE' WHERE code='ke_GE';
ALTER TABLE `gibbonTTDay` ADD `color` VARCHAR(6) NOT NULL AFTER `nameShort`;
INSERT INTO `gibboni18n` (`code`, `name`, `active`, `systemDefault`, `maintainerName`, `maintainerWebsite`, `dateFormat`, `dateFormatRegEx`, `dateFormatPHP`,`rtl`) VALUES ('el_GR', 'ελληνικά - Ελλάδα', 'N', 'N', 'Konstantinos Chonias', '', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\\d\\\d$/i', 'd-m-Y', 'N');
ALTER TABLE `gibbonTTDay` ADD `fontColor` VARCHAR(6) NOT NULL AFTER `color`;
UPDATE gibbonSetting SET description='Allowable choices for positive behaviour' WHERE scope='Behaviour' AND name='positiveDescriptors';
UPDATE gibbonSetting SET description='Allowable choices for negative behaviour' WHERE scope='Behaviour' AND name='negativeDescriptors';
UPDATE gibbonSetting SET description='Allowable choices for severity level (from lowest to highest)' WHERE scope='Behaviour' AND name='levels';
UPDATE gibbonLibraryType SET name='Audio/Visual Hardware' WHERE name='Audio/Visual';
INSERT INTO gibbonLibraryType SET name='Optical Media', active='Y', fields='a:10:{i:0;a:6:{s:4:\"name\";s:4:\"Type\";s:11:\"description\";s:35:\"What type of optical media is this?\";s:4:\"type\";s:6:\"Select\";s:7:\"options\";s:14:\"CD,DVD,Blu-Ray\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"Y\";}i:1;a:6:{s:4:\"name\";s:6:\"Format\";s:11:\"description\";s:38:\"Technical details of media formatting.\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:3:\"255\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:2;a:6:{s:4:\"name\";s:8:\"Language\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:3:\"255\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:3;a:6:{s:4:\"name\";s:9:\"Subtitles\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:3:\"255\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:4;a:6:{s:4:\"name\";s:12:\"Aspect Ratio\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:2:\"20\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:5;a:6:{s:4:\"name\";s:15:\"Number of Discs\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:6:\"Select\";s:7:\"options\";s:10:\",1,2,3,4,5\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:6;a:6:{s:4:\"name\";s:14:\"Content Rating\";s:11:\"description\";s:39:\"Details of age guidance or retrictions.\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:3:\"255\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:7;a:6:{s:4:\"name\";s:6:\"Studio\";s:11:\"description\";s:27:\"Name of originating studio.\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:3:\"255\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:8;a:6:{s:4:\"name\";s:12:\"Release Date\";s:11:\"description\";s:36:\"Format: dd/mm/yyyy, mm/yyyy or yyyy.\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:2:\"10\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}i:9;a:6:{s:4:\"name\";s:8:\"Run Time\";s:11:\"description\";s:11:\"In minutes.\";s:4:\"type\";s:4:\"Text\";s:7:\"options\";s:1:\"3\";s:7:\"default\";s:0:\"\";s:8:\"required\";s:1:\"N\";}}';
CREATE TABLE `gibbonINAssistant` (`gibbonINAssistantID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL, `gibbonPersonIDAssistant` int(10) UNSIGNED ZEROFILL NOT NULL, `comment` text COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`gibbonINAssistantID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `gibbonUsernameFormat` (`gibbonUsernameFormatID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, `gibbonRoleIDList` varchar(255) NULL, `format` varchar(255) NULL, `isDefault` ENUM('Y','N') NOT NULL DEFAULT 'N', `isNumeric` ENUM('Y','N') NOT NULL DEFAULT 'N',`numericValue` int(12) UNSIGNED NOT NULL DEFAULT 0, `numericIncrement` int(3) UNSIGNED NOT NULL DEFAULT 1, `numericSize` int(3) UNSIGNED NOT NULL DEFAULT 4, PRIMARY KEY (`gibbonUsernameFormatID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `gibbonUsernameFormat` SET gibbonRoleIDList='003', `format`=REPLACE((SELECT value FROM gibbonSetting WHERE scope='Application Form' AND name='usernameFormat'),'[preferredNameInitial]','[preferredName:1]'), `isDefault`='Y', `isNumeric`='N';
INSERT INTO `gibbonUsernameFormat` SET gibbonRoleIDList='001,002,006', `format`=REPLACE((SELECT value FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormUsernameFormat'),'[preferredNameInitial]','[preferredName:1]'), `isDefault`='N', `isNumeric`='N';
UPDATE gibbonAction SET URLList='house_manage.php,house_manage_edit.php,house_manage_add.php,house_manage_delete.php,house_manage_assign.php' WHERE name='Manage Houses' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='School Admin');
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Students'), 'Students by House', 0, 'Reports', 'View a report of student houses by year group.', 'report_students_byHouse.php', 'report_students_byHouse.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Students' AND gibbonAction.name='Students by House'));
UPDATE gibbonAction SET URLList='applicationForm_manage.php, applicationForm_manage_edit.php, applicationForm_manage_accept.php, applicationForm_manage_reject.php, applicationForm_manage_add.php' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Students') AND name='Manage Applications_edit';
UPDATE gibbonAction SET URLList='applicationForm_manage.php, applicationForm_manage_edit.php, applicationForm_manage_delete.php, applicationForm_manage_accept.php, applicationForm_manage_reject.php, applicationForm_manage_add.php' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Students') AND name='Manage Applications_editDelete';
INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`) VALUES ('Application Form Accepted', 'Students', 'Manage Applications_edit', 'Core', 'All,gibbonYearGroupID', 'Y');
ALTER TABLE `gibbonActivity` ADD `paymentType` ENUM('Entire Programme','Per Session','Per Week','Per Term') NULL DEFAULT 'Entire Programme' AFTER `payment`, ADD `paymentFirmness` ENUM('Finalised','Estimated') NULL DEFAULT 'Finalised' AFTER `paymentType`;
INSERT INTO `gibboni18n` (`code`, `name`, `active`, `systemDefault`, `maintainerName`, `maintainerWebsite`, `dateFormat`, `dateFormatRegEx`, `dateFormatPHP`,`rtl`) VALUES ('am_ET','አማርኛ - ኢትዮጵያ', 'N', 'N', 'Bruce Banner', '', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\\d\\\d$/i', 'd/m/Y', 'N');
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Timetable Admin'), 'Sync Course Enrolment', 0, 'Courses & Classes', 'Allows users to map enrolments from homerooms to classes.', 'courseEnrolment_sync.php,courseEnrolment_sync_add.php,courseEnrolment_sync_edit.php,courseEnrolment_sync_delete.php,courseEnrolment_sync_run.php', 'courseEnrolment_sync.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Timetable Admin' AND gibbonAction.name='Sync Course Enrolment'));
CREATE TABLE `gibbonCourseClassMap` (`gibbonCourseClassMapID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NULL,`gibbonRollGroupID` int(5) UNSIGNED ZEROFILL NULL, `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL NULL, UNIQUE KEY `gibbonCourseClassID` (gibbonCourseClassID), PRIMARY KEY (`gibbonCourseClassMapID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) VALUES ('Timetable Admin', 'autoEnrolCourses', 'Auto-Enrol Courses Default', 'Should auto-enrolment of new students into courses be turned on or off by default?', 'N');
UPDATE `gibbonNotificationEvent` SET actionName='View Student Profile_full' WHERE (event='Application Form Accepted' OR event='New Application Form') AND moduleName='Students';
INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) VALUES ('Application Form', 'availableYearsOfEntry', 'Available Years of Entry', 'Which school years should be available to apply to?', '');
UPDATE gibbonAction SET gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Planner') WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Resources');
DELETE FROM gibbonModule WHERE name='Resources';
INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) VALUES ('Application Form', 'enableLimitedYearsOfEntry', 'Enable Limited Years of Entry', 'If yes, applicants choices for Year of Entry can be limited to specific school years.', 'N');
ALTER TABLE `gibbonRubricCell` ADD INDEX(`gibbonRubricID`);
ALTER TABLE `gibbonRubricCell` ADD INDEX(`gibbonRubricColumnID`);
ALTER TABLE `gibbonRubricCell` ADD INDEX(`gibbonRubricRowID`);
ALTER TABLE `gibbonRubricColumn` ADD INDEX(`gibbonRubricID`);
ALTER TABLE `gibbonRubricEntry` ADD INDEX(`gibbonRubricID`);
ALTER TABLE `gibbonRubricEntry` ADD INDEX(`gibbonPersonID`);
ALTER TABLE `gibbonRubricEntry` ADD INDEX(`gibbonRubricCellID`);
ALTER TABLE `gibbonRubricEntry` ADD INDEX(`contextDBTable`);
ALTER TABLE `gibbonRubricEntry` ADD INDEX(`contextDBTableID`);
ALTER TABLE `gibbonRubricRow` ADD INDEX(`gibbonRubricID`);