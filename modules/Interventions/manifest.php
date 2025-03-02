<?php

// Basic Variables
$name = 'Interventions';
$description = 'Manage and track student interventions before formal IEP processes';
$entryURL = 'interventions_manage.php';
$type = 'Additional';
$category = 'Learn';
$version = '1.0.00';
$author = 'Gibbon Foundation';
$url = 'https://gibbonedu.org';

// Module Tables
$moduleTables[] = "CREATE TABLE `gibbonINIntervention` (
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDFormTutor` INT(10) UNSIGNED ZEROFILL NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('Referral','Form Tutor Review','Intervention','IEP','Resolved','Completed') NOT NULL DEFAULT 'Referral',
    `formTutorDecision` ENUM('Pending','Resolvable','Try Interventions','Try IEP') NOT NULL DEFAULT 'Pending',
    `formTutorNotes` TEXT NULL,
    `outcomeNotes` TEXT NULL,
    `outcomeDecision` ENUM('Pending','Success','Needs IEP') NULL DEFAULT 'Pending',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `timestampModified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionID`),
    INDEX(`gibbonPersonIDStudent`),
    INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINInterventionContributor` (
    `gibbonINInterventionContributorID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDContributor` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `type` ENUM('Teacher','Support Staff','External Agency','Other') NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionContributorID`),
    UNIQUE KEY `contributor` (`gibbonINInterventionID`, `gibbonPersonIDContributor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINInterventionStrategy` (
    `gibbonINInterventionStrategyID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `targetDate` DATE NOT NULL,
    `status` ENUM('Planned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionStrategyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINInterventionOutcome` (
    `gibbonINInterventionOutcomeID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionStrategyID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `outcome` TEXT NOT NULL,
    `evidence` TEXT NULL,
    `successful` ENUM('Yes','No','Partial') NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionOutcomeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINReferral` (
    `gibbonINReferralID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('Eligibility Assessment','Eligibility Complete') NOT NULL DEFAULT 'Eligibility Assessment',
    `eligibilityDecision` ENUM('Pending','Eligible','Not Eligible') NOT NULL DEFAULT 'Pending',
    `eligibilityNotes` TEXT NULL,
    `dateCreated` DATE NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINReferralID`),
    INDEX(`gibbonPersonIDStudent`),
    INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINEligibilityAssessment` (
    `gibbonINEligibilityAssessmentID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINReferralID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDContributor` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `assessment` TEXT NULL,
    `recommendation` ENUM('Pending','Eligible','Not Eligible') NOT NULL DEFAULT 'Pending',
    `dateCompleted` DATE NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINEligibilityAssessmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINEligibilityAssessmentType` (
    `gibbonINEligibilityAssessmentTypeID` INT(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`gibbonINEligibilityAssessmentTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// Module Action Rows
$actionRows[] = [
    'name' => 'Manage Interventions_all', 
    'precedence' => '1',
    'category' => 'Interventions',
    'description' => 'View and manage all student interventions',
    'URLList' => 'interventions_manage.php,interventions_manage_add.php,interventions_manage_edit.php,interventions_manage_delete.php,interventions_manage_contributor_add.php,interventions_manage_contributor_delete.php,interventions_manage_strategy_add.php,interventions_manage_strategy_edit.php,interventions_manage_outcome_add.php',
    'entryURL' => 'interventions_manage.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'N',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage Interventions_my',
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'View and manage interventions you have created',
    'URLList' => 'interventions_manage.php,interventions_manage_add.php,interventions_manage_edit.php,interventions_manage_delete.php,interventions_manage_contributor_add.php,interventions_manage_contributor_delete.php,interventions_manage_strategy_add.php,interventions_manage_strategy_edit.php,interventions_manage_outcome_add.php',
    'entryURL' => 'interventions_manage.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'N',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Submit Referral',
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'Submit a referral for a student who may need additional support',
    'URLList' => 'interventions_submit.php',
    'entryURL' => 'interventions_submit.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'N',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage Eligibility Assessments_all',
    'precedence' => '1',
    'category' => 'Eligibility',
    'description' => 'View and manage all eligibility assessments',
    'URLList' => 'eligibility_manage.php,eligibility_edit.php,eligibility_delete.php,eligibility_contributor_add.php,eligibility_contributor_delete.php,eligibility_assessment_edit.php',
    'entryURL' => 'eligibility_manage.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'N',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage Eligibility Assessments_my',
    'precedence' => '0',
    'category' => 'Eligibility',
    'description' => 'View and manage eligibility assessments you have created',
    'URLList' => 'eligibility_manage.php,eligibility_edit.php,eligibility_contributor_add.php,eligibility_assessment_edit.php',
    'entryURL' => 'eligibility_manage.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'N',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Complete Eligibility Assessment',
    'precedence' => '0',
    'category' => 'Eligibility',
    'description' => 'Complete assigned eligibility assessments',
    'URLList' => 'eligibility_assessment_edit.php',
    'entryURL' => 'eligibility_assessment_edit.php',
    'entrySidebar' => 'N',
    'menuShow' => 'N',
    'defaultPermissionAdmin' => 'N',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'Y'
];

$actionRows[] = [
    'name' => 'Manage Assessment Types',
    'precedence' => '0',
    'category' => 'Eligibility',
    'description' => 'Manage the types of eligibility assessments',
    'URLList' => 'eligibility_assessment_types_manage.php,eligibility_assessment_types_add.php,eligibility_assessment_types_edit.php,eligibility_assessment_types_delete.php',
    'entryURL' => 'eligibility_assessment_types_manage.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'N',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

// Module Settings
$gibbonSetting[] = "INSERT INTO `gibbonSetting` 
    (`scope`, `name`, `nameDisplay`, `description`, `value`) 
    VALUES 
    ('Interventions', 'requireFormTutorReview', 'Require Form Tutor Review', 'Require form tutor review before intervention implementation.', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonSetting` 
    (`scope`, `name`, `nameDisplay`, `description`, `value`) 
    VALUES 
    ('Interventions', 'notifyFormTutor', 'Notify Form Tutor', 'Automatically notify form tutors of new intervention referrals.', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonSetting` 
    (`scope`, `name`, `nameDisplay`, `description`, `value`) 
    VALUES 
    ('Interventions', 'requireEligibilityForIEP', 'Require Eligibility for IEP', 'Require eligibility assessment before creating an IEP.', 'Y');";

// Add default eligibility assessment types - split into individual statements
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentType` 
    (`name`, `description`, `active`) 
    VALUES 
    ('Academic', 'Assessment of academic performance and needs', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentType` 
    (`name`, `description`, `active`) 
    VALUES 
    ('Behavioral', 'Assessment of behavioral patterns and needs', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentType` 
    (`name`, `description`, `active`) 
    VALUES 
    ('Social-Emotional', 'Assessment of social and emotional development', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentType` 
    (`name`, `description`, `active`) 
    VALUES 
    ('Physical', 'Assessment of physical abilities and needs', 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentType` 
    (`name`, `description`, `active`) 
    VALUES 
    ('Communication', 'Assessment of communication skills and needs', 'Y');";

// Module Hooks - Convert to SQL statements
$hooks[] = "INSERT INTO `gibbonHook` 
    (`name`, `type`, `options`, `gibbonModuleID`) 
    VALUES 
    ('Interventions', 'Student Profile', 'a:3:{s:16:\"sourceModuleName\";s:13:\"Interventions\";s:18:\"sourceModuleAction\";s:36:\"hook_studentProfile_interventionsButton.php\";s:19:\"sourceModuleInclude\";s:36:\"hook_studentProfile_interventionsButton.php\";}', 
    (SELECT gibbonModuleID FROM gibbonModule WHERE name='Interventions'));";
