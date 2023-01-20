--
-- Data for table `gibbonPerson`
--
SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

--- User: testingadmin       7SSbB9FZN24Q
INSERT INTO `gibbonPerson` (`title`, `surname`, `firstName`, `preferredName`, `officialName`, `nameInCharacters`, `username`, `passwordStrong`, `passwordStrongSalt`, `passwordForceReset`, `status`, `canLogin`, `gibbonRoleIDPrimary`, `gibbonRoleIDAll`) VALUES
('Ms. ', 'TestUser', 'Admin', 'Admin', 'Admin TestUser', '', 'testingadmin', '015261d879c7fc2789d19b9193d189364baac34a98561fa205cd5f37b313cdb0', '/aBcEHKLnNpPrsStTUyz47', 'N', 'Full', 'Y', 001, '001,002,003,004,006') ON DUPLICATE KEY UPDATE lastTimestamp=NOW(), `passwordStrong`=VALUES(passwordStrong), `passwordStrongSalt`=VALUES(passwordStrongSalt), failCount=0;

INSERT INTO `gibbonStaff` (`gibbonPersonID`, `type`, `jobTitle`) VALUES ((SELECT `gibbonPersonID` FROM `gibbonPerson` WHERE `username`='testingadmin' LIMIT 1), 'Support', 'Test Admin') ON DUPLICATE KEY UPDATE `jobTitle`='Test Admin';

--- User: testingteacher     m86GVNLH7DbV
INSERT INTO `gibbonPerson` (`title`, `surname`, `firstName`, `preferredName`, `officialName`, `nameInCharacters`, `username`, `passwordStrong`, `passwordStrongSalt`, `passwordForceReset`, `status`, `canLogin`, `gibbonRoleIDPrimary`, `gibbonRoleIDAll`) VALUES
('Mr. ', 'TestUser', 'Teacher', 'Teacher', 'Teacher TestUser Admin', '', 'testingteacher', '3ea8c6a760d223038a96c900994410950057390a0e4a48a39e760d50cab68040', '.aBdegGhlNoqRxzZ012369', 'N', 'Full', 'Y', 002, '002') ON DUPLICATE KEY UPDATE lastTimestamp=NOW(), `passwordStrong`=VALUES(passwordStrong), `passwordStrongSalt`=VALUES(passwordStrongSalt), failCount=0;

--- User: testingstudent     WKLm9ELHLJL5
INSERT INTO `gibbonPerson` (`title`, `surname`, `firstName`, `preferredName`, `officialName`, `nameInCharacters`, `username`, `passwordStrong`, `passwordStrongSalt`, `passwordForceReset`, `status`, `canLogin`, `gibbonRoleIDPrimary`, `gibbonRoleIDAll`) VALUES
('Ms. ', 'TestUser', 'Student', 'Student', 'Student TestUser', '', 'testingstudent', '90ad15af1a18f0fcf9192997c93dda3731fa6d379bd25005711b659470fe0243', './aefFGhHIPrRTuvVwxz47', 'N', 'Full', 'Y', 003, '003') ON DUPLICATE KEY UPDATE lastTimestamp=NOW(), `passwordStrong`=VALUES(passwordStrong), `passwordStrongSalt`=VALUES(passwordStrongSalt), failCount=0;

--- User: testingparent      UVSf5t7epNa7
INSERT INTO `gibbonPerson` (`title`, `surname`, `firstName`, `preferredName`, `officialName`, `nameInCharacters`, `username`, `passwordStrong`, `passwordStrongSalt`, `passwordForceReset`, `status`, `canLogin`, `gibbonRoleIDPrimary`, `gibbonRoleIDAll`) VALUES
('Ms. ', 'TestUser', 'Parent', 'Parent', 'Parent TestUser', '', 'testingparent', '5562ca7b5775ed5678c3523035ec347da2b7e26b0f5bb5ad1d4ac6a9af0274d6', '/bceFHiIKmnOQRstVwxXZ2', 'N', 'Full', 'Y', 004, '004') ON DUPLICATE KEY UPDATE lastTimestamp=NOW(), `passwordStrong`=VALUES(passwordStrong), `passwordStrongSalt`=VALUES(passwordStrongSalt), failCount=0;

