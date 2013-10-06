<?
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name="IB PYP" ;
$description="A module to facilitate schools to run the IB Primary Years Program." ;
$entryURL="index.php" ;
$type="Additional" ;
$category="IB" ;
$version="1.2.00" ;
$author="Ross Parker" ;
$url="http://rossparker.org" ;

//Module tables
$moduleTables[0]="CREATE TABLE `ibPYPStaffTeaching` (
  `ibPYPStaffTeachingID` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `role` enum('Coordinator','Teacher (Curriculum)','Teacher') NOT NULL,
  PRIMARY KEY (`ibPYPStaffTeachingID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[1]="CREATE TABLE `ibPYPStudent` (
  `ibPYPStudentID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `gibbonSchoolYearIDStart` int(3) unsigned zerofill NOT NULL,
  `gibbonSchoolYearIDEnd` int(3) unsigned zerofill NOT NULL,
  PRIMARY KEY (`ibPYPStudentID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[2]="CREATE TABLE `ibPYPUnitMaster` (
  `ibPYPUnitMasterID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
  `timestamp` datetime NOT NULL,
  `name` varchar(50) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `theme` text NOT NULL,
  `centralIdea` text NOT NULL,
  `summativeAssessment` text NOT NULL,
  `relatedConcepts` text,
  `linesOfInquiry` text NOT NULL,
  `teacherQuestions` text NOT NULL,
  `provocation` text NOT NULL,
  `preAssessment` text NOT NULL,
  `formativeAssessment` text NOT NULL,
  `resources` text NOT NULL,
  `action` text NOT NULL,
  `environments` text NOT NULL,
  `assessOutcomes` text NOT NULL,
  `assessmentImprovements` text NOT NULL,
  `ideasThemes` text NOT NULL,
  `learningExperiencesConcepts` text NOT NULL,
  `learningExperiencesTransSkills` text NOT NULL,
  `learningExperiencesProfileAttitudes` text NOT NULL,
  `inquiriesQuestions` text NOT NULL,
  `questionsProvocations` text NOT NULL,
  `studentInitAction` text NOT NULL,
  `teachersNotes` text NOT NULL,
  PRIMARY KEY (`ibPYPUnitMasterID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[3]="CREATE TABLE  `ibPYPGlossary` (
`ibPYPGlossaryID` INT( 6 ) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`type` ENUM('Attitude','Concept','Learner Profile','Transdisciplinary Skill') NOT NULL ,
`title` VARCHAR( 100 ) NOT NULL ,
`category` VARCHAR( 100 ) NOT NULL ,
`content` TEXT NOT NULL
) ENGINE = MYISAM ;" ;

$moduleTables[4]="INSERT INTO `ibPYPGlossary` VALUES(null, 'Learner Profile', 'Inquirers', '', 'They develop their natural curiosity. They acquire the skills necessary to conduct inquiry and research and show independence in learning. They actively enjoy learning and this love of learning will be sustained throughout their lives.'),
(null, 'Learner Profile', 'Knowledgeable', '', 'They explore concepts, ideas and issues that have local and global significance. In so doing, they acquire in-depth knowledge and develop understanding across a broad and balanced range of disciplines.'),
(null, 'Learner Profile', 'Thinkers', '', 'They exercise initiative in applying thinking skills critically and creatively to recognize and approach complex problems, and make reasoned, ethical decisions.'),
(null, 'Learner Profile', 'Communicators', '', 'They understand and express ideas and information confidently and creatively in more than one language and in a variety of modes of communication. They work effectively and willingly in collaboration with others.'),
(null, 'Learner Profile', 'Principled', '', 'They act with integrity and honesty, with a strong sense of fairness, justice and respect for the dignity of the individual, groups and communities. They take responsibility for their own actions and the consequences that accompany them.'),
(null, 'Learner Profile', 'Open-minded', '', 'They understand and appreciate their own cultures and personal histories, and are open to the perspectives, values and traditions of other individuals and communities. They are accustomed to seeking and evaluating a range of points of view, and are willing to grow from the experience.'),
(null, 'Learner Profile', 'Caring', '', 'They show empathy, compassion and respect towards the needs and feelings of others. They have a personal commitment to service, and act to make a positive difference to the lives of others and to the environment.'),
(null, 'Learner Profile', 'Risk-takers', '', 'They approach unfamiliar situations and uncertainty with courage and forethought, and have the independence of spirit to explore new roles, ideas and strategies. They are brave and articulate in defending their beliefs.'),
(null, 'Learner Profile', 'Balanced', '', 'They understand the importance of intellectual, physical and emotional balance to achieve personal well-being for themselves and others.'),
(null, 'Learner Profile', 'Reflective', '', 'They give thoughtful consideration to their own learning and experience. They are able to assess and understand their strengths and limitations in order to support their learning and personal development.');" ;

$moduleTables[5]="CREATE TABLE `ibPYPUnitMasterBlock` (
  `ibPYPUnitMasterBlockID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `ibPYPUnitMasterID` int(10) unsigned zerofill NOT NULL,
  `ibPYPGlossaryID` int(6) unsigned zerofill DEFAULT NULL,
  `gibbonOutcomeID` int(8) unsigned zerofill DEFAULT NULL,
  `content` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  PRIMARY KEY (`ibPYPUnitMasterBlockID`)
) ENGINE=MyISAM ;" ;

$moduleTables[6]="CREATE TABLE `ibPYPUnitWorking` (
  `ibPYPUnitWorkingID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `ibPYPUnitMasterID` int(10) unsigned zerofill NOT NULL,
  `gibbonCourseClassID` int(8) unsigned zerofill NOT NULL,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
  `timestamp` datetime NOT NULL,
  `name` varchar(50) NOT NULL,
  `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
  `dateStart` date DEFAULT NULL,
  `gibbonRubricID` int(8) unsigned zerofill DEFAULT NULL,
  `theme` text NOT NULL,
  `centralIdea` text NOT NULL,
  `summativeAssessment` text NOT NULL,
  `relatedConcepts` text,
  `linesOfInquiry` text NOT NULL,
  `teacherQuestions` text NOT NULL,
  `provocation` text NOT NULL,
  `preAssessment` text NOT NULL,
  `formativeAssessment` text NOT NULL,
  `resources` text NOT NULL,
  `action` text NOT NULL,
  `environments` text NOT NULL,
  `assessOutcomes` text NOT NULL,
  `assessmentImprovements` text NOT NULL,
  `ideasThemes` text NOT NULL,
  `learningExperiencesConcepts` text NOT NULL,
  `learningExperiencesTransSkills` text NOT NULL,
  `learningExperiencesProfileAttitudes` text NOT NULL,
  `inquiriesQuestions` text NOT NULL,
  `questionsProvocations` text NOT NULL,
  `studentInitAction` text NOT NULL,
  `teachersNotes` text NOT NULL,
  PRIMARY KEY (`ibPYPUnitWorkingID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[7]="CREATE TABLE `ibPYPUnitWorkingBlock` (
  `ibPYPUnitWorkingBlockID` int(14) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `ibPYPUnitWorkingID` int(12) unsigned zerofill NOT NULL,
  `ibPYPGlossaryID` int(6) unsigned zerofill DEFAULT NULL,
  `gibbonOutcomeID` int(8) unsigned zerofill DEFAULT NULL,
  `content` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  PRIMARY KEY (`ibPYPUnitWorkingBlockID`)
) ENGINE=MyISAM ;" ;

$moduleTables[8]="CREATE TABLE `ibPYPUnitMasterSmartBlock` (
  `ibPYPUnitMasterSmartBlockID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `ibPYPUnitMasterID` int(10) unsigned zerofill NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NOT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  PRIMARY KEY (`ibPYPUnitMasterSmartBlockID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[9]="CREATE TABLE `ibPYPUnitWorkingSmartBlock` (
  `ibPYPUnitWorkingSmartBlockID` int(14) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `ibPYPUnitWorkingID` int(12) unsigned zerofill NOT NULL,
  `gibbonPlannerEntryID` int(14) unsigned zerofill DEFAULT NULL,
  `ibPYPUnitMasterSmartBlockID` int(12) unsigned zerofill DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NOT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `complete` enum('N','Y') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`ibPYPUnitWorkingSmartBlockID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;

$moduleTables[9]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'IB PYP', 'defaultRubric', 'Default Rubric', 'This is the default rubric associated with al new working units.', '');";

//Action rows
$actionRows[0]["name"]="Manage Staff - Teaching" ;
$actionRows[0]["precedence"]="0";
$actionRows[0]["category"]="Admin" ;
$actionRows[0]["description"]="Allows admins to manage staff." ;
$actionRows[0]["URLList"]="staff_manage.php, staff_manage_add.php, staff_manage_edit.php, staff_manage_delete.php" ;
$actionRows[0]["entryURL"]="staff_manage.php" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ;
$actionRows[0]["defaultPermissionTeacher"]="Y" ;
$actionRows[0]["defaultPermissionStudent"]="N" ;
$actionRows[0]["defaultPermissionParent"]="N" ;
$actionRows[0]["defaultPermissionSupport"]="N" ;
$actionRows[0]["categoryPermissionStaff"]="Y" ;
$actionRows[0]["categoryPermissionStudent"]="N" ;
$actionRows[0]["categoryPermissionParent"]="N" ;
$actionRows[0]["categoryPermissionOther"]="N" ;

$actionRows[1]["name"]="Manage Student Enrolment" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="Admin" ;
$actionRows[1]["description"]="Allows admins to manage students." ;
$actionRows[1]["URLList"]="student_manage.php, student_manage_add.php, student_manage_edit.php, student_manage_delete.php" ;
$actionRows[1]["entryURL"]="student_manage.php" ;
$actionRows[1]["defaultPermissionAdmin"]="Y" ;
$actionRows[1]["defaultPermissionTeacher"]="N" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="Units" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="Teaching & Learning" ;
$actionRows[2]["description"]="Allows users to control units, both masters and working." ;
$actionRows[2]["URLList"]="units_manage.php,units_manage_master_add.php,units_manage_master_edit.php,units_manage_master_delete.php,units_manage_master_deploy.php,units_manage_working_add.php,units_manage_working_edit.php,units_manage_working_delete.php,units_manage_working_copyback.php" ;
$actionRows[2]["entryURL"]="units_manage.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="Y" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="N" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$actionRows[3]["name"]="Essential Elements" ;
$actionRows[3]["precedence"]="0";
$actionRows[3]["category"]="Teaching & Learning" ;
$actionRows[3]["description"]="View and edit lists of concepts, skills, learner profiles and attitudes" ;
$actionRows[3]["URLList"]="glossary.php, glossary_add.php, glossary_edit.php, glossary_delete.php" ;
$actionRows[3]["entryURL"]="glossary.php" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="Y" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="N" ;
$actionRows[3]["categoryPermissionStaff"]="Y" ;
$actionRows[3]["categoryPermissionStudent"]="N" ;
$actionRows[3]["categoryPermissionParent"]="N" ;
$actionRows[3]["categoryPermissionOther"]="N" ;

$actionRows[4]["name"]="Manage Settings" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="Admin" ;
$actionRows[4]["description"]="Manage settings to control the behaviour of the module." ;
$actionRows[4]["URLList"]="settings_manage.php" ;
$actionRows[4]["entryURL"]="settings_manage.php" ;
$actionRows[4]["defaultPermissionAdmin"]="Y" ;
$actionRows[4]["defaultPermissionTeacher"]="N" ;
$actionRows[4]["defaultPermissionStudent"]="N" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="N" ;
$actionRows[4]["categoryPermissionStaff"]="Y" ;
$actionRows[4]["categoryPermissionStudent"]="N" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;

//Hooks
$array=array() ;
$array["unitTable"]="ibPYPUnitMaster" ;
$array["unitIDField"]="ibPYPUnitMasterID" ;
$array["unitCourseIDField"]="gibbonCourseID" ;
$array["unitNameField"]="name" ;
$array["unitDescriptionField"]="theme" ;
$array["unitSmartBlockTable"]="ibPYPUnitMasterSmartBlock" ;
$array["unitSmartBlockIDField"]="ibPYPUnitMasterSmartBlockID" ;
$array["unitSmartBlockJoinField"]="ibPYPUnitMasterID" ;
$array["unitSmartBlockTitleField"]="title" ;
$array["unitSmartBlockTypeField"]="type" ;
$array["unitSmartBlockLengthField"]="length" ;
$array["unitSmartBlockContentsField"]="contents" ;
$array["unitSmartBlockTeachersNotesField"]="teachersNotes" ;
$array["unitSmartBlockSequenceNumberField"]="sequenceNumber" ;
$array["classLinkTable"]="ibPYPUnitWorking" ;
$array["classLinkIDField"]="ibPYPUnitWorkingID" ;
$array["classLinkJoinFieldUnit"]="ibPYPUnitMasterID" ;
$array["classLinkJoinFieldClass"]="gibbonCourseClassID" ;
$array["classSmartBlockTable"]="ibPYPUnitWorkingSmartBlock" ;
$array["classSmartBlockIDField"]="ibPYPUnitWorkingSmartBlockID" ;
$array["classSmartBlockJoinField"]="ibPYPUnitWorkingID" ;
$array["classSmartBlockPlannerJoin"]="gibbonPlannerEntryID" ;
$array["classSmartBlockUnitBlockJoinField"]="ibPYPUnitMasterSmartBlockID" ;
$array["classSmartBlockTitleField"]="title" ;
$array["classSmartBlockTypeField"]="type" ;
$array["classSmartBlockLengthField"]="length" ;
$array["classSmartBlockContentsField"]="contents" ;
$array["classSmartBlockTeachersNotesField"]="teachersNotes" ;
$array["classSmartBlockSequenceNumberField"]="sequenceNumber" ;
$array["classSmartBlockCompleteField"]="complete" ;
$hooks[0]="INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'IB PYP', 'Unit', '" . serialize($array) . "', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));" ;
?>