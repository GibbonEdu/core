-- Add type field to support plans to differentiate between standard plans and IEPs
ALTER TABLE `gibbonINSupportPlan` 
ADD COLUMN `type` ENUM('Standard', 'IEP', 'Behavior', 'Academic', 'Other') NOT NULL DEFAULT 'Standard' AFTER `name`;

-- Add accommodations field for IEP-specific information
ALTER TABLE `gibbonINSupportPlan` 
ADD COLUMN `accommodations` TEXT NULL AFTER `resources`;

-- Add modifications field for IEP-specific information
ALTER TABLE `gibbonINSupportPlan` 
ADD COLUMN `modifications` TEXT NULL AFTER `accommodations`;

-- Add specialized services field for IEP-specific information
ALTER TABLE `gibbonINSupportPlan` 
ADD COLUMN `specializedServices` TEXT NULL AFTER `modifications`;

-- Create table for support plan contributors
CREATE TABLE `gibbonINSupportPlanContributor` (
    `gibbonINSupportPlanContributorID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDContributor` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `canEdit` ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanContributorID`),
    UNIQUE KEY `plan_contributor` (`gibbonINSupportPlanID`, `gibbonPersonIDContributor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create table for tracking support plan history/changes
CREATE TABLE `gibbonINSupportPlanHistory` (
    `gibbonINSupportPlanHistoryID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `action` ENUM('Create', 'Edit', 'Status Change', 'Delete') NOT NULL,
    `fieldName` VARCHAR(50) NULL,
    `oldValue` TEXT NULL,
    `newValue` TEXT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanHistoryID`),
    INDEX(`gibbonINSupportPlanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create table for progress tracking across report cycles
CREATE TABLE `gibbonINSupportPlanProgress` (
    `gibbonINSupportPlanProgressID` INT(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonINSupportPlanID` INT(14) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `reportingCycle` VARCHAR(50) NOT NULL,
    `progressSummary` TEXT NOT NULL,
    `goalProgress` TEXT NULL,
    `nextSteps` TEXT NULL,
    `date` DATE NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonINSupportPlanProgressID`),
    INDEX(`gibbonINSupportPlanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