--- User: testingsupport     84BNQAQfNyKa
INSERT INTO `gibbonPerson` (`title`, `surname`, `firstName`, `preferredName`, `officialName`, `nameInCharacters`, `username`, `passwordStrong`, `passwordStrongSalt`, `passwordForceReset`, `status`, `canLogin`, `gibbonRoleIDPrimary`, `gibbonRoleIDAll`) VALUES
('Ms. ', 'TestUser', 'Support', 'Support', 'Support TestUser', '', 'testingsupport', 'bd0688e5ca1c86f1f03417556ed53b9b1deed66bd4a0e75f660efb0ba0cb4671', 'aCdHikKlnpPqRstvXyY026', 'N', 'Full', 'Y', 006, '006') ON DUPLICATE KEY UPDATE lastTimestamp=NOW(), `passwordStrong`=VALUES(passwordStrong), `passwordStrongSalt`=VALUES(passwordStrongSalt), failCount=0;

--- Create a Testing Family, add testingparent and testingstudent
--- Uses WHERE NOT EXISTS for on-the-fly uniqueness check with no unique keys
INSERT INTO `gibbonFamily` (`name`, `nameAddress`, `homeAddress`, `homeAddressDistrict`, `homeAddressCountry`, `status`, `languageHomePrimary`, `languageHomeSecondary`, `familySync`) 
SELECT * FROM (SELECT 'Testing Family' as `0`, '123 ' as `1`, '123 Fictitious Lane' as `2`, 'Nowhere' as `3`, 'Antarctica' as `4`, 'Married' as `5`, 'English' as `6`, 'Danish' as `7`, 'TESTINGFAMILY' as `8`) AS uniqueCheck
WHERE NOT EXISTS (
    SELECT gibbonFamilyID FROM gibbonFamily WHERE `name`='Testing Family' AND `familySync`='TESTINGFAMILY'
) LIMIT 1;

INSERT INTO `gibbonFamilyAdult` (`gibbonFamilyID`, `gibbonPersonID`, `comment`, `childDataAccess`, `contactPriority`, `contactCall`, `contactSMS`, `contactEmail`, `contactMail`) 
SELECT * FROM (SELECT (SELECT gibbonFamilyID FROM gibbonFamily WHERE `name`='Testing Family' AND `familySync`='TESTINGFAMILY') as `0`, (SELECT gibbonPersonID FROM gibbonPerson WHERE username='testingparent') as `1`, '' as `2`, 'Y' as `3`, '1' as `4`, 'Y' as `5`, 'Y' as `6`, 'Y' as `7`, 'Y' as `8`) AS uniqueCheck
WHERE NOT EXISTS (
    SELECT gibbonFamilyAdultID FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) WHERE gibbonFamily.`name`='Testing Family' AND gibbonFamily.`familySync`='TESTINGFAMILY'
) LIMIT 1;

INSERT INTO `gibbonFamilyChild` (`gibbonFamilyID`, `gibbonPersonID`, `comment`) 
SELECT * FROM (SELECT (SELECT gibbonFamilyID FROM gibbonFamily WHERE `name`='Testing Family' AND `familySync`='TESTINGFAMILY') as `0`, (SELECT gibbonPersonID FROM gibbonPerson WHERE username='testingstudent') as `1`, '' as `2`)  AS uniqueCheck
WHERE NOT EXISTS (
    SELECT gibbonFamilyChildID FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonFamily.`name`='Testing Family' AND gibbonFamily.`familySync`='TESTINGFAMILY'
) LIMIT 1;

INSERT INTO `gibbonFamilyRelationship` (`gibbonFamilyID`, `gibbonPersonID1`, `gibbonPersonID2`, `relationship`) 
SELECT * FROM (SELECT (SELECT gibbonFamilyID FROM gibbonFamily WHERE `name`='Testing Family' AND `familySync`='TESTINGFAMILY') as `0`, (SELECT gibbonPersonID FROM gibbonPerson WHERE username='testingparent') as `1`, (SELECT gibbonPersonID FROM gibbonPerson WHERE username='testingstudent') as `2`, 'Mother' as `3`)  AS uniqueCheck
WHERE NOT EXISTS (
    SELECT gibbonFamilyRelationshipID FROM gibbonFamilyRelationship JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyRelationship.gibbonFamilyID) WHERE gibbonFamily.`name`='Testing Family' AND gibbonFamily.`familySync`='TESTINGFAMILY'
) LIMIT 1;

--- Create permissions to access the old application form system
INSERT IGNORE INTO `gibbonPermission` (`gibbonRoleID`, `gibbonActionID`) VALUES
(001, 0000074),
(001, 0000001),
(001, 0000078),
(001, 0000858),
(002, 0000074),
(004, 0000074),
(006, 0000074);
