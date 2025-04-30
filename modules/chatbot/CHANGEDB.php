<?php
// USE ;end TO SEPARATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = [];
$count = 0;

// v1.0.00
$sql[$count][0] = "1.0.00";
$sql[$count][1] = "
-- Initial table creation
CREATE TABLE gibbonChatBotCourseMaterials (
    gibbonChatBotCourseMaterialID INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    filePath VARCHAR(255),
    gibbonCourseID INT(8) UNSIGNED ZEROFILL NOT NULL,
    gibbonSchoolYearID INT(3) UNSIGNED ZEROFILL NOT NULL,
    dateAdded DATE NOT NULL,
    gibbonPersonIDCreator INT(10) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (gibbonChatBotCourseMaterialID),
    FOREIGN KEY (gibbonCourseID) REFERENCES gibbonCourse(gibbonCourseID),
    FOREIGN KEY (gibbonSchoolYearID) REFERENCES gibbonSchoolYear(gibbonSchoolYearID),
    FOREIGN KEY (gibbonPersonIDCreator) REFERENCES gibbonPerson(gibbonPersonID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;end

CREATE TABLE gibbonChatBotStudentProgress (
    gibbonChatBotStudentProgressID INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    gibbonPersonID INT(10) UNSIGNED ZEROFILL NOT NULL,
    gibbonCourseID INT(8) UNSIGNED ZEROFILL NOT NULL,
    gibbonSchoolYearID INT(3) UNSIGNED ZEROFILL NOT NULL,
    progress DECIMAL(5,2) DEFAULT 0.00,
    lastActivity DATETIME,
    PRIMARY KEY (gibbonChatBotStudentProgressID),
    UNIQUE KEY unique_student_course (gibbonPersonID, gibbonCourseID, gibbonSchoolYearID),
    FOREIGN KEY (gibbonPersonID) REFERENCES gibbonPerson(gibbonPersonID),
    FOREIGN KEY (gibbonCourseID) REFERENCES gibbonCourse(gibbonCourseID),
    FOREIGN KEY (gibbonSchoolYearID) REFERENCES gibbonSchoolYear(gibbonSchoolYearID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;end

INSERT INTO gibbonAction SET name='AI Teaching Assistant', precedence=0, category='Learning', description='Access the AI teaching assistant', URLList='chatbot.php', entryURL='chatbot.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='Y', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='Y', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='ChatBot');end

INSERT INTO gibbonAction SET name='Assessment Integration', precedence=1, category='Learning', description='View and analyze student assessment data with AI recommendations', URLList='assessment_integration.php', entryURL='assessment_integration.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='Y', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='Y', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='ChatBot');end

INSERT INTO gibbonAction SET name='Learning Management', precedence=2, category='Admin', description='Manage ChatBot learning data and training', URLList='learning_management.php', entryURL='learning_management.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='N', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='N', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='ChatBot');end";

// v0.0.0x
$count++;
$sql[$count][0] = "0.0.0x";
$sql[$count][1] = "-- One block for each subsequent version, place sql statements here for version, seperated by ;end";

// v1.0.01 - Future version placeholder
// $sql[] = "";
