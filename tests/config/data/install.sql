--
-- Default Data for Travis CI
--

INSERT INTO gibbonPerson SET gibbonPersonID=1, title='Mr.', surname='Mc Test', firstName='Test', preferredName='Test', officialName='Test McTest', username='admin', password='travis', passwordStrong='', passwordStrongSalt='', status='Full', canLogin='Y', passwordForceReset='N', gibbonRoleIDPrimary='001', gibbonRoleIDAll='001', email='';

UPDATE gibbonSetting SET value='http://127.0.0.1' WHERE scope='System' AND name='absoluteURL';
UPDATE gibbonSetting SET value='/home/travis' WHERE scope='System' AND name='absolutePath';
UPDATE gibbonSetting SET value='Travis CI' WHERE scope='System' AND name='systemName';
UPDATE gibbonSetting SET value='Travis CI' WHERE scope='System' AND name='organisationName';
UPDATE gibbonSetting SET value='TCI' WHERE scope='System' AND name='organisationNameShort';
UPDATE gibbonSetting SET value='' WHERE scope='System' AND name='organisationEmail';
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