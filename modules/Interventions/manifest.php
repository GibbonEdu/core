<?php


ALTER TABLE `gibbonINInvestigation` 
MODIFY COLUMN `status` enum('Referral','Resolved','Intervention','Investigation','Investigation Complete') DEFAULT NULL;

 

SELECT gibbonRoleID, (SELECT gibbonActionID FROM gibbonAction WHERE name='Manage Interventions_my' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Individual Needs'))FROM gibbonRole WHERE name='Teacher';

INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID) SELECT gibbonRoleID, (SELECT gibbonActionID FROM gibbonAction WHERE name='Manage Interventions_my' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Individual Needs')) FROM gibbonRole WHERE name='Support Staff';

-- Add eligibility management actions to Individual Needs module
INSERT INTO gibbonAction (gibbonModuleID, name, precedence, category, description, URLList, entryURL, defaultPermissionAdmin, defaultPermissionTeacher, defaultPermissionStudent, defaultPermissionParent, defaultPermissionSupport, categoryPermissionStaff, categoryPermissionStudent, categoryPermissionParent, categoryPermissionOther) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Individual Needs'), 'Manage Eligibility Assessments', 0, 'Eligibility', 'Allows users to manage eligibility assessments for students.', 'eligibility_manage.php,eligibility_edit.php,eligibility_contributor_add.php,eligibility_assessment_edit.php', 'eligibility_manage.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');

INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID) VALUES (001, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Individual Needs' AND gibbonAction.name='Manage Eligibility Assessments'));

INSERT INTO gibbonAction (gibbonModuleID, name, precedence, category, description, URLList, entryURL, defaultPermissionAdmin, defaultPermissionTeacher, defaultPermissionStudent, defaultPermissionParent, defaultPermissionSupport, categoryPermissionStaff, categoryPermissionStudent, categoryPermissionParent, categoryPermissionOther) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Individual Needs'), 'Manage Eligibility Assessments_my', 1, 'Eligibility', 'Allows users to manage their own eligibility assessments for students.', 'eligibility_manage_my.php,eligibility_edit_my.php,eligibility_contributor_add_my.php,eligibility_assessment_edit_my.php', 'eligibility_manage_my.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');

INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID) VALUES (001, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Individual Needs' AND gibbonAction.name='Manage Eligibility Assessments_my'));

-- Individual Needs Module Updates
ALTER TABLE `gibbonINInvestigation` MODIFY COLUMN `status` enum('Referral','Resolved','Intervention','Investigation','Investigation Complete','Eligibility Assessment','Eligibility Complete') DEFAULT NULL;

-- Add new fields to track eligibility assessment status
ALTER TABLE `gibbonINInvestigation` 
ADD COLUMN `eligibilityDecision` enum('Pending','Eligible','Not Eligible') DEFAULT 'Pending' AFTER `resolutionDetails`,
ADD COLUMN `eligibilityNotes` text AFTER `eligibilityDecision`;

-- Create a new table for eligibility assessment types
CREATE TABLE `gibbonINEligibilityAssessmentType` (
  `gibbonINEligibilityAssessmentTypeID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `sequenceNumber` int(3) NOT NULL,
  PRIMARY KEY (`gibbonINEligibilityAssessmentTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create a new table for eligibility assessment results
CREATE TABLE `gibbonINEligibilityAssessment` (
  `gibbonINEligibilityAssessmentID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `gibbonINInvestigationID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonINEligibilityAssessmentTypeID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDAssessor` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date NOT NULL,
  `result` enum('Pass','Fail','Inconclusive') NOT NULL,
  `notes` text,
  `documentPath` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gibbonINEligibilityAssessmentID`),
  KEY `gibbonINInvestigationID` (`gibbonINInvestigationID`),
  KEY `gibbonINEligibilityAssessmentTypeID` (`gibbonINEligibilityAssessmentTypeID`),
  KEY `gibbonPersonIDAssessor` (`gibbonPersonIDAssessor`),
  CONSTRAINT `gibbonINEligibilityAssessment_ibfk_1` FOREIGN KEY (`gibbonINInvestigationID`) REFERENCES `gibbonINInvestigation` (`gibbonINInvestigationID`) ON DELETE CASCADE,
  CONSTRAINT `gibbonINEligibilityAssessment_ibfk_2` FOREIGN KEY (`gibbonINEligibilityAssessmentTypeID`) REFERENCES `gibbonINEligibilityAssessmentType` (`gibbonINEligibilityAssessmentTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert default assessment types
INSERT INTO `gibbonINEligibilityAssessmentType` (`name`, `description`, `active`, `sequenceNumber`) VALUES
('Academic Assessment', 'Assessment of academic performance and capabilities', 'Y', 1),
('Behavioral Assessment', 'Assessment of behavioral patterns and challenges', 'Y', 2),
('Psychological Assessment', 'Assessment by school psychologist or counselor', 'Y', 3),
('Medical Assessment', 'Assessment of medical conditions affecting learning', 'Y', 4),
('Speech/Language Assessment', 'Assessment of speech and language capabilities', 'Y', 5);

-- Create Intervention tables
CREATE TABLE `gibbonINIntervention` (
  `gibbonINInterventionID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `gibbonINInvestigationID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `strategies` text NOT NULL,
  `targetDate` date NOT NULL,
  `status` enum('Pending','In Progress','Completed','Discontinued') NOT NULL DEFAULT 'Pending',
  `parentConsent` enum('Not Requested','Consent Given','Consent Denied','Awaiting Response') NOT NULL DEFAULT 'Not Requested',
  `parentConsentDate` date DEFAULT NULL,
  `gibbonPersonIDConsent` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `consentNotes` text,
  `consentDocumentPath` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gibbonINInterventionID`),
  KEY `gibbonINInvestigationID` (`gibbonINInvestigationID`),
  KEY `gibbonPersonIDCreator` (`gibbonPersonIDCreator`),
  CONSTRAINT `gibbonINIntervention_ibfk_1` FOREIGN KEY (`gibbonINInvestigationID`) REFERENCES `gibbonINInvestigation` (`gibbonINInvestigationID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 
CREATE TABLE `gibbonINInterventionUpdate` (
  `gibbonINInterventionUpdateID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `gibbonINInterventionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `comment` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `progress` enum('Not Started','Just Started','Progressing','Breakthrough','Setback','No Progress') NOT NULL,
  PRIMARY KEY (`gibbonINInterventionUpdateID`),
  KEY `gibbonINInterventionID` (`gibbonINInterventionID`),
  KEY `gibbonPersonID` (`gibbonPersonID`),
  CONSTRAINT `gibbonINInterventionUpdate_ibfk_1` FOREIGN KEY (`gibbonINInterventionID`) REFERENCES `gibbonINIntervention` (`gibbonINInterventionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `gibbonINInterventionContributor` (
  `gibbonINInterventionContributorID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `gibbonINInterventionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gibbonINInterventionContributorID`),
  KEY `gibbonINInterventionID` (`gibbonINInterventionID`),
  KEY `gibbonPersonID` (`gibbonPersonID`),
  CONSTRAINT `gibbonINInterventionContributor_ibfk_1` FOREIGN KEY (`gibbonINInterventionID`) REFERENCES `gibbonINIntervention` (`gibbonINInterventionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Add Intervention Management actions to the gibbonAction table
INSERT INTO gibbonAction (gibbonModuleID, name, precedence, category, description, URLList, entryURL, defaultPermissionAdmin, defaultPermissionTeacher, defaultPermissionStudent, defaultPermissionParent, defaultPermissionSupport, categoryPermissionStaff, categoryPermissionStudent, categoryPermissionParent, categoryPermissionOther) 
SELECT gibbonModuleID, 'Manage Interventions_all', 0, 'Interventions', 'Allows users to manage all interventions', 'interventions_manage.php,interventions_manage_edit.php,interventions_update.php,interventions_manage_contributor_add.php,interventions_manage_contributor_edit.php,interventions_manage_contributor_delete.php,interventions_update_edit.php,interventions_update_delete.php', 'interventions_manage.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N' 
FROM gibbonModule WHERE name='Individual Needs';

INSERT IGNORE INTO gibbonAction (gibbonModuleID, name, precedence, category, description, URLList, entryURL, defaultPermissionAdmin, defaultPermissionTeacher, defaultPermissionStudent, defaultPermissionParent, defaultPermissionSupport, categoryPermissionStaff, categoryPermissionStudent, categoryPermissionParent, categoryPermissionOther) 
SELECT gibbonModuleID, 'Manage Interventions_my', 0, 'Interventions', 'Allows users to manage interventions they have created or are contributing to', 'interventions_manage.php,interventions_manage_edit.php,interventions_update.php,interventions_manage_contributor_add.php,interventions_manage_contributor_edit.php,interventions_manage_contributor_delete.php,interventions_update_edit.php,interventions_update_delete.php', 'interventions_manage.php', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N' 
FROM gibbonModule WHERE name='Individual Needs';

-- Add permissions for the new actions
INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID)
SELECT gibbonRoleID, (SELECT gibbonActionID FROM gibbonAction WHERE name='Manage Interventions_all' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Individual Needs'))
FROM gibbonRole WHERE name='Administrator';

INSERT INTO gibbonPermission (gibbonRoleID eligibility assessments that they have created.', 'eligibility_manage.php,eligibility_edit.php,eligibility_contributor_add.php,eligibility_assessment_edit.php', 'eligibility_manage.php', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');
INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID) VALUES (002, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Individual Needs' AND gibbonAction.name='Manage Eligibility Assessments_my'));