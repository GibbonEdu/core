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
    `parentConsent` ENUM('Y','N') NOT NULL DEFAULT 'N',
    `parentConsultNotes` TEXT NULL,
    `status` ENUM(
        'Referral',
        'Form Tutor Review',
        'Eligibility Assessment',
        'Intervention Required',
        'Support Plan Active',
        'Ready for Evaluation',
        'Resolved',
        'Referred for IEP'
    ) NOT NULL DEFAULT 'Referral',
    `formTutorDecision` ENUM('Pending','Resolvable','Try Interventions','Try IEP') NOT NULL DEFAULT 'Pending',
    `formTutorNotes` TEXT NULL,
    `goals` TEXT NULL,
    `strategies` TEXT NULL,
    `resources` TEXT NULL,
    `targetDate` DATE NULL,
    `gibbonPersonIDStaff` INT(10) UNSIGNED ZEROFILL NULL,
    `dateStart` DATE NULL,
    `dateEnd` DATE NULL,
    `outcome` ENUM('Goals Achieved','Partial Progress','No Progress','Refer for IEP') NULL,
    `dateResolved` DATE NULL,
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

$moduleTables[] = "CREATE TABLE `gibbonINInterventionNote` (
    `gibbonINInterventionNoteID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `note` TEXT NOT NULL,
    `date` DATE NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionNoteID`),
    INDEX(`gibbonINInterventionID`)
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

// New table for intervention-based eligibility assessments
$moduleTables[] = "CREATE TABLE `gibbonINInterventionEligibilityAssessment` (
    `gibbonINInterventionEligibilityAssessmentID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `status` ENUM('In Progress','Complete') NOT NULL DEFAULT 'In Progress',
    `eligibilityDecision` ENUM('Pending','Eligible for IEP','Needs Intervention') NOT NULL DEFAULT 'Pending',
    `notes` TEXT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionEligibilityAssessmentID`),
    UNIQUE KEY `intervention` (`gibbonINInterventionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New table for intervention-based eligibility assessment contributors
$moduleTables[] = "CREATE TABLE `gibbonINInterventionEligibilityContributor` (
    `gibbonINInterventionEligibilityContributorID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionEligibilityAssessmentID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDContributor` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonINEligibilityAssessmentTypeID` INT(4) UNSIGNED ZEROFILL NULL,
    `notes` TEXT NULL,
    `status` ENUM('Pending','Complete') NOT NULL DEFAULT 'Pending',
    `contribution` TEXT NULL,
    `recommendation` ENUM('Pending','Eligible for IEP','Needs Intervention') NOT NULL DEFAULT 'Pending',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `timestampModified` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINInterventionEligibilityContributorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New table for assessment fields
$moduleTables[] = "CREATE TABLE `gibbonINEligibilityAssessmentField` (
    `gibbonINEligibilityAssessmentFieldID` INT(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINEligibilityAssessmentTypeID` INT(4) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `sequenceNumber` INT(3) NOT NULL,
    `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`gibbonINEligibilityAssessmentFieldID`),
    INDEX (`gibbonINEligibilityAssessmentTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New table for field ratings
$moduleTables[] = "CREATE TABLE `gibbonINEligibilityAssessmentFieldRating` (
    `gibbonINEligibilityAssessmentFieldRatingID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionEligibilityContributorID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonINEligibilityAssessmentFieldID` INT(6) UNSIGNED ZEROFILL NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `comment` TEXT NULL,
    PRIMARY KEY (`gibbonINEligibilityAssessmentFieldRatingID`),
    UNIQUE KEY `field_rating` (`gibbonINInterventionEligibilityContributorID`, `gibbonINEligibilityAssessmentFieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New table for assessment type subfields
$moduleTables[] = "CREATE TABLE `gibbonINEligibilityAssessmentSubfield` (
    `gibbonINEligibilityAssessmentSubfieldID` INT(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINEligibilityAssessmentTypeID` INT(4) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `sequenceNumber` INT(3) NOT NULL,
    `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`gibbonINEligibilityAssessmentSubfieldID`),
    INDEX(`gibbonINEligibilityAssessmentTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New table for contributor assessment ratings
$moduleTables[] = "CREATE TABLE `gibbonINInterventionEligibilityContributorRating` (
    `gibbonINInterventionEligibilityContributorRatingID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionEligibilityContributorID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonINEligibilityAssessmentSubfieldID` INT(6) UNSIGNED ZEROFILL NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `comment` TEXT NULL,
    PRIMARY KEY (`gibbonINInterventionEligibilityContributorRatingID`),
    UNIQUE KEY `contributor_subfield` (`gibbonINInterventionEligibilityContributorID`, `gibbonINEligibilityAssessmentSubfieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New tables for multiple support plans
$moduleTables[] = "CREATE TABLE `gibbonINSupportPlan` (
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINInterventionID` INT(12) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `goals` TEXT NOT NULL,
    `strategies` TEXT NOT NULL,
    `resources` TEXT NULL,
    `targetDate` DATE NOT NULL,
    `gibbonPersonIDStaff` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `dateStart` DATE NULL,
    `dateEnd` DATE NULL,
    `status` ENUM('Draft','Active','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
    `outcome` ENUM('Goals Achieved','Partial Progress','No Progress','Refer for IEP') NULL,
    `outcomeNotes` TEXT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `timestampModified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanID`),
    INDEX(`gibbonINInterventionID`),
    INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINSupportPlanNote` (
    `gibbonINSupportPlanNoteID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `note` TEXT NOT NULL,
    `date` DATE NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanNoteID`),
    INDEX(`gibbonINSupportPlanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// New tables for support plan contributors and progress tracking
$moduleTables[] = "CREATE TABLE `gibbonINSupportPlanContributor` (
    `gibbonINSupportPlanContributorID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `role` VARCHAR(100) NULL,
    `canEdit` ENUM('Y','N') NOT NULL DEFAULT 'N',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanContributorID`),
    UNIQUE KEY `contributor` (`gibbonINSupportPlanID`, `gibbonPersonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `gibbonINSupportPlanProgress` (
    `gibbonINSupportPlanProgressID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `progressDate` DATE NOT NULL,
    `progress` TEXT NOT NULL,
    `status` ENUM('On Track','Concerns','Achieved') NOT NULL DEFAULT 'On Track',
    `nextSteps` TEXT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `timestampModified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanProgressID`),
    INDEX(`gibbonINSupportPlanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// Module Action Rows
$actionRows[] = [
    'name' => 'Manage Interventions_all', 
    'precedence' => '1',
    'category' => 'Interventions',
    'description' => 'View and manage all student interventions',
    'URLList' => 'interventions_manage.php,interventions_manage_add.php,interventions_manage_edit.php,interventions_manage_delete.php,interventions_manage_contributor_add.php,interventions_manage_contributor_delete.php,interventions_manage_contributor_edit.php,interventions_manage_strategy_add.php,interventions_manage_strategy_edit.php,interventions_manage_outcome_add.php,intervention_eligibility_edit.php,intervention_eligibility_editProcess.php,intervention_eligibility_contributor_add.php,intervention_eligibility_contributor_addProcess.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php,intervention_eligibility_contributor_delete.php,intervention_eligibility_contributor_deleteProcess.php,intervention_eligibility_contributor_add_type.php,intervention_eligibility_contributor_add_typeProcess.php,intervention_support_plan_view.php,intervention_support_plan_contributor_add.php,intervention_support_plan_contributor_addProcess.php,intervention_support_plan_contributor_delete.php,intervention_support_plan_contributor_deleteProcess.php,intervention_support_plan_progress_add.php,intervention_support_plan_progress_addProcess.php,intervention_support_plan_progress_edit.php,intervention_support_plan_progress_editProcess.php,intervention_support_plan_progress_delete.php,intervention_support_plan_progress_deleteProcess.php,intervention_support_plan_add.php,intervention_support_plan_addProcess.php,intervention_support_plan_edit.php,intervention_support_plan_editProcess.php',
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
    'URLList' => 'interventions_manage.php,interventions_manage_add.php,interventions_manage_edit.php,interventions_manage_delete.php,interventions_manage_contributor_add.php,interventions_manage_contributor_delete.php,interventions_manage_contributor_edit.php,interventions_manage_strategy_add.php,interventions_manage_strategy_edit.php,interventions_manage_outcome_add.php,intervention_eligibility_edit.php,intervention_eligibility_editProcess.php,intervention_eligibility_contributor_add.php,intervention_eligibility_contributor_addProcess.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php,intervention_eligibility_contributor_delete.php,intervention_eligibility_contributor_deleteProcess.php,intervention_eligibility_contributor_add_type.php,intervention_eligibility_contributor_add_typeProcess.php,intervention_support_plan_view.php,intervention_support_plan_contributor_add.php,intervention_support_plan_contributor_addProcess.php,intervention_support_plan_contributor_delete.php,intervention_support_plan_contributor_deleteProcess.php,intervention_support_plan_progress_add.php,intervention_support_plan_progress_addProcess.php,intervention_support_plan_progress_edit.php,intervention_support_plan_progress_editProcess.php,intervention_support_plan_progress_delete.php,intervention_support_plan_progress_deleteProcess.php,intervention_support_plan_add.php,intervention_support_plan_addProcess.php,intervention_support_plan_edit.php,intervention_support_plan_editProcess.php',
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
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

// $actionRows[] = [
//     'name' => 'Manage Eligibility Assessments_all',
//     'precedence' => '1',
//     'category' => 'Eligibility',
//     'description' => 'View and manage all eligibility assessments',
//     'URLList' => 'eligibility_manage.php,eligibility_edit.php,eligibility_delete.php,eligibility_contributor_add.php,eligibility_contributor_delete.php,eligibility_assessment_edit.php,intervention_eligibility_edit.php,intervention_eligibility_editProcess.php,intervention_eligibility_contributor_add.php,intervention_eligibility_contributor_addProcess.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php,intervention_eligibility_contributor_delete.php,intervention_eligibility_contributor_deleteProcess.php',
//     'entryURL' => 'eligibility_manage.php',
//     'entrySidebar' => 'Y',
//     'menuShow' => 'Y',
//     'defaultPermissionAdmin' => 'Y',
//     'defaultPermissionTeacher' => 'N',
//     'defaultPermissionStudent' => 'N',
//     'defaultPermissionParent' => 'N',
//     'defaultPermissionSupport' => 'N',
//     'categoryPermissionStaff' => 'Y',
//     'categoryPermissionStudent' => 'N',
//     'categoryPermissionParent' => 'N',
//     'categoryPermissionOther' => 'N'
// ];


$actionRows[] = [
    'name' => 'Manage Eligibility Assessments_my',
    'precedence' => '0',
    'category' => 'Eligibility',
    'description' => 'View and manage eligibility assessments you have created',
    'URLList' => 'eligibility_manage.php,eligibility_edit.php,eligibility_contributor_add.php,eligibility_assessment_edit.php,intervention_eligibility_edit.php,intervention_eligibility_editProcess.php,intervention_eligibility_contributor_add.php,intervention_eligibility_contributor_addProcess.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php,intervention_eligibility_contributor_delete.php,intervention_eligibility_contributor_deleteProcess.php,intervention_eligibility_contributor_add_type.php,intervention_eligibility_contributor_add_typeProcess.php',
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
    'URLList' => 'eligibility_assessment_edit.php,intervention_eligibility_edit.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php',
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
    'URLList' => 'eligibility_assessment_types_manage.php,eligibility_assessment_types_add.php,eligibility_assessment_types_edit.php,eligibility_assessment_types_delete.php,eligibility_assessment_subfield_add.php,eligibility_assessment_subfield_edit.php,eligibility_assessment_subfield_delete.php,eligibility_assessment_subfield_addProcess.php,eligibility_assessment_subfield_editProcess.php,eligibility_assessment_subfield_deleteProcess.php',
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

$actionRows[] = [
    'name' => 'Student Profile Hook',
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'Access to the Interventions button in student profiles',
    'URLList' => 'hook_studentProfile_interventionsButton.php',
    'entryURL' => 'hook_studentProfile_interventionsButton.php',
    'entrySidebar' => 'N',
    'menuShow' => 'N',
    'defaultPermissionAdmin' => 'Y',
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
    'name' => 'Contributor Dashboard', 
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'View all interventions and eligibility assessments you are contributing to',
    'URLList' => 'interventions_contributor_dashboard.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_editProcess.php,intervention_eligibility_contributor_add_type.php,intervention_eligibility_contributor_add_typeProcess.php',
    'entryURL' => 'interventions_contributor_dashboard.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'Y',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage Eligibility Assessments', 
    'precedence' => '0',
    'category' => 'Eligibility',
    'description' => 'Manage intervention-based eligibility assessments',
    'URLList' => 'intervention_eligibility_manage.php,intervention_eligibility_edit.php,intervention_eligibility_delete.php,intervention_eligibility_contributor_add.php,intervention_eligibility_contributor_edit.php,intervention_eligibility_contributor_delete.php',
    'entryURL' => 'intervention_eligibility_manage.php',
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
    'name' => 'Intervention Process',
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'Manage interventions through a structured multi-step process',
    'URLList' => 'intervention_process.php,intervention_process_phase1Process.php,intervention_process_phase2Process.php,intervention_process_phase3Process.php,intervention_process_phase4Process.php,intervention_process_phase5Process.php,intervention_support_plan_add.php,intervention_support_plan_addProcess.php',
    'entryURL' => 'intervention_process.php',
    'entrySidebar' => 'Y',
    'menuShow' => 'N',
    'defaultPermissionAdmin' => 'Y',
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
    'name' => 'Support Plan Contributor',
    'precedence' => '0',
    'category' => 'Interventions',
    'description' => 'View and contribute to support plans you are assigned to',
    'URLList' => 'intervention_support_plan_view.php,intervention_support_plan_progress_add.php,intervention_support_plan_progress_addProcess.php,intervention_support_plan_progress_edit.php,intervention_support_plan_progress_editProcess.php,intervention_support_plan_add.php,intervention_support_plan_addProcess.php',
    'entryURL' => 'intervention_support_plan_view.php',
    'entrySidebar' => 'N',
    'menuShow' => 'N',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'Y'
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

// Add default subfields for Academic assessment type
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Academic' LIMIT 1),
    'Reading Comprehension', 'Ability to understand and interpret written text', 1, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Academic' LIMIT 1),
    'Written Expression', 'Ability to communicate ideas in writing', 2, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Academic' LIMIT 1),
    'Mathematics', 'Ability to understand and apply mathematical concepts', 3, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Academic' LIMIT 1),
    'Attention to Task', 'Ability to focus on academic tasks', 4, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Academic' LIMIT 1),
    'Organization', 'Ability to organize materials and assignments', 5, 'Y');";

// Add default subfields for Behavioral assessment type
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Behavioral' LIMIT 1),
    'Classroom Behavior', 'Ability to follow classroom rules and expectations', 1, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Behavioral' LIMIT 1),
    'Peer Interactions', 'Ability to interact appropriately with peers', 2, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Behavioral' LIMIT 1),
    'Adult Interactions', 'Ability to interact appropriately with adults', 3, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Behavioral' LIMIT 1),
    'Impulse Control', 'Ability to control impulsive behaviors', 4, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Behavioral' LIMIT 1),
    'Transition Management', 'Ability to manage transitions between activities', 5, 'Y');";

// Add default subfields for Social-Emotional assessment type
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Social-Emotional' LIMIT 1),
    'Emotional Regulation', 'Ability to regulate emotions appropriately', 1, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Social-Emotional' LIMIT 1),
    'Social Skills', 'Ability to interact socially with peers', 2, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Social-Emotional' LIMIT 1),
    'Self-Awareness', 'Awareness of own emotions and behaviors', 3, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Social-Emotional' LIMIT 1),
    'Relationship Building', 'Ability to build and maintain relationships', 4, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Social-Emotional' LIMIT 1),
    'Coping Skills', 'Ability to cope with stress and challenges', 5, 'Y');";

// Add default subfields for Physical assessment type
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Physical' LIMIT 1),
    'Fine Motor Skills', 'Control and coordination of small muscle movements', 1, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Physical' LIMIT 1),
    'Gross Motor Skills', 'Control and coordination of large muscle movements', 2, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Physical' LIMIT 1),
    'Balance and Coordination', 'Ability to maintain balance and coordinate movements', 3, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Physical' LIMIT 1),
    'Sensory Processing', 'Ability to process sensory information appropriately', 4, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Physical' LIMIT 1),
    'Physical Endurance', 'Ability to sustain physical activity', 5, 'Y');";

// Add default subfields for Communication assessment type
$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Communication' LIMIT 1),
    'Expressive Language', 'Ability to express thoughts and ideas verbally', 1, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Communication' LIMIT 1),
    'Receptive Language', 'Ability to understand verbal communication', 2, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Communication' LIMIT 1),
    'Articulation', 'Clarity of speech and pronunciation', 3, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Communication' LIMIT 1),
    'Pragmatic Language', 'Ability to use language in social contexts', 4, 'Y');";

$gibbonSetting[] = "INSERT INTO `gibbonINEligibilityAssessmentSubfield` 
    (`gibbonINEligibilityAssessmentTypeID`, `name`, `description`, `sequenceNumber`, `active`) 
    VALUES 
    ((SELECT gibbonINEligibilityAssessmentTypeID FROM gibbonINEligibilityAssessmentType WHERE name='Communication' LIMIT 1),
    'Non-Verbal Communication', 'Use of gestures, facial expressions, and body language', 5, 'Y');";

// Module Hooks
$array = array();
$array['sourceModuleName'] = 'Interventions';
$array['sourceModuleAction'] = 'Student Profile Hook';
$array['sourceModuleInclude'] = 'hook_studentProfile_interventionsButton.php';
$hooks[] = "INSERT INTO `gibbonHook` (`name`, `type`, `options`, `gibbonModuleID`) VALUES ('Interventions', 'Student Profile', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='Interventions'));";
