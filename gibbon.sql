-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: May 21, 2024 at 02:17 AM
-- Server version: 5.7.39
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `gibbon`
--

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAction`
--

CREATE TABLE `gibbonAction` (
  `gibbonActionID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'The action name should be unqiue to the module that it is related to',
  `precedence` int(2) NOT NULL,
  `category` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `helpURL` varchar(255) DEFAULT NULL,
  `URLList` text NOT NULL COMMENT 'Comma seperated list of all URLs that make up this action',
  `entryURL` varchar(255) NOT NULL,
  `entrySidebar` enum('Y','N') NOT NULL DEFAULT 'Y',
  `menuShow` enum('Y','N') NOT NULL DEFAULT 'Y',
  `defaultPermissionAdmin` enum('N','Y') NOT NULL DEFAULT 'N',
  `defaultPermissionTeacher` enum('N','Y') NOT NULL DEFAULT 'N',
  `defaultPermissionStudent` enum('N','Y') NOT NULL DEFAULT 'N',
  `defaultPermissionParent` enum('N','Y') NOT NULL DEFAULT 'N',
  `defaultPermissionSupport` enum('N','Y') NOT NULL DEFAULT 'N',
  `categoryPermissionStaff` enum('Y','N') NOT NULL DEFAULT 'Y',
  `categoryPermissionStudent` enum('Y','N') NOT NULL DEFAULT 'Y',
  `categoryPermissionParent` enum('Y','N') NOT NULL DEFAULT 'Y',
  `categoryPermissionOther` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonAction`
--

INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `helpURL`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES
(0000001, 0002, 'Application Form Settings', 0, 'Student Management', 'Allows admins to control the application form', 'administrators/getting-started/getting-started-with-gibbon/#admissions', 'applicationFormSettings.php', 'applicationFormSettings.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000002, 0002, 'Manage Users_editDelete', 1, 'User Management', 'Edit any user within the system', 'administrators/getting-started/getting-started-with-gibbon/#users', 'user_manage.php, user_manage_add.php, user_manage_edit.php, user_manage_delete.php, user_manage_password.php', 'user_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000003, 0001, 'Manage School Years', 0, 'Years, Days & Times', 'Allows user to control the definition of academic years within the system', 'administrators/getting-started/getting-started-with-gibbon/#years-days-times', 'schoolYear_manage.php,schoolYear_manage_edit.php,schoolYear_manage_delete.php,schoolYear_manage_add.php', 'schoolYear_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000004, 0145, 'Student Enrolment', 0, 'Current Students', 'Allows user to control student enrolment in the current year', NULL, 'studentEnrolment_manage.php,studentEnrolment_manage_add.php,studentEnrolment_manage_edit.php,studentEnrolment_manage_delete.php', 'studentEnrolment_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000005, 0003, 'System Settings', 0, 'Settings', 'Main system settings', NULL, 'systemSettings.php', 'systemSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000006, 0001, 'Manage Year Groups', 0, 'Groupings', '', NULL, 'yearGroup_manage.php,yearGroup_manage_edit.php,yearGroup_manage_add.php,yearGroup_manage_delete.php', 'yearGroup_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000007, 0001, 'Manage Form Groups', 0, 'Groupings', '', NULL, 'formGroup_manage.php,formGroup_manage_edit.php,formGroup_manage_add.php,formGroup_manage_delete.php', 'formGroup_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000008, 0001, 'Manage Houses', 0, 'Groupings', '', NULL, 'house_manage.php,house_manage_edit.php,house_manage_add.php,house_manage_delete.php,house_manage_assign.php', 'house_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000009, 0002, 'Manage Roles_all', 1, 'User Management', '', 'administrators/getting-started/getting-started-with-gibbon/#user-management-access', 'role_manage.php,role_manage_add.php,role_manage_edit.php,role_manage_delete.php,role_manage_duplicate.php,role_manage_view.php', 'role_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000010, 0003, 'Manage Modules', 0, 'Extend & Update', '', NULL, 'module_manage.php,module_manage_install.php,module_manage_edit.php,module_manage_uninstall.php,module_manage_update.php', 'module_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000011, 0005, 'Emergency SMS by Year Group', 0, 'Reports', 'Output all parental first mobile numbers by year group: if there are no details, then show emergency details.', NULL, 'report_emergencySMS_byYearGroup.php', 'report_emergencySMS_byYearGroup.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000012, 0002, 'Manage Permissions', 0, 'User Management', '', 'administrators/getting-started/getting-started-with-gibbon/#user-management-access', 'permission_manage.php,permission_manage_edit.php', 'permission_manage.php', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000013, 0001, 'Days of the Week', 0, 'Years, Days & Times', '', 'administrators/getting-started/getting-started-with-gibbon/#years-days-times', 'daysOfWeek_manage.php', 'daysOfWeek_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000014, 0008, 'Update Personal Data_family', 0, 'Request Updates', 'Allows users to update personal information for themselves and their family members', NULL, 'data_personal.php', 'data_personal.php', 'Y', 'Y', 'N', 'Y', 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000015, 0001, 'Manage Terms', 0, 'Years, Days & Times', '', 'administrators/getting-started/getting-started-with-gibbon/#years-days-times', 'schoolYearTerm_manage.php,schoolYearTerm_manage_add.php,schoolYearTerm_manage_edit.php,schoolYearTerm_manage_delete.php', 'schoolYearTerm_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000016, 0001, 'Manage Special Days', 0, 'Years, Days & Times', '', 'administrators/getting-started/getting-started-with-gibbon/#years-days-times', 'schoolYearSpecialDay_manage.php,schoolYearSpecialDay_manage_add.php,schoolYearSpecialDay_manage_edit.php,schoolYearSpecialDay_manage_delete.php', 'schoolYearSpecialDay_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000017, 0013, 'Manage Courses & Classes', 0, 'Courses & Classes', '', NULL, 'course_manage.php,course_manage_add.php,course_manage_edit.php,course_manage_delete.php,course_manage_class_add.php,course_manage_class_edit.phpcourse_manage_class_delete.php', 'course_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000018, 0013, 'Course Enrolment by Class', 0, 'Courses & Classes', '', NULL, 'courseEnrolment_manage.php,courseEnrolment_manage_class_edit.php,courseEnrolment_manage_class_edit_edit.php,courseEnrolment_manage_class_edit_delete.php', 'courseEnrolment_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000019, 0002, 'Manage Families', 0, 'User Management', '', NULL, 'family_manage.php,family_manage_add.php,family_manage_edit.php,family_manage_delete.php,family_manage_edit_editChild.php,family_manage_edit_deleteChild.php,family_manage_edit_editAdult.php,family_manage_edit_deleteAdult.php', 'family_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000020, 0003, 'Manage Themes', 0, 'Extend & Update', '', NULL, 'theme_manage.php,theme_manage_install.php,theme_manage_edit.php,theme_manage_uninstall.php', 'theme_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000021, 0005, 'Manage Medical Forms', 0, 'Medical', 'Manage medical form information for users', NULL, 'medicalForm_manage.php,medicalForm_manage_add.php,medicalForm_manage_edit.php,medicalForm_manage_delete.php,medicalForm_manage_condition_add.php,medicalForm_manage_condition_edit.php,medicalForm_manage_condition_delete.php', 'medicalForm_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000022, 0004, 'View Departments', 0, 'Departments', 'Allows uers to view all department details.', NULL, 'departments.php, department.php, department_course.php, department_course_class.php, department_course_class_full.php, department_course_unit_add.php, department_course_unit_edit.php, department_course_unit_delete.php, department_course_unit_duplicate.php, department_edit.php, department_course_edit.php', 'departments.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000023, 0005, 'View Student Profile_brief', 0, 'Profiles', 'View brief profile of any student in the school.', 'teachers/people/student-profiles/', 'student_view.php,student_view_details.php', 'student_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000024, 0005, 'View Student Profile_full', 3, 'Profiles', 'View full profile of any student in the school.', 'teachers/people/student-profiles/', 'student_view.php,student_view_details.php,student_view_details_notes_add.php,student_view_details_notes_edit.php', 'student_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000025, 0001, 'Manage Facilities', 0, 'Other', 'Allows users to create a list of spaces and rooms in the school', NULL, 'space_manage.php, space_manage_add.php, space_manage_edit.php, space_manage_delete.php', 'space_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000026, 0006, 'Attendance By Person', 0, 'Take Attendance', 'Take attendance, one person at a time', NULL, 'attendance_take_byPerson.php', 'attendance_take_byPerson.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000027, 0006, 'Attendance By Form Group_all', 1, 'Take Attendance', 'Take attendance, one form group at a time', NULL, 'attendance_take_byFormGroup.php', 'attendance_take_byFormGroup.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000028, 0006, 'Set Future Absence', 0, 'Future Information', 'Set future absences one student at a time', NULL, 'attendance_future_byPerson.php', 'attendance_future_byPerson.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000029, 0006, 'Students Not Present', 0, 'Reports', 'Print a report of students who are not present on a given day', NULL, 'report_studentsNotPresent_byDate.php,report_studentsNotPresent_byDate_print.php', 'report_studentsNotPresent_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000030, 0006, 'Form Groups Not Registered', 0, 'Reports', 'Print a report of form groups who have not been registered on a given day', NULL, 'report_formGroupsNotRegistered_byDate.php,report_formGroupsNotRegistered_byDate_print.php', 'report_formGroupsNotRegistered_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000031, 0006, 'Student History_all', 2, 'Reports', 'Print a report of all attendance data in the current school year for a student', NULL, 'report_studentHistory.php,report_studentHistory_print.php', 'report_studentHistory.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000032, 0136, 'Manage Staff_general', 0, 'Staff Management', 'Edit general information on members of staff.', NULL, 'staff_manage.php, staff_manage_add.php, staff_manage_edit.php, staff_manage_delete.php, staff_manage_edit_facility_add.php, staff_manage_edit_facility_delete.php', 'staff_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000033, 0007, 'View Markbook_allClassesAllData', 4, 'Markbook', 'View all markbook information for all users', 'teachers/assess/markbook/', 'markbook_view.php, markbook_view_full.php', 'markbook_view.php', 'N', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000034, 0007, 'Edit Markbook_singleClass', 0, 'Markbook', 'Edit columns and grades for a single class at a time.', 'teachers/assess/markbook/', 'markbook_edit.php, markbook_edit_add.php, markbook_edit_edit.php, markbook_edit_delete.php,markbook_edit_data.php,markbook_edit_targets.php,markbook_edit_copy.php', 'markbook_edit.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000035, 0009, 'Lesson Planner_viewMyClasses', 1, 'Planning', 'View all planner information for classes user is in', 'teachers/learn/planner/lesson-planner/', 'planner.php, planner_view_full.php, planner_deadlines.php, planner_view_full_post.php, planner_unitOverview.php', 'planner.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000036, 0009, 'Lesson Planner_viewAllEditMyClasses', 3, 'Planning', 'View all planner information and edit all planner information for classes user is in', 'teachers/learn/planner/lesson-planner/', 'planner.php, planner_view_full.php, planner_add.php, planner_edit.php, planner_delete.php, planner_deadlines.php, planner_duplicate.php, planner_view_full_post.php, planner_view_full_submit_edit.php, planner_bump.php, planner_unitOverview.php', 'planner.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000038, 0009, 'Lesson Planner_viewEditAllClasses', 4, 'Planning', 'View and edit all planner information for all classes', 'teachers/learn/planner/lesson-planner/', 'planner.php, planner_view_full.php, planner_add.php, planner_edit.php, planner_delete.php, planner_deadlines.php, planner_duplicate.php, planner_view_full_post.php, planner_view_full_submit_edit.php, planner_bump.php, planner_unitOverview.php', 'planner.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000039, 0007, 'View Markbook_myMarks', 2, 'Markbook', 'View your own marks', 'teachers/assess/markbook/', 'markbook_view.php', 'markbook_view.php', 'N', 'Y', 'N', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'Y'),
(0000040, 0009, 'Lesson Planner_viewMyChildrensClasses', 0, 'Planning', 'Allows parents to view their children\'s classes', 'teachers/learn/planner/lesson-planner/', 'planner.php, planner_view_full.php, planner_deadlines.php, planner_view_full_post.php, planner_unitOverview.php', 'planner.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000041, 0007, 'View Markbook_viewMyChildrensClasses', 1, '', 'Allows parents to view their children\'s classes', 'teachers/assess/markbook/', 'markbook_view.php', 'markbook_view.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000042, 0005, 'View Student Profile_myChildren', 1, 'Profiles', 'Allows parents to view their student\'s information', 'teachers/people/student-profiles/', 'student_view.php, student_view_details.php', 'student_view.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000043, 0005, 'Students by Form Group', 0, 'Reports', 'Print student form group lists', NULL, 'report_students_byFormGroup.php,report_students_byFormGroup_print.php', 'report_students_byFormGroup.php', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000044, 0006, 'Students Not Onsite', 0, 'Reports', 'Print a report of students who are not physically on the school campus on a given day', NULL, 'report_studentsNotOnsite_byDate.php,report_studentsNotOnsite_byDate_print.php', 'report_studentsNotOnsite_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000045, 0011, 'Individual Needs Records_view', 0, 'Individual Needs', 'Allows user to view IN records for all students', NULL, 'in_view.php, in_edit.php', 'in_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000046, 0011, 'Individual Needs Records_viewEdit', 2, 'Individual Needs', 'Allows users to edit IN records for all students', NULL, 'in_view.php, in_edit.php', 'in_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000047, 0012, 'Assess', 0, 'Crowd Assessment', 'Allows users to assess each other\'s work', 'teachers/assess/crowd-assessment/', 'crowdAssess.php,crowdAssess_view.php,crowdAssess_view_discuss.php, crowdAssess_view_discuss_post.php', 'crowdAssess.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000048, 0013, 'Manage Timetables', 0, 'Timetable', 'Allow admins to create and manage timetables', 'administrators/timetable/timetabling/', 'tt.php, tt_add.php, tt_edit.php, tt_delete.php, tt_import.php, tt_edit_day_add.php, tt_edit_day_edit.php, tt_edit_day_delete.php, tt_edit_day_edit_class.php, tt_edit_day_edit_class_delete.php, tt_edit_day_edit_class_add.php, tt_edit_day_edit_class_edit.php, tt_edit_day_edit_class_exception.php, tt_edit_day_edit_class_exception_add.php, tt_edit_day_edit_class_exception_delete.php,tt_edit_byClass.php', 'tt.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000049, 0013, 'Manage Columns', 0, 'Timetable', 'Allow admins to manage timetable columns', NULL, 'ttColumn.php, ttColumn_add.php, ttColumn_edit.php, ttColumn_delete.php, ttColumn_edit_row_add.php, ttColumn_edit_row_edit.php, ttColumn_edit_row_delete.php', 'ttColumn.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000050, 0013, 'Tie Days To Dates', 0, 'Timetable', 'Allows admins to place timetable days into the school calendar', NULL, 'ttDates.php, ttDates_edit.php, ttDates_edit_add.php, ttDates_edit_delete.php', 'ttDates.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000051, 0014, 'View Timetable by Person', 2, 'View Timetables', 'Allows users to view timetables', NULL, 'tt.php, tt_view.php', 'tt.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000052, 0015, 'View Activities_view', 0, 'Activities', 'Allows users to view activities', NULL, 'activities_view.php, activities_view_full.php', 'activities_view.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000053, 0015, 'View Activities_studentRegister', 1, 'Activities', 'Allows students to view activities and register', NULL, 'activities_view.php, activities_view_full.php, activities_view_register.php', 'activities_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000054, 0001, 'Activity Settings', 0, 'Learn', 'Control activity settings', NULL, 'activitySettings.php,activitySettings_type_add.php,activitySettings_type_edit.php,activitySettings_type_delete.php', 'activitySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000055, 0015, 'Manage Activities', 0, 'Administration', 'Allows managers to build activity program', NULL, 'activities_manage.php, activities_manage_add.php, activities_manage_edit.php, activities_manage_delete.php,activities_manage_enrolment.php,activities_manage_enrolment_add.php,activities_manage_enrolment_edit.php,activities_manage_enrolment_delete.php', 'activities_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000056, 0015, 'My Activities', 0, 'Activities', 'Allows a user to view the activities they are involved in', NULL, 'activities_my.php, activities_my_full.php', 'activities_my.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000057, 0015, 'Participants by Activity', 0, 'Reports', 'Print participant lists', NULL, 'report_participants.php, report_participants_print.php', 'report_participants.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000058, 0015, 'Attendance History by Activity', 0, 'Attendance', 'Print attendance lists', NULL, 'report_attendance.php, report_attendanceExport.php', 'report_attendance.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000059, 0015, 'Generate Invoices', 0, 'Administration', 'Print payment list', NULL, 'activities_payment.php', 'activities_payment.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000060, 0006, 'Student History_myChildren', 0, 'Reports', 'Print a report of all attendance data in the current school yearfor my children', NULL, 'report_studentHistory.php', 'report_studentHistory.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000061, 0009, 'Work Summary by Form Group', 0, 'Reports', 'Print work summary statistical data by form group', NULL, 'report_workSummary_byFormGroup.php', 'report_workSummary_byFormGroup.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000062, 0001, 'Manage Departments', 0, 'Groupings', 'Allows admins to create learning areas and administrative groups.', NULL, 'department_manage.php,department_manage_add.php,department_manage_edit.php,department_manage_delete.php', 'department_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000063, 0008, 'Personal Data Updates', 0, 'Manage Updates', 'Allows admins to process data update requests for personal data', 'administrators/user-admin/data-updater/#personal-data', 'data_personal_manage.php, data_personal_manage_edit.php, data_personal_manage_delete.php', 'data_personal_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000064, 0008, 'Update Medical Form_family', 0, 'Request Updates', 'Allows users to update medical information for themselves and their family members', NULL, 'data_medical.php', 'data_medical.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000065, 0008, 'Medical Form Updates', 0, 'Manage Updates', 'Allows admins to process data update requests for medical data', 'administrators/user-admin/data-updater/#medical-data', 'data_medical_manage.php, data_medical_manage_edit.php, data_medical_manage_delete.php', 'data_medical_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000066, 0013, 'Class Enrolment by Form Group', 0, 'Reports', 'Shows the number of classes students are enroled in, organised by form group', NULL, 'report_classEnrolment_byFormGroup.php', 'report_classEnrolment_byFormGroup.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000067, 0015, 'Activity Type by Form Group', 0, 'Reports', 'Print form group lists showing count of various activity types', NULL, 'report_activityType_formGroup.php', 'report_activityType_formGroup.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000068, 0016, 'External Assessment Data_view', 0, 'External Assessment', 'Allow users to view assessment data for all students', NULL, 'externalAssessment.php, externalAssessment_details.php', 'externalAssessment.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000069, 0016, 'External Assessment Data_manage', 1, 'External Assessment', 'Allows users to manage external assessment data', NULL, 'externalAssessment.php, externalAssessment_details.php, externalAssessment_manage_details_add.php, externalAssessment_manage_details_edit.php, externalAssessment_manage_details_delete.php', 'externalAssessment.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000070, 0002, 'Rollover', 0, 'Student Management', 'Allows admins to kick the school forward one year', NULL, 'rollover.php', 'rollover.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000072, 0005, 'Student Transport', 0, 'Reports', 'Shows student transport details', NULL, 'report_transport_student.php', 'report_transport_student.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000073, 0008, 'Student Data Updater History', 0, 'Reports', 'Allows users to check, for a group of students, how recently they have been updated', NULL, 'report_student_dataUpdaterHistory.php', 'report_student_dataUpdaterHistory.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000074, 0005, 'Application Form', 0, 'Admissions', 'Allows users, with or without an account, to apply for student place.', NULL, 'applicationForm.php', 'applicationForm.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000075, 0005, 'Medical Data Summary', 0, 'Reports', 'Allows users to show a summary of medical data for a group of students.', NULL, 'report_student_medicalSummary.php, report_student_medicalSummary_print.php', 'report_student_medicalSummary.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000077, 0005, 'Emergency Data Summary', 0, 'Reports', 'Allows users to show a summary of emergency contact data for a group of students.', NULL, 'report_student_emergencySummary.php, report_student_emergencySummary_print.php', 'report_student_emergencySummary.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000078, 0005, 'Manage Applications_editDelete', 1, 'Admissions', 'Allows admins to view and action applications', NULL, 'applicationForm_manage.php, applicationForm_manage_edit.php, applicationForm_manage_delete.php, applicationForm_manage_accept.php, applicationForm_manage_reject.php, applicationForm_manage_add.php', 'applicationForm_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000585, 0008, 'Update Personal Data_any', 1, 'Request Updates', 'Create personal data update request for any user', NULL, 'data_personal.php', 'data_personal.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000586, 0008, 'Update Medical Data_any', 1, 'Request Updates', 'Create medical data update request for any user', NULL, 'data_medical.php', 'data_medical.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000605, 0001, 'Behaviour Settings', 0, 'People', 'Manage settings for the Behaviour module', NULL, 'behaviourSettings.php', 'behaviourSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000606, 0119, 'Manage Behaviour Records_all', 1, 'Behaviour Records', 'Manage all behaviour records', 'teachers/people/behaviour/', 'behaviour_manage.php, behaviour_manage_add.php, behaviour_manage_edit.php, behaviour_manage_delete.php', 'behaviour_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000607, 0119, 'Manage Behaviour Records_my', 0, 'Behaviour Records', 'Manage behaviour records create by the user', 'teachers/people/behaviour/', 'behaviour_manage.php, behaviour_manage_add.php, behaviour_manage_edit.php, behaviour_manage_delete.php', 'behaviour_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000608, 0119, 'View Behaviour Records_all', 1, 'Behaviour Records', 'View behaviour records by student', 'teachers/people/behaviour/', 'behaviour_view.php,behaviour_view_details.php', 'behaviour_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000609, 0005, 'Emergency SMS by Transport', 0, 'Reports', 'Show SMS emergency details by transport route', NULL, 'report_emergencySMS_byTransport.php', 'report_emergencySMS_byTransport.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000610, 0001, 'Resource Settings', 0, 'Learn', 'Manage settings for the resources module', NULL, 'resourceSettings.php', 'resourceSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000611, 0009, 'Manage Resources_all', 1, 'Resources', 'Manage all resources', NULL, 'resources_manage.php, resources_manage_add.php, resources_manage_edit.php, resources_manage_delete.php', 'resources_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000612, 0009, 'Manage Resources_my', 0, 'Resources', 'Manage resources created by the user', NULL, 'resources_manage.php, resources_manage_add.php, resources_manage_edit.php, resources_manage_delete.php', 'resources_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000613, 0009, 'View Resources', 0, 'Resources', 'View resources', NULL, 'resources_view.php,resources_view_details.php,resources_view_full.php', 'resources_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000614, 0121, 'New Message_classes_my', 1, 'Manage Messages', 'Bulk email to any of my classes', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000615, 0121, 'New Message_classes_any', 9, 'Manage Messages', 'Bulk email to any class', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000616, 0121, 'New Message_classes_parents', 5, 'Manage Messages', 'Include parents in messages posted to classes', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000617, 0121, 'New Message_courses_my', 3, 'Manage Messages', 'Bulk email to any of my courses', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000618, 0121, 'New Message_courses_any', 11, 'Manage Messages', 'Bulk email to any courses', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000619, 0121, 'New Message_courses_parents', 7, 'Manage Messages', 'Include parents in messages posted to courses', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000620, 0121, 'New Message_formGroups_my', 2, 'Manage Messages', 'Bulk email to any of my form groups', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000621, 0121, 'New Message_formGroups_any', 10, 'Manage Messages', 'Bulk email to any form group', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000622, 0121, 'New Message_formGroups_parents', 6, 'Manage Messages', 'Include parents in messages posted to parents', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000623, 0121, 'New Message_activities_my', 0, 'Manage Messages', 'Bulk email to any of my activities', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000624, 0121, 'New Message_activities_any', 8, 'Manage Messages', 'Bulk email to any activity', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000625, 0121, 'New Message_activities_parents', 4, 'Manage Messages', 'Include parents in messages posted to activities', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000626, 0121, 'New Message_yearGroups_any', 8, 'Manage Messages', 'Bulk email to any year group', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000627, 0121, 'New Message_yearGroups_parents', 4, 'Manage Messages', 'Include parents in messages posted to year group', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000628, 0121, 'New Message_role', 8, 'Manage Messages', 'Bulk email to a particular role', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000629, 0121, 'Manage Messages_my', 0, 'Manage Messages', 'Edit all messages created by the user', NULL, 'messenger_manage.php,messenger_manage_delete.php,messenger_manage_edit.php,messenger_manage_report.php,messenger_send.php', 'messenger_manage.php', 'N', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000630, 0121, 'Manage Messages_all', 1, 'Manage Messages', 'Edit all messages', NULL, 'messenger_manage.php,messenger_manage_delete.php,messenger_manage_edit.php,messenger_manage_report.php,messenger_send.php', 'messenger_manage.php', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'Y', 'N'),
(0000631, 0003, 'Update', 0, 'Extend & Update', 'Update Gibbon to a new version', NULL, 'update.php', 'update.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000632, 0121, 'New Message_fromSchool', 0, 'Manage Messages', 'Bulk email from the school\'s email address', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000655, 0014, 'View Timetable by Facility', 0, 'View Timetables', 'View space usage according to the timetable', NULL, 'tt_space.php,tt_space_view.php', 'tt_space.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'Y'),
(0000656, 0013, 'Course Enrolment by Person', 0, 'Courses & Classes', 'Manage course enrolment for a single user', NULL, 'courseEnrolment_manage_byPerson.php, courseEnrolment_manage_byPerson_edit.php, courseEnrolment_manage_byPerson_edit_edit.php, courseEnrolment_manage_byPerson_edit_delete.php', 'courseEnrolment_manage_byPerson.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000657, 0121, 'New Message_applicants', 12, 'Manage Messages', 'Bulk email to applicants by intended school year of entry', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'Y', 'N'),
(0000658, 0121, 'New Message_individuals', 13, 'Manage Messages', 'Bulk email to indvidual users', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000659, 0121, 'New Message_houses_my', 14, 'Manage Messages', 'Bulk email to members of my house', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000660, 0121, 'New Message_houses_all', 15, 'Manage Messages', 'Bulk email to members of all houses', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000661, 0009, 'Unit Planner_all', 1, 'Planning', 'Manage all units within the school', NULL, 'units.php, units_add.php, units_delete.php, units_edit.php, units_duplicate.php, units_edit_deploy.php, units_edit_working.php, units_edit_working_copyback.php, units_edit_working_add.php, units_edit_copyBack.php, units_edit_copyForward.php, units_dump.php,units_edit_smartBlockify.php', 'units.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000662, 0009, 'Unit Planner_learningAreas', 0, 'Planning', 'Manage all units within the learning areas I have appropriate permission', NULL, 'units.php, units_add.php, units_delete.php, units_edit.php, units_duplicate.php, units_edit_deploy.php, units_edit_working.php, units_edit_working_copyback.php, units_edit_working_add.php, units_edit_copyBack.php, units_edit_copyForward.php, units_dump.php,units_edit_smartBlockify.php', 'units.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000673, 0015, 'Activity Spread by Form Group', 0, 'Reports', 'View spread of enrolment over terms and days by form group', NULL, 'report_activitySpread_formGroup.php', 'report_activitySpread_formGroup.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000674, 0001, 'Planner Settings', 0, 'Learn', 'Edit settings for the planner', NULL, 'plannerSettings.php', 'plannerSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000675, 0009, 'Manage Outcomes_viewAllEditLearningArea', 1, 'Outcomes', 'View all outcomes in the school, edit any from Learning Areas where you are Coordinator or Teacher (Curriculum)', NULL, 'outcomes.php, outcomes_add.php, outcomes_edit.php, outcomes_delete.php', 'outcomes.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000676, 0009, 'Manage Outcomes_viewEditAll', 2, 'Outcomes', 'Manage all outcomes in the school', NULL, 'outcomes.php, outcomes_add.php, outcomes_edit.php, outcomes_delete.php', 'outcomes.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000677, 0009, 'Manage Outcomes_viewAll', 0, 'Outcomes', 'View all outcomes in the school', NULL, 'outcomes.php', 'outcomes.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000678, 0126, 'Manage Rubrics_viewAllEditLearningArea', 0, 'Rubrics', 'View all rubrics in the school, edit any from Learning Areas where you are Coordinator or Teacher (Curriculum)', 'teachers/assess/rubrics/#getting-started', 'rubrics.php, rubrics_add.php, rubrics_edit.php, rubrics_delete.php, rubrics_edit_editRowsColumns.php, rubrics_duplicate.php', 'rubrics.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000679, 0126, 'Manage Rubrics_viewEditAll', 1, 'Rubrics', 'Manage all rubrics in the school', 'teachers/assess/rubrics/#getting-started', 'rubrics.php, rubrics_add.php, rubrics_edit.php, rubrics_delete.php, rubrics_edit_editRowsColumns.php, rubrics_duplicate.php', 'rubrics.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000705, 0015, 'Activity Choices by Student', 1, 'Reports', 'View all student activity choices in the current year for a given student', NULL, 'report_activityChoices_byStudent.php', 'report_activityChoices_byStudent.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000706, 0001, 'Manage Grade Scales', 1, 'Assess', 'Manage all aspects of grade scales, which are used throughout ARR to control grade input.', NULL, 'gradeScales_manage.php, gradeScales_manage_add.php, gradeScales_manage_edit.php, gradeScales_manage_delete.php, gradeScales_manage_edit_grade_add.php, gradeScales_manage_edit_grade_edit.php, gradeScales_manage_edit_grade_delete.php', 'gradeScales_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000707, 0145, 'New Students', 1, 'Reports', 'A report showing all new students in the current school year.', NULL, 'report_students_new.php', 'report_students_new.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000708, 0126, 'View Rubrics', 0, 'Rubrics', 'View all rubrics in the school, except students who can only view those for own year group.', 'teachers/assess/rubrics/', 'rubrics_view.php, rubrics_view_full.php', 'rubrics_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000709, 0015, 'Activity Enrolment Summary', 0, 'Reports', 'View summary enrolment information for all activities in the current year.', NULL, 'report_activityEnrollmentSummary.php', 'report_activityEnrollmentSummary.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000710, 0130, 'Manage Catalog', 0, 'Catalog', 'Control all items in the school library catalog', NULL, 'library_manage_catalog.php, library_manage_catalog_add.php, library_manage_catalog_edit.php, library_manage_catalog_delete.php, library_manage_catalog_duplicate.php', 'library_manage_catalog.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000711, 0130, 'Lending & Activity Log', 0, 'Catalog', 'Manage lending, returns, reservations, repairs, decommissioning, etc.', NULL, 'library_lending.php, library_lending_item.php,library_lending_item_signout.php,library_lending_item_return.php,library_lending_item_edit.php,library_lending_item_renew.php', 'library_lending.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000712, 0001, 'Library Settings', 0, 'Learn', 'Manage settings for the Library module', NULL, 'librarySettings.php', 'librarySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000713, 0005, 'Age & Gender Summary', 0, 'Reports', 'Summarises gender, age and school year', NULL, 'report_students_ageGenderSummary.php', 'report_students_ageGenderSummary.php', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000714, 0005, 'Form Group Summary', 0, 'Reports', 'Summarises gender and number of students across all form groups.', NULL, 'report_formGroupSummary.php', 'report_formGroupSummary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000715, 0001, 'Manage Alert Levels', 0, 'People', 'Manage the alert levels which are used throughout the school to flag problems.', NULL, 'alertLevelSettings.php', 'alertLevelSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000716, 0011, 'Individual Needs Records_viewContribute', 1, 'Individual Needs', 'Allows users to contribute teaching strategies to IN records for all students', NULL, 'in_view.php, in_edit.php', 'in_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000717, 0001, 'Individual Needs Settings', 0, 'Learn', 'Allows admins to control the descriptors available for use in the Individual Needs module.', NULL, 'inSettings.php, inSettings_add.php, inSettings_edit.php, inSettings_delete.php', 'inSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000718, 0011, 'Individual Needs Summary', 0, 'Individual Needs', 'Allows user to see a flexible summary of IN data.', NULL, 'in_summary.php', 'in_summary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000719, 0119, 'Find Behaviour Patterns', 0, 'Behaviour Tracking', 'Allows user to spot students who are repeat or regular offenders.', NULL, 'behaviour_pattern.php', 'behaviour_pattern.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000720, 0130, 'Browse The Library', 0, 'Catalog', 'Search and view all borrowable items maintained by the library', NULL, 'library_browse.php', 'library_browse.php', 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000721, 0130, 'View Overdue Items', 0, 'Reports', 'View items which are on loan and have exceeded their due date.', NULL, 'report_viewOverdueItems.php', 'report_viewOverdueItems.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000722, 0130, 'Student Borrowing Record', 0, 'Reports', 'View items borrowed by an individual student.', NULL, 'report_studentBorrowingRecord.php', 'report_studentBorrowingRecord.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000723, 0002, 'User Settings', 0, 'User Settings', 'Configure settings relating to user management.', NULL, 'userSettings.php', 'userSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000724, 0005, 'Family Address by Student', 0, 'Reports', 'View family addresses by student', NULL, 'report_familyAddress_byStudent.php', 'report_familyAddress_byStudent.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000725, 0002, 'Data Updater Settings', 0, 'User Settings', 'Configure options for the Data Updater module', NULL, 'dataUpdaterSettings.php', 'dataUpdaterSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000726, 0001, 'Formal Assessment Settings', 0, 'Assess', 'Configure External Assessment module options', NULL, 'formalAssessmentSettings.php', 'formalAssessmentSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000727, 0001, 'Markbook Settings', 0, 'Assess', 'Configure options for the Markbook module', NULL, 'markbookSettings.php', 'markbookSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000741, 0015, 'View Activities_studentRegisterByParent', 2, 'Activities', 'Allows parents to register their children for activities', NULL, 'activities_view.php, activities_view_full.php, activities_view_register.php', 'activities_view.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000742, 0002, 'Students Settings', 0, 'User Settings', 'Manage settings for the Student module', NULL, 'studentsSettings.php,studentsSettings_noteCategory_add.php,studentsSettings_noteCategory_edit.php,studentsSettings_noteCategory_delete.php', 'studentsSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000743, 0121, 'New Message_byEmail', 0, 'Manage Messages', 'Send messages by email.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000744, 0121, 'New Message_byMessageWall', 0, 'Manage Messages', 'Send messages by message wall.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000745, 0121, 'New Message_bySMS', 0, 'Manage Messages', 'Send messages by SMS.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000746, 0121, 'View Message Wall', 0, 'View Messages', 'Allows users to view all messages posted on their message wall.', 'teachers/other/messenger/', 'messageWall_view.php,messageWall_view_full.php', 'messageWall_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000747, 0001, 'Messenger Settings', 0, 'Other', 'Manage gateway settings for outgoing SMS messages.', NULL, 'messengerSettings.php', 'messengerSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000748, 0130, 'Catalog Summary', 0, 'Reports', 'Provides an summary overview of items in the catalog.', NULL, 'report_catalogSummary.php', 'report_catalogSummary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000755, 0145, 'Left Students', 1, 'Reports', 'A report showing all the students who have left within a specified date range.', NULL, 'report_students_left.php', 'report_students_left.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000756, 0001, 'Manage File Extensions', 0, 'Other', 'Manage file extensions allowed across the system', NULL, 'fileExtensions_manage.php,fileExtensions_manage_add.php,fileExtensions_manage_edit.php,fileExtensions_manage_delete.php', 'fileExtensions_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000757, 0005, 'Student ID Cards', 1, 'Reports', 'A report for bulk creation of student ID cards.', NULL, 'report_students_IDCards.php', 'report_students_IDCards.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000758, 0007, 'Edit Markbook_multipleClassesInDepartment', 1, 'Markbook', 'Edit columns and grades for a single class belonging to the user, or multiple classes within departments.', 'teachers/assess/markbook/', 'markbook_edit.php, markbook_edit_add.php, markbook_edit_edit.php, markbook_edit_delete.php,markbook_edit_data.php,markbook_edit_targets.php,markbook_edit_copy.php,markbook_edit_addMulti.php', 'markbook_edit.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000759, 0007, 'Edit Markbook_multipleClassesAcrossSchool', 2, 'Markbook', 'Edit columns and grades for a single class belonging to the user, or multiple classes across the whole school.', 'teachers/assess/markbook/', 'markbook_edit.php, markbook_edit_add.php, markbook_edit_edit.php, markbook_edit_delete.php,markbook_edit_data.php,markbook_edit_targets.php,markbook_edit_copy.php,markbook_edit_addMulti.php', 'markbook_edit.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000760, 0014, 'View Available Facilities', 0, 'Reports', 'View unassigned rooms by timetable.', NULL, 'report_viewAvailableSpaces.php', 'report_viewAvailableSpaces.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000764, 0008, 'Update Family Data_any', 1, 'Request Updates', 'Create family data update request for any user', NULL, 'data_family.php', 'data_family.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000765, 0008, 'Update Family Data_family', 0, 'Request Updates', 'Allows adults in a family to create data update request for their family.', NULL, 'data_family.php', 'data_family.php', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'N', 'Y', 'N'),
(0000766, 0008, 'Family Data Updates', 0, 'Manage Updates', 'Manage requests for updates to family data.', 'administrators/user-admin/data-updater/#family-data', 'data_family_manage.php,data_family_manage_edit.php,data_family_manage_delete.php', 'data_family_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000767, 0135, 'Manage Fee Categories', 0, 'Billing', 'Allows users to create, edit and delete fee categories.', NULL, 'feeCategories_manage.php,feeCategories_manage_add.php,feeCategories_manage_edit.php,feeCategories_manage_delete.php', 'feeCategories_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000768, 0135, 'Manage Invoicees', 0, 'Billing', 'Allows users to view and edit invoice recipients.', NULL, 'invoicees_manage.php,invoicees_manage_edit.php', 'invoicees_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000769, 0135, 'Manage Fees', 0, 'Billing', 'Allows users to create, view and edit fees.', NULL, 'fees_manage.php,fees_manage_edit.php,fees_manage_add.php', 'fees_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000770, 0135, 'Manage Billing Schedule', 0, 'Billing', 'Allows users to create, view and edit billing windows.', NULL, 'billingSchedule_manage.php,billingSchedule_manage_edit.php,billingSchedule_manage_add.php', 'billingSchedule_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000771, 0135, 'Manage Invoices', 0, 'Billing', 'Allows users to generate, view, delete and edit invoices.', NULL, 'invoices_manage.php,invoices_manage_edit.php,invoices_manage_add.php,invoices_manage_delete.php,invoices_manage_view.php,invoices_manage_issue.php,invoices_manage_print.php', 'invoices_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000772, 0001, 'Finance Settings', 0, 'Other', 'Allows users to edit the text that appears in invoices and receipts.', NULL, 'financeSettings.php', 'financeSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000773, 0008, 'Update Finance Data_any', 1, 'Request Updates', 'Create finance data update request for any user', NULL, 'data_finance.php', 'data_finance.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000774, 0008, 'Update Finance Data_family', 0, 'Request Updates', 'Allows adults in a family to create finance data update request for their family.', NULL, 'data_finance.php', 'data_finance.php', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'N', 'Y', 'N'),
(0000775, 0008, 'Finance Data Updates', 0, 'Manage Updates', 'Manage requests for updates to finance data.', 'administrators/user-admin/data-updater/#finance-data', 'data_finance_manage.php,data_finance_manage_edit.php,data_finance_manage_delete.php', 'data_finance_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000779, 0136, 'Staff Directory_brief', 1, 'Profiles', 'View brief profile of any staff member in the school.', NULL, 'staff_view.php,staff_view_details.php', 'staff_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000780, 0136, 'Staff Directory_full', 2, 'Profiles', 'View full profile of any staff member in the school.', NULL, 'staff_view.php,staff_view_details.php', 'staff_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000781, 0137, 'View Form Groups_all', 1, 'Form Groups', 'View a brief profile of form groups in school.', NULL, 'formGroups.php,formGroups_details.php', 'formGroups.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000796, 0001, 'Manage External Assessments', 1, 'Assess', 'Add, edit and delete external assessments.', NULL, 'externalAssessments_manage.php,externalAssessments_manage_edit.php,externalAssessments_manage_edit_field_add.php,externalAssessments_manage_edit_field_edit.php,externalAssessments_manage_edit_field_delete.php, externalAssessments_manage_add.php, externalAssessments_manage_delete.php', 'externalAssessments_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `helpURL`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES
(0000803, 0007, 'Edit Markbook_everything', 4, 'Markbook', 'Allows editing of any column in any class.', 'teachers/assess/markbook/', 'markbook_edit.php, markbook_edit_add.php, markbook_edit_edit.php, markbook_edit_delete.php,markbook_edit_data.php,markbook_edit_targets.php,markbook_edit_copy.php', 'markbook_edit.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000804, 0002, 'Manage Districts', 0, 'User Management', 'Manage a list of districts for address autocomplete.', NULL, 'district_manage.php, district_manage_add.php, district_manage_edit.php, district_manage_delete.php', 'district_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000805, 0005, 'Letters Home by Form Group', 0, 'Reports', 'Show students in form group, less those with an older sibling, so that letters can be carried home by oldest in family.', NULL, 'report_lettersHome_byFormGroup.php', 'report_lettersHome_byFormGroup.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000806, 0003, 'Manage Languages', 0, 'Extend & Update', 'Allows administrators to control system-wide language and localisation settings.', NULL, 'i18n_manage.php', 'i18n_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000807, 0005, 'Privacy Choices by Student', 0, 'Reports', 'Shows privacy options selected, for those students with a selection made.', NULL, 'report_privacy_student.php', 'report_privacy_student.php', 'N', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000808, 0014, 'View Available Teachers', 0, 'Reports', 'View unassigned teachers by timetable.', NULL, 'report_viewAvailableTeachers.php', 'report_viewAvailableTeachers.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000810, 0009, 'Parent Weekly Email Summary', 0, 'Reports', 'This report shows responses to the weekly summary email, organised by calendar week and role group.', NULL, 'report_parentWeeklyEmailSummaryConfirmation.php', 'report_parentWeeklyEmailSummaryConfirmation.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000812, 0014, 'Manage Facility Changes_allClasses', 2, 'Facilities', 'Allows a user to create and manage one-off location changes for all classes within the timetable.', NULL, 'spaceChange_manage.php,spaceChange_manage_add.php,spaceChange_manage_edit.php,spaceChange_manage_delete.php', 'spaceChange_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000813, 0014, 'Manage Facility Changes_myClasses', 0, 'Facilities', 'Allows a user to create and manage one-off location changes for their own classes within the timetable.', NULL, 'spaceChange_manage.php,spaceChange_manage_add.php,spaceChange_manage_edit.php,spaceChange_manage_delete.php', 'spaceChange_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000814, 0014, 'Manage Facility Bookings_allBookings', 0, 'Facilities', 'Allows a user to book a room for on-off use, and manage bookings made by all other users.', NULL, 'spaceBooking_manage.php,spaceBooking_manage_add.php,spaceBooking_manage_edit.php,spaceBooking_manage_delete.php', 'spaceBooking_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000815, 0014, 'Manage Facility Bookings_myBookings', 0, 'Facilities', 'Allows a user to book a room for on-off use, and manage their own bookings.', NULL, 'spaceBooking_manage.php,spaceBooking_manage_add.php,spaceBooking_manage_edit.php,spaceBooking_manage_delete.php', 'spaceBooking_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'Y'),
(0000817, 0009, 'Outcomes By Course', 0, 'Outcomes', 'This view gives an overview of which whole school and learning area outcomes are covered by classes in a given course, allowing for curriculum mapping by outcome and course.', NULL, 'curriculumMapping_outcomesByCourse.php', 'curriculumMapping_outcomesByCourse.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000818, 0121, 'New Quick Wall Message', 0, 'Manage Messages', 'Allows for the quick posting of a Message Wall message to all users.', NULL, 'messenger_postQuickWall.php', 'messenger_postQuickWall.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'N'),
(0000820, 0121, 'New Message_transport_any', 0, 'Manage Messages', 'Send messages users by transport field.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N'),
(0000821, 0121, 'New Message_transport_parents', 0, 'Manage Messages', 'Send messages parents of users by transport field.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N'),
(0000822, 0011, 'Archive Records', 0, 'Other', 'Allows for current records to be archived for viewing in the future.', NULL, 'in_archive.php', 'in_archive.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000823, 0014, 'View Timetable by Person_allYears', 3, 'View Timetables', 'Allows users to view timetables in all school years.', NULL, 'tt.php, tt_view.php', 'tt.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N'),
(0000824, 0135, 'Manage Budgets', 0, 'Expenses', 'Allows users to create, edit and delete budgets.', NULL, 'budgets_manage.php,budgets_manage_add.php,budgets_manage_edit.php,budgets_manage_delete.php', 'budgets_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000825, 0135, 'Manage Expense Approvers', 0, 'Expenses', 'Determines who can approve expense requests, in accordance to the Expense Approval Type setting in School Admin.', NULL, 'expenseApprovers_manage.php,expenseApprovers_manage_add.php,expenseApprovers_manage_edit.php,expenseApprovers_manage_delete.php', 'expenseApprovers_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000826, 0135, 'Manage Budget Cycles', 0, 'Expenses', 'Allows a sufficiently priviledged user to create and manage budget cycles.', NULL, 'budgetCycles_manage.php,budgetCycles_manage_add.php,budgetCycles_manage_edit.php,budgetCycles_manage_delete.php', 'budgetCycles_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000827, 0135, 'My Expense Requests', 0, 'Expenses', 'Allows a user to request expenses from budgets they have access to.', NULL, 'expenseRequest_manage.php,expenseRequest_manage_add.php,expenseRequest_manage_view.php,expenseRequest_manage_reimburse.php', 'expenseRequest_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000828, 0135, 'Manage Expenses_all', 0, 'Expenses', 'Gives access to full control all expenses across all budgets.', NULL, 'expenses_manage.php, expenses_manage_add.php, expenses_manage_edit.php, expenses_manage_print.php, expenses_manage_approve.php, expenses_manage_view.php', 'expenses_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000829, 0135, 'Manage Expenses_myBudgets', 0, 'Expenses', 'Gives access to control expenses, according to budget-level access rights.', NULL, 'expenses_manage.php, expenses_manage_edit.php, expenses_manage_print.php, expenses_manage_approve.php, expenses_manage_view.php', 'expenses_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'N'),
(0000830, 0003, 'Third Party Settings', 0, 'Settings', 'Allows administrators to configure and make use of third party services.', NULL, 'thirdPartySettings.php', 'thirdPartySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000831, 0002, 'Public Registration Settings', 0, 'Student Management', 'Gives access to enable and configure public registration.', NULL, 'publicRegistrationSettings.php', 'publicRegistrationSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000832, 0003, 'Sound Alarm', 0, 'Alarm', 'Allows user to issue a system-wide audio alert to all staff.', NULL, 'alarm.php', 'alarm.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000833, 0016, 'Manage Internal Assessments', 0, 'Internal Assessment', 'Allows privileged users to create and manage Internal Assessment columns.', NULL, 'internalAssessment_manage.php, internalAssessment_manage_add.php, internalAssessment_manage_edit.php, internalAssessment_manage_delete.php', 'internalAssessment_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000834, 0016, 'Write Internal Assessments_myClasses', 0, 'Internal Assessment', 'Allows teachers to enter Internal Assessment assessment data to columns in their classes.', NULL, 'internalAssessment_write.php, internalAssessment_write_data.php', 'internalAssessment_write.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000835, 0016, 'Write Internal Assessments_all', 1, 'Internal Assessment', 'Allows privileged users to enter Internal Assessment assessment data to columns in all classes.', NULL, 'internalAssessment_write.php, internalAssessment_write_data.php', 'internalAssessment_write.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000836, 0016, 'View Internal Assessments_mine', 0, 'Internal Assessment', 'Allows students to view their own Internal Assessment results.', NULL, 'internalAssessment_view.php', 'internalAssessment_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000837, 0016, 'View Internal Assessments_myChildrens', 1, 'Internal Assessment', 'Allows parents to view their childrens\' Internal Assessment results.', NULL, 'internalAssessment_view.php', 'internalAssessment_view.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000838, 0016, 'View Internal Assessments_all', 2, 'Internal Assessment', 'Allows staff to see Internal Assessment results for all children.', NULL, 'internalAssessment_view.php', 'internalAssessment_view.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000839, 0001, 'Facility Settings', 0, 'Other', 'Allows privileged users to manage settings for spaces.', NULL, 'spaceSettings.php', 'spaceSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000840, 0119, 'View Behaviour Records_myChildren', 0, 'Behaviour Records', 'View behaviour records for students within a family.', 'teachers/people/behaviour/', 'behaviour_view.php,behaviour_view_details.php', 'behaviour_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N'),
(0000841, 0014, 'View Master Timetable', 0, 'View Timetables', 'Allows a user to see all days, periods, teachers and rooms in a timetable.', NULL, 'tt_master.php', 'tt_master.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000842, 0141, 'Graphing_all', 2, 'Visualise', 'Allows a user to see progress tracking graphs for all students in school.', NULL, 'graphing.php', 'graphing.php', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000843, 0013, 'Course Enrolment Rollover', 0, 'Courses & Classes', 'Allows privileged users to move enrolments from the current year to the next year.', NULL, 'course_rollover.php', 'course_rollover.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000844, 0003, 'Custom Fields', 0, 'Customise', 'Allows a user to create, edit and delete custom fields for users.', NULL, 'customFields.php,customFields_add.php,customFields_edit.php,customFields_delete.php', 'customFields.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000845, 0014, 'Manage Student Enrolment', 0, 'Edit Timetables', 'Allows a departmental Coordinator or Assistant Coordinator to manage student enrolment within their department.', NULL, 'studentEnrolment_manage.php, studentEnrolment_manage_edit.php, studentEnrolment_manage_edit_edit.php', 'studentEnrolment_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000846, 0001, 'Tracking Settings', 0, 'Assess', 'Allows a user to manage settings for the Tracking module.', NULL, 'trackingSettings.php', 'trackingSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000847, 0141, 'Data Points', 0, 'Analyse', 'Allows a user to export certain key assessment data points to a spreadsheet.', NULL, 'dataPoints.php', 'dataPoints.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000848, 0119, 'View Behaviour Letters', 0, 'Behaviour Tracking', 'Allows a user to view automated behaviour letters sent out by the system.', NULL, 'behaviour_letters.php', 'behaviour_letters.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000849, 0135, 'View Invoices_myChildren', 1, 'Billing', 'Allows parents to view invoices issued to members of their family.', NULL, 'invoices_view.php, invoices_view_print.php', 'invoices_view.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000850, 0011, 'View Individual Education Plans_myChildren', 0, 'Individual Needs', 'Allows parents to view individual needs plans for members of their family.', NULL, 'iep_view_myChildren.php', 'iep_view_myChildren.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N'),
(0000851, 0003, 'String Replacement', 0, 'Customise', 'Allows for interface strings to be replaced with custom values.', NULL, 'stringReplacement_manage.php, stringReplacement_manage_add.php, stringReplacement_manage_edit.php, stringReplacement_manage_delete.php', 'stringReplacement_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000853, 0121, 'Canned Response', 0, 'Manage Messages', 'Allows for the creation of message templates.', NULL, 'cannedResponse_manage.php, cannedResponse_manage_add.php, cannedResponse_manage_edit.php, cannedResponse_manage_delete.php', 'cannedResponse_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000854, 0136, 'Job Openings', 0, 'Staff Management', 'Allows for the creation of job openings, which can be used in the job application form.', NULL, 'jobOpenings_manage.php, jobOpenings_manage_add.php, jobOpenings_manage_edit.php, jobOpenings_manage_delete.php', 'jobOpenings_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000855, 0002, 'Staff Settings', 0, 'User Settings', 'Controls settings for users with role category Staff.', NULL, 'staffSettings.php,staffSettings_manage_add.php,staffSettings_manage_edit.php,staffSettings_manage_delete.php', 'staffSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000856, 0136, 'Application Form', 0, 'Staff Management', 'Allows prospective staff to apply for job openings.', NULL, 'applicationForm.php, applicationForm_jobOpenings_view.php', 'applicationForm.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000857, 0002, 'Staff Application Form Settings', 0, 'Staff Management', 'Allows admins to control the staff application form.', NULL, 'staffApplicationFormSettings.php', 'staffApplicationFormSettings.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000858, 0136, 'Manage Applications', 0, 'Staff Management', 'Allows administrators to view and action staff applications.', NULL, 'applicationForm_manage.php, applicationForm_manage_edit.php, applicationForm_manage_delete.php, applicationForm_manage_accept.php, applicationForm_manage_reject.php', 'applicationForm_manage.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000859, 0136, 'Manage Staff_confidential', 1, 'Staff Management', 'Edit general and confidential information on members of staff.', NULL, 'staff_manage.php, staff_manage_add.php, staff_manage_edit.php, staff_manage_delete.php, staff_manage_edit_contract_add.php, staff_manage_edit_contract_edit.php, staff_manage_edit_facility_add.php, staff_manage_edit_facility_delete.php', 'staff_manage.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000860, 0015, 'Enter Activity Attendance', 1, 'Attendance', 'Record student attendance for activities.', NULL, 'activities_attendance.php,activities_attendanceProcess.php', 'activities_attendance.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000861, 0015, 'Printable Attendance Sheet', 1, 'Attendance', 'Generate a printable attendance sheet for activities.', NULL, 'activities_attendance_sheet.php,activities_attendance_sheetPrint.php', 'activities_attendance_sheet.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000862, 0016, 'View External Assessments_mine', 0, 'External Assessment', 'Allows a student to view their own external assessment records.', NULL, 'externalAssessment_view.php', 'externalAssessment_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000863, 0016, 'View External Assessments_myChildrens', 1, 'External Assessment', 'Allows a parent to view external assessment records for their children.', NULL, 'externalAssessment_view.php', 'externalAssessment_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N'),
(0000864, 0135, 'View Invoices_mine', 0, 'Billing', 'Allows a student to view their own invoices.', NULL, 'invoices_view.php, invoices_view_print.php', 'invoices_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000865, 0014, 'Manage Facility Changes_myDepartment', 1, 'Facilities', 'Allows a department coordinator to manage changes for all classes in their department.', NULL, 'spaceChange_manage.php,spaceChange_manage_add.php,spaceChange_manage_edit.php,spaceChange_manage_delete.php', 'spaceChange_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000866, 0121, 'New Message_attendance', 0, 'Manage Messages', 'Bulk email by student attendance.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000868, 0007, 'Manage Weightings_everything', 1, 'Markbook', 'Manage markbook weightings for any class.', NULL, 'weighting_manage.php,weighting_manage_add.php,weighting_manage_edit.php,weighting_manage_delete.php', 'weighting_manage.php', 'Y', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000869, 0007, 'Manage Weightings_singleClass', 0, 'Markbook', 'Manage markbook weightings for a single class at a time.', NULL, 'weighting_manage.php,weighting_manage_add.php,weighting_manage_edit.php,weighting_manage_delete.php', 'weighting_manage.php', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000870, 0001, 'Dashboard Settings', 0, 'Other', 'Manage settings that control Staff, Student and Parent dashboards.', NULL, 'dashboardSettings.php', 'dashboardSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000872, 0003, 'Display Settings', 0, 'Settings', 'Allows administrators to configure the system display settings.', NULL, 'displaySettings.php', 'displaySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000873, 0009, 'Scope & Sequence', 0, 'Curriculum Overview', 'Allows users to generate scope and sequence documentation for individual courses, based on the Unit Planner.', NULL, 'scopeAndSequence.php', 'scopeAndSequence.php', 'N', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000874, 0009, 'Concept Explorer', 0, 'Curriculum Overview', 'Allows users to browse and explore concepts and keywords, based on the Unit Planner.', NULL, 'conceptExplorer.php', 'conceptExplorer.php', 'N', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000875, 0006, 'Attendance By Class', 0, 'Take Attendance', 'Take attendance, one class at a time', NULL, 'attendance_take_byCourseClass.php', 'attendance_take_byCourseClass.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000876, 0006, 'View Daily Attendance', 0, 'Take Attendance', 'View attendance, by form group and class', NULL, 'attendance.php', 'attendance.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000877, 0001, 'Attendance Settings', 0, 'People', 'Allows administrators to configure the attendance module.', NULL, 'attendanceSettings.php,attendanceSettings_manage_add.php,attendanceSettings_manage_edit.php,attendanceSettings_manage_delete.php', 'attendanceSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000878, 0006, 'Classes Not Registered', 0, 'Reports', 'Print a report of classes who have not been registered on a given day', NULL, 'report_courseClassesNotRegistered_byDate.php,report_courseClassesNotRegistered_byDate_print.php', 'report_courseClassesNotRegistered_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000879, 0006, 'Manage Attendance Logs', 0, 'Take Attendance', 'Edit student attendance logs.', NULL, 'attendance_take_byPerson_edit.php,attendance_take_byPerson_delete.php', 'attendance_take_byPerson_edit.php', 'Y', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000880, 0006, 'Attendance Summary by Date', 0, 'Reports', 'Print a report of student attendace in a given date range', NULL, 'report_summary_byDate.php,report_summary_byDate_print.php', 'report_summary_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000881, 0006, 'Attendance Trends', 0, 'Reports', 'Display a graph of student attendance types over time', NULL, 'report_graph_byType.php', 'report_graph_byType.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000882, 0121, 'New Message_cannedResponse', 0, 'Manage Messages', 'Allows user to use pre-defined Canned Responses.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000883, 0121, 'New Message_readReceipts', 0, 'Manage Messages', 'Allows users to include read receipts in emails.', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000884, 0005, 'First Aid Record_editAll', 2, 'Medical', 'Allows user to record first aid visits and actions.', NULL, 'firstAidRecord.php, firstAidRecord_add.php, firstAidRecord_edit.php', 'firstAidRecord.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000885, 0005, 'View Student Profile_fullNoNotes', 2, 'Profiles', 'View full profile of any student in the school, without access to Notes.', 'teachers/people/student-profiles/', 'student_view.php,student_view_details.php', 'student_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000886, 0003, 'System Check', 0, 'System', 'Check system versions and extensions installed.', NULL, 'systemCheck.php', 'systemCheck.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000887, 0006, 'Student Self Registration', 0, 'Take Attendance', 'Allows students to self register as Present, provided they are within a certain range of IP addresses.', NULL, 'attendance_studentSelfRegister.php', 'attendance_studentSelfRegister.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000888, 0015, 'My Activities_viewEditEnrolment', 0, 'Activities', 'Allows an activity organizer to manage enrolment for their activities.', NULL, 'activities_my.php,activities_my_full.php,activities_manage_enrolment.php,activities_manage_enrolment_add.php,activities_manage_enrolment_edit.php,activities_manage_enrolment_delete.php', 'activities_my.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000889, 0015, 'Enter Activity Attendance_leader', 0, 'Attendance', 'Record student attendance for activities you organise, coach or assist in.', NULL, 'activities_attendance.php,activities_attendanceProcess.php', 'activities_attendance.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N'),
(0000890, 0015, 'Activity Attendance by Date', 0, 'Reports', 'Record student attendance for activities.', NULL, 'report_attendance_byDate.php,report_attendance_byDate_print.php', 'report_attendance_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000891, 0003, 'Notification Events', 0, 'Customise', 'Manage settings for system notifications.', NULL, 'notificationSettings.php,notificationSettings_manage_edit.php', 'notificationSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000892, 0005, 'Manage Applications_edit', 0, 'Admissions', 'Allows admins to view and action applications, but not to delete them', NULL, 'applicationForm_manage.php, applicationForm_manage_edit.php, applicationForm_manage_accept.php, applicationForm_manage_reject.php, applicationForm_manage_add.php', 'applicationForm_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000893, 0006, 'Attendance By Form Group_myGroups', 0, 'Take Attendance', 'Take attendance for a teacher\'s own roll groups', NULL, 'attendance_take_byFormGroup.php', 'attendance_take_byFormGroup.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000894, 0014, 'View Timetable by Person_my', 1, 'View Timetables', 'Allows users to view their own timetable', NULL, 'tt.php, tt_view.php', 'tt.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'Y'),
(0000895, 0014, 'View Timetable by Person_myChildren', 0, 'View Timetables', 'Allows parents to view their children\'s timetable', NULL, 'tt.php, tt_view.php', 'tt.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N'),
(0000896, 0005, 'View Student Profile_my', 1, 'Profiles', 'Allows students to view their own information', 'teachers/people/student-profiles/', 'student_view.php, student_view_details.php', 'student_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000897, 0007, 'View Markbook_myClasses', 3, 'Profiles', 'Allows teachers to view their own markbook information', 'teachers/assess/markbook/', 'markbook_view.php, markbook_view_full.php', 'markbook_view.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000898, 0145, 'Student Enrolment Trends', 0, 'Visualise', 'Provides a visual graph of student enrolment over a range of time.', NULL, 'report_graph_studentEnrolment.php', 'report_graph_studentEnrolment.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000899, 0005, 'Students by House', 0, 'Reports', 'View a report of student houses by year group.', NULL, 'report_students_byHouse.php', 'report_students_byHouse.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000900, 0013, 'Sync Course Enrolment', 0, 'Courses & Classes', 'Allows users to map enrolments from homerooms to classes.', NULL, 'courseEnrolment_sync.php,courseEnrolment_sync_add.php,courseEnrolment_sync_edit.php,courseEnrolment_sync_delete.php,courseEnrolment_sync_run.php', 'courseEnrolment_sync.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000901, 0006, 'Student History_my', 1, 'Reports', 'Allows a student to print a report of their attendance data in the current school year.', NULL, 'report_studentHistory.php', 'report_studentHistory.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000903, 0015, 'Activity Choices by Form Group', 0, 'Reports', 'View all student activity choices in the current year for a given form group.', NULL, 'report_activityChoices_byFormGroup.php', 'report_activityChoices_byFormGroup.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000904, 0008, 'My Data Updates', 0, 'Request Updates', 'Provides an overview of any required data updates for a user, including family data if applicable.', NULL, 'data_updates.php', 'data_updates.php', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y'),
(0000905, 0121, 'Manage Groups_all', 1, 'Targets', 'Allows management of custom groups for message targetting', NULL, 'groups_manage.php,groups_manage_add.php,groups_manage_edit.php,groups_manage_delete.php', 'groups_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000906, 0121, 'Manage Groups_my', 0, 'Targets', 'Allows management of custom groups for message targetting', NULL, 'groups_manage.php,groups_manage_add.php,groups_manage_edit.php,groups_manage_delete.php', 'groups_manage.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000907, 0121, 'New Message_groups_any', 1, 'Manage Messages', 'Bulk email to any group', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000908, 0121, 'New Message_groups_my', 0, 'Manage Messages', 'Bulk email to any group a user owns or is a member of', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000909, 0121, 'New Message_groups_parents', 1, 'Manage Messages', 'Include parents in messages posted to groups', 'teachers/other/messenger/#getting-started', 'messenger_post.php', 'messenger_post.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000910, 0008, 'Family Data Updater History', 0, 'Reports', 'Allows users to check, for active families, how recently they have been updated.', NULL, 'report_family_dataUpdaterHistory.php', 'report_family_dataUpdaterHistory.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000911, 0002, 'Manage Users_edit', 0, 'User Management', 'Allows admin to edit any user within the system, but not to delete them.', 'administrators/getting-started/getting-started-with-gibbon/#users', 'user_manage.php, user_manage_add.php, user_manage_edit.php, user_manage_password.php', 'user_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000912, 0003, 'Import From File', 0, 'Data', 'Allows a user to view and run available imports.', NULL, 'import_manage.php,import_run.php,export_run.php,import_history.php,import_history_view.php', 'import_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000914, 0136, 'Manage Substitutes', 0, 'Staff Management', 'Edit information for users who can provide staff coverage.', NULL, 'substitutes_manage.php,substitutes_manage_add.php,substitutes_manage_edit.php,substitutes_manage_delete.php,coverage_availability.php', 'substitutes_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000915, 0136, 'New Absence_mine', 0, 'Absences', 'Allows a user to submit their own staff absences.', NULL, 'absences_add.php', 'absences_add.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000916, 0136, 'New Absence_any', 2, 'Absences', 'Submit staff absences for any user.', NULL, 'absences_add.php', 'absences_add.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000917, 0136, 'View Absences_mine', 0, 'Absences', 'Provides an overview of staff absences for the selected user.', NULL, 'absences_view_byPerson.php,absences_view_details.php', 'absences_view_byPerson.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000918, 0136, 'View Absences_any', 2, 'Absences', 'Provides an overview of staff absences for the selected user.', NULL, 'absences_view_byPerson.php,absences_view_details.php', 'absences_view_byPerson.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000919, 0136, 'Approve Staff Absences', 0, 'Absences', 'Allows users to approve or decline staff absences.', NULL, 'absences_approval.php,absences_approval_action.php', 'absences_approval.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000920, 0136, 'Manage Staff Absences', 0, 'Absences', 'Allows administrators to edit and delete staff absences.', NULL, 'absences_manage.php,absences_manage_edit.php,absences_manage_edit_edit.php,absences_manage_delete.php', 'absences_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000921, 0011, 'Individual Needs Overview', 1, 'Reports', 'Provides a visual graph of individual needs school-wide.', NULL, 'report_graph_overview.php', 'report_graph_overview.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000922, 0136, 'Request Coverage', 0, 'Coverage', 'Allows a staff member to request coverage for their absences.', NULL, 'coverage_request.php,coverage_view_details.php', 'coverage_request.php', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000923, 0136, 'My Coverage', 0, 'Coverage', 'Provides an overview of coverage for staff absences.', NULL, 'coverage_my.php,coverage_view_details.php,coverage_availability.php,coverage_view_cancel.php,coverage_view_edit.php', 'coverage_my.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000924, 0136, 'Open Requests', 0, 'Coverage', 'Users can view and accept any available coverage requests.', NULL, 'coverage_view.php,coverage_view_accept.php,coverage_view_decline.php', 'coverage_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N', 'Y'),
(0000925, 0136, 'Manage Staff Coverage', 0, 'Coverage', 'Allows administrators to manage coverage requests.', NULL, 'coverage_manage.php,coverage_manage_add.php,coverage_manage_edit.php,coverage_manage_delete.php,coverage_view_details.php', 'coverage_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000926, 0136, 'Staff Absence Summary', 0, 'Reports', 'Provides an overview of staff absences for the school year.', NULL, 'report_absences_summary.php', 'report_absences_summary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000927, 0136, 'Weekly Absences', 0, 'Reports', 'A week-by-week overview of staff absences.', NULL, 'report_absences_weekly.php', 'report_absences_weekly.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000928, 0136, 'Substitute Availability', 0, 'Coverage', 'Allows users to view the availability of subs by date.', NULL, 'report_subs_availability.php', 'report_subs_availability.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000929, 0005, 'View Student Profile_fullEditAllNotes', 4, 'Profiles', 'View full profile of any student in the school, with access to edit and delete notes.', 'teachers/people/student-profiles/', 'student_view.php,student_view_details.php,student_view_details_notes_add.php,student_view_details_notes_edit.php,student_view_details_notes_delete.php', 'student_view.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000930, 0006, 'Consecutive Absences', 0, 'Reports', 'Returns a list of students who have had a specified number of consecutive days absent.', NULL, 'report_consecutiveAbsences.php', 'report_consecutiveAbsences.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000931, 0002, 'Manage Roles_viewOnly', 0, 'User Management', 'Allows a user to view roles but not change them.', 'administrators/getting-started/getting-started-with-gibbon/#user-management-access', 'role_manage.php,role_manage_view.php', 'role_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000932, 0009, 'Lesson Planner_viewOnly', 2, 'Planning', 'View all planner information for all classes.', 'teachers/learn/planner/lesson-planner/', 'planner.php,planner_view_full.php,planner_unitOverview.php,planner_deadlines.php', 'planner.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000933, 0006, 'Students Not In Class', 0, 'Reports', 'Generate a report of students who are not present in class on a given day.', NULL, 'report_studentsNotInClass_byDate.php', 'report_studentsNotInClass_byDate.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000934, 0136, 'Staff Coverage Summary', 0, 'Reports', 'Provides an overview of staff coverage for the school year.', NULL, 'report_coverage_summary.php', 'report_coverage_summary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'Y'),
(0000935, 0011, 'Manage Investigations_all', 1, 'Investigation', 'Allow a user to manage all Indiviudal Needs investigations.', NULL, 'investigations_manage.php,investigations_manage_add.php,investigations_manage_edit.php,investigations_manage_delete.php', 'investigations_manage.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000936, 0011, 'Manage Investigations_my', 0, 'Investigation', 'Allow a user to manage their own Indiviudal Needs investigations.', NULL, 'investigations_manage.php,investigations_manage_add.php,investigations_manage_edit.php', 'investigations_manage.php', 'Y', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000937, 0011, 'Submit Contributions', 0, 'Investigation', 'Allows Class Teachers and Heads of Year to contribute to investigations.', NULL, 'investigations_submit.php,investigations_submit_detail.php', 'investigations_submit.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000938, 0144, 'Manage Reporting Cycles', 0, 'Administration', 'Manage the names, dates and scopes of reporting cycles.', 'administrators/reports/reporting_cycles/', 'reporting_cycles_manage.php,reporting_cycles_manage_add.php,reporting_cycles_manage_edit.php,reporting_cycles_manage_delete.php,reporting_cycles_manage_duplicate.php,reporting_scopes_manage.php,reporting_scopes_manage_add.php,reporting_scopes_manage_edit.php,reporting_scopes_manage_delete.php', 'reporting_cycles_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000939, 0144, 'Manage Criteria', 0, 'Administration', 'Manage the types of data which are recorded per reporting cycle.', NULL, 'reporting_criteria_manage.php,reporting_criteria_manage_add.php,reporting_criteria_manage_addMultiple.php,reporting_criteria_manage_edit.php,reporting_criteria_manage_delete.php,criteriaTypes_manage.php,criteriaTypes_manage_add.php,criteriaTypes_manage_edit.php,criteriaTypes_manage_delete.php', 'reporting_criteria_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000940, 0144, 'Manage Access', 0, 'Administration', 'Manage when and how users can access active reporting cycles.', NULL, 'reporting_access_manage.php,reporting_access_manage_add.php,reporting_access_manage_edit.php,reporting_access_manage_delete.php', 'reporting_access_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000941, 0144, 'Report Settings', 0, 'Administration', '', NULL, 'settings.php', 'settings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000942, 0144, 'Manage Archives', 0, 'Archive', 'Manage the filesystem locations for saving PDF reports.', NULL, 'archive_manage.php,archive_manage_add.php,archive_manage_edit.php,archive_manage_delete.php,archive_manage_migrate.php', 'archive_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000943, 0144, 'View by Student', 2, 'Archive', 'Allows users to view and download a student\'s report PDFs.', NULL, 'archive_byStudent.php,archive_byStudent_view.php,archive_byStudent_download.php', 'archive_byStudent.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000944, 0144, 'View by Report', 2, 'Archive', 'Allows users to view and download batched report PDFs.', NULL, 'archive_byReport.php,archive_byReport_view.php,archive_byReport_download.php', 'archive_byReport.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000945, 0144, 'View Reports_myChildren', 1, 'Archive', 'Allows parents to view their children\'s report archive.', NULL, 'archive_byFamily.php,archive_byStudent_view.php,archive_byStudent_download.php', 'archive_byFamily.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'Y'),
(0000946, 0144, 'View Reports_mine', 0, 'Archive', 'Allows students to view their own report archive.', NULL, 'archive_byStudent_view.php,archive_byStudent_download.php', 'archive_byStudent_view.php', 'Y', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N'),
(0000947, 0144, 'View Draft Reports', 0, 'Archive', 'Allows users to view draft reports in the archive.', NULL, 'archive_byReport.php,archive_byStudent.php', 'archive_byReport.php', 'Y', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000948, 0144, 'View Past Reports', 0, 'Archive', 'Allows users to view past reports in the archive.', NULL, 'archive_byReport.php,archive_byStudent.php', 'archive_byReport.php', 'Y', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y'),
(0000949, 0144, 'My Reporting', 0, 'Contribute', 'Provides a progress view of any active reports for this user.', NULL, 'reporting_my.php', 'reporting_my.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000950, 0144, 'Generate Reports', 0, 'Publish', 'Allows users to generate PDF reports in batches or one at a time.', NULL, 'reports_generate.php,reports_generate_batch.php,reports_generate_single.php', 'reports_generate.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000951, 0144, 'Manage Reports', 0, 'Publish', 'Allows users to manage the types of reports that can be generated.', NULL, 'reports_manage.php,reports_manage_add.php,reports_manage_edit.php,reports_manage_delete.php', 'reports_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000952, 0144, 'Template Builder', 0, 'Publish', 'Design report templates using a set of components.', 'administrators/reports/templates/', 'templates_preview.php,templates_manage.php,templates_manage_add.php,templates_manage_edit.php,templates_manage_duplicate.php,templates_manage_delete.php,templates_manage_section_add.php,templates_manage_section_edit.php,templates_manage_section_delete.php,templates_assets.php,templates_assets_components_preview.php,templates_assets_components_add.php,templates_assets_components_edit.php,templates_assets_components_delete.php,templates_assets_components_duplicate.php,templates_assets_fonts_preview.php,templates_assets_fonts_edit.php', 'templates_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000953, 0144, 'Proof Read_all', 0, 'Contribute', 'Allows users to proof-read reporting comments for any active reporting cycle.', NULL, 'reporting_proofread.php', 'reporting_proofread.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000954, 0144, 'Proof Read_mine', 0, 'Contribute', 'Allows users to proof-read reporting comments for reports they have access to.', NULL, 'reporting_proofread.php', 'reporting_proofread.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000955, 0144, 'Send Notifications', 0, 'Administration', 'Allows users to select and send a pre-defined notification to other users.', NULL, 'notification_send.php', 'notification_send.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000956, 0144, 'Progress by Reporting Cycle', 0, 'Progress', 'View report writing progress by reporting cycle.', NULL, 'progress_byReportingCycle.php', 'progress_byReportingCycle.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000957, 0144, 'Progress by Person', 0, 'Progress', 'View report writing progress by person.', NULL, 'progress_byPerson.php', 'progress_byPerson.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000958, 0144, 'Proof Reading Progress', 0, 'Progress', 'View proof-reading completion by reporting cycle.', NULL, 'progress_byProofReading.php', 'progress_byProofReading.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000959, 0144, 'Write Reports_editAll', 2, 'Contribute', 'View and edit all reports, even after they are closed.', NULL, 'reporting_write.php,reporting_write_byStudent.php', 'reporting_write.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000960, 0144, 'Write Reports_mine', 1, 'Contribute', 'View and edit only the reports this user has access to.', NULL, 'reporting_write.php,reporting_write_byStudent.php', 'reporting_write.php', 'Y', 'Y', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000961, 0145, 'Withdraw Student', 0, 'Current Students', 'Enables admin to set a student to left and notify other users.', NULL, 'student_withdraw.php', 'student_withdraw.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000962, 0144, 'Upload Reports', 0, 'Archive', 'Enables users to upload reports from a ZIP archive.', NULL, 'archive_manage_upload.php,archive_manage_uploadPreview.php', 'archive_manage_upload.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000963, 0003, 'View Logs', 0, 'Data', 'Enables users to browse Gibbon\'s event log.', NULL, 'logs_view.php', 'logs_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000964, 0137, 'View Form Groups_myChildren', 0, 'Form Groups', 'View the form groups in which a user\'s children study.', NULL, 'formGroups.php,formGroups_details.php', 'formGroups.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', 'N'),
(0000965, 0003, 'System Overview', 0, 'System', '', NULL, 'systemOverview.php', 'systemOverview.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000966, 0003, 'Manage Services', 0, 'Extend & Update', '', NULL, 'services_manage.php', 'services_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000967, 0001, 'Email Summary Settings', 0, 'Other', '', NULL, 'emailSummarySettings.php', 'emailSummarySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000968, 0001, 'Manage Medical Conditions', 0, 'People', 'Manage the list of medical conditions that can be attached to student medical records.', NULL, 'medicalConditions_manage.php,medicalConditions_manage_add.php,medicalConditions_manage_edit.php,medicalConditions_manage_delete.php', 'medicalConditions_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000969, 0003, 'Email Templates', 0, 'Customise', '', NULL, 'emailTemplates_manage.php,emailTemplates_manage_duplicate.php,emailTemplates_manage_edit.php,emailTemplates_manage_delete.php', 'emailTemplates_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000970, 0144, 'Send Reports', 0, 'Publish', '', NULL, 'reports_send.php,reports_send_batch.php', 'reports_send.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000971, 0005, 'My Student History', 0, 'Visualise', '', NULL, 'report_myStudentHistory.php', 'report_myStudentHistory.php', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000972, 0003, 'Server Info', 0, 'System', '', NULL, 'serverInfo.php', 'serverInfo.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000973, 0003, 'Cache Manager', 0, 'Utilities', '', NULL, 'cacheManager.php', 'cacheManager.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000974, 0003, 'Security & Privacy Settings', 0, 'Settings', 'Manage settings related to user security and privacy.', NULL, 'privacySettings.php', 'privacySettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000975, 0003, 'Data Retention', 0, 'Utilities', 'Comply with privacy regulations by flushing older, non-academic, data from the system.', NULL, 'dataRetention.php', 'dataRetention.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000976, 0008, 'Update Staff Data_any', 1, 'Request Updates', 'Create staff data update request for any user', NULL, 'data_staff.php', 'data_staff.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000977, 0008, 'Update Staff Data_my', 0, 'Request Updates', 'Allows users to create data update request for their staff record.', NULL, 'data_staff.php', 'data_staff.php', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'N', 'Y', 'N'),
(0000978, 0008, 'Staff Data Updates', 0, 'Manage Updates', 'Manage requests for updates to staff data.', NULL, 'data_staff_manage.php,data_staff_manage_edit.php,data_staff_manage_delete.php', 'data_staff_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000979, 0002, 'Personal Document Settings', 0, 'User Settings', 'Manage types of personal documents users can upload.', NULL, 'personalDocumentSettings.php,personalDocumentSettings_manage_add.php,personalDocumentSettings_manage_edit.php,personalDocumentSettings_manage_delete.php', 'personalDocumentSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000980, 0005, 'Personal Document Summary', 0, 'Reports', 'Allows users to view a summary of student personal documents.', NULL, 'report_student_personalDocumentSummary.php', 'report_student_personalDocumentSummary.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000981, 0144, 'Student Name Conflicts', 0, 'Progress', 'Allows users to check report comments for mismatched names.', NULL, 'progress_studentNameConflicts.php', 'progress_studentNameConflicts.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000982, 0006, 'Ad Hoc Attendance', 0, 'Take Attendance', 'Allows users to take school-wide attendance for ad hoc groups of students.', NULL, 'attendance_take_adHoc.php', 'attendance_take_adHoc.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000983, 0003, 'Active Sessions', 0, 'Utilities', '', NULL, 'activeSessions.php', 'activeSessions.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000984, 0003, 'Upload Photos & Files', 0, 'Data', '', NULL, 'file_upload.php', 'file_upload.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000985, 0145, 'Admissions Accounts', 0, 'Prospective Students', '', NULL, 'admissions_manage.php,admissions_manage_add.php,admissions_manage_edit.php,admissions_manage_delete.php', 'admissions_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000986, 0145, 'Manage Applications', 0, 'Prospective Students', '', NULL, 'applications_manage.php,applications_manage_add.php,applications_manage_edit.php,applications_manage_delete.php,applications_manage_accept.php,applications_manage_reject.php,applications_manage_view.php', 'applications_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000987, 0145, 'Manage Other Forms', 0, 'Prospective Students', '', NULL, 'forms_manage.php,forms_manage_add.php,forms_manage_edit.php,forms_manage_delete.php,forms_manage_accept.php,forms_manage_reject.php,forms_manage_view.php', 'forms_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000988, 0003, 'Form Builder', 0, 'Customise', '', NULL, 'formBuilder.php,formBuilder_preview.php,formBuilder_add.php,formBuilder_edit.php,formBuilder_delete.php,formBuilder_duplicate.php,formBuilder_page_add.php,formBuilder_page_edit.php,formBuilder_page_delete.php,formBuilder_page_edit_field_add.php,formBuilder_page_edit_field_edit.php,formBuilder_page_edit_field_delete.php', 'formBuilder.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `helpURL`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES
(0000989, 0001, 'Admissions Settings', 0, 'People', '', NULL, 'admissions_settings.php', 'admissions_settings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000990, 0145, 'My Application Forms', 0, 'Admissions', '', NULL, 'applicationForm.php,applicationFormView.php', 'applicationFormView.php', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'Y', 'Y', 'Y', 'N'),
(0000991, 0121, 'New Message_confidential', 0, 'Manage Messages', 'Enable users to send confidential messages which are not listed in Manage Messages for any other user.', NULL, 'messenger_post.php', 'messenger_post.php', 'Y', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000992, 0013, 'Timetable Settings', 0, 'Settings', 'Manage settings to control timetable functionality.', NULL, 'ttSettings.php', 'ttSettings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000993, 0136, 'Duty Schedule_edit', 0, 'Reports', 'Manage the staff duty schedule.', NULL, 'staff_duty.php,staff_duty_edit.php', 'staff_duty.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000994, 0136, 'Duty Schedule_view', 0, 'Reports', 'View the staff duty schedule.', NULL, 'staff_duty.php', 'staff_duty.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000995, 0136, 'Daily Coverage Planner', 0, 'Coverage', '', NULL, 'coverage_planner.php,coverage_planner_assign.php,coverage_planner_unassign.php', 'coverage_planner.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000996, 0005, 'First Aid Record_viewOnlyAddNotes', 0, 'Medical', 'Allows user to view first aid records and add incident notes, but not add or edit records.', NULL, 'firstAidRecord.php,firstAidRecord_edit.php', 'firstAidRecord.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000997, 0130, 'Manage Library Shelves', 0, 'Catalog', 'Allows user to manage shelves displaying library items for library browsing.', NULL, 'library_manage_shelves.php, library_manage_shelves_add.php, library_manage_shelves_edit.php, library_manage_shelves_delete.php', 'library_manage_shelves.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N'),
(0000998, 0119, 'View Behaviour Records_my', 0, 'Behaviour Records', 'Enables users to view the behaviour records created by them', NULL, 'behaviour_view.php,behaviour_view_details.php', 'behaviour_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivity`
--

CREATE TABLE `gibbonActivity` (
  `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL DEFAULT '000',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `registration` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT 'Can a parent/student select this for registration?',
  `name` varchar(40) NOT NULL DEFAULT '',
  `provider` enum('School','External') NOT NULL DEFAULT 'School',
  `type` varchar(255) NOT NULL,
  `gibbonSchoolYearTermIDList` text NOT NULL,
  `listingStart` date DEFAULT NULL,
  `listingEnd` date DEFAULT NULL,
  `programStart` date DEFAULT NULL,
  `programEnd` date DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) NOT NULL DEFAULT '',
  `maxParticipants` int(3) NOT NULL DEFAULT '0',
  `description` text,
  `payment` decimal(8,2) DEFAULT NULL,
  `paymentType` enum('Entire Programme','Per Session','Per Week','Per Term') DEFAULT 'Entire Programme',
  `paymentFirmness` enum('Finalised','Estimated') DEFAULT 'Finalised'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivityAttendance`
--

CREATE TABLE `gibbonActivityAttendance` (
  `gibbonActivityAttendanceID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDTaker` int(10) UNSIGNED ZEROFILL NOT NULL,
  `attendance` text NOT NULL,
  `date` date DEFAULT NULL,
  `timestampTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivitySlot`
--

CREATE TABLE `gibbonActivitySlot` (
  `gibbonActivitySlotID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSpaceID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `locationExternal` varchar(50) NOT NULL,
  `gibbonDaysOfWeekID` int(2) UNSIGNED ZEROFILL NOT NULL,
  `timeStart` time NOT NULL,
  `timeEnd` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivityStaff`
--

CREATE TABLE `gibbonActivityStaff` (
  `gibbonActivityStaffID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL DEFAULT '00000000',
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL DEFAULT '0000000000',
  `role` enum('Organiser','Coach','Assistant','Other') NOT NULL DEFAULT 'Organiser'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivityStudent`
--

CREATE TABLE `gibbonActivityStudent` (
  `gibbonActivityStudentID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL DEFAULT '00000000',
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL DEFAULT '0000000000',
  `status` enum('Accepted','Pending','Waiting List','Not Accepted','Left') NOT NULL DEFAULT 'Pending',
  `timestamp` datetime NOT NULL,
  `gibbonActivityIDBackup` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `invoiceGenerated` enum('N','Y') NOT NULL DEFAULT 'N',
  `gibbonFinanceInvoiceID` int(14) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonActivityType`
--

CREATE TABLE `gibbonActivityType` (
  `gibbonActivityTypeID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) DEFAULT NULL,
  `description` text,
  `access` enum('None','View','Register') DEFAULT 'Register',
  `enrolmentType` enum('Competitive','Selection') DEFAULT 'Competitive',
  `maxPerStudent` int(3) NOT NULL DEFAULT '0',
  `waitingList` enum('Y','N') DEFAULT 'Y',
  `backupChoice` enum('Y','N') DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAdmissionsAccount`
--

CREATE TABLE `gibbonAdmissionsAccount` (
  `gibbonAdmissionsAccountID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `ipAddress` varchar(40) DEFAULT NULL,
  `accessID` varchar(64) DEFAULT NULL,
  `accessToken` varchar(64) DEFAULT NULL,
  `timestampTokenExpire` timestamp NULL DEFAULT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampActive` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAdmissionsApplication`
--

CREATE TABLE `gibbonAdmissionsApplication` (
  `gibbonAdmissionsApplicationID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormPageID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `foreignTable` varchar(60) DEFAULT NULL,
  `foreignTableID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Incomplete','Pending','Waiting List','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Incomplete',
  `priority` int(1) NOT NULL DEFAULT '0',
  `owner` varchar(75) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `data` mediumtext,
  `result` text,
  `milestones` text,
  `gibbonPaymentIDSubmit` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPaymentIDProcess` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `timestampModified` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDModified` int(10) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAlarm`
--

CREATE TABLE `gibbonAlarm` (
  `gibbonAlarmID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('General','Lockdown','Custom') DEFAULT NULL,
  `status` enum('Current','Past') NOT NULL DEFAULT 'Past',
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampStart` timestamp NULL DEFAULT NULL,
  `timestampEnd` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAlarmConfirm`
--

CREATE TABLE `gibbonAlarmConfirm` (
  `gibbonAlarmConfirmID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonAlarmID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAlertLevel`
--

CREATE TABLE `gibbonAlertLevel` (
  `gibbonAlertLevelID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `color` varchar(7) NOT NULL COMMENT 'RGB Hex, leading #',
  `colorBG` varchar(7) NOT NULL COMMENT 'RGB Hex, leading #',
  `description` text NOT NULL,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonAlertLevel`
--

INSERT INTO `gibbonAlertLevel` (`gibbonAlertLevelID`, `name`, `nameShort`, `color`, `colorBG`, `description`, `sequenceNumber`) VALUES
(001, 'High', 'H', '#CC0000', '#F6CECB', 'Highest level of severity, requiring intense and immediate readiness, action, individual support or differentiation.', 3),
(002, 'Medium', 'M', '#FF7414', '#FFD2A9', 'Moderate severity, requiring intermediate level of readiness, action, individual support or differentiation.', 2),
(003, 'Low', 'L', '#939090', '#dddddd', 'Low severity, requiring little to no readiness, action, individual support or differentiation.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonApplicationForm`
--

CREATE TABLE `gibbonApplicationForm` (
  `gibbonApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonApplicationFormHash` varchar(40) DEFAULT NULL,
  `surname` varchar(60) NOT NULL DEFAULT '',
  `firstName` varchar(60) NOT NULL DEFAULT '',
  `preferredName` varchar(60) NOT NULL DEFAULT '',
  `officialName` varchar(150) NOT NULL,
  `nameInCharacters` varchar(20) NOT NULL,
  `gender` enum('M','F','Other','Unspecified') NOT NULL DEFAULT 'Unspecified',
  `username` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Waiting List','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  `dob` date DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `homeAddress` mediumtext,
  `homeAddressDistrict` varchar(255) DEFAULT NULL,
  `homeAddressCountry` varchar(255) DEFAULT NULL,
  `phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone1CountryCode` varchar(7) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone2CountryCode` varchar(7) NOT NULL,
  `phone2` varchar(20) NOT NULL,
  `countryOfBirth` varchar(30) NOT NULL,
  `gibbonSchoolYearIDEntry` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonYearGroupIDEntry` int(3) UNSIGNED ZEROFILL NOT NULL,
  `dayType` varchar(255) DEFAULT NULL,
  `referenceEmail` varchar(100) DEFAULT NULL,
  `schoolName1` varchar(50) NOT NULL,
  `schoolAddress1` varchar(255) NOT NULL,
  `schoolGrades1` varchar(20) NOT NULL,
  `schoolLanguage1` varchar(50) NOT NULL,
  `schoolDate1` date DEFAULT NULL,
  `schoolName2` varchar(50) NOT NULL,
  `schoolAddress2` varchar(255) NOT NULL,
  `schoolGrades2` varchar(20) NOT NULL,
  `schoolLanguage2` varchar(50) NOT NULL,
  `schoolDate2` date DEFAULT NULL,
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL DEFAULT NULL,
  `siblingName1` varchar(50) NOT NULL,
  `siblingDOB1` date DEFAULT NULL,
  `siblingSchool1` varchar(50) NOT NULL,
  `siblingSchoolJoiningDate1` date DEFAULT NULL,
  `siblingName2` varchar(50) NOT NULL,
  `siblingDOB2` date DEFAULT NULL,
  `siblingSchool2` varchar(50) NOT NULL,
  `siblingSchoolJoiningDate2` date DEFAULT NULL,
  `siblingName3` varchar(50) NOT NULL,
  `siblingDOB3` date DEFAULT NULL,
  `siblingSchool3` varchar(50) NOT NULL,
  `siblingSchoolJoiningDate3` date DEFAULT NULL,
  `languageHomePrimary` varchar(30) NOT NULL,
  `languageHomeSecondary` varchar(30) NOT NULL,
  `languageFirst` varchar(30) NOT NULL,
  `languageSecond` varchar(30) NOT NULL,
  `languageThird` varchar(30) NOT NULL,
  `medicalInformation` text NOT NULL,
  `sen` enum('N','Y') DEFAULT NULL,
  `senDetails` text NOT NULL,
  `languageChoice` varchar(100) DEFAULT NULL,
  `languageChoiceExperience` text,
  `scholarshipInterest` enum('N','Y') NOT NULL DEFAULT 'N',
  `scholarshipRequired` enum('N','Y') NOT NULL DEFAULT 'N',
  `payment` enum('Family','Company') NOT NULL DEFAULT 'Family',
  `companyName` varchar(100) DEFAULT NULL,
  `companyContact` varchar(100) DEFAULT NULL,
  `companyAddress` varchar(255) DEFAULT NULL,
  `companyEmail` text,
  `companyCCFamily` enum('N','Y') DEFAULT NULL COMMENT 'When company is billed, should family receive a copy?',
  `companyPhone` varchar(20) DEFAULT NULL,
  `companyAll` enum('Y','N') DEFAULT NULL,
  `gibbonFinanceFeeCategoryIDList` text,
  `agreement` enum('N','Y') DEFAULT NULL,
  `parent1gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `parent1title` varchar(5) DEFAULT NULL,
  `parent1surname` varchar(60) DEFAULT '',
  `parent1firstName` varchar(60) DEFAULT '',
  `parent1preferredName` varchar(60) DEFAULT '',
  `parent1officialName` varchar(150) DEFAULT NULL,
  `parent1nameInCharacters` varchar(20) DEFAULT NULL,
  `parent1gender` enum('M','F','Other','Unspecified') DEFAULT 'Unspecified',
  `parent1relationship` varchar(50) DEFAULT NULL,
  `parent1languageFirst` varchar(30) DEFAULT NULL,
  `parent1languageSecond` varchar(30) DEFAULT NULL,
  `parent1email` varchar(75) DEFAULT NULL,
  `parent1phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') DEFAULT '',
  `parent1phone1CountryCode` varchar(7) DEFAULT NULL,
  `parent1phone1` varchar(20) DEFAULT NULL,
  `parent1phone2Type` enum('','Mobile','Home','Work','Fax','Pager','Other') DEFAULT '',
  `parent1phone2CountryCode` varchar(7) DEFAULT NULL,
  `parent1phone2` varchar(20) DEFAULT NULL,
  `parent1profession` varchar(30) DEFAULT NULL,
  `parent1employer` varchar(30) DEFAULT NULL,
  `parent2title` varchar(5) DEFAULT NULL,
  `parent2surname` varchar(60) DEFAULT '',
  `parent2firstName` varchar(60) DEFAULT '',
  `parent2preferredName` varchar(60) DEFAULT '',
  `parent2officialName` varchar(150) DEFAULT NULL,
  `parent2nameInCharacters` varchar(20) DEFAULT NULL,
  `parent2gender` enum('M','F','Other','Unspecified') DEFAULT 'Unspecified',
  `parent2relationship` varchar(50) DEFAULT NULL,
  `parent2languageFirst` varchar(30) DEFAULT NULL,
  `parent2languageSecond` varchar(30) DEFAULT NULL,
  `parent2email` varchar(75) DEFAULT NULL,
  `parent2phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') DEFAULT '',
  `parent2phone1CountryCode` varchar(7) DEFAULT NULL,
  `parent2phone1` varchar(20) DEFAULT NULL,
  `parent2phone2Type` enum('','Mobile','Home','Work','Fax','Pager','Other') DEFAULT '',
  `parent2phone2CountryCode` varchar(7) DEFAULT NULL,
  `parent2phone2` varchar(20) DEFAULT NULL,
  `parent2profession` varchar(30) DEFAULT NULL,
  `parent2employer` varchar(30) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` int(1) NOT NULL DEFAULT '0',
  `milestones` text NOT NULL,
  `notes` text NOT NULL,
  `dateStart` date DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `howDidYouHear` varchar(255) DEFAULT NULL,
  `howDidYouHearMore` varchar(255) DEFAULT NULL,
  `paymentMade` enum('N','Y','Exemption') NOT NULL DEFAULT 'N',
  `paymentMade2` enum('N','Y','Exemption') NOT NULL DEFAULT 'N',
  `gibbonPaymentID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPaymentID2` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `studentID` varchar(10) DEFAULT NULL,
  `privacy` text,
  `fields` text NOT NULL COMMENT 'Serialised array of custom field values',
  `parent1fields` text NOT NULL COMMENT 'Serialised array of custom field values',
  `parent2fields` text NOT NULL COMMENT 'Serialised array of custom field values'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonApplicationFormFile`
--

CREATE TABLE `gibbonApplicationFormFile` (
  `gibbonApplicationFormFileID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonApplicationFormLink`
--

CREATE TABLE `gibbonApplicationFormLink` (
  `gibbonApplicationFormLinkID` int(12) UNSIGNED NOT NULL,
  `gibbonApplicationFormID1` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonApplicationFormID2` int(12) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonApplicationFormRelationship`
--

CREATE TABLE `gibbonApplicationFormRelationship` (
  `gibbonApplicationFormRelationshipID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `relationship` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAttendanceCode`
--

CREATE TABLE `gibbonAttendanceCode` (
  `gibbonAttendanceCodeID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `type` enum('Core','Additional') NOT NULL,
  `direction` enum('In','Out') NOT NULL,
  `scope` enum('Onsite','Onsite - Late','Offsite','Offsite - Left','Offsite - Late') NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `reportable` enum('Y','N') NOT NULL,
  `future` enum('Y','N') NOT NULL,
  `prefill` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonRoleIDAll` varchar(90) NOT NULL,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonAttendanceCode`
--

INSERT INTO `gibbonAttendanceCode` (`gibbonAttendanceCodeID`, `name`, `nameShort`, `type`, `direction`, `scope`, `active`, `reportable`, `future`, `prefill`, `gibbonRoleIDAll`, `sequenceNumber`) VALUES
(001, 'Present', 'P', 'Core', 'In', 'Onsite', 'Y', 'Y', 'N', 'Y', '001,002,006', 1),
(002, 'Present - Late', 'PL', 'Core', 'In', 'Onsite - Late', 'Y', 'Y', 'N', 'N', '001,002,006', 2),
(003, 'Present - Offsite', 'PS', 'Core', 'In', 'Offsite', 'Y', 'Y', 'Y', 'Y', '001,002,006', 3),
(004, 'Absent', 'A', 'Core', 'Out', 'Offsite', 'Y', 'Y', 'Y', 'Y', '001,002,006', 4),
(005, 'Left', 'L', 'Core', 'Out', 'Offsite - Left', 'Y', 'Y', 'N', 'Y', '001,002,006', 5),
(006, 'Left - Early', 'LE', 'Core', 'Out', 'Offsite - Left', 'Y', 'Y', 'N', 'Y', '001,002,006', 6);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAttendanceLogCourseClass`
--

CREATE TABLE `gibbonAttendanceLogCourseClass` (
  `gibbonAttendanceLogCourseClassID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDTaker` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date DEFAULT NULL,
  `timestampTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAttendanceLogFormGroup`
--

CREATE TABLE `gibbonAttendanceLogFormGroup` (
  `gibbonAttendanceLogFormGroupID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDTaker` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date DEFAULT NULL,
  `timestampTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonAttendanceLogPerson`
--

CREATE TABLE `gibbonAttendanceLogPerson` (
  `gibbonAttendanceLogPersonID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonAttendanceCodeID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `direction` enum('In','Out') NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT '',
  `reason` varchar(30) NOT NULL DEFAULT '',
  `context` enum('Form Group','Class','Person','Future','Self Registration') DEFAULT NULL,
  `comment` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `gibbonPersonIDTaker` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampTaken` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonBehaviour`
--

CREATE TABLE `gibbonBehaviour` (
  `gibbonBehaviourID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `date` date NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Positive','Negative') CHARACTER SET utf8 NOT NULL,
  `descriptor` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `level` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL,
  `followup` text COLLATE utf8_unicode_ci NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fields` text COLLATE utf8_unicode_ci,
  `gibbonMultiIncidentID` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonBehaviourFollowUp`
--

CREATE TABLE `gibbonBehaviourFollowUp` (
  `gibbonBehaviourFollowUpID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonBehaviourID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `followUp` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonBehaviourLetter`
--

CREATE TABLE `gibbonBehaviourLetter` (
  `gibbonBehaviourLetterID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `letterLevel` enum('1','2','3') NOT NULL,
  `status` enum('Warning','Issued') NOT NULL,
  `type` enum('Negative','Positive') NOT NULL DEFAULT 'Negative',
  `recordCountAtCreation` int(3) NOT NULL,
  `body` text NOT NULL,
  `recipientList` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCountry`
--

CREATE TABLE `gibbonCountry` (
  `printable_name` varchar(80) NOT NULL,
  `iddCountryCode` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonCountry`
--

INSERT INTO `gibbonCountry` (`printable_name`, `iddCountryCode`) VALUES
('Afghanistan', '93'),
('Albania', '355'),
('Algeria', '213'),
('American Samoa', '1 684'),
('Andorra', '376'),
('Angola', '244'),
('Anguilla', '1 264'),
('Antarctica', '672'),
('Antigua and Barbuda', '1 268'),
('Argentina', '54'),
('Armenia', '374'),
('Aruba', '297'),
('Australia', '61'),
('Austria', '43'),
('Azerbaijan', '994'),
('Bahamas', '1 242'),
('Bahrain', '973'),
('Bangladesh', '880'),
('Barbados', '1 246'),
('Belarus', '375'),
('Belgium', '32'),
('Belize', '501'),
('Benin', '229'),
('Bermuda', '1 441'),
('Bhutan', '975'),
('Bolivia', '591'),
('Bosnia and Herzegovina', '387'),
('Botswana', '267'),
('Bouvet Island', ''),
('Brazil', '55'),
('British Indian Ocean Territory', ''),
('Brunei Darussalam', ''),
('Bulgaria', '359'),
('Burkina Faso', '226'),
('Burundi', '257'),
('Cambodia', '855'),
('Cameroon', '237'),
('Canada', '1'),
('Cape Verde', '238'),
('Cayman Islands', '1 345'),
('Central African Republic', '236'),
('Chad', '235'),
('Chile', '56'),
('China', '86'),
('Christmas Island', '61'),
('Cocos (Keeling) Islands', '61'),
('Colombia', '57'),
('Comoros', '269'),
('Congo', ''),
('Congo, the Democratic Republic of the', ''),
('Cook Islands', '682'),
('Costa Rica', '506'),
('Croatia', '385'),
('Cuba', '53'),
('Cyprus', '357'),
('Czech Republic', '420'),
('Denmark', '45'),
('Djibouti', '253'),
('Dominica', '1 767'),
('Dominican Republic', '1 809'),
('Ecuador', '593'),
('Egypt', '20'),
('El Salvador', '503'),
('Equatorial Guinea', '240'),
('Eritrea', '291'),
('Estonia', '372'),
('Ethiopia', '251'),
('Falkland Islands (Malvinas)', ''),
('Faroe Islands', '298'),
('Fiji', '679'),
('Finland', '358'),
('France', '33'),
('French Guiana', ''),
('French Polynesia', '689'),
('French Southern Territories', ''),
('Gabon', '241'),
('Gambia', '220'),
('Georgia', '995'),
('Germany', '49'),
('Ghana', '233'),
('Gibraltar', '350'),
('Greece', '30'),
('Greenland', '299'),
('Grenada', '1 473'),
('Guadeloupe', ''),
('Guam', '1 671'),
('Guatemala', '502'),
('Guinea', '224'),
('Guinea-Bissau', '245'),
('Guyana', '592'),
('Haiti', '509'),
('Heard Island and Mcdonald Islands', ''),
('Holy See (Vatican City State)', ''),
('Honduras', '504'),
('Hong Kong', '852'),
('Hungary', '36'),
('Iceland', '354'),
('India', '91'),
('Indonesia', '62'),
('Iran, Islamic Republic of', ''),
('Iraq', '964'),
('Ireland', '353'),
('Israel', '972'),
('Italy', '39'),
('Ivory Coast (Côte d\'Ivoire)', '225'),
('Jamaica', '1 876'),
('Japan', '81'),
('Jordan', '962'),
('Kazakhstan', '7'),
('Kenya', '254'),
('Kiribati', '686'),
('Korea, Democratic People\'s Republic of', ''),
('Korea, Republic of', ''),
('Kuwait', '965'),
('Kyrgyzstan', '996'),
('Lao People\'s Democratic Republic', '856'),
('Latvia', '371'),
('Lebanon', '961'),
('Lesotho', '266'),
('Liberia', '231'),
('Libya', '218'),
('Liechtenstein', '423'),
('Lithuania', '370'),
('Luxembourg', '352'),
('Macao', '853'),
('Macedonia, the Former Yugoslav Republic of', ''),
('Madagascar', '261'),
('Malawi', '265'),
('Malaysia', '60'),
('Maldives', '960'),
('Mali', '223'),
('Malta', '356'),
('Marshall Islands', '692'),
('Martinique', ''),
('Mauritania', '222'),
('Mauritius', '230'),
('Mayotte', '262'),
('Mexico', '52'),
('Micronesia, Federated States of', ''),
('Moldova, Republic of', ''),
('Monaco', '377'),
('Mongolia', '976'),
('Montserrat', '1 664'),
('Morocco', '212'),
('Mozambique', '258'),
('Myanmar', '95'),
('Namibia', '264'),
('Nauru', '674'),
('Nepal', '977'),
('Netherlands', '31'),
('Netherlands Antilles', '599'),
('New Caledonia', '687'),
('New Zealand', '64'),
('Nicaragua', '505'),
('Niger', '227'),
('Nigeria', '234'),
('Niue', '683'),
('Norfolk Island', '672'),
('Northern Mariana Islands', '1 670'),
('Norway', '47'),
('Oman', '968'),
('Pakistan', '92'),
('Palau', '680'),
('Palestinian Territory, Occupied', ''),
('Panama', '507'),
('Papua New Guinea', '675'),
('Paraguay', '595'),
('Peru', '51'),
('Philippines', '63'),
('Pitcairn', ''),
('Poland', '48'),
('Portugal', '351'),
('Puerto Rico', '1'),
('Qatar', '974'),
('Reunion', ''),
('Romania', '40'),
('Russia', '7'),
('Rwanda', '250'),
('Saint Helena', '290'),
('Saint Kitts and Nevis', '1 869'),
('Saint Lucia', '1 758'),
('Saint Pierre and Miquelon', '508'),
('Saint Vincent and the Grenadines', '1 784'),
('Samoa', '685'),
('San Marino', '378'),
('Sao Tome and Principe', '239'),
('Saudi Arabia', '966'),
('Senegal', '221'),
('Serbia and Montenegro', ''),
('Seychelles', '248'),
('Sierra Leone', '232'),
('Singapore', '65'),
('Slovakia', '421'),
('Slovenia', '386'),
('Solomon Islands', '677'),
('Somalia', '252'),
('South Africa', '27'),
('South Georgia and the South Sandwich Islands', ''),
('South Sudan', '211'),
('Spain', '34'),
('Sri Lanka', '94'),
('Sudan', '249'),
('Suriname', '597'),
('Svalbard and Jan Mayen', ''),
('Swaziland', '268'),
('Sweden', '46'),
('Switzerland', '41'),
('Syrian Arab Republic', ''),
('Taiwan', '886'),
('Tajikistan', '992'),
('Tanzania, United Republic of', ''),
('Thailand', '66'),
('Timor-Leste', '670'),
('Togo', '228'),
('Tokelau', '690'),
('Tonga', '676'),
('Trinidad and Tobago', '1 868'),
('Tunisia', '216'),
('Turkey', '90'),
('Turkmenistan', '993'),
('Turks and Caicos Islands', '1 649'),
('Tuvalu', '688'),
('Uganda', '256'),
('Ukraine', '380'),
('United Arab Emirates', '971'),
('United Kingdom', '44'),
('United States', '1'),
('United States Minor Outlying Islands', ''),
('Uruguay', '598'),
('Uzbekistan', '998'),
('Vanuatu', '678'),
('Venezuela', '58'),
('Vietnam', '84'),
('Virgin Islands, British', ''),
('Virgin Islands, U.s.', ''),
('Wallis and Futuna', '681'),
('Western Sahara', ''),
('Yemen', '967'),
('Zambia', '260'),
('Zimbabwe', '263');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCourse`
--

CREATE TABLE `gibbonCourse` (
  `gibbonCourseID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `name` varchar(60) NOT NULL,
  `nameShort` varchar(12) NOT NULL,
  `description` text NOT NULL,
  `map` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT 'Should this course be included in curriculum maps and other summaries?',
  `gibbonYearGroupIDList` varchar(255) NOT NULL,
  `orderBy` int(3) NOT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCourseClass`
--

CREATE TABLE `gibbonCourseClass` (
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL DEFAULT '',
  `nameShort` varchar(8) NOT NULL,
  `reportable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `attendance` enum('Y','N') NOT NULL DEFAULT 'Y',
  `enrolmentMin` int(4) DEFAULT NULL,
  `enrolmentMax` int(4) DEFAULT NULL,
  `gibbonScaleIDTarget` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCourseClassMap`
--

CREATE TABLE `gibbonCourseClassMap` (
  `gibbonCourseClassMapID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCourseClassPerson`
--

CREATE TABLE `gibbonCourseClassPerson` (
  `gibbonCourseClassPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `role` enum('Student','Teacher','Assistant','Technician','Parent','Student - Left','Teacher - Left') NOT NULL,
  `dateEnrolled` date DEFAULT NULL,
  `dateUnenrolled` date DEFAULT NULL,
  `reportable` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCrowdAssessDiscuss`
--

CREATE TABLE `gibbonCrowdAssessDiscuss` (
  `gibbonCrowdAssessDiscussID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryHomeworkID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comment` text NOT NULL,
  `gibbonCrowdAssessDiscussIDReplyTo` int(16) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonCustomField`
--

CREATE TABLE `gibbonCustomField` (
  `gibbonCustomFieldID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `context` varchar(60) NOT NULL DEFAULT 'Person',
  `name` varchar(50) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `description` varchar(255) NOT NULL,
  `type` enum('varchar','text','date','time','url','select','checkboxes','radio','yesno','editor','color','number','image','file','code') NOT NULL,
  `options` text NOT NULL COMMENT 'Field length for varchar, rows for text, comma-separate list for select/checkbox.',
  `required` enum('N','Y') NOT NULL DEFAULT 'N',
  `hidden` enum('Y','N') DEFAULT 'N',
  `heading` varchar(90) NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `activePersonStudent` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonStaff` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonParent` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonOther` tinyint(1) NOT NULL DEFAULT '0',
  `activeApplicationForm` tinyint(1) NOT NULL DEFAULT '0',
  `activeDataUpdater` tinyint(1) NOT NULL DEFAULT '0',
  `activePublicRegistration` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDataRetention`
--

CREATE TABLE `gibbonDataRetention` (
  `gibbonDataRetentionID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `tables` text NOT NULL,
  `status` enum('Success','Partial Failure') DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gibbonPersonIDOperator` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDaysOfWeek`
--

CREATE TABLE `gibbonDaysOfWeek` (
  `gibbonDaysOfWeekID` int(2) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(10) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `sequenceNumber` int(2) NOT NULL,
  `schoolDay` enum('Y','N') NOT NULL DEFAULT 'Y',
  `schoolOpen` time DEFAULT NULL,
  `schoolStart` time DEFAULT NULL,
  `schoolEnd` time DEFAULT NULL,
  `schoolClose` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonDaysOfWeek`
--

INSERT INTO `gibbonDaysOfWeek` (`gibbonDaysOfWeekID`, `name`, `nameShort`, `sequenceNumber`, `schoolDay`, `schoolOpen`, `schoolStart`, `schoolEnd`, `schoolClose`) VALUES
(01, 'Monday', 'Mon', 1, 'Y', '07:45:00', '08:30:00', '15:30:00', '17:00:00'),
(02, 'Tuesday', 'Tue', 2, 'Y', '07:45:00', '08:30:00', '15:30:00', '17:00:00'),
(03, 'Wednesday', 'Wed', 3, 'Y', '07:45:00', '08:30:00', '15:30:00', '17:00:00'),
(04, 'Thursday', 'Thu', 4, 'Y', '07:45:00', '08:30:00', '15:30:00', '17:00:00'),
(05, 'Friday', 'Fri', 5, 'Y', '07:45:00', '08:30:00', '15:30:00', '17:00:00'),
(06, 'Saturday', 'Sat', 6, 'N', NULL, NULL, NULL, NULL),
(07, 'Sunday', 'Sun', 7, 'N', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDepartment`
--

CREATE TABLE `gibbonDepartment` (
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Learning Area','Administration') NOT NULL DEFAULT 'Learning Area',
  `name` varchar(40) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `subjectListing` varchar(255) NOT NULL,
  `blurb` text NOT NULL,
  `logo` varchar(255) NOT NULL,
  `sequenceNumber` int(4) UNSIGNED DEFAULT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDepartmentResource`
--

CREATE TABLE `gibbonDepartmentResource` (
  `gibbonDepartmentResourceID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Link','File') NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDepartmentStaff`
--

CREATE TABLE `gibbonDepartmentStaff` (
  `gibbonDepartmentStaffID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `role` enum('Coordinator','Assistant Coordinator','Teacher (Curriculum)','Teacher','Director','Manager','Administrator','Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDiscussion`
--

CREATE TABLE `gibbonDiscussion` (
  `gibbonDiscussionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `foreignTable` varchar(60) NOT NULL,
  `foreignTableID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDTarget` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `type` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `comment` text,
  `attachmentType` enum('File','Link') DEFAULT NULL,
  `attachmentLocation` text,
  `gibbonDiscussionIDReplyTo` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonDistrict`
--

CREATE TABLE `gibbonDistrict` (
  `gibbonDistrictID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonEmailTemplate`
--

CREATE TABLE `gibbonEmailTemplate` (
  `gibbonEmailTemplateID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Core','Additional','Custom') NOT NULL DEFAULT 'Core',
  `templateType` varchar(120) NOT NULL,
  `moduleName` varchar(30) NOT NULL,
  `templateName` varchar(120) NOT NULL,
  `templateSubject` varchar(255) DEFAULT NULL,
  `templateBody` text,
  `variables` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonEmailTemplate`
--

INSERT INTO `gibbonEmailTemplate` (`gibbonEmailTemplateID`, `type`, `templateType`, `moduleName`, `templateName`, `templateSubject`, `templateBody`, `variables`, `timestamp`) VALUES
(000001, 'Core', 'Send Reports to Parents', 'Reports', 'Send Reports to Parents', '{{reportName|title}} for {{studentPreferredName}} {{studentSurname}}', '<p>Dear {{parentPreferredName}} {{parentSurname}},</p>\r\n<p>This email includes a link to {{studentPreferredName}}\'s {{reportName|title}} created on {{date}}.</p>\r\n<p>Click the button below to download this report. To protect your student\'s security and privacy, this download link will expire after 1 week.</p>\r\n<p>Thank you,<br />{{organisationAdministratorName}}</p>', '{\"reportName\": \"Test Report\", \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"parentPreferredName\": [\"firstName\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"date\": [\"date\"]\r\n}', '2022-01-24 11:20:30'),
(000002, 'Core', 'Send Reports to Students', 'Reports', 'Send Reports to Students', 'Your {{reportName|title}}', '<p>Dear {{studentPreferredName}} {{studentSurname}},</p>\r\n<p>This email includes a link to your {{reportName|title}} created on {{date}}.</p>\r\n<p>Click the button below to download this report. To protect your security and privacy, this download link will expire after 1 week.</p>\r\n<p>Thank you,<br />{{organisationAdministratorName}}</p>', '{\"reportName\": \"Test Report\", \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"date\": [\"date\"]\r\n}', '2022-01-24 11:20:30'),
(000003, 'Core', 'Negative Behaviour Letter 1', 'Behaviour', 'Negative Behaviour Letter 1', 'Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', 'Dear Parent/Guardian,<br/><br/>This letter has been automatically generated to alert you to the fact that your child, {{studentPreferredName}}, has reached {{behaviourCount}} negative behaviour incidents. Please see the list below for the details of these incidents:<br/><br/>{{behaviourRecord|raw}}<br/>This letter represents the first communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br/><br/>If you would like more information on this matter, please contact your child\'s tutor.', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000004, 'Core', 'Negative Behaviour Letter 2', 'Behaviour', 'Negative Behaviour Letter 2', 'Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', 'Dear Parent/Guardian,<br/><br/>This letter has been automatically generated to alert you to the fact that your child, {{studentPreferredName}}, has reached {{behaviourCount}} negative behaviour incidents. Please see the list below for the details of these incidents:<br/><br/>{{behaviourRecord|raw}}<br/>This letter represents the second communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br/><br/>If you would like more information on this matter, please contact your child\'s tutor.', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000005, 'Core', 'Negative Behaviour Letter 3', 'Behaviour', 'Negative Behaviour Letter 3', 'Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', 'Dear Parent/Guardian,<br/><br/>This letter has been automatically generated to alert you to the fact that your child, {{studentPreferredName}}, has reached {{behaviourCount}} negative behaviour incidents. Please see the list below for the details of these incidents:<br/><br/>{{behaviourRecord|raw}}<br/>This letter represents the final communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br/><br/>If you would like more information on this matter, please contact your child\'s tutor.', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000006, 'Core', 'Positive Behaviour Letter 1', 'Behaviour', 'Positive Behaviour Letter 1', 'Positive Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', '', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000007, 'Core', 'Positive Behaviour Letter 2', 'Behaviour', 'Positive Behaviour Letter 2', 'Positive Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', '', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000008, 'Core', 'Positive Behaviour Letter 3', 'Behaviour', 'Positive Behaviour Letter 3', 'Positive Behaviour Letter for {{studentSurname}}, {{studentPreferredName}} ({{studentFormGroup}}) via {{systemName}} at {{organisationName}}', '', '{\"behaviourCount\": [\"randomDigit\"], \r\n\"behaviourRecord\": [\"paragraph\"], \r\n\"studentPreferredName\": [\"firstName\"],\r\n\"studentSurname\": [\"lastName\"],\r\n\"studentFormGroup\": \"Y07\",\r\n\"parentPreferredName\": [\"firstNameFemale\"],\r\n\"parentSurname\": [\"lastName\"],\r\n\"parentTitle\": [\"titleFemale\"],\r\n\"formTutorPreferredName\": [\"firstNameMale\"],\r\n\"formTutorSurname\": [\"lastName\"],\r\n\"formTutorTitle\": [\"titleMale\"],\r\n\"formTutorEmail\": [\"safeEmail\"],\r\n\"date\": [\"date\"]\r\n}', '2021-10-20 05:58:10'),
(000009, 'Core', 'Admissions Access Link', 'Admissions', 'Admissions Access Link', 'Admissions Access', '<p>Dear {{email}},</p>\r\n<p>Thank you for your admissions request. Please click the button below to access your current or pending application forms. You can also check the status of your forms as well as submit new forms.</p>\r\n<p><span>This is an automated email initiated by the admissions page at {{organisationNameShort}}. If</span> <span>you</span> <span>did</span> <span>not</span> <span>initiate</span> this email, please let us know. You are welcome to reply to this email should you have any questions.</p>\r\n<p>Regards,<br />{{organisationAdmissionsName}},<br />{{organisationNameShort}} Admissions Administrator</p>', '{\"email\": [\"safeEmail\"], \r\n\"date\": [\"date\"], \"link\": \"https://gibbonedu.org\", \"organisationAdmissionsEmail\": [\"safeEmail\"], \"organisationAdmissionsName\": [\"name\"]\r\n}', '2022-05-25 18:39:48'),
(000010, 'Core', 'Application Form Reference Request', 'Admissions', 'Application Form Reference Request', 'Request For Reference', '<p>To whom it may concern,</p>\r\n<p>This email is being sent in relation to the application of a current or former student of your school: {{studentPreferredName}} {{studentSurname}}.</p>\r\n<p>In assessing their application for our school, we would like to enlist your help in completing the following reference form: {{applicationRefereeLink}}.</p>\r\n<p>Please feel free to contact me, should you have any questions in regard to this matter.</p>\r\n<p>Regards,<br />{{organisationAdmissionsName}},<br />{{organisationNameShort}} Admissions Administrator</p>', '{\r\n \"email\": [\"safeEmail\"],\r\n \"date\": [\"date\"],\r\n \"applicationRefereeLink\": \"https://gibbonedu.org\",\r\n \"studentPreferredName\": [\"firstName\"],\r\n \"studentSurname\": [\"lastName\"],\r\n \"studentOfficialName\": [\"name\"],\r\n \"parentTitle\": [\"titleFemale\"],\r\n \"parentPreferredName\": [\"firstNameFemale\"],\r\n \"parentSurname\": [\"lastName\"],\r\n \"organisationAdmissionsName\": [\"name\"],\r\n \"organisationAdmissionsEmail\": [\"safeEmail\"]\r\n}', '2022-05-25 18:47:19'),
(000011, 'Core', 'Application Form Confirmation', 'Admissions', 'Application Form Confirmation', 'Application Form Confirmation', '<p>Dear {{parentTitle}} {{parentPreferredName}} {{parentSurname}},</p>\r\n<p>Thank you for applying for a student place at {{organisationName}}.</p>\r\n<p>Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.</p>\r\n<p>In the meantime, should you have any questions please contact {{organisationAdmissionsName}} at {{organisationAdmissionsEmail}}.</p>\r\n<p>Please find a copy of your submitted details below, for your reference:<br />{{submissionDetails|raw}}</p>', '{\r\n \"email\": [\"safeEmail\"],\r\n \"date\": [\"date\"],\r\n \"applicationID\": [\"randomDigit\"],\r\n \"applicationName\": \"Sample Application Form\",\r\n \"submissionDetails\": \"Example Data\",\r\n \"studentPreferredName\": [\"firstName\"],\r\n \"studentSurname\": [\"lastName\"],\r\n \"studentOfficialName\": [\"name\"],\r\n \"parentTitle\": [\"titleFemale\"],\r\n \"parentPreferredName\": [\"firstNameFemale\"],\r\n \"parentSurname\": [\"lastName\"],\r\n \"organisationAdmissionsName\": [\"name\"],\r\n \"organisationAdmissionsEmail\": [\"safeEmail\"]\r\n}', '2022-05-25 18:46:20'),
(000012, 'Core', 'Parent Welcome Email', 'Admissions', 'Parent Welcome Email', 'Welcome to {{systemName}} at {{organisationNameShort}}', '<p>Dear {{parentPreferredName}} {{parentSurname}},</p>\r\n<p>Your {{applicationName}} for {{studentPreferredName}} {{studentSurname}} has been accepted.</p>\r\n<p>Welcome to {{systemName}}, {{organisationNameShort}}\'s system for managing school information. You can access the system by going to {{absoluteURL}} and logging in with your new username and password:</p>\r\n<p>Username: {{username}}<br />Password: {{password}}</p>\r\n<p>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).</p>\r\n<p>Please feel free to reply to this email should you have any questions.</p>\r\n<p>{{organisationAdmissionsName}},<br />{{organisationNameShort}} Admissions Administrator</p>', '{\r\n \"email\": [\"safeEmail\"],\r\n \"date\": [\"date\"],\r\n \"username\": [\"userName\"],\r\n \"password\": [\"password\"],\r\n \"applicationID\": [\"randomDigit\"],\r\n \"applicationName\": \"Sample Application Form\",\r\n \"studentPreferredName\": [\"firstName\"],\r\n \"studentSurname\": [\"lastName\"],\r\n \"studentOfficialName\": [\"name\"],\r\n \"parentTitle\": [\"titleFemale\"],\r\n \"parentPreferredName\": [\"firstNameFemale\"],\r\n \"parentSurname\": [\"lastName\"],\r\n \"organisationAdmissionsName\": [\"name\"],\r\n \"organisationAdmissionsEmail\": [\"safeEmail\"]\r\n}', '2022-05-25 18:50:29'),
(000013, 'Core', 'Student Welcome Email', 'Admissions', 'Student Welcome Email', 'Welcome to {{systemName}} at {{organisationNameShort}}', '<p>Dear {{studentPreferredName}} {{studentSurname}},</p>\r\n<p>Welcome to {{systemName}}, {{organisationNameShort}}\'s system for managing school information. You can access the system by going to {{absoluteURL}} and logging in with your new username and password:</p>\r\n<p>Username: {{username}}<br />Password: {{password}}<br /><br />In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).</p>\r\n<p>Please feel free to reply to this email should you have any questions.</p>\r\n<p>{{organisationAdmissionsName}},<br />{{organisationNameShort}} Admissions Administrator</p>', '{\r\n \"email\": [\"safeEmail\"],\r\n \"date\": [\"date\"],\r\n \"username\": [\"userName\"],\r\n \"password\": [\"password\"],\r\n \"applicationID\": [\"randomDigit\"],\r\n \"applicationName\": \"Sample Application Form\",\r\n \"studentPreferredName\": [\"firstName\"],\r\n \"studentSurname\": [\"lastName\"],\r\n \"studentOfficialName\": [\"name\"],\r\n \"parentTitle\": [\"titleFemale\"],\r\n \"parentPreferredName\": [\"firstNameFemale\"],\r\n \"parentSurname\": [\"lastName\"],\r\n \"organisationAdmissionsName\": [\"name\"],\r\n \"organisationAdmissionsEmail\": [\"safeEmail\"]\r\n}', '2022-05-25 18:50:25'),
(000014, 'Core', 'Application Form Fee Request', 'Admissions', 'Application Form Fee Request', 'Application Fee', '<p>Thank you for your application submission. Please pay the following processing fee before your application is complete. Payment can be made by credit card, using our secure online payment gateway. Click the button below to pay now.</p>', '{\r\n \"email\": [\"safeEmail\"],\r\n \"date\": [\"date\"],\r\n \"link\": \"https://gibbonedu.org\",\r\n \"applicationID\": [\"randomDigit\"],\r\n \"applicationName\": \"Sample Application Form\",\r\n \"applicationFee\": [\"numberBetween\", 100, 400],\r\n \"studentPreferredName\": [\"firstName\"],\r\n \"studentSurname\": [\"lastName\"],\r\n \"studentOfficialName\": [\"name\"],\r\n \"parentTitle\": [\"titleFemale\"],\r\n \"parentPreferredName\": [\"firstNameFemale\"],\r\n \"parentSurname\": [\"lastName\"],\r\n \"organisationAdmissionsName\": [\"name\"],\r\n \"organisationAdmissionsEmail\": [\"safeEmail\"]\r\n}', '2022-05-25 18:54:31');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonExternalAssessment`
--

CREATE TABLE `gibbonExternalAssessment` (
  `gibbonExternalAssessmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `nameShort` varchar(10) NOT NULL,
  `description` varchar(255) NOT NULL,
  `website` text NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `allowFileUpload` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonExternalAssessment`
--

INSERT INTO `gibbonExternalAssessment` (`gibbonExternalAssessmentID`, `name`, `nameShort`, `description`, `website`, `active`, `allowFileUpload`) VALUES
(0001, 'Cognitive Abilities Test', 'CAT', 'UK-based standardised tests that provides scores in maths, verbal and non-verbal skills, as well as KS3 and GCSE predicted grades.', '', 'Y', 'N'),
(0002, 'GCSE/iGCSE', 'GCSE', 'UK-based General Certificate of Secondary Education', '', 'Y', 'N'),
(0003, 'IB Diploma', 'IB Diploma', 'International Baccalaureate Diploma', 'http://www.ibo.org/', 'Y', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonExternalAssessmentField`
--

CREATE TABLE `gibbonExternalAssessmentField` (
  `gibbonExternalAssessmentFieldID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonExternalAssessmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `order` int(4) NOT NULL,
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonExternalAssessmentField`
--

INSERT INTO `gibbonExternalAssessmentField` (`gibbonExternalAssessmentFieldID`, `gibbonExternalAssessmentID`, `name`, `category`, `order`, `gibbonScaleID`, `gibbonYearGroupIDList`) VALUES
(000001, 0001, 'Maths', '1_Scores', 1, 00010, NULL),
(000002, 0001, 'Non-Verbal', '1_Scores', 2, 00010, NULL),
(000003, 0001, 'Verbal', '1_Scores', 3, 00010, NULL),
(000004, 0001, 'English', '2_KS3 Target Grades', 3, 00011, '001,002,003'),
(000005, 0001, 'Maths', '2_KS3 Target Grades', 7, 00011, '001,002,003'),
(000006, 0001, 'Science', '2_KS3 Target Grades', 11, 00011, '001,002,003'),
(000007, 0001, 'English Language', '3_GCSE Target Grades', 10, 00012, '004,005'),
(000008, 0001, 'Mathematics', '3_GCSE Target Grades', 18, 00012, '004,005'),
(000009, 0001, 'Science - Double Award', '3_GCSE Target Grades', 25, 00012, '004,005'),
(000010, 0001, 'Art & Design', '2_KS3 Target Grades', 1, 00011, '001,002,003'),
(000011, 0001, 'Design & Tech', '2_KS3 Target Grades', 2, 00011, '001,002,003'),
(000012, 0001, 'Geography', '2_KS3 Target Grades', 4, 00011, '001,002,003'),
(000013, 0001, 'History', '2_KS3 Target Grades', 5, 00011, '001,002,003'),
(000014, 0001, 'ICT', '2_KS3 Target Grades', 6, 00011, '001,002,003'),
(000015, 0001, 'MFL', '2_KS3 Target Grades', 8, 00011, '001,002,003'),
(000016, 0001, 'Music', '2_KS3 Target Grades', 9, 00011, '001,002,003'),
(000017, 0001, 'PE', '2_KS3 Target Grades', 10, 00011, '001,002,003'),
(000018, 0001, 'Art & Design', '3_GCSE Target Grades', 1, 00012, '004,005'),
(000019, 0001, 'Business Studies', '3_GCSE Target Grades', 2, 00012, '004,005'),
(000020, 0001, 'D&T - Electronics', '3_GCSE Target Grades', 3, 00012, '004,005'),
(000021, 0001, 'D&T - Food', '3_GCSE Target Grades', 4, 00012, '004,005'),
(000022, 0001, 'D&T - Graphics', '3_GCSE Target Grades', 5, 00012, '004,005'),
(000023, 0001, 'D&T - Resistant Materials', '3_GCSE Target Grades', 6, 00012, '004,005'),
(000024, 0001, 'D&T - Systems Control', '3_GCSE Target Grades', 7, 00012, '004,005'),
(000025, 0001, 'D&T - Textiles', '3_GCSE Target Grades', 8, 00012, '004,005'),
(000026, 0001, 'Drama', '3_GCSE Target Grades', 9, 00012, '004,005'),
(000027, 0001, 'English Literature', '3_GCSE Target Grades', 11, 00012, '004,005'),
(000028, 0001, 'French', '3_GCSE Target Grades', 12, 00012, '004,005'),
(000029, 0001, 'Geography', '3_GCSE Target Grades', 13, 00012, '004,005'),
(000030, 0001, 'German', '3_GCSE Target Grades', 14, 00012, '004,005'),
(000031, 0001, 'History', '3_GCSE Target Grades', 15, 00012, '004,005'),
(000032, 0001, 'Home Economics', '3_GCSE Target Grades', 16, 00012, '004,005'),
(000033, 0001, 'Information Technology', '3_GCSE Target Grades', 17, 00012, '004,005'),
(000034, 0001, 'Media Studies', '3_GCSE Target Grades', 19, 00012, '004,005'),
(000035, 0001, 'Music', '3_GCSE Target Grades', 20, 00012, '004,005'),
(000036, 0001, 'Physical Education', '3_GCSE Target Grades', 21, 00012, '004,005'),
(000037, 0001, 'Religious Education', '3_GCSE Target Grades', 22, 00012, '004,005'),
(000038, 0001, 'Science - Biology', '3_GCSE Target Grades', 23, 00012, '004,005'),
(000039, 0001, 'Science - Chemistry', '3_GCSE Target Grades', 24, 00012, '004,005'),
(000040, 0001, 'Science - Physics', '3_GCSE Target Grades', 26, 00012, '004,005'),
(000041, 0001, 'Science - Single Award', '3_GCSE Target Grades', 27, 00012, '004,005'),
(000042, 0001, 'Sociology', '3_GCSE Target Grades', 28, 00012, '004,005'),
(000043, 0001, 'Spanish', '3_GCSE Target Grades', 29, 00012, '004,005'),
(000044, 0001, 'Statistics', '3_GCSE Target Grades', 30, 00012, '004,005'),
(000045, 0002, 'Art & Design', '1_Final Grade', 1, 00003, '004,005'),
(000046, 0002, 'Chinese (Mandarin)', '1_Final Grade', 2, 00003, '004,005'),
(000047, 0002, 'Drama', '1_Final Grade', 3, 00003, '004,005'),
(000048, 0002, 'Dutch', '1_Final Grade', 4, 00003, '004,005'),
(000049, 0002, 'Economics', '1_Final Grade', 5, 00003, '004,005'),
(000050, 0002, 'English Language', '1_Final Grade', 6, 00003, '004,005'),
(000051, 0002, 'English Literature', '1_Final Grade', 7, 00003, '004,005'),
(000052, 0002, 'Environmental Management', '1_Final Grade', 8, 00003, '004,005'),
(000053, 0002, 'Mathematics', '1_Final Grade', 9, 00003, '004,005'),
(000054, 0002, 'Media Studies', '1_Final Grade', 10, 00003, '004,005'),
(000055, 0002, 'Physical Education', '1_Final Grade', 11, 00003, '004,005'),
(000056, 0002, 'Science - Double Award', '1_Final Grade', 12, 00003, '004,005'),
(000057, 0002, 'Spanish', '1_Final Grade', 13, 00003, '004,005'),
(000058, 0002, 'Art & Design', '0_Target Grade', 1, 00012, '004,005'),
(000059, 0002, 'Chinese (Mandarin)', '0_Target Grade', 2, 00012, '004,005'),
(000060, 0002, 'Drama', '0_Target Grade', 3, 00012, '004,005'),
(000061, 0002, 'Dutch', '0_Target Grade', 4, 00012, '004,005'),
(000062, 0002, 'Economics', '0_Target Grade', 5, 00012, '004,005'),
(000063, 0002, 'English Language', '0_Target Grade', 6, 00012, '004,005'),
(000064, 0002, 'English Literature', '0_Target Grade', 7, 00012, '004,005'),
(000065, 0002, 'Environmental Management', '0_Target Grade', 8, 00012, '004,005'),
(000066, 0002, 'Mathematics', '0_Target Grade', 9, 00012, '004,005'),
(000067, 0002, 'Media Studies', '0_Target Grade', 10, 00012, '004,005'),
(000068, 0002, 'Physical Education', '0_Target Grade', 11, 00012, '004,005'),
(000069, 0002, 'Science - Double Award', '0_Target Grade', 12, 00012, '004,005'),
(000070, 0002, 'Spanish', '0_Target Grade', 13, 00012, '004,005'),
(000071, 0003, 'IB Diploma Total', '0_Target Grade', 0, 00014, '006,007'),
(000072, 0003, 'IB Diploma Total', '1_Final Grade', 0, 00014, '006,007'),
(000073, 0003, 'Chinese A: Language and Literature HL', '0_Target Grade', 1, 00013, '006,007'),
(000074, 0003, 'Chinese A: Language and Literature SL', '0_Target Grade', 2, 00013, '006,007'),
(000075, 0003, 'English A: Language and Literature HL', '0_Target Grade', 3, 00013, '006,007'),
(000076, 0003, 'English A: Language and Literature SL', '0_Target Grade', 4, 00013, '006,007'),
(000077, 0003, 'English A: Literature HL', '0_Target Grade', 5, 00013, '006,007'),
(000078, 0003, 'English A: Literature SL', '0_Target Grade', 6, 00013, '006,007'),
(000079, 0003, 'Self-Taught Language SL', '0_Target Grade', 7, 00013, '006,007'),
(000080, 0003, 'Chinese B HL', '0_Target Grade', 8, 00013, '006,007'),
(000081, 0003, 'Chinese B SL', '0_Target Grade', 9, 00013, '006,007'),
(000082, 0003, 'Spanish B HL', '0_Target Grade', 10, 00013, '006,007'),
(000083, 0003, 'Spanish B SL', '0_Target Grade', 11, 00013, '006,007'),
(000084, 0003, 'Italian ab initio SL', '0_Target Grade', 12, 00013, '006,007'),
(000085, 0003, 'Economics HL', '0_Target Grade', 13, 00013, '006,007'),
(000086, 0003, 'Economics SL', '0_Target Grade', 14, 00013, '006,007'),
(000087, 0003, 'Psychology HL', '0_Target Grade', 15, 00013, '006,007'),
(000088, 0003, 'Psychology SL', '0_Target Grade', 16, 00013, '006,007'),
(000089, 0003, 'Environmental Systems and Society SL', '0_Target Grade', 17, 00013, '006,007'),
(000090, 0003, 'Chemistry HL', '0_Target Grade', 18, 00013, '006,007'),
(000091, 0003, 'Chemistry SL', '0_Target Grade', 19, 00013, '006,007'),
(000092, 0003, 'Physics HL', '0_Target Grade', 20, 00013, '006,007'),
(000093, 0003, 'Physics SL', '0_Target Grade', 21, 00013, '006,007'),
(000094, 0003, 'Mathematics HL', '0_Target Grade', 22, 00013, '006,007'),
(000095, 0003, 'Mathematics SL', '0_Target Grade', 23, 00013, '006,007'),
(000096, 0003, 'Maths Studies SL', '0_Target Grade', 24, 00013, '006,007'),
(000097, 0003, 'Theatre Arts HL', '0_Target Grade', 25, 00013, '006,007'),
(000098, 0003, 'Theatre Arts SL', '0_Target Grade', 26, 00013, '006,007'),
(000099, 0003, 'Visual Arts HL', '0_Target Grade', 27, 00013, '006,007'),
(000100, 0003, 'Visual Arts SL', '0_Target Grade', 28, 00013, '006,007'),
(000101, 0003, 'Chinese A: Language and Literature HL', '1_Final Grade', 1, 00013, '006,007'),
(000102, 0003, 'Chinese A: Language and Literature SL', '1_Final Grade', 2, 00013, '006,007'),
(000103, 0003, 'English A: Language and Literature HL', '1_Final Grade', 3, 00013, '006,007'),
(000104, 0003, 'English A: Language and Literature SL', '1_Final Grade', 4, 00013, '006,007'),
(000105, 0003, 'English A: Literature HL', '1_Final Grade', 5, 00013, '006,007'),
(000106, 0003, 'English A: Literature SL', '1_Final Grade', 6, 00013, '006,007'),
(000107, 0003, 'Self-Taught Language SL', '1_Final Grade', 7, 00013, '006,007'),
(000108, 0003, 'Chinese B HL', '1_Final Grade', 8, 00013, '006,007'),
(000109, 0003, 'Chinese B SL', '1_Final Grade', 9, 00013, '006,007'),
(000110, 0003, 'Spanish B HL', '1_Final Grade', 10, 00013, '006,007'),
(000111, 0003, 'Spanish B SL', '1_Final Grade', 11, 00013, '006,007'),
(000112, 0003, 'Italian ab initio SL', '1_Final Grade', 12, 00013, '006,007'),
(000113, 0003, 'Economics HL', '1_Final Grade', 13, 00013, '006,007'),
(000114, 0003, 'Economics SL', '1_Final Grade', 14, 00013, '006,007'),
(000115, 0003, 'Psychology HL', '1_Final Grade', 15, 00013, '006,007'),
(000116, 0003, 'Psychology SL', '1_Final Grade', 16, 00013, '006,007'),
(000117, 0003, 'Environmental Systems and Society SL', '1_Final Grade', 17, 00013, '006,007'),
(000118, 0003, 'Chemistry HL', '1_Final Grade', 18, 00013, '006,007'),
(000119, 0003, 'Chemistry SL', '1_Final Grade', 19, 00013, '006,007'),
(000120, 0003, 'Physics HL', '1_Final Grade', 20, 00013, '006,007'),
(000121, 0003, 'Physics SL', '1_Final Grade', 21, 00013, '006,007'),
(000122, 0003, 'Mathematics HL', '1_Final Grade', 22, 00013, '006,007'),
(000123, 0003, 'Mathematics SL', '1_Final Grade', 23, 00013, '006,007'),
(000124, 0003, 'Maths Studies SL', '1_Final Grade', 24, 00013, '006,007'),
(000125, 0003, 'Theatre Arts HL', '1_Final Grade', 25, 00013, '006,007'),
(000126, 0003, 'Theatre Arts SL', '1_Final Grade', 26, 00013, '006,007'),
(000127, 0003, 'Visual Arts HL', '1_Final Grade', 27, 00013, '006,007'),
(000128, 0003, 'Visual Arts SL', '1_Final Grade', 28, 00013, '006,007');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonExternalAssessmentStudent`
--

CREATE TABLE `gibbonExternalAssessmentStudent` (
  `gibbonExternalAssessmentStudentID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonExternalAssessmentID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date NOT NULL,
  `attachment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonExternalAssessmentStudentEntry`
--

CREATE TABLE `gibbonExternalAssessmentStudentEntry` (
  `gibbonExternalAssessmentStudentEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonExternalAssessmentStudentID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonExternalAssessmentFieldID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'Key for the actual grade achieved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFamily`
--

CREATE TABLE `gibbonFamily` (
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `nameAddress` varchar(100) NOT NULL COMMENT 'The formal name to be used for addressing the family (e.g. Mr. & Mrs. Smith)',
  `homeAddress` mediumtext NOT NULL,
  `homeAddressDistrict` varchar(255) NOT NULL,
  `homeAddressCountry` varchar(255) NOT NULL,
  `status` enum('Married','Separated','Divorced','De Facto','Other','Single') NOT NULL,
  `languageHomePrimary` varchar(30) NOT NULL,
  `languageHomeSecondary` varchar(30) DEFAULT NULL,
  `familySync` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFamilyAdult`
--

CREATE TABLE `gibbonFamilyAdult` (
  `gibbonFamilyAdultID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `comment` text NOT NULL,
  `childDataAccess` enum('Y','N') NOT NULL,
  `contactPriority` int(2) NOT NULL DEFAULT '1',
  `contactCall` enum('Y','N') NOT NULL,
  `contactSMS` enum('Y','N') NOT NULL,
  `contactEmail` enum('Y','N') NOT NULL,
  `contactMail` enum('Y','N') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFamilyChild`
--

CREATE TABLE `gibbonFamilyChild` (
  `gibbonFamilyChildID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFamilyRelationship`
--

CREATE TABLE `gibbonFamilyRelationship` (
  `gibbonFamilyRelationshipID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID1` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID2` int(10) UNSIGNED ZEROFILL NOT NULL,
  `relationship` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Person 1 is [relationship] to person 2?';

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFamilyUpdate`
--

CREATE TABLE `gibbonFamilyUpdate` (
  `gibbonFamilyUpdateID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `nameAddress` varchar(100) NOT NULL DEFAULT '',
  `homeAddress` mediumtext NOT NULL,
  `homeAddressDistrict` varchar(255) NOT NULL DEFAULT '',
  `homeAddressCountry` varchar(255) NOT NULL DEFAULT '',
  `languageHomePrimary` varchar(30) NOT NULL,
  `languageHomeSecondary` varchar(30) NOT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFileExtension`
--

CREATE TABLE `gibbonFileExtension` (
  `gibbonFileExtensionID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Document','Spreadsheet','Presentation','Graphics/Design','Video','Audio','Other') NOT NULL DEFAULT 'Other',
  `extension` varchar(7) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonFileExtension`
--

INSERT INTO `gibbonFileExtension` (`gibbonFileExtensionID`, `type`, `extension`, `name`) VALUES
(0001, 'Document', 'doc', 'Microsoft Word 97/2000/XP'),
(0002, 'Document', 'docx', 'Microsoft Word 2007+'),
(0003, 'Document', 'pages', 'Apple Pages'),
(0004, 'Document', 'odt', 'OpenOffice Text'),
(0005, 'Document', 'txt', 'Plain Text'),
(0006, 'Document', 'rtf', 'Rich Text Format'),
(0007, 'Spreadsheet', 'xls', 'Microsoft Excel 97/2000/XP'),
(0008, 'Spreadsheet', 'xlsx', 'Microsoft Excel 2007+'),
(0009, 'Spreadsheet', 'ods', 'OpenOffice SpreadSheet'),
(0010, 'Spreadsheet', 'numbers', 'Apple Numbers'),
(0011, 'Spreadsheet', 'csv', 'Comma Seperate Values'),
(0012, 'Presentation', 'ppt', 'Microsoft PowerPoint 97/2000/XP'),
(0013, 'Presentation', 'pptx', 'Microsoft PowerPoint 2007+'),
(0014, 'Presentation', 'key', 'Apple Keynote'),
(0015, 'Audio', 'mp3', 'MPEG Audio'),
(0016, 'Audio', 'mp4', 'MPEG Audio'),
(0017, 'Audio', 'm4a', 'MPEG Audio'),
(0018, 'Audio', 'wma', 'Windows Media Audio'),
(0019, 'Audio', 'ogg', 'Vorbis Ogg'),
(0020, 'Audio', 'aac', 'MPEG Audio'),
(0021, 'Graphics/Design', 'png', 'Portable Network Graphics'),
(0022, 'Graphics/Design', 'jpg', 'Joint Picture Expert Group'),
(0023, 'Graphics/Design', 'gif', 'Graphics Interchange Format'),
(0024, 'Graphics/Design', 'acorn', 'Acorn'),
(0025, 'Graphics/Design', 'ai', 'Adobe Illustrator'),
(0026, 'Graphics/Design', 'psd', 'Adobe Photoshop'),
(0028, 'Graphics/Design', 'xcf', 'GIMP eXperimental Computing Facility'),
(0029, 'Video', 'avi', 'Audio Video Interleave'),
(0030, 'Video', 'wmv', 'Windows Media Video'),
(0031, 'Video', 'mpg', 'MPEG Video'),
(0032, 'Video', 'mov', 'QuickTime Movie'),
(0033, 'Video', 'flv', 'Adobe Flash Video'),
(0034, 'Other', 'fla', 'Adobe Flash'),
(0035, 'Video', 'swf', 'Adobe Flash'),
(0036, 'Graphics/Design', 'skp', 'Google SketchUp'),
(0037, 'Document', 'pdf', 'Portable Document Format'),
(0038, 'Graphics/Design', 'jpeg', 'Joint Picture Expert Group'),
(0039, 'Video', 'mpeg', 'MPEG Video'),
(0040, 'Other', 'sb', 'Scratch'),
(0041, 'Video', 'm4v', 'MPG Varient'),
(0042, 'Other', 'zip', 'ZIP Compressed Archive'),
(0043, 'Document', 'htm', 'HyperText Marrkup Language'),
(0044, 'Document', 'html', 'HyperText Marrkup Language'),
(0045, 'Video', '3gp', '3rd Generation Partnership Video'),
(0046, 'Other', 'sb2', 'Scratch 2');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceBillingSchedule`
--

CREATE TABLE `gibbonFinanceBillingSchedule` (
  `gibbonFinanceBillingScheduleID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `invoiceIssueDate` date DEFAULT NULL,
  `invoiceDueDate` date DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceBudget`
--

CREATE TABLE `gibbonFinanceBudget` (
  `gibbonFinanceBudgetID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `nameShort` varchar(8) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `category` varchar(255) NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceBudgetCycle`
--

CREATE TABLE `gibbonFinanceBudgetCycle` (
  `gibbonFinanceBudgetCycleID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(7) NOT NULL,
  `status` enum('Past','Current','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `dateStart` date NOT NULL,
  `dateEnd` date NOT NULL,
  `sequenceNumber` int(6) NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceBudgetCycleAllocation`
--

CREATE TABLE `gibbonFinanceBudgetCycleAllocation` (
  `gibbonFinanceBudgetCycleAllocationID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceBudgetID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceBudgetCycleID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `value` decimal(14,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceBudgetPerson`
--

CREATE TABLE `gibbonFinanceBudgetPerson` (
  `gibbonFinanceBudgetPersonID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceBudgetID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `access` enum('Full','Write','Read') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceExpense`
--

CREATE TABLE `gibbonFinanceExpense` (
  `gibbonFinanceExpenseID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceBudgetID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceBudgetCycleID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(60) NOT NULL,
  `body` text NOT NULL,
  `status` enum('Requested','Approved','Rejected','Cancelled','Ordered','Paid') NOT NULL,
  `cost` decimal(12,2) NOT NULL,
  `countAgainstBudget` enum('Y','N') NOT NULL DEFAULT 'Y',
  `purchaseBy` enum('School','Self') NOT NULL DEFAULT 'School',
  `purchaseDetails` text NOT NULL,
  `paymentMethod` enum('Cash','Cheque','Credit Card','Bank Transfer','Other') DEFAULT NULL,
  `paymentDate` date DEFAULT NULL,
  `paymentAmount` decimal(12,2) DEFAULT NULL,
  `gibbonPersonIDPayment` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `paymentID` varchar(100) DEFAULT NULL,
  `paymentReimbursementReceipt` varchar(255) NOT NULL,
  `paymentReimbursementStatus` enum('Requested','Complete') DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `statusApprovalBudgetCleared` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceExpenseApprover`
--

CREATE TABLE `gibbonFinanceExpenseApprover` (
  `gibbonFinanceExpenseApproverID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `sequenceNumber` int(4) DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceExpenseLog`
--

CREATE TABLE `gibbonFinanceExpenseLog` (
  `gibbonFinanceExpenseLogID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceExpenseID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` enum('Request','Approval - Partial - Budget','Approval - Partial - School','Approval - Final','Approval - Exempt','Rejection','Cancellation','Order','Payment','Reimbursement Request','Reimbursement Completion','Comment') NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceFee`
--

CREATE TABLE `gibbonFinanceFee` (
  `gibbonFinanceFeeID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `nameShort` varchar(6) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonFinanceFeeCategoryID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `fee` decimal(12,2) NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceFeeCategory`
--

CREATE TABLE `gibbonFinanceFeeCategory` (
  `gibbonFinanceFeeCategoryID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `nameShort` varchar(6) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonFinanceFeeCategory`
--

INSERT INTO `gibbonFinanceFeeCategory` (`gibbonFinanceFeeCategoryID`, `name`, `nameShort`, `description`, `active`, `gibbonPersonIDCreator`, `timestampCreator`, `gibbonPersonIDUpdate`, `timestampUpdate`) VALUES
(0001, 'Other', 'OTHR', 'Category for fees not fitting into any other category.', 'Y', 0000000001, '2013-07-12 08:25:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceInvoice`
--

CREATE TABLE `gibbonFinanceInvoice` (
  `gibbonFinanceInvoiceID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceInvoiceeID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `invoiceTo` enum('Family','Company') NOT NULL DEFAULT 'Family',
  `billingScheduleType` enum('Scheduled','Ad Hoc') NOT NULL DEFAULT 'Ad Hoc',
  `separated` enum('N','Y') DEFAULT NULL COMMENT 'Has this invoice been separated from its schedule in gibbonFinanceBillingSchedule? Only applies to scheduled invoices. Separation takes place during invoice issueing.',
  `gibbonFinanceBillingScheduleID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Issued','Paid','Paid - Partial','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
  `gibbonFinanceFeeCategoryIDList` text,
  `invoiceIssueDate` date DEFAULT NULL,
  `invoiceDueDate` date DEFAULT NULL,
  `paidDate` date DEFAULT NULL,
  `paidAmount` decimal(13,2) DEFAULT NULL COMMENT 'The current running total amount paid to this invoice',
  `gibbonPaymentID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `reminderCount` int(3) NOT NULL DEFAULT '0',
  `notes` text NOT NULL,
  `key` varchar(40) NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceInvoicee`
--

CREATE TABLE `gibbonFinanceInvoicee` (
  `gibbonFinanceInvoiceeID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `invoiceTo` enum('Family','Company') NOT NULL,
  `companyName` varchar(100) DEFAULT NULL,
  `companyContact` varchar(100) DEFAULT NULL,
  `companyAddress` varchar(255) DEFAULT NULL,
  `companyEmail` text,
  `companyCCFamily` enum('N','Y') DEFAULT NULL COMMENT 'When company is billed, should family receive a copy?',
  `companyPhone` varchar(20) DEFAULT NULL,
  `companyAll` enum('Y','N') DEFAULT NULL COMMENT 'Should company pay all invoices?.',
  `gibbonFinanceFeeCategoryIDList` text COMMENT 'If companyAll is N, list category IDs for campany to pay here.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceInvoiceeUpdate`
--

CREATE TABLE `gibbonFinanceInvoiceeUpdate` (
  `gibbonFinanceInvoiceeUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `gibbonFinanceInvoiceeID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `invoiceTo` enum('Family','Company') NOT NULL,
  `companyName` varchar(100) DEFAULT NULL,
  `companyContact` varchar(100) DEFAULT NULL,
  `companyAddress` varchar(255) DEFAULT NULL,
  `companyEmail` text,
  `companyCCFamily` enum('N','Y') DEFAULT NULL COMMENT 'When company is billed, should family receive a copy?',
  `companyPhone` varchar(20) DEFAULT NULL,
  `companyAll` enum('Y','N') DEFAULT NULL COMMENT 'Should company pay all invoices?.',
  `gibbonFinanceFeeCategoryIDList` text COMMENT 'If companyAll is N, list category IDs for campany to pay here.',
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFinanceInvoiceFee`
--

CREATE TABLE `gibbonFinanceInvoiceFee` (
  `gibbonFinanceInvoiceFeeID` int(15) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFinanceInvoiceID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `feeType` enum('Standard','Ad Hoc') NOT NULL DEFAULT 'Ad Hoc',
  `gibbonFinanceFeeID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `separated` enum('N','Y') DEFAULT NULL COMMENT 'Has this fee been separated from its parent in gibbonFinanceFee? Only applies to Standard fees. Separation takes place during invoice issueing.',
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `gibbonFinanceFeeCategoryID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `fee` decimal(12,2) DEFAULT NULL,
  `sequenceNumber` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFirstAid`
--

CREATE TABLE `gibbonFirstAid` (
  `gibbonFirstAidID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDPatient` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDFirstAider` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDFollowUp` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `description` text NOT NULL,
  `actionTaken` text NOT NULL,
  `followUp` text NOT NULL,
  `date` date NOT NULL,
  `timeIn` time NOT NULL,
  `timeOut` time DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFirstAidFollowUp`
--

CREATE TABLE `gibbonFirstAidFollowUp` (
  `gibbonFirstAidFollowUpID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFirstAidID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `followUp` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonForm`
--

CREATE TABLE `gibbonForm` (
  `gibbonFormID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` varchar(60) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `public` enum('Y','N') NOT NULL DEFAULT 'N',
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL,
  `config` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonForm`
--

INSERT INTO `gibbonForm` (`gibbonFormID`, `name`, `description`, `type`, `active`, `public`, `gibbonYearGroupIDList`, `config`) VALUES
(000001, 'Sample Application Form', 'An example of many of the available fields in the application form. Be sure to edit this to customize it for your school.', 'Application', 'N', 'N', '001,002,003', '{\"applicationReferee\":\"Y\",\"applicationRefereeLink\":\"https:\\/\\/gibbonedu.org\",\"sendSubmissionEmail\":\"Y\",\"submissionEmailTemplate\":\"000011\",\"createStudent\":\"Y\",\"createFamily\":\"Y\",\"createParents\":\"Y\",\"autoHouseAssign\":\"Y\",\"newStudentDetails\":\"Y\",\"studentDefaultEmail\":\"[username]@localhost.test\",\"studentDefaultWebsite\":\"http:\\/\\/localhost\\/[username]\",\"enrolStudent\":\"Y\",\"enableLimitedYearsOfEntry\":\"Y\",\"availableYearsOfEntry\":[\"025\",\"026\"],\"createMedicalRecord\":\"Y\",\"createINRecord\":\"Y\",\"createInvoicee\":\"Y\",\"acceptanceEmailStudentDefault\":\"N\",\"acceptanceEmailStudentTemplate\":\"000013\",\"acceptanceEmailParentDefault\":\"Y\",\"acceptanceEmailParentTemplate\":\"000012\"}');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFormField`
--

CREATE TABLE `gibbonFormField` (
  `gibbonFormFieldID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormPageID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `fieldName` varchar(90) NOT NULL,
  `fieldType` varchar(60) NOT NULL,
  `fieldGroup` varchar(60) NOT NULL,
  `required` enum('Y','N','X') NOT NULL DEFAULT 'N',
  `hidden` enum('Y','N') NOT NULL DEFAULT 'N',
  `prefill` enum('Y','N') NOT NULL DEFAULT 'N',
  `options` text,
  `label` varchar(90) DEFAULT NULL,
  `description` text,
  `conditional` text COMMENT 'json',
  `defaultValue` varchar(255) DEFAULT NULL,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonFormField`
--

INSERT INTO `gibbonFormField` (`gibbonFormFieldID`, `gibbonFormPageID`, `fieldName`, `fieldType`, `fieldGroup`, `required`, `hidden`, `prefill`, `options`, `label`, `description`, `conditional`, `defaultValue`, `sequenceNumber`) VALUES
(0000000001, 00000001, 'headingStudentPersonalData', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Student Personal Data', NULL, NULL, NULL, 2),
(0000000002, 00000001, 'surname', 'varchar', 'StudentFields', 'X', 'N', 'N', NULL, 'Surname', 'Family name as shown in ID documents.', NULL, NULL, 3),
(0000000003, 00000001, 'firstName', 'varchar', 'StudentFields', 'X', 'N', 'N', NULL, 'First Name', 'First name as shown in ID documents.', NULL, NULL, 4),
(0000000004, 00000001, 'preferredName', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'Preferred Name', 'Most common name, alias, nickname, etc.', NULL, NULL, 5),
(0000000005, 00000001, 'officialName', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'Official Name', 'Full name as shown in ID documents.', NULL, NULL, 6),
(0000000006, 00000001, 'nameInCharacters', 'varchar', 'StudentFields', 'N', 'N', 'N', NULL, 'Name In Characters', 'Chinese or other character-based name.', NULL, NULL, 7),
(0000000007, 00000001, 'gender', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'Gender', NULL, NULL, NULL, 8),
(0000000008, 00000001, 'dob', 'date', 'StudentFields', 'Y', 'N', 'N', NULL, 'Date of Birth', NULL, NULL, NULL, 9),
(0000000009, 00000001, 'headingStudentBackground', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Student Background', NULL, NULL, NULL, 10),
(0000000010, 00000001, 'languageHomePrimary', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'Home Language - Primary', 'The primary language used in the student\'s home.', NULL, NULL, 11),
(0000000011, 00000001, 'languageHomeSecondary', 'varchar', 'StudentFields', 'N', 'N', 'N', NULL, 'Home Language - Secondary', NULL, NULL, NULL, 12),
(0000000012, 00000001, 'languageFirst', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'First Language', 'Student\'s native/first/mother language.', NULL, NULL, 13),
(0000000013, 00000001, 'languageSecond', 'varchar', 'StudentFields', 'N', 'N', 'N', NULL, 'Second Language', NULL, NULL, NULL, 14),
(0000000014, 00000001, 'languageThird', 'varchar', 'StudentFields', 'N', 'N', 'N', NULL, 'Third Language', NULL, NULL, NULL, 15),
(0000000015, 00000001, 'countryOfBirth', 'varchar', 'StudentFields', 'Y', 'N', 'N', NULL, 'Country of Birth', NULL, NULL, NULL, 16),
(0000000016, 00000001, 'headingStudentContact', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Student Contact', NULL, NULL, NULL, 17),
(0000000017, 00000001, 'phone', 'phone', 'StudentFields', 'N', 'N', 'N', NULL, 'Phone', 'Type, country code, number.', NULL, NULL, 20),
(0000000018, 00000002, 'headingStudentEducation', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Student Education', NULL, NULL, NULL, 1),
(0000000019, 00000002, 'gibbonSchoolYearIDEntry', 'varchar', 'AdmissionsFields', 'X', 'N', 'N', NULL, 'Anticipated Year of Entry', 'What school year will the student join in?', NULL, NULL, 2),
(0000000020, 00000002, 'dateStart', 'date', 'AdmissionsFields', 'X', 'N', 'N', NULL, 'Intended Start Date', 'Student\'s intended first day at school.', NULL, NULL, 3),
(0000000021, 00000002, 'gibbonYearGroupIDEntry', 'varchar', 'AdmissionsFields', 'X', 'N', 'N', NULL, 'Year Group at Entry', 'Which year level will student enter.', NULL, NULL, 4),
(0000000022, 00000002, 'gibbonFormGroupIDEntry', 'varchar', 'AdmissionsFields', 'N', 'Y', 'N', NULL, 'Form Group at Entry', 'If set, the student will automatically be enrolled on Accept.', NULL, NULL, 5),
(0000000023, 00000002, 'referenceEmail', 'varchar', 'AdmissionsFields', 'Y', 'N', 'N', NULL, 'Current School Reference Email', 'An email address for a referee at the applicant\'s current school.', NULL, NULL, 9),
(0000000024, 00000002, 'previousSchools', 'varchar', 'AdmissionsFields', 'N', 'N', 'N', NULL, 'Previous Schools', 'Please give information on the last two schools attended by the applicant.', NULL, NULL, 10),
(0000000025, 00000002, 'dayType', 'varchar', 'AdmissionsFields', 'N', 'N', 'N', NULL, 'Day Type', 'This is example text.', NULL, NULL, 6),
(0000000026, 00000003, 'headingHomeAddress', 'heading', 'LayoutHeadings', 'N', 'N', 'N', 'familySection', 'Home Address', 'This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.', NULL, NULL, 1),
(0000000027, 00000003, 'homeAddress', 'varchar', 'FamilyFields', 'X', 'N', 'Y', NULL, 'Home Address', 'Unit, Building, Street', NULL, NULL, 2),
(0000000028, 00000003, 'homeAddressDistrict', 'varchar', 'FamilyFields', 'Y', 'N', 'Y', NULL, 'Home Address (District)', 'County, State, District', NULL, NULL, 3),
(0000000029, 00000003, 'homeAddressCountry', 'varchar', 'FamilyFields', 'Y', 'N', 'Y', NULL, 'Home Address (Country)', NULL, NULL, NULL, 4),
(0000000030, 00000003, 'headingParentGuardian1PersonalData', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection1', 'Parent/Guardian 1 Personal Data', NULL, NULL, NULL, 6),
(0000000031, 00000003, 'parent1title', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Title', NULL, NULL, NULL, 7),
(0000000032, 00000003, 'parent1surname', 'varchar', 'Parent1Fields', 'X', 'N', 'Y', NULL, 'Surname', 'Family name as shown in ID documents.', NULL, NULL, 8),
(0000000033, 00000003, 'parent1firstName', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'First Name', 'First name as shown in ID documents.', NULL, NULL, 9),
(0000000034, 00000003, 'parent1preferredName', 'varchar', 'Parent1Fields', 'X', 'N', 'Y', NULL, 'Preferred Name', 'Most common name, alias, nickname, etc.', NULL, NULL, 10),
(0000000035, 00000003, 'parent1officialName', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Official Name', 'Full name as shown in ID documents.', NULL, NULL, 11),
(0000000036, 00000003, 'parent1nameInCharacters', 'varchar', 'Parent1Fields', 'N', 'N', 'Y', NULL, 'Name In Characters', 'Chinese or other character-based name.', NULL, NULL, 12),
(0000000037, 00000003, 'parent1gender', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Gender', NULL, NULL, NULL, 13),
(0000000038, 00000003, 'parent1relationship', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Relationship', NULL, NULL, NULL, 14),
(0000000039, 00000003, 'headingParentGuardian1PersonalBackground', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection1', 'Parent/Guardian 1 Personal Background', NULL, NULL, NULL, 15),
(0000000040, 00000003, 'parent1languageFirst', 'varchar', 'Parent1Fields', 'N', 'N', 'Y', NULL, 'First Language', 'Student\'s native/first/mother language.', NULL, NULL, 16),
(0000000041, 00000003, 'parent1languageSecond', 'varchar', 'Parent1Fields', 'N', 'N', 'Y', NULL, 'Second Language', NULL, NULL, NULL, 17),
(0000000042, 00000003, 'headingParentGuardian1Contact', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection1', 'Parent/Guardian 1 Contact', NULL, NULL, NULL, 18),
(0000000043, 00000003, 'parent1email', 'email', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Email', NULL, NULL, NULL, 19),
(0000000044, 00000003, 'parent1phone', 'phone', 'Parent1Fields', 'N', 'N', 'N', NULL, 'Phone', 'Type, country code, number.', NULL, NULL, 20),
(0000000045, 00000003, 'headingParentGuardian1Employment', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection1', 'Parent/Guardian 1 Employment', NULL, NULL, NULL, 21),
(0000000046, 00000003, 'parent1profession', 'varchar', 'Parent1Fields', 'Y', 'N', 'Y', NULL, 'Profession', NULL, NULL, NULL, 22),
(0000000047, 00000003, 'parent1employer', 'varchar', 'Parent1Fields', 'N', 'N', 'Y', NULL, 'Employer', NULL, NULL, NULL, 23),
(0000000048, 00000003, 'secondParent', 'checkbox', 'Parent2Fields', 'N', 'N', 'N', NULL, 'Do not include a second parent/guardian', NULL, NULL, NULL, 26),
(0000000049, 00000003, 'headingParentGuardian2PersonalData', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection2', 'Parent/Guardian 2 Personal Data', NULL, NULL, NULL, 27),
(0000000050, 00000003, 'parent2title', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Title', NULL, NULL, NULL, 28),
(0000000051, 00000003, 'parent2surname', 'varchar', 'Parent2Fields', 'X', 'N', 'Y', NULL, 'Surname', 'Family name as shown in ID documents.', NULL, NULL, 29),
(0000000052, 00000003, 'parent2firstName', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'First Name', 'First name as shown in ID documents.', NULL, NULL, 30),
(0000000053, 00000003, 'parent2preferredName', 'varchar', 'Parent2Fields', 'X', 'N', 'Y', NULL, 'Preferred Name', 'Most common name, alias, nickname, etc.', NULL, NULL, 31),
(0000000054, 00000003, 'parent2officialName', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Official Name', 'Full name as shown in ID documents.', NULL, NULL, 32),
(0000000055, 00000003, 'parent2nameInCharacters', 'varchar', 'Parent2Fields', 'N', 'N', 'Y', NULL, 'Name In Characters', 'Chinese or other character-based name.', NULL, NULL, 33),
(0000000056, 00000003, 'parent2gender', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Gender', NULL, NULL, NULL, 34),
(0000000057, 00000003, 'parent2relationship', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Relationship', NULL, NULL, NULL, 35),
(0000000058, 00000003, 'headingParentGuardian2PersonalBackground', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection2', 'Parent/Guardian 2 Personal Background', NULL, NULL, NULL, 36),
(0000000059, 00000003, 'parent2languageFirst', 'varchar', 'Parent2Fields', 'N', 'N', 'Y', NULL, 'First Language', 'Student\'s native/first/mother language.', NULL, NULL, 37),
(0000000060, 00000003, 'parent2languageSecond', 'varchar', 'Parent2Fields', 'N', 'N', 'Y', NULL, 'Second Language', NULL, NULL, NULL, 38),
(0000000061, 00000003, 'headingParentGuardian2Contact', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection2', 'Parent/Guardian 2 Contact', NULL, NULL, NULL, 39),
(0000000062, 00000003, 'parent2email', 'email', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Email', NULL, NULL, NULL, 40),
(0000000063, 00000003, 'parent2phone', 'phone', 'Parent2Fields', 'N', 'N', 'Y', NULL, 'Phone', 'Type, country code, number.', NULL, NULL, 41),
(0000000064, 00000003, 'headingParentGuardian2Employment', 'subheading', 'LayoutHeadings', 'N', 'N', 'N', 'parentSection2', 'Parent/Guardian 2 Employment', NULL, NULL, NULL, 42),
(0000000065, 00000003, 'parent2profession', 'varchar', 'Parent2Fields', 'Y', 'N', 'Y', NULL, 'Profession', NULL, NULL, NULL, 43),
(0000000066, 00000003, 'parent2employer', 'varchar', 'Parent2Fields', 'N', 'N', 'Y', NULL, 'Employer', NULL, NULL, NULL, 44),
(0000000067, 00000003, 'heading', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Parent/Guardian 1', '', NULL, NULL, 5),
(0000000068, 00000003, 'headingParentGuardian2', 'heading', 'LayoutHeadings', 'N', 'N', 'N', 'familySection', 'Parent/Guardian 2', NULL, NULL, NULL, 25),
(0000000069, 00000003, 'parent1Documents', 'personalDocument', 'PersonalDocuments', 'N', 'N', 'N', NULL, 'Personal Documents', NULL, NULL, NULL, 24),
(0000000070, 00000003, 'headingSiblings', 'heading', 'LayoutHeadings', 'N', 'N', 'N', 'familySection', 'Siblings', NULL, NULL, NULL, 46),
(0000000071, 00000003, 'siblings', 'varchar', 'FamilyFields', 'N', 'N', 'Y', NULL, 'Siblings', 'Please give information on the applicants\'s siblings.', NULL, NULL, 47),
(0000000072, 00000004, 'requiredDocuments', 'files', 'RequiredDocuments', 'N', 'N', 'N', 'Most Recent School Report,Recent Photo of Applicant,Special Educational Needs Report ', 'Required Documents', '', NULL, NULL, 3),
(0000000073, 00000004, 'headingRequiredDocuments', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Supporting Documents', 'Please provide the following documents.', NULL, NULL, 1),
(0000000074, 00000004, 'studentDocuments', 'personalDocument', 'PersonalDocuments', 'N', 'N', 'N', NULL, 'Personal Documents', NULL, NULL, NULL, 2),
(0000000075, 00000005, 'headingMedicalInformation', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Medical Information', NULL, NULL, NULL, 1),
(0000000076, 00000005, 'medical', 'varchar', 'MedicalFields', 'X', 'N', 'N', NULL, 'Medical Conditions', 'Does your child have any medical conditions or concerns?', NULL, NULL, 2),
(0000000077, 00000005, 'medicalInformation', 'varchar', 'MedicalFields', 'Y', 'N', 'N', NULL, 'Medical Information', 'Please indicate any medical conditions.', NULL, NULL, 3),
(0000000078, 00000005, 'headingSpecialEducationalNeeds', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Special Educational Needs', NULL, NULL, NULL, 4),
(0000000079, 00000005, 'sen', 'varchar', 'INFields', 'X', 'N', 'N', NULL, 'Special Educational Needs (SEN)', 'Are there any known or suspected SEN concerns, or previous SEN assessments?', NULL, NULL, 5),
(0000000080, 00000005, 'senDetails', 'varchar', 'INFields', 'Y', 'N', 'N', NULL, 'SEN Details', 'Provide any comments or information concerning your child\'s development and SEN history.', NULL, NULL, 6),
(0000000081, 00000005, 'headingPayment', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Payment', '', NULL, NULL, 7),
(0000000082, 00000005, 'payment', 'varchar', 'FinanceFields', 'X', 'N', 'Y', NULL, 'Send Future Invoices To', NULL, NULL, NULL, 9),
(0000000083, 00000005, 'companyName', 'varchar', 'FinanceFields', 'X', 'N', 'Y', NULL, 'Company Name', NULL, NULL, NULL, 10),
(0000000084, 00000005, 'companyContact', 'varchar', 'FinanceFields', 'X', 'N', 'Y', NULL, 'Company Contact Person', NULL, NULL, NULL, 11),
(0000000085, 00000005, 'companyAddress', 'varchar', 'FinanceFields', 'X', 'N', 'Y', NULL, 'Company Address', NULL, NULL, NULL, 12),
(0000000086, 00000005, 'companyEmail', 'varchar', 'FinanceFields', 'X', 'N', 'Y', NULL, 'Company Emails', 'Comma-separated list of email address', NULL, NULL, 13),
(0000000087, 00000005, 'companyCCFamily', 'varchar', 'FinanceFields', 'N', 'N', 'Y', NULL, 'CC Family?', 'Should the family be sent a copy of billing emails?', NULL, NULL, 14),
(0000000088, 00000005, 'companyPhone', 'varchar', 'FinanceFields', 'N', 'N', 'Y', NULL, 'Company Phone', NULL, NULL, NULL, 15),
(0000000089, 00000005, 'companyAll', 'varchar', 'FinanceFields', 'N', 'N', 'Y', NULL, 'Company All?', 'Should all items be billed to the specified company, or just some?', NULL, NULL, 16),
(0000000090, 00000005, 'gibbonFinanceFeeCategoryIDList', 'varchar', 'FinanceFields', 'N', 'N', 'Y', NULL, 'Company Fee Categories', 'If the specified company is not paying all fees, which categories are they paying?', NULL, NULL, 17),
(0000000091, 00000005, 'headingAgreement', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Agreement', 'This is example text. Edit it to suit your school context.', NULL, NULL, 22),
(0000000092, 00000005, 'agreement', 'checkbox', 'AgreementFields', 'X', 'N', 'N', NULL, 'Do you agree to the above?', NULL, NULL, NULL, 23),
(0000000093, 00000002, 'headingLanguageSelection', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Language Selection', 'This is example text. Edit it to suit your school context.', NULL, NULL, 11),
(0000000094, 00000002, 'languageChoice', 'select', 'LanguageFields', 'N', 'N', 'N', 'One,Two,Three', 'Language Choice', 'Please choose preferred additional language to study.', NULL, NULL, 12),
(0000000095, 00000002, 'languageChoiceExperience', 'varchar', 'LanguageFields', 'N', 'N', 'N', NULL, 'Language Choice Experience', 'Has the applicant studied the selected language before? If so, please describe the level and type of experience.', NULL, NULL, 13),
(0000000096, 00000002, 'headingScholarships', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Scholarships', 'Information to display before the scholarship options', NULL, NULL, 14),
(0000000097, 00000002, 'scholarshipInterest', 'radio', 'ScholarshipFields', 'N', 'N', 'N', NULL, 'Interest', 'Indicate if you are interested in a scholarship.', NULL, NULL, 15),
(0000000098, 00000002, 'scholarshipRequired', 'radio', 'ScholarshipFields', 'N', 'N', 'N', NULL, 'Required?', 'Is a scholarship required for you to take up a place at the school?', NULL, NULL, 16),
(0000000099, 00000005, 'text', 'layout', 'LayoutText', 'N', 'N', 'N', NULL, '', 'If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.', NULL, NULL, 8),
(0000000100, 00000005, 'headingMiscellaneous', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Miscellaneous', NULL, NULL, NULL, 18),
(0000000101, 00000005, 'howDidYouHear', 'varchar', 'AdmissionsFields', 'N', 'N', 'Y', NULL, 'How Did You Hear About Us?', NULL, NULL, NULL, 19),
(0000000102, 00000001, 'email', 'varchar', 'StudentFields', 'N', 'N', 'N', NULL, 'Email', NULL, NULL, NULL, 18),
(0000000103, 00000001, 'headingStudent', 'heading', 'LayoutHeadings', 'N', 'N', 'N', NULL, 'Student', NULL, NULL, NULL, 1),
(0000000104, 00000003, 'parent2Documents', 'personalDocument', 'PersonalDocuments', 'N', 'N', 'N', NULL, 'Personal Documents', NULL, NULL, NULL, 45);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFormGroup`
--

CREATE TABLE `gibbonFormGroup` (
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(20) NOT NULL,
  `nameShort` varchar(8) NOT NULL,
  `gibbonPersonIDTutor` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDTutor2` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDTutor3` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDEA` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDEA2` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDEA3` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSpaceID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupIDNext` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `attendance` enum('Y','N') NOT NULL DEFAULT 'Y',
  `website` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFormPage`
--

CREATE TABLE `gibbonFormPage` (
  `gibbonFormPageID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) NOT NULL,
  `introduction` text,
  `postscript` text,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonFormPage`
--

INSERT INTO `gibbonFormPage` (`gibbonFormPageID`, `gibbonFormID`, `name`, `introduction`, `postscript`, `sequenceNumber`) VALUES
(00000001, 000001, 'Student', '', '', 1),
(00000002, 000001, 'Enrolment', '', '', 2),
(00000003, 000001, 'Family', '', '', 3),
(00000004, 000001, 'Documents', '', '', 4),
(00000005, 000001, 'Final Details', '', '', 5);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFormSubmission`
--

CREATE TABLE `gibbonFormSubmission` (
  `gibbonFormSubmissionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormPageID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `foreignTable` varchar(60) DEFAULT NULL,
  `foreignTableID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Incomplete','Pending','Processed','Withdrawn') NOT NULL DEFAULT 'Incomplete',
  `owner` varchar(75) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `data` mediumtext,
  `result` text,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `timestampModified` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDModified` int(10) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonFormUpload`
--

CREATE TABLE `gibbonFormUpload` (
  `gibbonFormUploadID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormFieldID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `foreignTable` varchar(60) DEFAULT NULL,
  `foreignTableID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `confirmed` enum('Y','N') NOT NULL DEFAULT 'N',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonGroup`
--

CREATE TABLE `gibbonGroup` (
  `gibbonGroupID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDOwner` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) NOT NULL,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `timestampUpdated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonGroupPerson`
--

CREATE TABLE `gibbonGroupPerson` (
  `gibbonGroupPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonGroupID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonHook`
--

CREATE TABLE `gibbonHook` (
  `gibbonHookID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('Public Home Page','Student Profile','Parental Dashboard','Staff Dashboard','Student Dashboard','Report Writing','Staff Profile','Lesson Planner') DEFAULT NULL,
  `options` text NOT NULL,
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL NOT NULL COMMENT 'The module which installed this hook.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonHouse`
--

CREATE TABLE `gibbonHouse` (
  `gibbonHouseID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `nameShort` varchar(10) NOT NULL,
  `logo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibboni18n`
--

CREATE TABLE `gibboni18n` (
  `gibboni18nID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `version` varchar(10) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `installed` enum('Y','N') NOT NULL DEFAULT 'N',
  `systemDefault` enum('Y','N') NOT NULL DEFAULT 'N',
  `dateFormat` varchar(20) NOT NULL,
  `dateFormatRegEx` text NOT NULL,
  `dateFormatPHP` varchar(20) NOT NULL,
  `rtl` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibboni18n`
--

INSERT INTO `gibboni18n` (`gibboni18nID`, `code`, `name`, `version`, `active`, `installed`, `systemDefault`, `dateFormat`, `dateFormatRegEx`, `dateFormatPHP`, `rtl`) VALUES
(0001, 'en_GB', 'English - United Kingdom', NULL, 'Y', 'N', 'Y', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0002, 'en_US', 'English - United States', NULL, 'Y', 'N', 'N', 'mm/dd/yyyy', '/(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20\\d\\d)/', 'm/d/Y', 'N'),
(0003, 'es_ES', 'Español - España', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0004, 'zh_CN', '汉语 - 中国', NULL, 'Y', 'N', 'N', 'yyyy-mm-dd', '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', 'Y-m-d', 'N'),
(0005, 'zh_HK', '體字 - 香港', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0007, 'pl_PL', 'Język polski - Polska', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0008, 'it_IT', 'Italiano - Italia', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0010, 'id_ID', 'Bahasa Indonesia - Indonesia', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0011, 'ar_SA', 'العربية - المملكة العربية السعودية', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'Y'),
(0012, 'fr_FR', 'Français - France', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0013, 'ur_PK', 'پاکستان - اُردُو', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'Y'),
(0014, 'sw_KE', 'Swahili', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0015, 'pt_PT', 'Português', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0016, 'ro_RO', 'Română', NULL, 'Y', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0017, 'ja_JP', '日本語', NULL, 'N', 'N', 'N', 'yyyy-mm-dd', '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', 'Y-m-d', 'N'),
(0018, 'ru_RU', 'ру́сский язы́к', NULL, 'N', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0019, 'uk_UA', 'українська мова', NULL, 'N', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0020, 'bn_BD', 'বাংলা', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0021, 'da_DK', 'Dansk - Danmark', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0022, 'fa_IR', 'فارسی', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'Y'),
(0023, 'pt_BR', 'Português - Brasil', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0024, 'ka_GE', 'ქართული ენა', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0025, 'nl_NL', 'Dutch - Nederland', NULL, 'Y', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0026, 'hu_HU', 'Magyar - Magyarország', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0027, 'bg_BG', 'български език', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0028, 'ko_KP', '한국어 - 대한민국', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0029, 'fi_FI', 'Suomen Kieli - Suomi', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0030, 'de_DE', 'Deutsch - Deutschland', NULL, 'Y', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[.](0[1-9]|1[012])[.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0031, 'in_OR', 'ଓଡ଼ିଆ - इंडिया', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0032, 'no_NO', 'Norsk - Norge', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0033, 'vi_VN', 'Tiếng Việt - Việt Nam', NULL, 'Y', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0034, 'sq_AL', 'Shqip - Shqipëri', NULL, 'Y', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0035, 'th_TH', 'ภาษาไทย - ราชอาณาจักรไทย', NULL, 'Y', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0036, 'el_GR', 'ελληνικά - Ελλάδα', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0037, 'am_ET', 'አማርኛ - ኢትዮጵያ', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0038, 'om_ET', 'Afaan Oromo - Ethiopia', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0039, 'hr_HR', 'Hrvatski - Hrvatska', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0040, 'et_EE', 'Eesti Keel - Eesti', NULL, 'N', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0041, 'he_IL', 'עברית - ישראל', NULL, 'Y', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'Y'),
(0042, 'tr_TR', 'Türkçe - Türkiye', NULL, 'Y', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0043, 'my_MM', 'မြန်မာ - မြန်မာ', NULL, 'N', 'N', 'N', 'dd-mm-yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd-m-Y', 'N'),
(0044, 'es_MX', 'Español - Mexico', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0045, 'af_ZA', 'Afrikaans - Suid-Afrika', '21.0.00', 'Y', 'Y', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0046, 'uk_UA', 'українська мова - Україна', '21.0.00', 'Y', 'N', 'N', 'dd.mm.yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd.m.Y', 'N'),
(0047, 'es_DO', 'Español - República Dominicana', NULL, 'Y', 'N', 'N', 'dd/mm/yyyy', '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\\d\\d$/i', 'd/m/Y', 'N'),
(0048, 'lt_LT', 'Lietuvių Kalba - Lithuanian', '25.0.00', 'Y', 'N', 'N', 'yyyy.mm.dd', '/^[0-9]{4}.(0[1-9]|1[0-2]).(0[1-9]|[1-2][0-9]|3[0-1])$/', 'Y.m.d', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonIN`
--

CREATE TABLE `gibbonIN` (
  `gibbonINID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `strategies` text NOT NULL,
  `targets` text NOT NULL,
  `notes` text NOT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINArchive`
--

CREATE TABLE `gibbonINArchive` (
  `gibbonINArchiveID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `strategies` text NOT NULL,
  `targets` text NOT NULL,
  `notes` text NOT NULL,
  `descriptors` text NOT NULL COMMENT 'Serialised array of descriptors.',
  `archiveTitle` varchar(50) NOT NULL,
  `archiveTimestamp` timestamp NULL DEFAULT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINAssistant`
--

CREATE TABLE `gibbonINAssistant` (
  `gibbonINAssistantID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDAssistant` int(10) UNSIGNED ZEROFILL NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINDescriptor`
--

CREATE TABLE `gibbonINDescriptor` (
  `gibbonINDescriptorID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `nameShort` varchar(5) NOT NULL,
  `description` text NOT NULL,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonINDescriptor`
--

INSERT INTO `gibbonINDescriptor` (`gibbonINDescriptorID`, `name`, `nameShort`, `description`, `sequenceNumber`) VALUES
(001, 'Special Education Needs', 'SEN', 'Official learning needs that have been professionally identified.', 1),
(002, 'English as an Additional Language', 'EAL', 'Obvious language needs in English acquisition.', 2),
(003, 'Other Needs', 'ON', 'Any other case. E.g. learning issues that have not been assessed, or ongoing home/family issues that should be known to staff and which may relate to teaching and learning.', 3);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINInvestigation`
--

CREATE TABLE `gibbonINInvestigation` (
  `gibbonINInvestigationID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `status` enum('Referral','Resolved','Investigation','Investigation Complete') DEFAULT NULL,
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `strategiesTried` text NOT NULL,
  `parentsInformed` enum('N','Y') NOT NULL DEFAULT 'N',
  `parentsResponse` text,
  `resolutionDetails` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINInvestigationContribution`
--

CREATE TABLE `gibbonINInvestigationContribution` (
  `gibbonINInvestigationContributionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonINInvestigationID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Teacher','Head of Year') NOT NULL DEFAULT 'Teacher',
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `cognition` text,
  `memory` text,
  `selfManagement` text,
  `attention` text,
  `socialInteraction` text,
  `communication` text,
  `comment` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonINPersonDescriptor`
--

CREATE TABLE `gibbonINPersonDescriptor` (
  `gibbonINPersonDescriptorID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonINDescriptorID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonAlertLevelID` int(3) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonInternalAssessmentColumn`
--

CREATE TABLE `gibbonInternalAssessmentColumn` (
  `gibbonInternalAssessmentColumnID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `groupingID` int(8) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'A value used to group multiple columns.',
  `name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `attainment` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonScaleIDAttainment` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `effort` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonScaleIDEffort` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `comment` enum('Y','N') NOT NULL DEFAULT 'Y',
  `uploadedResponse` enum('N','Y') NOT NULL DEFAULT 'N',
  `complete` enum('N','Y') NOT NULL,
  `completeDate` date DEFAULT NULL,
  `viewableStudents` enum('N','Y') NOT NULL,
  `viewableParents` enum('N','Y') NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonInternalAssessmentEntry`
--

CREATE TABLE `gibbonInternalAssessmentEntry` (
  `gibbonInternalAssessmentEntryID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonInternalAssessmentColumnID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `attainmentValue` varchar(10) DEFAULT NULL,
  `attainmentDescriptor` varchar(100) DEFAULT NULL,
  `effortValue` varchar(10) DEFAULT NULL,
  `effortDescriptor` varchar(100) DEFAULT NULL,
  `comment` text,
  `response` text,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLanguage`
--

CREATE TABLE `gibbonLanguage` (
  `gibbonLanguageID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonLanguage`
--

INSERT INTO `gibbonLanguage` (`gibbonLanguageID`, `name`) VALUES
(0001, 'Afrikaans'),
(0002, 'Albanian'),
(0003, 'Arabic'),
(0004, 'Armenian'),
(0005, 'Basque'),
(0006, 'Bengali'),
(0007, 'Bulgarian'),
(0008, 'Catalan'),
(0009, 'Cambodian'),
(0010, 'Chinese (Mandarin)'),
(0011, 'Chinese (Cantonese)'),
(0012, 'Croatian'),
(0013, 'Czech'),
(0014, 'Danish'),
(0015, 'Dutch'),
(0016, 'English'),
(0017, 'Estonian'),
(0018, 'Fijian'),
(0019, 'Finnish'),
(0020, 'French'),
(0021, 'Georgian'),
(0022, 'German'),
(0023, 'Greek'),
(0024, 'Gujarati'),
(0025, 'Hebrew'),
(0026, 'Hindi'),
(0027, 'Hungarian'),
(0028, 'Icelandic'),
(0029, 'Indonesian'),
(0030, 'Irish'),
(0031, 'Italian'),
(0032, 'Japanese'),
(0033, 'Javanese'),
(0034, 'Korean'),
(0035, 'Latin'),
(0036, 'Latvian'),
(0037, 'Lithuanian'),
(0038, 'Macedonian'),
(0039, 'Malay'),
(0040, 'Malayalam'),
(0041, 'Maltese'),
(0042, 'Maori'),
(0043, 'Marathi'),
(0044, 'Mongolian'),
(0045, 'Nepali'),
(0046, 'Norwegian'),
(0047, 'Persian'),
(0048, 'Polish'),
(0049, 'Portuguese'),
(0050, 'Punjabi'),
(0051, 'Quechua'),
(0052, 'Romanian'),
(0053, 'Russian'),
(0054, 'Samoan'),
(0055, 'Serbian'),
(0056, 'Slovak'),
(0057, 'Slovenian'),
(0058, 'Spanish'),
(0059, 'Swahili'),
(0060, 'Swedish'),
(0061, 'Tamil'),
(0062, 'Tatar'),
(0063, 'Telugu'),
(0064, 'Thai'),
(0065, 'Tibetan'),
(0066, 'Tongan'),
(0067, 'Turkish'),
(0068, 'Ukrainian'),
(0069, 'Urdu'),
(0070, 'Uzbek'),
(0071, 'Vietnamese'),
(0072, 'Welsh'),
(0073, 'Xhosa'),
(0074, 'Odia'),
(0075, 'Myanmar'),
(0076, 'Burmese'),
(0077, 'Filipino'),
(0078, 'Sinhala'),
(0079, 'Malagasy'),
(0080, 'Maldivian'),
(0081, 'Somali'),
(0082, 'Zulu'),
(0083, 'Tamazight');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLibraryItem`
--

CREATE TABLE `gibbonLibraryItem` (
  `gibbonLibraryItemID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonLibraryItemIDParent` int(10) DEFAULT NULL,
  `gibbonLibraryTypeID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Name for book, model for computer, etc.',
  `producer` varchar(255) NOT NULL COMMENT 'Author for book, manufacturer for computer, etc',
  `fields` text NOT NULL COMMENT 'JSON object',
  `vendor` varchar(100) NOT NULL,
  `purchaseDate` date DEFAULT NULL,
  `invoiceNumber` varchar(50) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `imageType` enum('','Link','File') NOT NULL DEFAULT '' COMMENT 'Type of image. Image should be 240px x 240px, or smaller.',
  `imageLocation` varchar(255) NOT NULL COMMENT 'URL or local FS path of image.',
  `comment` text NOT NULL,
  `gibbonSpaceID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `locationDetail` varchar(255) NOT NULL,
  `ownershipType` enum('School','Individual') NOT NULL DEFAULT 'School',
  `gibbonPersonIDOwnership` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'If owned by school, then this is the main user. If owned by individual, then this is that individual.',
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'Who is responsible for managing this item? By default this will be the person who added the record, but it can be changed.',
  `replacement` enum('Y','N') NOT NULL DEFAULT 'Y',
  `replacementCost` decimal(10,2) DEFAULT NULL,
  `gibbonSchoolYearIDReplacement` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `physicalCondition` enum('','As New','Lightly Worn','Moderately Worn','Damaged','Unusable') NOT NULL,
  `bookable` enum('N','Y') NOT NULL DEFAULT 'N',
  `borrowable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` enum('Available','In Use','Decommissioned','Lost','On Loan','Repair','Reserved','On Order') NOT NULL DEFAULT 'Available' COMMENT 'The current status of the item.',
  `gibbonPersonIDStatusResponsible` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'The person who is responsible for the current status.',
  `gibbonPersonIDStatusRecorder` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'The person who recored the current status.',
  `timestampStatus` datetime DEFAULT NULL COMMENT 'The time the status was recorded',
  `returnExpected` date DEFAULT NULL COMMENT 'The time when the event expires.',
  `returnAction` enum('Make Available','Decommission','Repair','Reserve') DEFAULT NULL COMMENT 'What to do when the item is returned?',
  `gibbonPersonIDReturnAction` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` datetime NOT NULL,
  `gibbonPersonIDUpdate` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampUpdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLibraryItemEvent`
--

CREATE TABLE `gibbonLibraryItemEvent` (
  `gibbonLibraryItemEventID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonLibraryItemID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Decommission','Loss','Loan','Repair','Reserve','Other') NOT NULL DEFAULT 'Other' COMMENT 'This is maintained even after the item is returned, so we know what type of event it was.',
  `status` enum('Available','Decommissioned','Lost','On Loan','Repair','Reserved','Returned') NOT NULL DEFAULT 'Available',
  `gibbonPersonIDStatusResponsible` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'The person who was responsible for the event.',
  `gibbonPersonIDOut` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'The person who recored the event.',
  `timestampOut` datetime DEFAULT NULL COMMENT 'The time the event was recorded',
  `returnExpected` date DEFAULT NULL COMMENT 'The time when the event expires.',
  `returnAction` enum('Make Available','Decommission','Repair','Reserve') DEFAULT NULL COMMENT 'What to do when the item is returned?',
  `gibbonPersonIDReturnAction` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampReturn` datetime DEFAULT NULL,
  `gibbonPersonIDIn` int(10) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLibraryShelf`
--

CREATE TABLE `gibbonLibraryShelf` (
  `gibbonLibraryShelfID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  `shuffle` enum('N','Y') NOT NULL DEFAULT 'N',
  `field` varchar(90) NOT NULL,
  `fieldValue` varchar(90) DEFAULT NULL,
  `type` enum('Manual','Automatic') NOT NULL DEFAULT 'Manual',
  `sequenceNumber` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLibraryShelfItem`
--

CREATE TABLE `gibbonLibraryShelfItem` (
  `gibbonLibraryShelfItemID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonLibraryItemID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonLibraryShelfID` int(6) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLibraryType`
--

CREATE TABLE `gibbonLibraryType` (
  `gibbonLibraryTypeID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `fields` text NOT NULL COMMENT 'JSON object'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonLibraryType`
--

INSERT INTO `gibbonLibraryType` (`gibbonLibraryTypeID`, `name`, `active`, `fields`) VALUES
(00004, 'Print Publication', 'Y', '[{\"name\":\"Format\",\"description\":\"\",\"type\":\"Select\",\"options\":\",Art - Original,Art - Reproduction,Book,Braille,Cartographic material,Chart,Diorama,Electronic Resource,Filmstrip,Flash Card,Game,Globe,Journal,Kit,Large print,Magazine,Manuscript,Microform,Microscope slide,Model,Motion Picture,Music,Picture,Realia,Resource,Serial,Slide,Sound Recording,Technical Drawing,Text,Toy,Transparency,Videorecording\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Publisher\",\"description\":\"Name of the company who published the volume.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Publication Date\",\"description\":\"Format: dd\\/mm\\/yyyy, mm\\/yyyy or yyyy.\",\"type\":\"Text\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Country of Publication\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Edition\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"ISBN10\",\"description\":\"10-digit unique ISBN number.\",\"type\":\"Text\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"ISBN13\",\"description\":\"13-digit unique ISBN number.\",\"type\":\"Text\",\"options\":\"13\",\"default\":\"\",\"required\":\"Y\"},{\"name\":\"Description\",\"description\":\"A brief blurb describing the volume.\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Subjects\",\"description\":\"Comma separated list of subjects.\",\"type\":\"Textarea\",\"options\":\"2\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Collection\",\"description\":\"\",\"type\":\"Select\",\"options\":\",Fiction, Fiction - Best Sellers, Fiction - Classics, Fiction - Mystery, Fiction - Series, Fiction - Young Adult, Nonfiction, Nonfiction - College Prep, Nonfiction - Graphic Novels, Nonfiction - Life Skills, Nonfiction - Reference\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Control Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Cataloging Authority\",\"description\":\"Issuing authority for Control Number.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Reader Age (Youngest)\",\"description\":\"Age in years, youngest reading age recommendation.\",\"type\":\"Text\",\"options\":\"3\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Reader Age (Oldest)\",\"description\":\"Age in years, oldest reading age recommendation.\",\"type\":\"Text\",\"options\":\"3\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Page Count\",\"description\":\"The number of pages in the volume.\",\"type\":\"Text\",\"options\":\"4\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Height\",\"description\":\"The physical height of the volume, in cm.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Width\",\"description\":\"The physical width of the volume, in cm.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Thickness\",\"description\":\"The physical thickness of the volume, in cm.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Language\",\"description\":\"The primary language of the volume.\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Link\",\"description\":\"Link to web-based information on the volume.\",\"type\":\"URL\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"}]'),
(00007, 'Computer', 'Y', '[{\"name\":\"Form Factor\",\"description\":\"\",\"type\":\"Select\",\"options\":\"Desktop, Laptop, Tablet, Phone, Set-Top Box, Rack-Mounted Server, Other\",\"default\":\"Laptop\",\"required\":\"Y\"},{\"name\":\"Operating System\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"CPU Type\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"CPU Speed\",\"description\":\"In GHz.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Memory\",\"description\":\"Total RAM, in GB.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Storage Type\",\"description\":\"Primary internal storage type.\",\"type\":\"Select\",\"options\":\",HDD, SSD, Hybrid, Other\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Storage\",\"description\":\"Total HDD/SDD capacity, in GB.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wireless MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wired MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, display dongles, remotes etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd/mm/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Last Reinstall Date\",\"description\":\"Format: dd/mm/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]'),
(00008, 'Electronics', 'Y', '[{\"name\":\"Type\",\"description\":\"What kind of product is this?\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"Y\"},{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, remotes controls, etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd\\/mm\\/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log\\/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]'),
(00009, 'Other', 'Y', '[{\"name\":\"Type\",\"description\":\"What kind of product is this?\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"Y\"}]'),
(00010, 'Software', 'Y', '[{\"name\":\"Version\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Operating System\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"License Type\",\"description\":\"E.g. Open Source, Site License, number of users.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"License Name\",\"description\":\"If the software is registered, who is it registered to?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"License Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"License Expiry\",\"description\":\"Format: dd\\/mm\\/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"License Management Link\",\"description\":\"Link to web-based management tool.\",\"type\":\"URL\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"}]'),
(00011, 'Audio/Visual Hardware', 'Y', '[{\"name\":\"Type\",\"description\":\"What kind of product is this?\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"Y\"},{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, remotes controls, etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd\\/mm\\/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log\\/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]'),
(00012, 'Optical Media', 'Y', '[{\"name\":\"Type\",\"description\":\"What type of optical media is this?\",\"type\":\"Select\",\"options\":\"CD,DVD,Blu-Ray\",\"default\":\"\",\"required\":\"Y\"},{\"name\":\"Format\",\"description\":\"Technical details of media formatting.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Language\",\"description\":\"\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Subtitles\",\"description\":\"\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Aspect Ratio\",\"description\":\"\",\"type\":\"Text\",\"options\":\"20\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Number of Discs\",\"description\":\"\",\"type\":\"Select\",\"options\":\",1,2,3,4,5\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Content Rating\",\"description\":\"Details of age guidance or retrictions.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Studio\",\"description\":\"Name of originating studio.\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Release Date\",\"description\":\"Format: dd\\/mm\\/yyyy, mm\\/yyyy or yyyy.\",\"type\":\"Text\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Run Time\",\"description\":\"In minutes.\",\"type\":\"Text\",\"options\":\"3\",\"default\":\"\",\"required\":\"N\"}]'),
(00013, 'Telephone', 'Y', '[{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Telephone Number\",\"description\":\"External telephone number\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Telephone Extension\",\"description\":\"Internal telephone extension\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, remotes controls, etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd\\/mm\\/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wireless MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wired MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log\\/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonLog`
--

CREATE TABLE `gibbonLog` (
  `gibbonLogID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(50) NOT NULL,
  `serialisedArray` text,
  `ip` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMarkbookColumn`
--

CREATE TABLE `gibbonMarkbookColumn` (
  `gibbonMarkbookColumnID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonHookID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearTermID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `groupingID` int(8) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'A value used to group multiple markbook columns.',
  `type` varchar(50) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `description` text NOT NULL,
  `date` date DEFAULT NULL,
  `sequenceNumber` int(3) UNSIGNED NOT NULL DEFAULT '0',
  `attachment` varchar(255) NOT NULL,
  `attainment` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonScaleIDAttainment` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `attainmentWeighting` decimal(5,2) DEFAULT NULL,
  `attainmentRaw` enum('Y','N') NOT NULL DEFAULT 'N',
  `attainmentRawMax` decimal(8,2) DEFAULT NULL,
  `effort` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonScaleIDEffort` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonRubricIDAttainment` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonRubricIDEffort` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `comment` enum('Y','N') NOT NULL DEFAULT 'Y',
  `uploadedResponse` enum('Y','N') NOT NULL DEFAULT 'Y',
  `complete` enum('N','Y') NOT NULL,
  `completeDate` date DEFAULT NULL,
  `viewableStudents` enum('N','Y') NOT NULL,
  `viewableParents` enum('N','Y') NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMarkbookEntry`
--

CREATE TABLE `gibbonMarkbookEntry` (
  `gibbonMarkbookEntryID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonMarkbookColumnID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `modifiedAssessment` enum('N','Y') DEFAULT NULL,
  `attainmentValue` varchar(10) DEFAULT NULL,
  `attainmentValueRaw` varchar(10) DEFAULT NULL,
  `attainmentDescriptor` varchar(100) DEFAULT NULL,
  `attainmentConcern` enum('N','Y','P') DEFAULT NULL COMMENT '''P'' denotes that student has exceed their personal target',
  `effortValue` varchar(10) DEFAULT NULL,
  `effortDescriptor` varchar(100) DEFAULT NULL,
  `effortConcern` enum('N','Y') DEFAULT NULL,
  `comment` text,
  `response` varchar(255) DEFAULT NULL,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMarkbookTarget`
--

CREATE TABLE `gibbonMarkbookTarget` (
  `gibbonMarkbookTargetID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMarkbookWeight`
--

CREATE TABLE `gibbonMarkbookWeight` (
  `gibbonMarkbookWeightID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `type` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `reportable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `calculate` enum('term','year') NOT NULL DEFAULT 'year',
  `weighting` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMedicalCondition`
--

CREATE TABLE `gibbonMedicalCondition` (
  `gibbonMedicalConditionID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonMedicalCondition`
--

INSERT INTO `gibbonMedicalCondition` (`gibbonMedicalConditionID`, `name`, `description`) VALUES
(0001, 'Allergy - Food', NULL),
(0002, 'Allergy - Insect', NULL),
(0003, 'Allergy - Drug', NULL),
(0004, 'Allergy - Animal', NULL),
(0005, 'Allergy - Grass/Pollen', NULL),
(0006, 'Allergy - Other', NULL),
(0007, 'Asthma', NULL),
(0008, 'G6PD Deficiency', NULL),
(0009, 'Joint Problems', NULL),
(0010, 'Diabetes', NULL),
(0011, 'Hypertension', NULL),
(0012, 'Convulsions/Epilepsy', NULL),
(0013, 'Kidney Disease', NULL),
(0014, 'Rare Blood Type', NULL),
(0015, 'Heart Condition', NULL),
(0016, 'Previous Concussion or Head Injury', NULL),
(0017, 'Previous Serious Injury', NULL),
(0018, 'Dizziness or Fainting spells', NULL),
(0019, 'Rheumatic Fever', NULL),
(0020, 'Frequent Nose Bleeds', NULL),
(0021, 'Psychological Condition', NULL),
(0022, 'Hearing Impairment', NULL),
(0023, 'Visual Impairment', NULL),
(0024, 'Visual Impairment - Requiring Contact Lenses or Glasses', NULL),
(0025, 'Visual Impairment - Colour Blindness', NULL),
(0026, 'Travel Sickness', NULL),
(0027, 'Other', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMessenger`
--

CREATE TABLE `gibbonMessenger` (
  `gibbonMessengerID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Draft','Sending','Sent') NOT NULL DEFAULT 'Sent',
  `email` enum('N','Y') NOT NULL DEFAULT 'N',
  `messageWall` enum('N','Y') NOT NULL DEFAULT 'N',
  `messageWallPin` enum('N','Y') NOT NULL DEFAULT 'N',
  `messageWall_dateStart` date DEFAULT NULL,
  `messageWall_dateEnd` date DEFAULT NULL,
  `sms` enum('N','Y') NOT NULL DEFAULT 'N',
  `subject` varchar(60) NOT NULL,
  `body` text NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `emailFrom` varchar(75) DEFAULT NULL,
  `emailReplyTo` varchar(75) DEFAULT NULL,
  `emailReport` text NOT NULL,
  `emailReceipt` enum('N','Y') DEFAULT NULL,
  `emailReceiptText` text,
  `individualNaming` enum('N','Y') NOT NULL DEFAULT 'N',
  `includeSignature` enum('N','Y') NOT NULL DEFAULT 'N',
  `smsReport` text NOT NULL,
  `confidential` enum('N','Y') NOT NULL DEFAULT 'N',
  `enableSharingLink` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMessengerCannedResponse`
--

CREATE TABLE `gibbonMessengerCannedResponse` (
  `gibbonMessengerCannedResponseID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `subject` varchar(30) NOT NULL,
  `body` text NOT NULL,
  `timestampCreator` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMessengerReceipt`
--

CREATE TABLE `gibbonMessengerReceipt` (
  `gibbonMessengerReceiptID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonMessengerID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `sent` enum('N','Y') NOT NULL DEFAULT 'N',
  `targetType` enum('Class','Course','Form Group','Year Group','Activity','Role','Applicants','Individuals','Houses','Role Category','Transport','Attendance','Group') NOT NULL,
  `targetID` varchar(30) NOT NULL,
  `contactType` enum('Email','SMS') DEFAULT NULL,
  `contactDetail` varchar(255) DEFAULT NULL,
  `key` varchar(40) DEFAULT NULL,
  `confirmed` enum('N','Y') DEFAULT NULL,
  `confirmedTimestamp` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDListStudent` text,
  `nameListStudent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMessengerTarget`
--

CREATE TABLE `gibbonMessengerTarget` (
  `gibbonMessengerTargetID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonMessengerID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Class','Course','Form Group','Year Group','Activity','Role','Applicants','Individuals','Houses','Role Category','Transport','Attendance','Group') DEFAULT NULL,
  `id` varchar(30) NOT NULL,
  `parents` enum('N','Y') NOT NULL DEFAULT 'N',
  `students` enum('N','Y') NOT NULL DEFAULT 'N',
  `staff` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonMigration`
--

CREATE TABLE `gibbonMigration` (
  `gibbonMigrationID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) NOT NULL,
  `version` varchar(8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonModule`
--

CREATE TABLE `gibbonModule` (
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL NOT NULL COMMENT 'This number is assigned at install, and is only unique to the installation',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT 'This name should be globally unique preferably, but certainly locally unique',
  `description` text NOT NULL,
  `entryURL` varchar(255) NOT NULL DEFAULT 'index.php',
  `type` enum('Core','Additional') NOT NULL DEFAULT 'Core',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `category` varchar(12) NOT NULL,
  `version` varchar(8) NOT NULL,
  `author` varchar(40) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonModule`
--

INSERT INTO `gibbonModule` (`gibbonModuleID`, `name`, `description`, `entryURL`, `type`, `active`, `category`, `version`, `author`, `url`) VALUES
(0001, 'School Admin', 'Allows administrators to configure school settings.', 'schoolYear_manage.php', 'Core', 'Y', 'Admin', '', 'Ross Parker', 'http://rossparker.org'),
(0002, 'User Admin', 'Allows administrators to manage users.', 'user_manage.php', 'Core', 'Y', 'Admin', '', 'Ross Parker', 'http://rossparker.org'),
(0003, 'System Admin', 'Allows administrators to configure system settings.', 'systemOverview.php', 'Core', 'Y', 'Admin', '', 'Ross Parker', 'http://rossparker.org'),
(0004, 'Departments', 'View details within a department', 'departments.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0005, 'Students', 'Allows users to view student data', 'student_view.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0006, 'Attendance', 'School attendance taking', 'attendance.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0007, 'Markbook', 'A system for keeping track of marks', 'markbook_view.php', 'Core', 'Y', 'Assess', '', 'Ross Parker', 'http://rossparker.org'),
(0008, 'Data Updater', 'Allow users to update their family\'s data', 'data_updates.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0009, 'Planner', 'Supports lesson planning and information sharing for staff, student and parents', 'planner.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0011, 'Individual Needs', 'Individual Needs', 'in_view.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0012, 'Crowd Assessment', 'Allows users to assess each other\'s work', 'crowdAssess.php', 'Core', 'Y', 'Assess', '', 'Ross Parker', 'http://rossparker.org'),
(0013, 'Timetable Admin', 'Timetable administration', 'tt.php', 'Core', 'Y', 'Admin', '', 'Ross Parker', 'http://rossparker.org'),
(0014, 'Timetable', 'Allows users to view timetables', 'tt.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0015, 'Activities', 'Run a school activities program', 'activities_view.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0016, 'Formal Assessment', 'Facilitates tracking of student performance in external examinations.', 'externalAssessment.php', 'Core', 'Y', 'Assess', '', 'Ross Parker', 'http://rossparker.org'),
(0119, 'Behaviour', 'Tracking Student Behaviour', 'behaviour_manage.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0121, 'Messenger', 'Unified messenger for email, message wall and more.', 'messenger_manage.php', 'Core', 'Y', 'Other', '', 'Ross Parker', 'http://rossparker.org'),
(0126, 'Rubrics', 'Allows users to create rubrics for assessment', 'rubrics.php', 'Core', 'Y', 'Assess', '', 'Ross Parker', 'http://rossparker.org'),
(0130, 'Library', 'Allows the management of a catalog from which items can be borrowed.', 'library_manage_catalog.php', 'Core', 'Y', 'Learn', '', 'Ross Parker', 'http://rossparker.org'),
(0135, 'Finance', 'Allows a school to issue invoices and track payments.', 'invoices_manage.php', 'Core', 'Y', 'Other', '', 'Ross Parker', 'http://rossparker.org'),
(0136, 'Staff', 'Allows users to view staff information', 'staff_view.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0137, 'Form Groups', 'Allows users to view a listing of form groups', 'formGroups.php', 'Core', 'Y', 'People', '', 'Ross Parker', 'http://rossparker.org'),
(0141, 'Tracking', 'Provides visual graphing of student progress, as recorded in the Markbook and Internal Assessment.', 'graphing.php', 'Core', 'Y', 'Assess', '', 'Ross Parker', 'https://rossparker.org'),
(0144, 'Reports', 'Report writing and PDF generation', 'reporting_my.php', 'Core', 'Y', 'Assess', 'v19.0.', 'Sandra Kuipers', 'https://github.com/SKuipers'),
(0145, 'Admissions', '', 'studentEnrolment_manage.php', 'Core', 'Y', 'People', '', 'Sandra Kuipers', 'https://github.com/SKuipers');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonNotification`
--

CREATE TABLE `gibbonNotification` (
  `gibbonNotificationID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `status` enum('New','Archived') NOT NULL DEFAULT 'New',
  `gibbonModuleID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `count` int(4) NOT NULL DEFAULT '1',
  `text` text NOT NULL,
  `actionLink` varchar(255) NOT NULL COMMENT 'Relative to absoluteURL, start with a forward slash',
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonNotificationEvent`
--

CREATE TABLE `gibbonNotificationEvent` (
  `gibbonNotificationEventID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `event` varchar(90) NOT NULL,
  `moduleName` varchar(30) NOT NULL,
  `actionName` varchar(50) NOT NULL,
  `type` enum('Core','Additional','CLI') NOT NULL DEFAULT 'Core',
  `scopes` varchar(255) NOT NULL DEFAULT 'All',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonNotificationEvent`
--

INSERT INTO `gibbonNotificationEvent` (`gibbonNotificationEventID`, `event`, `moduleName`, `actionName`, `type`, `scopes`, `active`) VALUES
(000001, 'Daily Behaviour Summary', 'Behaviour', 'Find Behaviour Patterns', 'CLI', 'All', 'Y'),
(000002, 'New Negative Record', 'Behaviour', 'View Behaviour Records_all', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000003, 'New Positive Record', 'Behaviour', 'View Behaviour Records_all', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000004, 'Family Data Updates', 'Data Updater', 'Family Data Updates', 'Core', 'All', 'Y'),
(000005, 'Finance Data Updates', 'Data Updater', 'Finance Data Updates', 'Core', 'All', 'Y'),
(000006, 'Medical Form Updates', 'Data Updater', 'Medical Form Updates', 'Core', 'All', 'Y'),
(000007, 'Personal Data Updates', 'Data Updater', 'Personal Data Updates', 'Core', 'All', 'Y'),
(000008, 'Login - Failed', 'User Admin', 'Manage Users', 'Core', 'All', 'Y'),
(000009, 'New Public Registration', 'User Admin', 'Manage Users', 'Core', 'All', 'Y'),
(000010, 'New Application Form', 'Admissions', 'View Student Profile_full', 'Core', 'All', 'Y'),
(000011, 'New Application Form', 'Staff', 'Manage Applications', 'Core', 'All', 'Y'),
(000012, 'Student Withdrawn', 'Activities', 'Manage Activities', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000013, 'New Activity Registration', 'Activities', 'Manage Activities', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000014, 'Updated Individual Needs', 'Individual Needs', 'Individual Needs Records_viewEdit', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000015, 'New Student Note', 'Students', 'View Student Profile_full', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000016, 'Updated Privacy Settings', 'Students', 'View Student Profile_full', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000017, 'Daily Attendance Summary', 'Attendance', 'Form Groups Not Registered', 'CLI', 'All', 'Y'),
(000018, 'User Status Check and Fix', 'User Admin', 'Manage Users', 'CLI', 'All', 'Y'),
(000019, 'Overdue Loan Items', 'Library', 'Lending & Activity Log', 'CLI', 'All', 'Y'),
(000020, 'Behaviour Letters', 'Behaviour', 'View Behaviour Records_all', 'CLI', 'All', 'Y'),
(000021, 'Parent Weekly Email Summary', 'School Admin', 'Parent Weekly Email Summary', 'CLI', 'All', 'Y'),
(000022, 'Application Form Accepted', 'Admissions', 'View Student Profile_full', 'Core', 'All,gibbonYearGroupID', 'Y'),
(000023, 'Weekly Attendance Summary', 'Attendance', 'Attendance Summary by Date', 'CLI', 'All,gibbonYearGroupID', 'Y'),
(000024, 'Student Bumped', 'Activities', 'Manage Activities', 'Core', 'All', 'Y'),
(000025, 'Updated Behaviour Record', 'Behaviour', 'View Behaviour Records_all', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000026, 'Student Withdrawn', 'Admissions', 'View Student Profile_full', 'Core', 'All,gibbonYearGroupID', 'Y'),
(000027, 'New Staff', 'Staff', 'Staff Directory_full', 'Core', 'All', 'Y'),
(000028, 'Staff Left', 'Staff', 'Staff Directory_full', 'Core', 'All', 'Y'),
(000029, 'Parent Daily Email Summary', 'School Admin', 'Parent Weekly Email Summary', 'CLI', 'All', 'Y'),
(000030, 'Tutor Daily Email Summary', 'School Admin', 'Parent Weekly Email Summary', 'CLI', 'All', 'Y'),
(000031, 'Medical Condition', 'Students', 'Manage Medical Forms', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000032, 'New Application with SEN/Medical', 'Admissions', 'Manage Applications', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000033, 'Staff Data Updates', 'Data Updater', 'Staff Data Updates', 'Core', 'All', 'Y'),
(000034, 'Updated Timetable Subscriber', 'Timetable', 'View Timetable by Person', 'Core', 'All', 'Y'),
(000035, 'New Investigation', 'Individual Needs', 'Manage Investigations', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000036, 'Behaviour Record for IN Student', 'Behaviour', 'View Behaviour Records_all', 'Core', 'All,gibbonPersonIDStudent,gibbonYearGroupID', 'Y'),
(000037, 'Remove Stale Notifications', 'User Admin', 'Manage Users', 'CLI', 'All', 'Y'),
(000038, 'Activity Enrolment Added', 'Activities', 'View Activities', 'Core', 'All', 'Y'),
(000039, 'Activity Enrolment Removed', 'Activities', 'View Activities', 'Core', 'All', 'Y'),
(000040, 'Activity Status Changed', 'Activities', 'View Activities', 'Core', 'All', 'Y'),
(000041, 'First Aid Qualification Expiry', 'Staff', 'Manage Staff', 'Core', 'All', 'Y'),
(000042, 'Student Form Group Changed', 'Admissions', 'View Student Profile_full', 'Core', 'All,gibbonYearGroupID', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonNotificationListener`
--

CREATE TABLE `gibbonNotificationListener` (
  `gibbonNotificationListenerID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonNotificationEventID` int(6) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `scopeType` varchar(30) DEFAULT NULL,
  `scopeID` int(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonOutcome`
--

CREATE TABLE `gibbonOutcome` (
  `gibbonOutcomeID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `nameShort` varchar(14) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `scope` enum('School','Learning Area') NOT NULL,
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPayment`
--

CREATE TABLE `gibbonPayment` (
  `gibbonPaymentID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `foreignTable` varchar(50) NOT NULL,
  `foreignTableID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'Person recording the transaction',
  `type` varchar(60) NOT NULL DEFAULT 'Online',
  `status` enum('Complete','Partial','Final','Failure') NOT NULL DEFAULT 'Complete' COMMENT 'Complete means paid in one go, partial is part of a set of payments, and final is last in a set of payments.',
  `amount` decimal(13,2) NOT NULL,
  `gateway` varchar(30) DEFAULT NULL,
  `onlineTransactionStatus` enum('Success','Failure') DEFAULT NULL,
  `paymentToken` varchar(50) DEFAULT NULL,
  `paymentPayerID` varchar(50) DEFAULT NULL,
  `paymentTransactionID` varchar(50) DEFAULT NULL,
  `paymentReceiptID` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPermission`
--

CREATE TABLE `gibbonPermission` (
  `permissionID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRoleID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonActionID` int(7) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonPermission`
--

INSERT INTO `gibbonPermission` (`permissionID`, `gibbonRoleID`, `gibbonActionID`) VALUES
(0000053554, 001, 0000705),
(0000053555, 006, 0000705),
(0000053556, 002, 0000705),
(0000053557, 001, 0000709),
(0000053558, 002, 0000709),
(0000053559, 001, 0000673),
(0000053560, 006, 0000673),
(0000053561, 002, 0000673),
(0000053562, 001, 0000067),
(0000053563, 006, 0000067),
(0000053564, 002, 0000067),
(0000053565, 001, 0000058),
(0000053566, 006, 0000058),
(0000053567, 002, 0000058),
(0000053568, 001, 0000055),
(0000053569, 001, 0000056),
(0000053570, 003, 0000056),
(0000053571, 002, 0000056),
(0000053572, 001, 0000057),
(0000053573, 006, 0000057),
(0000053574, 002, 0000057),
(0000053575, 001, 0000059),
(0000053576, 006, 0000059),
(0000053578, 003, 0000053),
(0000053579, 001, 0000052),
(0000053580, 004, 0000052),
(0000053581, 006, 0000052),
(0000053582, 002, 0000052),
(0000053587, 001, 0000026),
(0000053588, 006, 0000026),
(0000053589, 002, 0000026),
(0000053590, 001, 0000027),
(0000053591, 006, 0000027),
(0000053592, 002, 0000027),
(0000053593, 001, 0000030),
(0000053594, 006, 0000030),
(0000053595, 002, 0000030),
(0000053596, 001, 0000028),
(0000053597, 006, 0000028),
(0000053598, 002, 0000028),
(0000053599, 001, 0000031),
(0000053600, 006, 0000031),
(0000053601, 002, 0000031),
(0000053602, 004, 0000060),
(0000053603, 001, 0000044),
(0000053604, 006, 0000044),
(0000053605, 002, 0000044),
(0000053606, 001, 0000029),
(0000053607, 006, 0000029),
(0000053608, 002, 0000029),
(0000053609, 001, 0000719),
(0000053610, 002, 0000719),
(0000053611, 001, 0000606),
(0000053612, 002, 0000607),
(0000053613, 001, 0000608),
(0000053614, 002, 0000608),
(0000053615, 001, 0000047),
(0000053616, 004, 0000047),
(0000053617, 003, 0000047),
(0000053618, 002, 0000047),
(0000053619, 001, 0000764),
(0000053620, 001, 0000765),
(0000053621, 004, 0000765),
(0000053622, 006, 0000765),
(0000053623, 002, 0000765),
(0000053624, 001, 0000773),
(0000053625, 001, 0000774),
(0000053626, 004, 0000774),
(0000053627, 006, 0000774),
(0000053628, 002, 0000774),
(0000053629, 001, 0000586),
(0000053630, 004, 0000064),
(0000053631, 001, 0000585),
(0000053632, 004, 0000014),
(0000053633, 006, 0000014),
(0000053634, 002, 0000014),
(0000053635, 001, 0000022),
(0000053636, 004, 0000022),
(0000053637, 003, 0000022),
(0000053638, 006, 0000022),
(0000053639, 002, 0000022),
(0000053640, 001, 0000069),
(0000053641, 001, 0000068),
(0000053642, 002, 0000068),
(0000053643, 001, 0000772),
(0000053644, 001, 0000770),
(0000053645, 001, 0000767),
(0000053646, 001, 0000769),
(0000053647, 001, 0000768),
(0000053648, 001, 0000771),
(0000053649, 002, 0000716),
(0000053650, 001, 0000046),
(0000053651, 001, 0000718),
(0000053652, 001, 0000720),
(0000053653, 004, 0000720),
(0000053654, 003, 0000720),
(0000053655, 006, 0000720),
(0000053656, 002, 0000720),
(0000053657, 001, 0000748),
(0000053658, 006, 0000748),
(0000053659, 001, 0000711),
(0000053660, 001, 0000710),
(0000053661, 001, 0000722),
(0000053662, 002, 0000722),
(0000053663, 001, 0000721),
(0000053664, 001, 0000759),
(0000053665, 002, 0000758),
(0000053666, 001, 0000034),
(0000053667, 002, 0000034),
(0000053668, 004, 0000041),
(0000053669, 001, 0000033),
(0000053670, 006, 0000033),
(0000053671, 002, 0000033),
(0000053672, 003, 0000039),
(0000053673, 001, 0000630),
(0000053674, 002, 0000629),
(0000053675, 001, 0000624),
(0000053676, 001, 0000623),
(0000053677, 002, 0000623),
(0000053678, 001, 0000625),
(0000053679, 002, 0000625),
(0000053680, 001, 0000657),
(0000053681, 001, 0000743),
(0000053682, 002, 0000743),
(0000053683, 001, 0000744),
(0000053684, 002, 0000744),
(0000053685, 001, 0000745),
(0000053686, 001, 0000615),
(0000053687, 001, 0000614),
(0000053688, 002, 0000614),
(0000053689, 001, 0000616),
(0000053690, 002, 0000616),
(0000053691, 001, 0000618),
(0000053692, 001, 0000617),
(0000053693, 002, 0000617),
(0000053694, 001, 0000619),
(0000053695, 002, 0000619),
(0000053696, 001, 0000632),
(0000053697, 001, 0000660),
(0000053698, 002, 0000660),
(0000053699, 001, 0000658),
(0000053700, 002, 0000658),
(0000053701, 001, 0000628),
(0000053702, 001, 0000621),
(0000053703, 001, 0000620),
(0000053704, 002, 0000620),
(0000053705, 001, 0000622),
(0000053706, 002, 0000622),
(0000053707, 001, 0000626),
(0000053708, 002, 0000626),
(0000053709, 001, 0000627),
(0000053710, 001, 0000746),
(0000053711, 004, 0000746),
(0000053712, 003, 0000746),
(0000053713, 002, 0000746),
(0000053714, 002, 0000036),
(0000053715, 001, 0000038),
(0000053716, 004, 0000040),
(0000053717, 003, 0000035),
(0000053718, 002, 0000675),
(0000053719, 001, 0000676),
(0000053720, 001, 0000661),
(0000053721, 002, 0000662),
(0000053722, 001, 0000061),
(0000053723, 002, 0000061),
(0000053724, 001, 0000611),
(0000053725, 006, 0000612),
(0000053726, 002, 0000612),
(0000053727, 001, 0000613),
(0000053728, 006, 0000613),
(0000053729, 002, 0000613),
(0000053730, 001, 0000781),
(0000053731, 003, 0000781),
(0000053732, 006, 0000781),
(0000053733, 002, 0000781),
(0000053734, 002, 0000678),
(0000053735, 001, 0000679),
(0000053736, 001, 0000708),
(0000053737, 004, 0000708),
(0000053738, 003, 0000708),
(0000053739, 002, 0000708),
(0000053740, 001, 0000726),
(0000053741, 001, 0000054),
(0000053742, 001, 0000715),
(0000053743, 001, 0000605),
(0000053744, 001, 0000013),
(0000053745, 001, 0000062),
(0000053746, 001, 0000756),
(0000053747, 001, 0000706),
(0000053748, 001, 0000008),
(0000053749, 001, 0000717),
(0000053750, 001, 0000712),
(0000053751, 001, 0000674),
(0000053752, 001, 0000610),
(0000053753, 001, 0000007),
(0000053754, 001, 0000003),
(0000053755, 001, 0000747),
(0000053756, 001, 0000025),
(0000053757, 001, 0000016),
(0000053758, 001, 0000742),
(0000053759, 001, 0000015),
(0000053760, 001, 0000006),
(0000053761, 001, 0000727),
(0000053762, 001, 0000779),
(0000053763, 001, 0000780),
(0000053764, 001, 0000713),
(0000053765, 001, 0000077),
(0000053766, 006, 0000077),
(0000053767, 002, 0000077),
(0000053768, 001, 0000724),
(0000053769, 001, 0000755),
(0000053770, 001, 0000075),
(0000053771, 006, 0000075),
(0000053772, 002, 0000075),
(0000053773, 001, 0000707),
(0000053774, 002, 0000707),
(0000053775, 001, 0000714),
(0000053776, 001, 0000073),
(0000053777, 006, 0000073),
(0000053778, 002, 0000073),
(0000053779, 001, 0000757),
(0000053780, 001, 0000072),
(0000053781, 006, 0000072),
(0000053782, 002, 0000072),
(0000053783, 001, 0000043),
(0000053784, 006, 0000043),
(0000053785, 002, 0000043),
(0000053786, 003, 0000023),
(0000053787, 001, 0000024),
(0000053788, 006, 0000024),
(0000053789, 002, 0000024),
(0000053790, 004, 0000042),
(0000053791, 001, 0000010),
(0000053792, 001, 0000020),
(0000053793, 001, 0000005),
(0000053794, 001, 0000631),
(0000053795, 001, 0000760),
(0000053796, 001, 0000051),
(0000053797, 004, 0000051),
(0000053798, 003, 0000051),
(0000053799, 006, 0000051),
(0000053800, 002, 0000051),
(0000053801, 001, 0000655),
(0000053802, 006, 0000655),
(0000053803, 002, 0000655),
(0000053804, 001, 0000066),
(0000053805, 001, 0000018),
(0000053806, 001, 0000656),
(0000053807, 001, 0000049),
(0000053808, 001, 0000017),
(0000053809, 001, 0000048),
(0000053810, 001, 0000050),
(0000053812, 001, 0000725),
(0000053813, 001, 0000766),
(0000053814, 001, 0000775),
(0000053816, 001, 0000019),
(0000053817, 001, 0000021),
(0000053818, 001, 0000012),
(0000053819, 001, 0000009),
(0000053820, 001, 0000032),
(0000053821, 001, 0000723),
(0000053822, 001, 0000002),
(0000053823, 001, 0000065),
(0000053824, 001, 0000063),
(0000053825, 001, 0000070),
(0000053826, 001, 0000004),
(0000053827, 001, 0000778),
(0000053828, 001, 0000777),
(0000053829, 001, 0000776),
(0000053842, 001, 0000796),
(0000053851, 001, 0000802),
(0000053852, 002, 0000802),
(0000053853, 004, 0000781),
(0000053854, 001, 0000803),
(0000053855, 001, 0000804),
(0000053856, 001, 0000805),
(0000053857, 001, 0000804),
(0000053858, 001, 0000806),
(0000053859, 001, 0000807),
(0000053860, 002, 0000807),
(0000053861, 001, 0000808),
(0000053862, 001, 0000809),
(0000053863, 001, 0000810),
(0000053864, 002, 0000810),
(0000053865, 001, 0000811),
(0000053866, 001, 0000812),
(0000053867, 002, 0000813),
(0000053868, 001, 0000814),
(0000053869, 002, 0000815),
(0000053870, 001, 0000816),
(0000053871, 001, 0000817),
(0000053872, 002, 0000817),
(0000053873, 001, 0000818),
(0000053874, 002, 0000818),
(0000053875, 006, 0000818),
(0000053876, 001, 0000819),
(0000053878, 001, 0000820),
(0000053879, 001, 0000821),
(0000053880, 001, 0000822),
(0000053881, 001, 0000823),
(0000053882, 001, 0000824),
(0000053883, 001, 0000825),
(0000053884, 001, 0000826),
(0000053885, 001, 0000827),
(0000053886, 002, 0000827),
(0000053887, 006, 0000827),
(0000053888, 001, 0000828),
(0000053889, 002, 0000829),
(0000053890, 006, 0000829),
(0000053891, 001, 0000830),
(0000053892, 001, 0000831),
(0000053893, 001, 0000832),
(0000053894, 001, 0000833),
(0000053895, 002, 0000834),
(0000053896, 001, 0000835),
(0000053897, 003, 0000836),
(0000053898, 004, 0000837),
(0000053899, 001, 0000838),
(0000053900, 002, 0000838),
(0000053901, 001, 0000839),
(0000053902, 001, 0000841),
(0000053903, 001, 0000842),
(0000053904, 001, 0000843),
(0000053905, 001, 0000844),
(0000053906, 001, 0000846),
(0000053907, 001, 0000847),
(0000053908, 001, 0000848),
(0000053909, 004, 0000849),
(0000053910, 001, 0000851),
(0000053912, 001, 0000853),
(0000053913, 001, 0000854),
(0000053914, 001, 0000855),
(0000053915, 001, 0000856),
(0000053916, 002, 0000856),
(0000053917, 006, 0000856),
(0000053918, 001, 0000857),
(0000053920, 001, 0000859),
(0000053921, 001, 0000860),
(0000053922, 002, 0000860),
(0000053923, 001, 0000861),
(0000053924, 002, 0000861),
(0000053925, 001, 0000866),
(0000053926, 001, 0000867),
(0000053927, 001, 0000868),
(0000053928, 001, 0000869),
(0000053929, 002, 0000869),
(0000053930, 001, 0000870),
(0000053931, 001, 0000871),
(0000053932, 001, 0000872),
(0000053933, 001, 0000873),
(0000053934, 002, 0000873),
(0000053935, 001, 0000874),
(0000053936, 002, 0000874),
(0000053937, 001, 0000875),
(0000053938, 002, 0000875),
(0000053939, 001, 0000876),
(0000053940, 002, 0000876),
(0000053941, 001, 0000877),
(0000053942, 001, 0000878),
(0000053943, 002, 0000878),
(0000053944, 006, 0000878),
(0000053945, 001, 0000879),
(0000053946, 001, 0000880),
(0000053947, 002, 0000880),
(0000053948, 001, 0000881),
(0000053949, 001, 0000882),
(0000053950, 001, 0000883),
(0000053951, 001, 0000884),
(0000053952, 001, 0000886),
(0000053953, 006, 0000746),
(0000053954, 001, 0000888),
(0000053955, 001, 0000889),
(0000053956, 002, 0000889),
(0000053957, 001, 0000890),
(0000053958, 002, 0000890),
(0000053959, 001, 0000891),
(0000053960, 003, 0000894),
(0000053961, 004, 0000895),
(0000053962, 003, 0000896),
(0000053963, 001, 0000898),
(0000053964, 001, 0000899),
(0000053965, 001, 0000900),
(0000053966, 001, 0000902),
(0000053967, 001, 0000903),
(0000053968, 002, 0000903),
(0000053969, 001, 0000904),
(0000053970, 002, 0000904),
(0000053971, 004, 0000904),
(0000053972, 006, 0000904),
(0000053973, 001, 0000905),
(0000053974, 002, 0000906),
(0000053975, 001, 0000907),
(0000053976, 002, 0000908),
(0000053977, 001, 0000909),
(0000053978, 001, 0000910),
(0000053979, 001, 0000912),
(0000053980, 001, 0000913),
(0000053981, 001, 0000914),
(0000053982, 001, 0000916),
(0000053983, 001, 0000918),
(0000053984, 001, 0000919),
(0000053985, 001, 0000920),
(0000053986, 001, 0000921),
(0000053987, 001, 0000922),
(0000053988, 001, 0000923),
(0000053989, 001, 0000924),
(0000053990, 001, 0000925),
(0000053991, 001, 0000926),
(0000053992, 001, 0000927),
(0000053993, 001, 0000928),
(0000053994, 001, 0000930),
(0000053995, 001, 0000933),
(0000053996, 001, 0000934),
(0000053997, 001, 0000935),
(0000053998, 002, 0000936),
(0000053999, 001, 0000937),
(0000054000, 002, 0000937),
(0000054001, 001, 0000938),
(0000054002, 001, 0000939),
(0000054003, 001, 0000940),
(0000054004, 001, 0000941),
(0000054005, 001, 0000942),
(0000054006, 001, 0000943),
(0000054007, 001, 0000944),
(0000054008, 004, 0000945),
(0000054009, 003, 0000946),
(0000054010, 001, 0000947),
(0000054011, 001, 0000948),
(0000054012, 002, 0000948),
(0000054013, 001, 0000949),
(0000054014, 002, 0000949),
(0000054015, 001, 0000950),
(0000054016, 001, 0000951),
(0000054017, 001, 0000952),
(0000054018, 001, 0000953),
(0000054019, 002, 0000954),
(0000054020, 001, 0000955),
(0000054021, 001, 0000956),
(0000054022, 001, 0000957),
(0000054023, 001, 0000958),
(0000054024, 001, 0000959),
(0000054025, 002, 0000960),
(0000054026, 001, 0000961),
(0000054027, 001, 0000962),
(0000054028, 001, 0000963),
(0000054029, 001, 0000965),
(0000054030, 001, 0000966),
(0000054031, 001, 0000967),
(0000054032, 001, 0000968),
(0000054033, 001, 0000969),
(0000054034, 001, 0000970),
(0000054035, 001, 0000971),
(0000054036, 002, 0000971),
(0000054037, 001, 0000972),
(0000054038, 001, 0000973),
(0000054039, 001, 0000974),
(0000054040, 001, 0000975),
(0000054041, 001, 0000976),
(0000054042, 001, 0000977),
(0000054043, 002, 0000977),
(0000054044, 001, 0000978),
(0000054045, 001, 0000979),
(0000054046, 001, 0000980),
(0000054047, 002, 0000980),
(0000054048, 001, 0000981),
(0000054049, 006, 0000977),
(0000054050, 001, 0000982),
(0000054051, 001, 0000983),
(0000054052, 001, 0000984),
(0000054053, 001, 0000985),
(0000054054, 001, 0000986),
(0000054056, 001, 0000988),
(0000054057, 001, 0000989),
(0000054058, 002, 0000990),
(0000054059, 004, 0000990),
(0000054060, 006, 0000990),
(0000054061, 001, 0000991),
(0000054062, 001, 0000992),
(0000054063, 001, 0000993),
(0000054064, 001, 0000994),
(0000054065, 002, 0000994),
(0000054066, 001, 0000995),
(0000054067, 001, 0000997);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPerson`
--

CREATE TABLE `gibbonPerson` (
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(5) NOT NULL,
  `surname` varchar(60) NOT NULL DEFAULT '',
  `firstName` varchar(60) NOT NULL DEFAULT '',
  `preferredName` varchar(60) NOT NULL DEFAULT '',
  `officialName` varchar(150) NOT NULL,
  `nameInCharacters` varchar(60) NOT NULL,
  `gender` enum('M','F','Other','Unspecified') NOT NULL DEFAULT 'Unspecified',
  `username` varchar(20) NOT NULL,
  `passwordStrong` varchar(255) NOT NULL,
  `passwordStrongSalt` varchar(255) NOT NULL,
  `passwordForceReset` enum('N','Y') NOT NULL DEFAULT 'N' COMMENT 'Force user to reset password on next login.',
  `status` enum('Full','Expected','Left','Pending Approval') NOT NULL DEFAULT 'Full',
  `canLogin` enum('Y','N') NOT NULL DEFAULT 'Y',
  `gibbonRoleIDPrimary` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRoleIDAll` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `emailAlternate` varchar(75) DEFAULT NULL,
  `image_240` varchar(255) DEFAULT NULL,
  `lastIPAddress` varchar(15) NOT NULL DEFAULT '',
  `lastTimestamp` timestamp NULL DEFAULT NULL,
  `lastFailIPAddress` varchar(15) DEFAULT NULL,
  `lastFailTimestamp` timestamp NULL DEFAULT NULL,
  `failCount` int(1) DEFAULT '0',
  `address1` mediumtext NOT NULL,
  `address1District` varchar(255) NOT NULL,
  `address1Country` varchar(255) NOT NULL,
  `address2` mediumtext NOT NULL,
  `address2District` varchar(255) NOT NULL,
  `address2Country` varchar(255) NOT NULL,
  `phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone1CountryCode` varchar(7) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone3Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone3CountryCode` varchar(7) NOT NULL,
  `phone3` varchar(20) NOT NULL,
  `phone2Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone2CountryCode` varchar(7) NOT NULL,
  `phone2` varchar(20) NOT NULL,
  `phone4Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone4CountryCode` varchar(7) NOT NULL,
  `phone4` varchar(20) NOT NULL,
  `website` varchar(255) NOT NULL,
  `languageFirst` varchar(30) NOT NULL,
  `languageSecond` varchar(30) NOT NULL,
  `languageThird` varchar(30) NOT NULL,
  `countryOfBirth` varchar(30) NOT NULL,
  `birthCertificateScan` varchar(255) NOT NULL,
  `ethnicity` varchar(255) NOT NULL,
  `religion` varchar(30) NOT NULL,
  `profession` varchar(90) NOT NULL,
  `employer` varchar(90) NOT NULL,
  `jobTitle` varchar(90) NOT NULL,
  `emergency1Name` varchar(90) NOT NULL,
  `emergency1Number1` varchar(30) NOT NULL,
  `emergency1Number2` varchar(30) NOT NULL,
  `emergency1Relationship` varchar(30) NOT NULL,
  `emergency2Name` varchar(90) NOT NULL,
  `emergency2Number1` varchar(30) NOT NULL,
  `emergency2Number2` varchar(30) NOT NULL,
  `emergency2Relationship` varchar(30) NOT NULL,
  `gibbonHouseID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `studentID` varchar(15) NOT NULL,
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `gibbonSchoolYearIDClassOf` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `lastSchool` varchar(100) NOT NULL,
  `nextSchool` varchar(100) NOT NULL,
  `departureReason` varchar(100) NOT NULL,
  `transport` varchar(255) NOT NULL,
  `transportNotes` text NOT NULL,
  `calendarFeedPersonal` text NOT NULL,
  `viewCalendarSchool` enum('Y','N') NOT NULL DEFAULT 'Y',
  `viewCalendarPersonal` enum('Y','N') NOT NULL DEFAULT 'Y',
  `viewCalendarSpaceBooking` enum('Y','N') NOT NULL DEFAULT 'N',
  `gibbonApplicationFormID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `lockerNumber` varchar(20) NOT NULL,
  `vehicleRegistration` varchar(20) NOT NULL,
  `personalBackground` varchar(255) NOT NULL,
  `messengerLastRead` datetime DEFAULT NULL,
  `privacy` text,
  `dayType` varchar(255) DEFAULT NULL COMMENT 'Student day type, as specified in the application form.',
  `gibbonThemeIDPersonal` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibboni18nIDPersonal` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `studentAgreements` text,
  `googleAPIRefreshToken` text NOT NULL,
  `microsoftAPIRefreshToken` text NOT NULL,
  `genericAPIRefreshToken` text NOT NULL,
  `receiveNotificationEmails` enum('Y','N') NOT NULL DEFAULT 'Y',
  `mfaSecret` varchar(16) DEFAULT NULL,
  `mfaToken` text,
  `cookieConsent` enum('Y','N') DEFAULT NULL,
  `fields` text NOT NULL COMMENT 'JSON object of custom field values'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonalDocument`
--

CREATE TABLE `gibbonPersonalDocument` (
  `gibbonPersonalDocumentID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonalDocumentTypeID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `foreignTable` varchar(60) NOT NULL,
  `foreignTableID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `document` enum('Passport','ID Card','Visa','Document') NOT NULL DEFAULT 'Document',
  `documentNumber` varchar(120) DEFAULT NULL,
  `documentName` varchar(120) DEFAULT NULL,
  `documentType` varchar(60) DEFAULT NULL,
  `dateIssue` date DEFAULT NULL,
  `dateExpiry` date DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `country` varchar(60) DEFAULT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonalDocumentType`
--

CREATE TABLE `gibbonPersonalDocumentType` (
  `gibbonPersonalDocumentTypeID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `type` enum('Core','Additional') NOT NULL DEFAULT 'Additional',
  `document` enum('Passport','ID Card','Visa','Document') NOT NULL DEFAULT 'Document',
  `fields` text,
  `required` enum('Y','N') NOT NULL DEFAULT 'Y',
  `sequenceNumber` int(3) NOT NULL DEFAULT '0',
  `activePersonStudent` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonStaff` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonParent` tinyint(1) NOT NULL DEFAULT '0',
  `activePersonOther` tinyint(1) NOT NULL DEFAULT '0',
  `activeApplicationForm` tinyint(1) NOT NULL DEFAULT '0',
  `activeDataUpdater` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonPersonalDocumentType`
--

INSERT INTO `gibbonPersonalDocumentType` (`gibbonPersonalDocumentTypeID`, `name`, `description`, `active`, `type`, `document`, `fields`, `required`, `sequenceNumber`, `activePersonStudent`, `activePersonStaff`, `activePersonParent`, `activePersonOther`, `activeApplicationForm`, `activeDataUpdater`) VALUES
(001, 'Primary Passport', '', 'Y', 'Core', 'Passport', '[\"documentName\",\"documentNumber\",\"country\",\"dateIssue\",\"dateExpiry\",\"filePath\"]', 'N', 1, 1, 1, 0, 0, 1, 1),
(002, 'Additional Passport', '', 'Y', 'Core', 'Passport', '[\"documentName\",\"documentNumber\",\"country\",\"dateIssue\",\"dateExpiry\",\"filePath\"]', 'N', 2, 1, 1, 0, 0, 0, 1),
(003, 'ID Card', '', 'Y', 'Core', 'ID Card', '[\"documentNumber\",\"filePath\"]', 'N', 3, 1, 1, 1, 1, 1, 1),
(004, 'Residency/Visa', '', 'Y', 'Core', 'Visa', '[\"documentType\",\"dateExpiry\"]', 'N', 4, 1, 1, 1, 1, 0, 1),
(005, 'Birth Certificate', '', 'Y', 'Core', 'Document', '[\"country\",\"filePath\"]', 'N', 5, 1, 1, 1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonMedical`
--

CREATE TABLE `gibbonPersonMedical` (
  `gibbonPersonMedicalID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `longTermMedication` enum('','Y','N') NOT NULL,
  `longTermMedicationDetails` text NOT NULL,
  `comment` text NOT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonMedicalCondition`
--

CREATE TABLE `gibbonPersonMedicalCondition` (
  `gibbonPersonMedicalConditionID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonMedicalID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(100) NOT NULL,
  `gibbonAlertLevelID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `triggers` varchar(255) NOT NULL,
  `reaction` varchar(255) NOT NULL,
  `response` varchar(255) NOT NULL,
  `medication` varchar(255) NOT NULL,
  `lastEpisode` date DEFAULT NULL,
  `lastEpisodeTreatment` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonMedicalConditionUpdate`
--

CREATE TABLE `gibbonPersonMedicalConditionUpdate` (
  `gibbonPersonMedicalConditionUpdateID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonMedicalUpdateID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonMedicalConditionID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonMedicalID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `gibbonAlertLevelID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `triggers` varchar(255) NOT NULL,
  `reaction` varchar(255) NOT NULL,
  `response` varchar(255) NOT NULL,
  `medication` varchar(255) NOT NULL,
  `lastEpisode` date DEFAULT NULL,
  `lastEpisodeTreatment` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonMedicalUpdate`
--

CREATE TABLE `gibbonPersonMedicalUpdate` (
  `gibbonPersonMedicalUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `gibbonPersonMedicalID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `longTermMedication` enum('','Y','N') NOT NULL,
  `longTermMedicationDetails` text NOT NULL,
  `comment` text NOT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonReset`
--

CREATE TABLE `gibbonPersonReset` (
  `gibbonPersonResetID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `key` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonStatusLog`
--

CREATE TABLE `gibbonPersonStatusLog` (
  `gibbonPersonStatusLogID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `statusOld` enum('Full','Expected','Left','Pending Approval') NOT NULL DEFAULT 'Full',
  `statusNew` enum('Full','Expected','Left','Pending Approval') NOT NULL DEFAULT 'Full',
  `reason` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPersonUpdate`
--

CREATE TABLE `gibbonPersonUpdate` (
  `gibbonPersonUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(5) NOT NULL,
  `surname` varchar(60) NOT NULL DEFAULT '',
  `firstName` varchar(60) NOT NULL DEFAULT '',
  `preferredName` varchar(60) NOT NULL DEFAULT '',
  `officialName` varchar(150) NOT NULL,
  `nameInCharacters` varchar(60) NOT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `emailAlternate` varchar(75) DEFAULT NULL,
  `address1` mediumtext NOT NULL,
  `address1District` varchar(255) NOT NULL,
  `address1Country` varchar(255) NOT NULL,
  `address2` mediumtext NOT NULL,
  `address2District` varchar(255) NOT NULL,
  `address2Country` varchar(255) NOT NULL,
  `phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone1CountryCode` varchar(7) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone3Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone3CountryCode` varchar(7) NOT NULL,
  `phone3` varchar(20) NOT NULL,
  `phone2Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone2CountryCode` varchar(7) NOT NULL,
  `phone2` varchar(20) NOT NULL,
  `phone4Type` enum('','Mobile','Home','Work','Fax','Pager','Other') NOT NULL DEFAULT '',
  `phone4CountryCode` varchar(7) NOT NULL,
  `phone4` varchar(20) NOT NULL,
  `languageFirst` varchar(30) NOT NULL,
  `languageSecond` varchar(30) NOT NULL,
  `languageThird` varchar(30) NOT NULL,
  `countryOfBirth` varchar(30) NOT NULL,
  `ethnicity` varchar(255) NOT NULL,
  `religion` varchar(30) NOT NULL,
  `profession` varchar(90) DEFAULT NULL,
  `employer` varchar(90) DEFAULT NULL,
  `jobTitle` varchar(90) DEFAULT NULL,
  `emergency1Name` varchar(90) DEFAULT NULL,
  `emergency1Number1` varchar(30) DEFAULT NULL,
  `emergency1Number2` varchar(30) DEFAULT NULL,
  `emergency1Relationship` varchar(30) DEFAULT NULL,
  `emergency2Name` varchar(90) DEFAULT NULL,
  `emergency2Number1` varchar(30) DEFAULT NULL,
  `emergency2Number2` varchar(30) DEFAULT NULL,
  `emergency2Relationship` varchar(30) DEFAULT NULL,
  `vehicleRegistration` varchar(20) NOT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `privacy` text,
  `fields` text NOT NULL COMMENT 'Serialised array of custom field values'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntry`
--

CREATE TABLE `gibbonPlannerEntry` (
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `timeStart` time DEFAULT NULL,
  `timeEnd` time DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `teachersNotes` mediumtext NOT NULL,
  `homework` enum('N','Y') NOT NULL DEFAULT 'N',
  `homeworkDueDateTime` datetime DEFAULT NULL,
  `homeworkDetails` mediumtext NOT NULL,
  `homeworkTimeCap` int(3) DEFAULT NULL,
  `homeworkLocation` enum('Out of Class','In Class') DEFAULT NULL,
  `homeworkSubmission` enum('N','Y') NOT NULL,
  `homeworkSubmissionDateOpen` date DEFAULT NULL,
  `homeworkSubmissionDrafts` varchar(1) DEFAULT NULL,
  `homeworkSubmissionType` enum('','Link','File','Link/File') NOT NULL,
  `homeworkSubmissionRequired` enum('Optional','Required') DEFAULT NULL,
  `homeworkCrowdAssess` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessOtherTeachersRead` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessOtherParentsRead` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessClassmatesParentsRead` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessSubmitterParentsRead` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessOtherStudentsRead` enum('N','Y') NOT NULL,
  `homeworkCrowdAssessClassmatesRead` enum('N','Y') NOT NULL,
  `viewableStudents` enum('Y','N') NOT NULL DEFAULT 'Y',
  `viewableParents` enum('Y','N') NOT NULL DEFAULT 'N',
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryDiscuss`
--

CREATE TABLE `gibbonPlannerEntryDiscuss` (
  `gibbonPlannerEntryDiscussID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comment` text NOT NULL,
  `gibbonPlannerEntryDiscussIDReplyTo` int(16) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryGuest`
--

CREATE TABLE `gibbonPlannerEntryGuest` (
  `gibbonPlannerEntryGuestID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `role` enum('Guest Student','Guest Teacher','Guest Assistant','Guest Technician','Guest Parent','Other Guest') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryHomework`
--

CREATE TABLE `gibbonPlannerEntryHomework` (
  `gibbonPlannerEntryHomeworkID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Link','File') NOT NULL,
  `version` enum('Draft','Final') NOT NULL,
  `status` enum('On Time','Late','Exemption') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `count` int(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryOutcome`
--

CREATE TABLE `gibbonPlannerEntryOutcome` (
  `gibbonPlannerEntryOutcomeID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonOutcomeID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryStudentHomework`
--

CREATE TABLE `gibbonPlannerEntryStudentHomework` (
  `gibbonPlannerEntryStudentHomeworkID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `homeworkDueDateTime` datetime NOT NULL,
  `homeworkDetails` mediumtext NOT NULL,
  `homeworkComplete` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Allows students to add homework deadlines themselves';

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerEntryStudentTracker`
--

CREATE TABLE `gibbonPlannerEntryStudentTracker` (
  `gibbonPlannerEntryStudentTrackerID` int(16) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `homeworkComplete` enum('Y','N') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonPlannerParentWeeklyEmailSummary`
--

CREATE TABLE `gibbonPlannerParentWeeklyEmailSummary` (
  `gibbonPlannerParentWeeklyEmailSummaryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDParent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `weekOfYear` int(2) NOT NULL,
  `key` varchar(40) NOT NULL,
  `confirmed` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReport`
--

CREATE TABLE `gibbonReport` (
  `gibbonReportID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportArchiveID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportTemplateID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `name` varchar(90) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` enum('None','Draft','Published') NOT NULL DEFAULT 'None',
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL,
  `queryBuilderQueryID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `accessDate` datetime DEFAULT NULL,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestampGenerated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportArchive`
--

CREATE TABLE `gibbonReportArchive` (
  `gibbonReportArchiveID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `readonly` enum('Y','N') NOT NULL DEFAULT 'N',
  `viewableStaff` enum('Y','N') NOT NULL DEFAULT 'N',
  `viewableStudents` enum('Y','N') NOT NULL DEFAULT 'N',
  `viewableParents` enum('Y','N') NOT NULL DEFAULT 'N',
  `viewableOther` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonReportArchive`
--

INSERT INTO `gibbonReportArchive` (`gibbonReportArchiveID`, `name`, `path`, `readonly`, `viewableStaff`, `viewableStudents`, `viewableParents`, `viewableOther`) VALUES
(00001, 'Default Archive', '/uploads/reports', 'N', 'Y', 'N', 'N', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportArchiveEntry`
--

CREATE TABLE `gibbonReportArchiveEntry` (
  `gibbonReportArchiveEntryID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportArchiveID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('Single','Batch') NOT NULL DEFAULT 'Single',
  `status` enum('Draft','Final') NOT NULL DEFAULT 'Draft',
  `reportIdentifier` varchar(255) DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampSent` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDAccessed` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampAccessed` timestamp NULL DEFAULT NULL,
  `timestampAccessExpiry` timestamp NULL DEFAULT NULL,
  `accessToken` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingAccess`
--

CREATE TABLE `gibbonReportingAccess` (
  `gibbonReportingAccessID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportingScopeIDList` varchar(255) DEFAULT NULL,
  `gibbonRoleIDList` varchar(255) DEFAULT NULL,
  `gibbonPersonIDList` varchar(255) DEFAULT NULL,
  `accessType` enum('Role','Person') NOT NULL DEFAULT 'Role',
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `canWrite` enum('Y','N') NOT NULL DEFAULT 'N',
  `canProofRead` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingCriteria`
--

CREATE TABLE `gibbonReportingCriteria` (
  `gibbonReportingCriteriaID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportingScopeID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportingCriteriaTypeID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonCourseID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `target` enum('Per Student','Per Group') NOT NULL DEFAULT 'Per Student',
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `category` varchar(255) DEFAULT NULL,
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `sequenceNumber` int(3) DEFAULT NULL,
  `groupID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingCriteriaType`
--

CREATE TABLE `gibbonReportingCriteriaType` (
  `gibbonReportingCriteriaTypeID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) DEFAULT NULL,
  `valueType` varchar(30) DEFAULT NULL,
  `defaultValue` varchar(255) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `characterLimit` int(6) DEFAULT NULL,
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `options` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonReportingCriteriaType`
--

INSERT INTO `gibbonReportingCriteriaType` (`gibbonReportingCriteriaTypeID`, `name`, `valueType`, `defaultValue`, `active`, `characterLimit`, `gibbonScaleID`, `options`) VALUES
(0000000001, 'Comment, Short', 'Comment', NULL, 'Y', 500, NULL, NULL),
(0000000002, 'Comment, Long', 'Comment', NULL, 'Y', 1000, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingCycle`
--

CREATE TABLE `gibbonReportingCycle` (
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL,
  `name` varchar(90) DEFAULT NULL,
  `nameShort` varchar(20) DEFAULT NULL,
  `sequenceNumber` int(3) DEFAULT NULL,
  `cycleNumber` int(3) DEFAULT NULL,
  `cycleTotal` int(3) DEFAULT NULL,
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `milestones` text,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingProgress`
--

CREATE TABLE `gibbonReportingProgress` (
  `gibbonReportingProgressID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingScopeID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `status` enum('In Progress','Complete') NOT NULL DEFAULT 'In Progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingProof`
--

CREATE TABLE `gibbonReportingProof` (
  `gibbonReportingProofID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingValueID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` enum('Done','Edited','Accepted','Declined') NOT NULL DEFAULT 'Done',
  `reason` varchar(255) DEFAULT NULL,
  `comment` text,
  `gibbonPersonIDProofed` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampProofed` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDActioned` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampActioned` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingScope`
--

CREATE TABLE `gibbonReportingScope` (
  `gibbonReportingScopeID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `scopeType` enum('Year Group','Form Group','Course') NOT NULL DEFAULT 'Year Group',
  `name` varchar(90) DEFAULT NULL,
  `sequenceNumber` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportingValue`
--

CREATE TABLE `gibbonReportingValue` (
  `gibbonReportingValueID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportingCriteriaID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDStudent` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `comment` text,
  `gibbonPersonIDCreated` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDModified` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportPrototypeSection`
--

CREATE TABLE `gibbonReportPrototypeSection` (
  `gibbonReportPrototypeSectionID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(90) DEFAULT NULL,
  `type` enum('Core','Additional') NOT NULL DEFAULT 'Additional',
  `category` varchar(90) DEFAULT NULL,
  `types` varchar(60) DEFAULT NULL,
  `config` text,
  `templateParams` text,
  `templateFile` varchar(255) DEFAULT NULL,
  `dataSources` text,
  `icon` varchar(120) DEFAULT NULL,
  `fonts` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportTemplate`
--

CREATE TABLE `gibbonReportTemplate` (
  `gibbonReportTemplateID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `context` enum('Student Enrolment','Reporting Cycle','Custom Query') NOT NULL DEFAULT 'Student Enrolment',
  `name` varchar(90) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `orientation` char(1) NOT NULL DEFAULT 'P',
  `pageSize` varchar(20) NOT NULL DEFAULT 'A4',
  `marginX` decimal(4,2) NOT NULL DEFAULT '10.00',
  `marginY` decimal(4,2) NOT NULL DEFAULT '10.00',
  `flags` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `config` text,
  `stylesheet` varchar(120) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportTemplateFont`
--

CREATE TABLE `gibbonReportTemplateFont` (
  `gibbonReportTemplateFontID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `fontName` varchar(90) NOT NULL,
  `fontTCPDF` varchar(90) DEFAULT NULL,
  `fontPath` text,
  `fontType` enum('R','B','I','BI') NOT NULL DEFAULT 'R',
  `fontFamily` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonReportTemplateSection`
--

CREATE TABLE `gibbonReportTemplateSection` (
  `gibbonReportTemplateSectionID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonReportTemplateID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonReportPrototypeSectionID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `name` varchar(90) DEFAULT NULL,
  `type` enum('Body','Header','Footer') NOT NULL DEFAULT 'Body',
  `flags` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `page` int(3) NOT NULL DEFAULT '0',
  `config` text,
  `sequenceNumber` int(3) UNSIGNED DEFAULT NULL,
  `templateParams` text,
  `templateContent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonResource`
--

CREATE TABLE `gibbonResource` (
  `gibbonResourceID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` text NOT NULL,
  `gibbonYearGroupIDList` varchar(255) NOT NULL,
  `type` enum('File','HTML','Link') NOT NULL,
  `category` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `tags` text NOT NULL,
  `content` text NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonResourceTag`
--

CREATE TABLE `gibbonResourceTag` (
  `gibbonResourceTagID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `tag` varchar(100) NOT NULL,
  `count` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRole`
--

CREATE TABLE `gibbonRole` (
  `gibbonRoleID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `category` enum('Staff','Student','Parent','Other') NOT NULL DEFAULT 'Staff',
  `name` varchar(20) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `description` varchar(60) NOT NULL,
  `type` enum('Core','Additional') NOT NULL DEFAULT 'Core',
  `canLoginRole` enum('Y','N') NOT NULL DEFAULT 'Y',
  `futureYearsLogin` enum('Y','N') NOT NULL DEFAULT 'Y',
  `pastYearsLogin` enum('Y','N') NOT NULL DEFAULT 'Y',
  `restriction` enum('None','Same Role','Admin Only') NOT NULL DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonRole`
--

INSERT INTO `gibbonRole` (`gibbonRoleID`, `category`, `name`, `nameShort`, `description`, `type`, `canLoginRole`, `futureYearsLogin`, `pastYearsLogin`, `restriction`) VALUES
(001, 'Staff', 'Administrator', 'Adm', 'Controls all aspects of the system', 'Core', 'Y', 'Y', 'Y', 'Admin Only'),
(002, 'Staff', 'Teacher', 'Tcr', 'Regular, classroom teacher', 'Core', 'Y', 'Y', 'Y', 'None'),
(003, 'Student', 'Student', 'Std', 'Person studying in the school', 'Core', 'Y', 'Y', 'Y', 'None'),
(004, 'Parent', 'Parent', 'Prt', 'Parent or guardian of person studying in', 'Core', 'Y', 'Y', 'Y', 'None'),
(006, 'Staff', 'Support Staff', 'SSt', 'Staff who support teaching and learning', 'Core', 'Y', 'Y', 'Y', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRubric`
--

CREATE TABLE `gibbonRubric` (
  `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `scope` enum('School','Learning Area') NOT NULL,
  `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) NOT NULL,
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRubricCell`
--

CREATE TABLE `gibbonRubricCell` (
  `gibbonRubricCellID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricColumnID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricRowID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `contents` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRubricColumn`
--

CREATE TABLE `gibbonRubricColumn` (
  `gibbonRubricColumnID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(20) NOT NULL,
  `backgroundColor` varchar(7) DEFAULT NULL,
  `sequenceNumber` int(2) NOT NULL,
  `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL DEFAULT NULL,
  `visualise` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRubricEntry`
--

CREATE TABLE `gibbonRubricEntry` (
  `gibbonRubricEntry` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricCellID` int(11) UNSIGNED ZEROFILL NOT NULL,
  `contextDBTable` varchar(255) NOT NULL COMMENT 'Which database table is this entry related to?',
  `contextDBTableID` int(20) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonRubricRow`
--

CREATE TABLE `gibbonRubricRow` (
  `gibbonRubricRowID` int(9) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(40) NOT NULL,
  `backgroundColor` varchar(7) DEFAULT NULL,
  `sequenceNumber` int(2) NOT NULL,
  `gibbonOutcomeID` int(8) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonScale`
--

CREATE TABLE `gibbonScale` (
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(40) NOT NULL,
  `nameShort` varchar(5) NOT NULL,
  `usage` varchar(50) NOT NULL,
  `lowestAcceptable` varchar(5) DEFAULT NULL COMMENT 'This is the sequence number of the lowest grade a student can get without being unsatisfactory',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `numeric` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonScale`
--

INSERT INTO `gibbonScale` (`gibbonScaleID`, `name`, `nameShort`, `usage`, `lowestAcceptable`, `active`, `numeric`) VALUES
(00001, 'International Baccalaureate', 'IB', '7 (highest) to 1 (lowest)', '', 'N', 'Y'),
(00002, 'International Baccalaureate EE', 'IBEE', 'A (highest) to E (lowest)', '', 'N', 'N'),
(00003, 'United Kingdom GCSE/iGCSE', 'GCSE', 'A* (highest) to U (lowest)', '', 'Y', 'N'),
(00004, 'Percentage', '%', '100 (highest) to 0 (lowest)', '51', 'Y', 'Y'),
(00005, 'Full Letter Grade', 'FLG', 'A+ (highest) to F (lowest)', '', 'N', 'N'),
(00006, 'Simple Letter Grade', 'SLG', 'A (highest) to F (lowest)', '', 'N', 'N'),
(00007, 'International College HK', 'ICHK', '7 (highest) to 1 (lowest)', '4', 'Y', 'Y'),
(00009, 'Completion', 'Comp', 'Has task has been completed?', '1', 'Y', 'N'),
(00010, 'Cognitive Abilities Test', 'CAT', '140 (highest) to 60 (lowest)', '70', 'Y', 'Y'),
(00011, 'UK National Curriculum KS3', 'KS3', '8A (highest) to B3 (lowest)', '14', 'Y', 'N'),
(00012, 'United Kingdom GCSE/iGCSE Predicted', 'GPrd', '8A (highest) to B3 (lowest)', '', 'Y', 'N'),
(00013, 'IB Diploma (Subject)', 'IBDS', '7 (highest) to 1 (lowest)', '4', 'Y', 'Y'),
(00014, 'IB Diploma (Total)', 'IBDT', '45 (highest) to 0', '22', 'Y', 'Y'),
(00015, 'UK National Curriculum KS3 Simplified', 'KS3S', 'Level 8 (highest) to Level 3 (lowest)', '4', 'Y', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonScaleGrade`
--

CREATE TABLE `gibbonScaleGrade` (
  `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL NOT NULL,
  `gibbonScaleID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `value` varchar(10) NOT NULL,
  `descriptor` varchar(50) NOT NULL,
  `sequenceNumber` int(5) NOT NULL,
  `isDefault` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonScaleGrade`
--

INSERT INTO `gibbonScaleGrade` (`gibbonScaleGradeID`, `gibbonScaleID`, `value`, `descriptor`, `sequenceNumber`, `isDefault`) VALUES
(0000001, 00001, '7', '7', 1, 'N'),
(0000002, 00001, '6', '6', 2, 'N'),
(0000003, 00001, '5', '5', 3, 'N'),
(0000004, 00001, '4', '4', 4, 'N'),
(0000005, 00001, '3', '3', 5, 'N'),
(0000006, 00001, '2', '2', 6, 'N'),
(0000007, 00001, '1', '1', 7, 'N'),
(0000008, 00002, 'A', '49–60', 1, 'N'),
(0000009, 00002, 'B', '40–48', 2, 'N'),
(0000010, 00002, 'C', '32–39', 3, 'N'),
(0000011, 00002, 'D', '22–31', 4, 'N'),
(0000012, 00002, 'E', '0–21', 5, 'N'),
(0000013, 00003, 'A*', 'A*', 1, 'N'),
(0000014, 00003, 'A', 'A', 2, 'N'),
(0000015, 00003, 'B', 'B', 3, 'N'),
(0000016, 00003, 'C', 'C', 4, 'N'),
(0000017, 00003, 'D', 'D', 5, 'N'),
(0000018, 00003, 'E', 'E', 6, 'N'),
(0000019, 00003, 'F', 'F', 7, 'N'),
(0000020, 00003, 'G', 'G', 8, 'N'),
(0000021, 00003, 'U', 'Unclassified', 9, 'N'),
(0000022, 00004, '100%', '100%', 1, 'N'),
(0000023, 00004, '99%', '99%', 2, 'N'),
(0000024, 00004, '98%', '98%', 3, 'N'),
(0000025, 00004, '97%', '97%', 4, 'N'),
(0000026, 00004, '96%', '96%', 5, 'N'),
(0000027, 00004, '95%', '95%', 6, 'N'),
(0000028, 00004, '94%', '94%', 7, 'N'),
(0000029, 00004, '93%', '93%', 8, 'N'),
(0000030, 00004, '92%', '92%', 9, 'N'),
(0000031, 00004, '91%', '91%', 10, 'N'),
(0000032, 00004, '90%', '90%', 11, 'N'),
(0000033, 00004, '89%', '89%', 12, 'N'),
(0000034, 00004, '88%', '88%', 13, 'N'),
(0000035, 00004, '87%', '87%', 14, 'N'),
(0000036, 00004, '86%', '86%', 15, 'N'),
(0000037, 00004, '85%', '85%', 16, 'N'),
(0000038, 00004, '84%', '84%', 17, 'N'),
(0000039, 00004, '83%', '83%', 18, 'N'),
(0000040, 00004, '82%', '82%', 19, 'N'),
(0000041, 00004, '81%', '81%', 20, 'N'),
(0000042, 00004, '80%', '80%', 21, 'N'),
(0000043, 00004, '79%', '79%', 22, 'N'),
(0000044, 00004, '78%', '78%', 23, 'N'),
(0000045, 00004, '77%', '77%', 24, 'N'),
(0000046, 00004, '76%', '76%', 25, 'N'),
(0000047, 00004, '75%', '75%', 26, 'N'),
(0000048, 00004, '74%', '74%', 27, 'N'),
(0000049, 00004, '73%', '73%', 28, 'N'),
(0000050, 00004, '72%', '72%', 29, 'N'),
(0000051, 00004, '71%', '71%', 30, 'N'),
(0000052, 00004, '70%', '70%', 31, 'N'),
(0000053, 00004, '69%', '69%', 32, 'N'),
(0000054, 00004, '68%', '68%', 33, 'N'),
(0000055, 00004, '67%', '67%', 34, 'N'),
(0000056, 00004, '66%', '66%', 35, 'N'),
(0000057, 00004, '65%', '65%', 36, 'N'),
(0000058, 00004, '64%', '64%', 37, 'N'),
(0000059, 00004, '63%', '63%', 38, 'N'),
(0000060, 00004, '62%', '62%', 39, 'N'),
(0000061, 00004, '61%', '61%', 40, 'N'),
(0000062, 00004, '60%', '60%', 41, 'N'),
(0000063, 00004, '59%', '59%', 42, 'N'),
(0000064, 00004, '58%', '58%', 43, 'N'),
(0000065, 00004, '57%', '57%', 44, 'N'),
(0000066, 00004, '56%', '56%', 45, 'N'),
(0000067, 00004, '55%', '55%', 46, 'N'),
(0000068, 00004, '54%', '54%', 47, 'N'),
(0000069, 00004, '53%', '53%', 48, 'N'),
(0000070, 00004, '52%', '52%', 49, 'N'),
(0000071, 00004, '51%', '51%', 50, 'N'),
(0000072, 00004, '50%', '50%', 51, 'N'),
(0000073, 00004, '49%', '49%', 52, 'N'),
(0000074, 00004, '48%', '48%', 53, 'N'),
(0000075, 00004, '47%', '47%', 54, 'N'),
(0000076, 00004, '46%', '46%', 55, 'N'),
(0000077, 00004, '45%', '45%', 56, 'N'),
(0000078, 00004, '44%', '44%', 57, 'N'),
(0000079, 00004, '43%', '43%', 58, 'N'),
(0000080, 00004, '42%', '42%', 59, 'N'),
(0000081, 00004, '41%', '41%', 60, 'N'),
(0000082, 00004, '40%', '40%', 61, 'N'),
(0000083, 00004, '39%', '39%', 62, 'N'),
(0000084, 00004, '38%', '38%', 63, 'N'),
(0000085, 00004, '37%', '37%', 64, 'N'),
(0000086, 00004, '36%', '36%', 65, 'N'),
(0000087, 00004, '35%', '35%', 66, 'N'),
(0000088, 00004, '34%', '34%', 67, 'N'),
(0000089, 00004, '33%', '33%', 68, 'N'),
(0000090, 00004, '32%', '32%', 69, 'N'),
(0000091, 00004, '31%', '31%', 70, 'N'),
(0000092, 00004, '30%', '30%', 71, 'N'),
(0000093, 00004, '29%', '29%', 72, 'N'),
(0000094, 00004, '28%', '28%', 73, 'N'),
(0000095, 00004, '27%', '27%', 74, 'N'),
(0000096, 00004, '26%', '26%', 75, 'N'),
(0000097, 00004, '25%', '25%', 76, 'N'),
(0000098, 00004, '24%', '24%', 77, 'N'),
(0000099, 00004, '23%', '23%', 78, 'N'),
(0000100, 00004, '22%', '22%', 79, 'N'),
(0000101, 00004, '21%', '21%', 80, 'N'),
(0000102, 00004, '20%', '20%', 81, 'N'),
(0000103, 00004, '19%', '19%', 82, 'N'),
(0000104, 00004, '18%', '18%', 83, 'N'),
(0000105, 00004, '17%', '17%', 84, 'N'),
(0000106, 00004, '16%', '16%', 85, 'N'),
(0000107, 00004, '15%', '15%', 86, 'N'),
(0000108, 00004, '14%', '14%', 87, 'N'),
(0000109, 00004, '13%', '13%', 88, 'N'),
(0000110, 00004, '12%', '12%', 89, 'N'),
(0000111, 00004, '11%', '11%', 90, 'N'),
(0000112, 00004, '10%', '10%', 91, 'N'),
(0000113, 00004, '9%', '9%', 92, 'N'),
(0000114, 00004, '8%', '8%', 93, 'N'),
(0000115, 00004, '7%', '7%', 94, 'N'),
(0000116, 00004, '6%', '6%', 95, 'N'),
(0000117, 00004, '5%', '5%', 96, 'N'),
(0000118, 00004, '4%', '4%', 97, 'N'),
(0000119, 00004, '3%', '3%', 98, 'N'),
(0000120, 00004, '2%', '2%', 99, 'N'),
(0000121, 00004, '1%', '1%', 100, 'N'),
(0000122, 00004, '0%', '0%', 101, 'N'),
(0000123, 00005, 'A+', 'A+', 1, 'N'),
(0000124, 00005, 'A', 'A', 2, 'N'),
(0000125, 00005, 'A-', 'A-', 3, 'N'),
(0000126, 00005, 'B+', 'B+', 4, 'N'),
(0000127, 00005, 'B', 'B', 5, 'N'),
(0000128, 00005, 'B-', 'B-', 6, 'N'),
(0000129, 00005, 'C+', 'C+', 7, 'N'),
(0000130, 00005, 'C', 'C', 8, 'N'),
(0000131, 00005, 'C-', 'C-', 9, 'N'),
(0000132, 00005, 'D+', 'D+', 10, 'N'),
(0000133, 00005, 'D', 'D', 11, 'N'),
(0000134, 00005, 'D-', 'D-', 12, 'N'),
(0000135, 00005, 'E+', 'E+', 13, 'N'),
(0000136, 00005, 'E', 'E', 14, 'N'),
(0000137, 00005, 'E-', 'E-', 15, 'N'),
(0000138, 00005, 'F', 'F', 16, 'N'),
(0000139, 00006, 'A', 'A', 1, 'N'),
(0000140, 00006, 'B', 'B', 2, 'N'),
(0000141, 00006, 'C', 'C', 3, 'N'),
(0000142, 00006, 'D', 'D', 4, 'N'),
(0000143, 00006, 'E', 'E', 5, 'N'),
(0000144, 00006, 'F', 'F', 6, 'N'),
(0000145, 00007, '7', 'Exceptional  Performance', 1, 'N'),
(0000146, 00007, '6', 'Well Above Expected Level', 2, 'N'),
(0000147, 00007, '5', 'Above Expected Level', 3, 'N'),
(0000148, 00007, '4', 'At Expected Level', 4, 'N'),
(0000149, 00007, '3', 'Below Expected Level', 5, 'N'),
(0000150, 00007, '2', 'Well Below Expected Level', 6, 'N'),
(0000151, 00007, '1', 'Cause For Concern', 7, 'N'),
(0000152, 00009, 'Complete', 'Work complete', 1, 'N'),
(0000153, 00009, 'Incomplete', 'Work incomplete', 3, 'N'),
(0000154, 00009, 'Late', 'Work submitted late', 2, 'N'),
(0000155, 00007, 'Incomplete', 'Work incomplete', 8, 'N'),
(0000156, 00001, 'Incomplete', 'Work incomplete', 8, 'N'),
(0000157, 00003, 'Incomplete', 'Work incomplete', 10, 'N'),
(0000158, 00004, 'Incomplete', 'Work incomplete', 102, 'N'),
(0000159, 00005, 'Incomplete', 'Work incomplete', 17, 'N'),
(0000160, 00006, 'Incomplete', 'Work incomplete', 7, 'N'),
(0000162, 00010, '60', '60', 110, 'N'),
(0000163, 00010, '61', '61', 109, 'N'),
(0000164, 00010, '62', '62', 108, 'N'),
(0000165, 00010, '63', '63', 107, 'N'),
(0000166, 00010, '64', '64', 106, 'N'),
(0000167, 00010, '65', '65', 105, 'N'),
(0000168, 00010, '66', '66', 104, 'N'),
(0000169, 00010, '67', '67', 103, 'N'),
(0000170, 00010, '68', '68', 102, 'N'),
(0000171, 00010, '69', '69', 101, 'N'),
(0000172, 00010, '70', '70', 100, 'N'),
(0000173, 00010, '71', '71', 99, 'N'),
(0000174, 00010, '72', '72', 98, 'N'),
(0000175, 00010, '73', '73', 97, 'N'),
(0000176, 00010, '74', '74', 96, 'N'),
(0000177, 00010, '75', '75', 95, 'N'),
(0000178, 00010, '76', '76', 94, 'N'),
(0000179, 00010, '77', '77', 93, 'N'),
(0000180, 00010, '78', '78', 92, 'N'),
(0000181, 00010, '79', '79', 91, 'N'),
(0000182, 00010, '80', '80', 90, 'N'),
(0000183, 00010, '81', '81', 89, 'N'),
(0000184, 00010, '82', '82', 88, 'N'),
(0000185, 00010, '83', '83', 87, 'N'),
(0000186, 00010, '84', '84', 86, 'N'),
(0000187, 00010, '85', '85', 85, 'N'),
(0000188, 00010, '86', '86', 84, 'N'),
(0000189, 00010, '87', '87', 83, 'N'),
(0000190, 00010, '88', '88', 82, 'N'),
(0000191, 00010, '89', '89', 81, 'N'),
(0000192, 00010, '90', '90', 80, 'N'),
(0000193, 00010, '91', '91', 79, 'N'),
(0000194, 00010, '92', '92', 78, 'N'),
(0000195, 00010, '93', '93', 77, 'N'),
(0000196, 00010, '94', '94', 76, 'N'),
(0000197, 00010, '95', '95', 75, 'N'),
(0000198, 00010, '96', '96', 74, 'N'),
(0000199, 00010, '97', '97', 73, 'N'),
(0000200, 00010, '98', '98', 72, 'N'),
(0000201, 00010, '99', '99', 71, 'N'),
(0000202, 00010, '100', '100', 70, 'N'),
(0000203, 00010, '101', '101', 69, 'N'),
(0000204, 00010, '102', '102', 68, 'N'),
(0000205, 00010, '103', '103', 67, 'N'),
(0000206, 00010, '104', '104', 66, 'N'),
(0000207, 00010, '105', '105', 65, 'N'),
(0000208, 00010, '106', '106', 64, 'N'),
(0000209, 00010, '107', '107', 63, 'N'),
(0000210, 00010, '108', '108', 62, 'N'),
(0000211, 00010, '109', '109', 61, 'N'),
(0000212, 00010, '110', '110', 60, 'N'),
(0000213, 00010, '111', '111', 59, 'N'),
(0000214, 00010, '112', '112', 58, 'N'),
(0000215, 00010, '113', '113', 57, 'N'),
(0000216, 00010, '114', '114', 56, 'N'),
(0000217, 00010, '115', '115', 55, 'N'),
(0000218, 00010, '116', '116', 54, 'N'),
(0000219, 00010, '117', '117', 53, 'N'),
(0000220, 00010, '118', '118', 52, 'N'),
(0000221, 00010, '119', '119', 51, 'N'),
(0000222, 00010, '120', '120', 50, 'N'),
(0000223, 00010, '121', '121', 49, 'N'),
(0000224, 00010, '122', '122', 48, 'N'),
(0000225, 00010, '123', '123', 47, 'N'),
(0000226, 00010, '124', '124', 46, 'N'),
(0000227, 00010, '125', '125', 45, 'N'),
(0000228, 00010, '126', '126', 44, 'N'),
(0000229, 00010, '127', '127', 43, 'N'),
(0000230, 00010, '128', '128', 42, 'N'),
(0000231, 00010, '129', '129', 41, 'N'),
(0000232, 00010, '130', '130', 40, 'N'),
(0000233, 00010, '131', '131', 39, 'N'),
(0000234, 00010, '132', '132', 38, 'N'),
(0000235, 00010, '133', '133', 37, 'N'),
(0000236, 00010, '134', '134', 36, 'N'),
(0000237, 00010, '135', '135', 35, 'N'),
(0000238, 00010, '136', '136', 34, 'N'),
(0000239, 00010, '137', '137', 33, 'N'),
(0000240, 00010, '138', '138', 32, 'N'),
(0000241, 00010, '139', '139', 31, 'N'),
(0000242, 00010, '140', '140', 30, 'N'),
(0000243, 00011, '8A', '8A', 1, 'N'),
(0000244, 00011, '8B', '8B', 2, 'N'),
(0000245, 00011, '8C', '8C', 3, 'N'),
(0000246, 00011, '7A', '7A', 4, 'N'),
(0000247, 00011, '7B', '7B', 5, 'N'),
(0000248, 00011, '7C', '7C', 6, 'N'),
(0000249, 00011, '6A', '6A', 7, 'N'),
(0000250, 00011, '6B', '6B', 8, 'N'),
(0000251, 00011, '6C', '6C', 9, 'N'),
(0000252, 00011, '5A', '5A', 9, 'N'),
(0000253, 00011, '5B', '5B', 10, 'N'),
(0000254, 00011, '5C', '5C', 11, 'N'),
(0000255, 00011, '4A', '4A', 12, 'N'),
(0000256, 00011, '4B', '4B', 13, 'N'),
(0000257, 00011, '4C', '4C', 14, 'N'),
(0000258, 00011, 'B3', 'B3', 15, 'N'),
(0000259, 00012, 'A', 'A', 1, 'N'),
(0000260, 00012, 'A/B', 'A/B', 2, 'N'),
(0000261, 00012, 'B', 'B', 3, 'N'),
(0000262, 00012, 'B/C', 'B/C', 4, 'N'),
(0000263, 00012, 'C', 'C', 5, 'N'),
(0000264, 00012, 'C/D', 'C/D', 6, 'N'),
(0000265, 00012, 'D', 'D', 7, 'N'),
(0000266, 00012, 'D/E', 'D/E', 8, 'N'),
(0000267, 00012, 'E', 'E', 9, 'N'),
(0000268, 00012, 'E/F', 'E/F', 10, 'N'),
(0000269, 00012, 'F', 'F', 11, 'N'),
(0000270, 00012, 'G', 'G', 12, 'N'),
(0000271, 00012, 'U', 'Unclassified', 13, 'N'),
(0000272, 00010, '141', '141', 29, 'N'),
(0000273, 00013, '7', '7', 1, 'N'),
(0000274, 00013, '6', '6', 2, 'N'),
(0000275, 00013, '5', '5', 3, 'N'),
(0000276, 00013, '4', '4', 4, 'N'),
(0000277, 00013, '3', '3', 5, 'N'),
(0000278, 00013, '2', '2', 6, 'N'),
(0000279, 00013, '1', '1', 7, 'N'),
(0000280, 00014, '45', '45', 1, 'N'),
(0000281, 00014, '44', '44', 2, 'N'),
(0000282, 00014, '43', '43', 3, 'N'),
(0000283, 00014, '42', '42', 4, 'N'),
(0000284, 00014, '41', '41', 5, 'N'),
(0000285, 00014, '40', '40', 6, 'N'),
(0000286, 00014, '39', '39', 7, 'N'),
(0000287, 00014, '38', '38', 8, 'N'),
(0000288, 00014, '37', '37', 9, 'N'),
(0000289, 00014, '36', '36', 10, 'N'),
(0000290, 00014, '35', '35', 11, 'N'),
(0000291, 00014, '34', '34', 12, 'N'),
(0000292, 00014, '33', '33', 13, 'N'),
(0000293, 00014, '32', '32', 14, 'N'),
(0000294, 00014, '31', '31', 15, 'N'),
(0000295, 00014, '30', '30', 16, 'N'),
(0000296, 00014, '29', '29', 17, 'N'),
(0000297, 00014, '28', '28', 18, 'N'),
(0000298, 00014, '27', '27', 19, 'N'),
(0000299, 00014, '26', '26', 20, 'N'),
(0000300, 00014, '25', '25', 21, 'N'),
(0000301, 00014, '24', '24', 22, 'N'),
(0000302, 00014, '23', '23', 23, 'N'),
(0000303, 00014, '22', '22', 24, 'N'),
(0000304, 00014, '21', '21', 25, 'N'),
(0000305, 00014, '20', '20', 26, 'N'),
(0000306, 00014, '19', '19', 27, 'N'),
(0000307, 00014, '18', '18', 28, 'N'),
(0000308, 00014, '17', '17', 29, 'N'),
(0000309, 00014, '16', '16', 30, 'N'),
(0000310, 00014, '15', '15', 31, 'N'),
(0000311, 00014, '14', '14', 32, 'N'),
(0000312, 00014, '13', '13', 33, 'N'),
(0000313, 00014, '12', '12', 34, 'N'),
(0000314, 00014, '11', '11', 35, 'N'),
(0000315, 00014, '10', '10', 36, 'N'),
(0000316, 00014, '9', '9', 37, 'N'),
(0000317, 00014, '8', '8', 38, 'N'),
(0000318, 00014, '7', '7', 39, 'N'),
(0000319, 00014, '6', '6', 40, 'N'),
(0000320, 00014, '5', '5', 41, 'N'),
(0000321, 00014, '4', '4', 42, 'N'),
(0000322, 00014, '3', '3', 43, 'N'),
(0000323, 00014, '2', '2', 44, 'N'),
(0000324, 00014, '1', '1', 45, 'N'),
(0000325, 00015, '8', 'Level 8', 1, 'N'),
(0000326, 00015, '7', 'Level 7', 2, 'N'),
(0000327, 00015, '6', 'Level 6', 3, 'N'),
(0000328, 00015, '5', 'Level 5', 4, 'N'),
(0000329, 00015, '4', 'Level 4', 5, 'N'),
(0000330, 00015, '3', 'Level 3', 6, 'N');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSchoolYear`
--

CREATE TABLE `gibbonSchoolYear` (
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(9) NOT NULL DEFAULT '',
  `status` enum('Past','Current','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `sequenceNumber` int(3) NOT NULL,
  `firstDay` date DEFAULT NULL,
  `lastDay` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonSchoolYear`
--

INSERT INTO `gibbonSchoolYear` (`gibbonSchoolYearID`, `name`, `status`, `sequenceNumber`, `firstDay`, `lastDay`) VALUES
(025,'2024-2025','Current',3,'2024-05-20','2025-06-29'),
(026,'2025-2026','Upcoming',4,'2025-08-11','2026-06-29');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSchoolYearSpecialDay`
--

CREATE TABLE `gibbonSchoolYearSpecialDay` (
  `gibbonSchoolYearSpecialDayID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearTermID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `type` enum('School Closure','Timing Change','Off Timetable') DEFAULT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `schoolOpen` time DEFAULT NULL,
  `schoolStart` time DEFAULT NULL,
  `schoolEnd` time DEFAULT NULL,
  `schoolClose` time DEFAULT NULL,
  `context` enum('Year Group','Form Group') DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL,
  `gibbonFormGroupIDList` varchar(255) DEFAULT NULL,
  `cancelActivities` enum('Y','N') DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSchoolYearTerm`
--

CREATE TABLE `gibbonSchoolYearTerm` (
  `gibbonSchoolYearTermID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `sequenceNumber` int(5) NOT NULL,
  `name` varchar(20) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `firstDay` date NOT NULL,
  `lastDay` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonSchoolYearTerm`
--

INSERT INTO `gibbonSchoolYearTerm` (`gibbonSchoolYearTermID`, `gibbonSchoolYearID`, `sequenceNumber`, `name`, `nameShort`, `firstDay`, `lastDay`) VALUES
(00034,025,31,'Term 1','T1','2024-05-20','2024-12-30'),
(00035,025,32,'Term 2','T2','2025-01-07','2025-04-13'),
(00036,025,33,'Term 3','T3','2025-04-14','2025-06-29'),
(00037,026,34,'Term 1','T1','2025-08-11','2025-12-28'),
(00038,026,35,'Term 2','T2','2026-01-05','2026-04-05'),
(00039,026,36,'Term 3','T3','2026-04-13','2026-06-29');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSession`
--

CREATE TABLE `gibbonSession` (
  `gibbonSessionID` varchar(40) NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonActionID` int(7) UNSIGNED ZEROFILL DEFAULT NULL,
  `sessionData` mediumtext,
  `sessionStatus` varchar(20) DEFAULT NULL,
  `timestampCreated` timestamp NULL DEFAULT NULL,
  `timestampModified` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSetting`
--

CREATE TABLE `gibbonSetting` (
  `gibbonSettingID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `scope` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `nameDisplay` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonSetting`
--

INSERT INTO `gibbonSetting` (`gibbonSettingID`, `scope`, `name`, `nameDisplay`, `description`, `value`) VALUES
(00001, 'System', 'absoluteURL', 'Base URL', 'The address at which the whole system resides.', ''),
(00002, 'System', 'organisationName', 'Organisation Name', '', ''),
(00003, 'System', 'organisationNameShort', 'Organisation Initials', '', ''),
(00006, 'System', 'pagination', 'Pagination Count', 'Must be numeric. Number of records shown per page.', '50'),
(00007, 'System', 'systemName', 'System Name', '', 'Gibbon'),
(00008, 'System', 'indexText', 'Index Page Text', 'Text displayed in system\'s welcome page.', 'Welcome to Gibbon, the free, open, flexible school platform. Designed by teachers for learning, Gibbon gives you the school tools you need.'),
(00009, 'System', 'absolutePath', 'Base Path', 'The local FS path to the system', ''),
(00011, 'System', 'timezone', 'Timezone', 'The timezone where the school is located', 'Asia/Hong_Kong'),
(00013, 'System', 'analytics', 'Analytics', 'Javascript code to integrate statistics, such as Google Analytics', ''),
(00014, 'System', 'emailLink', 'Link To Email', 'The link that points to the school/\'s email system', ''),
(00015, 'System', 'webLink', 'Link To Web', 'The link that points to the school/\'s website', ''),
(00018, 'System', 'defaultAssessmentScale', 'Default Assessment Scale', 'This is the scale used as a default where assessment scales need to be selected.', '00007'),
(00021, 'System', 'country', 'Country', 'The country the school is located in', ''),
(00022, 'System', 'organisationLogo', 'Logo', 'Relative path to site logo (400 x 100px)', 'themes/Default/img/logo.png'),
(00023, 'System', 'calendarFeed', 'School Google Calendar ID', 'Google Calendar ID for your school calendar. Only enables timetable integration when logging in via Google.', ''),
(00024, 'Activities', 'access', 'Access', 'System-wide access control', 'Register'),
(00025, 'Activities', 'payment', 'Payment', 'Payment system', 'Per Activity'),
(00026, 'Activities', 'enrolmentType', 'Enrolment Type', 'Enrolment process type', 'Competitive'),
(00027, 'Activities', 'backupChoice', 'Backup Choice', 'Allow students to choose a backup, in case enroled activity is full.', 'Y'),
(00029, 'Application Form', 'introduction', 'Introduction', 'Information to display before the form', ''),
(00030, 'Application Form', 'postscript', 'Postscript', 'Information to display at the end of the form', ''),
(00031, 'Application Form', 'scholarships', 'Scholarships', 'Information to display before the scholarship options', ''),
(00032, 'Application Form', 'agreement', 'Agreement', 'Without this text, which is displayed above the agreement, users will not be asked to agree to anything', ''),
(00033, 'Application Form', 'publicApplications', 'Public Applications?', 'If yes, members of the public can submit applications', 'Y'),
(00034, 'Behaviour', 'positiveDescriptors', 'Positive Descriptors', 'Allowable choices for positive behaviour', 'Attitude to learning,Collaboration,Community spirit,Creativity,Effort,Leadership,Participation,Persistence,Problem solving,Quality of work,Values'),
(00035, 'Behaviour', 'negativeDescriptors', 'Negative Descriptors', 'Allowable choices for negative behaviour', 'Classwork - Late,Classwork - Incomplete,Classwork - Unacceptable,Disrespectful,Disruptive,Homework - Late,Homework - Incomplete,Homework - Unacceptable,ICT Misuse,Truancy,Other'),
(00036, 'Behaviour', 'levels', 'Levels', 'Allowable choices for severity level (from lowest to highest)', ',Stage 1,Stage 1 (Actioned),Stage 2,Stage 2 (Actioned),Stage 3,Stage 3 (Actioned),Actioned'),
(00037, 'Resources', 'categories', 'Categories', 'Allowable choices for category', 'Article,Book,Document,Graphic,Idea,Music,Object,Painting,Person,Photo,Place,Poetry,Prose,Rubric,Text,Video,Website,Work Sample,Other'),
(00038, 'Resources', 'purposesGeneral', 'Purposes (General)', 'Allowable choices for purpose when creating a resource', 'Assessment Aid,Concept,Inspiration,Learner Profile,Mass Mailer Attachment,Provocation,Skill,Teaching and Learning Strategy,Other'),
(00039, 'System', 'version', 'Version', 'The version of the Gibbon database', '27.0.01'),
(00040, 'Resources', 'purposesRestricted', 'Purposes (Restricted)', 'Additional allowable choices for purpose when creating a resource, for those with \"Manage All Resources\" rights', ''),
(00041, 'System', 'organisationEmail', 'Organisation Email', 'General email address for the school', ''),
(00042, 'Activities', 'dateType', 'Date Type', 'Should activities be organised around dates (flexible) or terms (easy)?', 'Term'),
(00043, 'System', 'installType', 'Install Type', 'The purpose of this installation of Gibbon', 'Production'),
(00044, 'System', 'statsCollection', 'Statistics Collection', 'To track Gibbon uptake, the system tracks basic data (current URL, install type, organisation name) on each install. Do you want to help?', 'Y'),
(00045, 'Activities', 'maxPerTerm', 'Maximum Activities per Term', 'The most a student can sign up for in one term. Set to 0 for unlimited.', '0'),
(00046, 'Planner', 'lessonDetailsTemplate', 'Lesson Details Template', 'Template to be inserted into Lesson Details field', ''),
(00047, 'Planner', 'teachersNotesTemplate', 'Teacher\'s Notes Template', 'Template to be inserted into Teacher\'s Notes field', ''),
(00048, 'Planner', 'smartBlockTemplate', 'Smart Block Template', 'Template to be inserted into new block in Smart Unit', ''),
(00049, 'Planner', 'unitOutlineTemplate', 'Unit Outline Template', 'Template to be inserted into Unit Outline section of planner', ''),
(00050, 'Application Form', 'milestones', 'Milestones', 'Comma-separated list of the major steps in the application process. Applicants can be tracked through the various stages.', 'Information Reviewed,Documents Checked,Ready to Accept'),
(00051, 'Library', 'defaultLoanLength', 'Default Loan Length', 'The standard loan length for a library item, in days', '7'),
(00052, 'Behaviour', 'policyLink', 'Policy Link', 'A link to the school behaviour policy.', ''),
(00053, 'Library', 'browseBGColor', 'Browse Library BG Colour', 'Click to select a colour.', ''),
(00054, 'Library', 'browseBGImage', 'Browse Library BG Image', 'URL to background image used behind library browsing screen.', ''),
(00055, 'System', 'passwordPolicyAlpha', 'Password - Alpha Requirement', 'Require both upper and lower case alpha characters?', 'Y'),
(00056, 'System', 'passwordPolicyNumeric', 'Password - Numeric Requirement', 'Require at least one numeric character?', 'Y'),
(00057, 'System', 'passwordPolicyNonAlphaNumeric', 'Password - Non-Alphanumeric Requirement', 'Require at least one non-alphanumeric character (e.g. punctuation mark or space)?', 'N'),
(00058, 'System', 'passwordPolicyMinLength', 'Password - Minimum Length', 'Minimum acceptable password length.', '8'),
(00059, 'User Admin', 'ethnicity', 'Ethnicity', 'Comma-separated list of ethnicities available in system', ''),
(00060, 'User Admin', 'nationality', 'Nationality', 'Comma-separated list of nationalities available in system. If blank, system will default to list of countries', ''),
(00061, 'User Admin', 'residencyStatus', 'Residency Status', 'Comma-separated list of residency status available in system. If blank, system will allow text input', ''),
(00063, 'User Admin', 'personalDataUpdaterRequiredFields', 'Personal Data Updater Required Fields', 'Serialized array listed personal fields in data updater, and whether or not they are required.', 'a:47:{s:5:\"title\";s:1:\"N\";s:7:\"surname\";s:1:\"Y\";s:9:\"firstName\";s:1:\"N\";s:10:\"otherNames\";s:1:\"N\";s:13:\"preferredName\";s:1:\"Y\";s:12:\"officialName\";s:1:\"Y\";s:16:\"nameInCharacters\";s:1:\"N\";s:3:\"dob\";s:1:\"N\";s:5:\"email\";s:1:\"N\";s:14:\"emailAlternate\";s:1:\"N\";s:8:\"address1\";s:1:\"Y\";s:16:\"address1District\";s:1:\"N\";s:15:\"address1Country\";s:1:\"N\";s:8:\"address2\";s:1:\"N\";s:16:\"address2District\";s:1:\"N\";s:15:\"address2Country\";s:1:\"N\";s:10:\"phone1Type\";s:1:\"N\";s:17:\"phone1CountryCode\";s:1:\"N\";s:6:\"phone1\";s:1:\"N\";s:6:\"phone2\";s:1:\"N\";s:6:\"phone3\";s:1:\"N\";s:6:\"phone4\";s:1:\"N\";s:13:\"languageFirst\";s:1:\"N\";s:14:\"languageSecond\";s:1:\"N\";s:13:\"languageThird\";s:1:\"N\";s:14:\"countryOfBirth\";s:1:\"N\";s:9:\"ethnicity\";s:1:\"N\";s:12:\"citizenship1\";s:1:\"N\";s:20:\"citizenship1Passport\";s:1:\"N\";s:12:\"citizenship2\";s:1:\"N\";s:20:\"citizenship2Passport\";s:1:\"N\";s:8:\"religion\";s:1:\"N\";s:20:\"nationalIDCardNumber\";s:1:\"N\";s:15:\"residencyStatus\";s:1:\"N\";s:14:\"visaExpiryDate\";s:1:\"N\";s:10:\"profession\";s:1:\"N\";s:8:\"employer\";s:1:\"N\";s:8:\"jobTitle\";s:1:\"N\";s:14:\"emergency1Name\";s:1:\"N\";s:17:\"emergency1Number1\";s:1:\"N\";s:17:\"emergency1Number2\";s:1:\"N\";s:22:\"emergency1Relationship\";s:1:\"N\";s:14:\"emergency2Name\";s:1:\"N\";s:17:\"emergency2Number1\";s:1:\"N\";s:17:\"emergency2Number2\";s:1:\"N\";s:22:\"emergency2Relationship\";s:1:\"N\";s:19:\"vehicleRegistration\";s:1:\"N\";}'),
(00065, 'School Admin', 'primaryExternalAssessmentByYearGroup', 'Primary External Assessment By Year Group', 'Serialized array connected gibbonExternalAssessmentID to gibbonYearGroupID, and specify which field set to use.', 'a:7:{s:3:\"001\";s:1:\"-\";s:3:\"002\";s:1:\"-\";s:3:\"003\";s:1:\"-\";s:3:\"004\";s:1:\"-\";s:3:\"005\";s:1:\"-\";s:3:\"006\";s:1:\"-\";s:3:\"007\";s:1:\"-\";}'),
(00066, 'Markbook', 'markbookType', 'Markbook Type', 'Comma-separated list of types to make available in the Markbook.', 'Essay,Exam,Homework,Reflection,Test,Unit,End of Year,Other'),
(00067, 'System', 'allowableHTML', 'Allowable HTML', 'TinyMCE-style list of acceptable HTML tags and options.', 'br[style|class],strong[style|class],b[style|class],em[style|class],span[style|class],p[style|class],address[style|class],pre[style|class],h1[style|class],h2[style|class],h3[style|class],h4[style|class],h5[style|class],h6[style|class],table[style|class],thead[style|class],tbody[style|class],tfoot[style|class],tr[style|class],td[style|class|colspan|rowspan],ol[style|class],ul[style|class],li[style|class],blockquote[style|class],a[style|class|target|href],img[style|class|src|width|height],video[style|class],source[style|class],hr[style|class],iframe[style|class|width|height|src|frameborder|allowfullscreen],embed[style|class],div[style|class],sup[style|class],sub[style|class],code[style|class],details[style|class],summary[style|class],pagebreak,columnbreak'),
(00068, 'Application Form', 'howDidYouHear', 'How Did Your Hear?', 'Comma-separated list', 'Advertisement,Personal Recommendation,World Wide Web,Others'),
(00070, 'Messenger', 'smsUsername', 'SMS Username', 'SMS gateway username.', ''),
(00071, 'Messenger', 'smsPassword', 'SMS Password', 'SMS gateway password.', ''),
(00072, 'Messenger', 'smsURL', 'SMS URL', 'SMS gateway URL for send requests.', ''),
(00073, 'Messenger', 'smsURLCredit', 'SMS URL Credit', 'SMS gateway URL for checking credit.', ''),
(00074, 'System', 'currency', 'Currency', 'System-wde currency for financial transactions. Support for online payment in this currency depends on your credit card gateway: please consult their support documentation.', 'USD $'),
(00075, 'System', 'enablePayments', 'Enable Payments', 'Should payments be enabled across the system?', 'N'),
(00076, 'System', 'paymentAPIUsername', 'API Username', 'API details are provided by the payment gateway provider', ''),
(00077, 'System', 'paymentAPIPassword', 'API Password', 'API details are provided by the payment gateway provider', ''),
(00078, 'System', 'paymentAPISignature', 'API Signature', 'API details are provided by the payment gateway provider', ''),
(00079, 'Application Form', 'applicationFee', 'Application Submission Fee', 'The cost of applying to the school. Paid when submitting the application form.', '0'),
(00080, 'Application Form', 'requiredDocuments', 'Required Documents', 'Comma-separated list of documents which must be submitted electronically with the application form.', ''),
(00081, 'Application Form', 'requiredDocumentsCompulsory', 'Required Documents Compulsory?', 'Are the required documents compulsory?', 'N'),
(00082, 'Application Form', 'requiredDocumentsText', 'Required Documents Text', 'Explanatory text to appear with the required documents?', ''),
(00083, 'Application Form', 'notificationStudentDefault', 'Student Notification Default', 'Should student acceptance email be turned on or off by default.', 'On'),
(00084, 'Application Form', 'languageOptionsActive', 'Language Options Active', 'Should the Language Options section be turned on?', 'Off'),
(00085, 'Application Form', 'languageOptionsBlurb', 'Language Options Blurb', 'Introductory text if Language Options section is turned on.', ''),
(00086, 'Application Form', 'languageOptionsLanguageList', 'Language Options Language List', 'Comma-separated list of available language selections if Language Options section is turned on.', ''),
(00088, 'User Admin', 'personalBackground', 'Personal Background', 'Should users be allowed to set their own personal backgrounds?', 'Y'),
(00091, 'User Admin', 'dayTypeOptions', 'Day-Type Options', 'Comma-separated list of options to make available (e.g. half-day, full-day). If blank, this field will not show up in the application form.', ''),
(00092, 'User Admin', 'dayTypeText', 'Day-Type Text', 'Explanatory text to include with Day-Type Options.', ''),
(00095, 'Markbook', 'showStudentAttainmentWarning', 'Show Student Attainment Warning', 'Show low attainment grade visual warning to students?', 'Y'),
(00096, 'Markbook', 'showStudentEffortWarning', 'Show Student Effort Warning', 'Show low effort grade visual warning to students?', 'Y'),
(00097, 'Markbook', 'showParentAttainmentWarning', 'Show Parent Attainment Warning', 'Show low attainment grade visual warning to parents?', 'Y'),
(00098, 'Markbook', 'showParentEffortWarning', 'Show Parent Effort Warning', 'Show low effort grade visual warning to parents?', 'Y'),
(00099, 'Planner', 'allowOutcomeEditing', 'Allow Outcome Editing', 'Should the text within outcomes be editable when planning lessons and units?', 'Y'),
(00100, 'User Admin', 'privacy', 'Privacy', 'Should privacy options be turned on across the system?', 'N'),
(00101, 'User Admin', 'privacyBlurb', 'Privacy Blurb', 'Descriptive text to accompany image privacy option when shown to users.', ''),
(00102, 'Finance', 'invoiceText', 'Invoice Text', 'Text to appear in invoice, above invoice details and fees.', ''),
(00103, 'Finance', 'invoiceNotes', 'Invoice Notes', 'Text to appear in invoice, below invoice details and fees.', ''),
(00104, 'Finance', 'receiptText', 'Receipt Text', 'Text to appear in receipt, above receipt details and fees.', ''),
(00105, 'Finance', 'receiptNotes', 'Receipt Notes', 'Text to appear in receipt, below receipt details and fees.', ''),
(00106, 'Finance', 'reminder1Text', 'Reminder 1 Text', 'Text to appear in first level reminder level, above invoice details and fees.', ''),
(00107, 'Finance', 'reminder2Text', 'Reminder 2 Text', 'Text to appear in second level reminder level, above invoice details and fees.', ''),
(00108, 'Finance', 'reminder3Text', 'Reminder 3 Text', 'Text to appear in third level reminder level, above invoice details and fees.', ''),
(00109, 'Finance', 'email', 'Email', 'Email address to send finance emails from.', ''),
(00110, 'Application Form', 'notificationParentsDefault', 'Parents Notification Default', 'Should parent acceptance email be turned on or off by default.', 'On'),
(00111, 'User Admin', 'privacyOptions', 'Privacy Options', 'Comma-separated list of choices to make available if privacy options are turned on.', ''),
(00112, 'Planner', 'sharingDefaultParents', 'Sharing Default: Parents', 'When adding lessons and deploying units, should sharing default for parents be Y or N?', 'Y'),
(00113, 'Planner', 'sharingDefaultStudents', 'Sharing Default: Students', 'When adding lessons and deploying units, should sharing default for students be Y or N?', 'Y'),
(00116, 'Application Form', 'notificationParentsMessage', 'Parents Notification Message', 'A custom message to add to the standard email to parents on acceptance.', ''),
(00117, 'Application Form', 'notificationStudentMessage', 'Student Notification Message', 'A custom message to add to the standard email to students on acceptance.', ''),
(00118, 'Finance', 'invoiceNumber', 'Invoice Number Style', 'How should invoice numbers be constructed?', 'Invoice ID'),
(00119, 'User Admin', 'departureReasons', 'Departure Reasons', 'Comma-separated list of reasons for departure from school. If blank, user can enter any text.', ''),
(00120, 'System', 'googleOAuth', 'Google Integration', '', 'Migrated'),
(00127, 'Markbook', 'personalisedWarnings', 'Personalised Warnings', 'Should markbook warnings be based on personal targets, if they are available?', 'Y'),
(00128, 'Activities', 'disableExternalProviderSignup', 'Disable External Provider Signup', 'Should we turn off the option to sign up for activities provided by an outside agency?', 'N'),
(00129, 'Activities', 'hideExternalProviderCost', 'Hide External Provider Cost', 'Should we hide the cost of activities provided by an outside agency from the Activities View?', 'N'),
(00130, 'System', 'cuttingEdgeCode', 'Cutting Edge Code', 'Are you running cutting edge code, instead of stable versions?', 'N'),
(00131, 'System', 'cuttingEdgeCodeLine', 'Cutting Edge Code Line', 'What line of SQL code did the last cutting edge update hit?', ''),
(00132, 'System', 'gibboneduComOrganisationName', 'gibbonedu.com Organisation Name', 'Name of organisation, as registered with gibbonedu.com, for access to value-added services.', ''),
(00133, 'System', 'gibboneduComOrganisationKey', 'gibbonedu.com Organisation Key', 'Organisation\'s private key, as registered with gibbonedu.com, for access to value-added services.', ''),
(00134, 'Application Form', 'studentDefaultEmail', 'Student Default Email', 'Set default email for students on acceptance, using [username] to insert username.', ''),
(00135, 'Application Form', 'studentDefaultWebsite', 'Student Default Website', 'Set default website for students on acceptance, using [username] to insert username.', ''),
(00136, 'School Admin', 'studentAgreementOptions', 'Student Agreement Options', 'Comma-separated list of agreements that students might be asked to sign in school (e.g. ICT Policy).', ''),
(00137, 'Markbook', 'attainmentAlternativeName', 'Attainment Alternative Name', 'A name to use instead of \"Attainment\" in the first grade column of the markbook.', ''),
(00138, 'Markbook', 'effortAlternativeName', 'Effort Alternative Name', 'A name to use instead of \"Effort\" in the second grade column of the markbook.', ''),
(00139, 'Markbook', 'attainmentAlternativeNameAbrev', 'Attainment Alternative Name Abbreviation', 'A short name to use instead of \"Attainment\" in the first grade column of the markbook.', ''),
(00140, 'Markbook', 'effortAlternativeNameAbrev', 'Effort Alternative Name Abbreviation', 'A short name to use instead of \"Effort\" in the second grade column of the markbook..', ''),
(00141, 'School Admin', 'parentWeeklyEmailSummaryIncludeBehaviour', 'Parent Weekly Email Summary Include Behaviour', 'Should behaviour information be included in the weekly planner email summary that goes out to parents?', 'Y'),
(00142, 'Finance', 'financeOnlinePaymentEnabled', 'Enable Online Payment', 'Should invoices be payable online, via an encrypted link in the invoice? Requires correctly configured payment gateway in System Settings.', 'N'),
(00143, 'Finance', 'financeOnlinePaymentThreshold', 'Online Payment Threshold', 'If invoices are payable online, what is the maximum payment allowed? Useful for controlling payment fees. No value means unlimited.', ''),
(00144, 'Departments', 'makeDepartmentsPublic', 'Make Departments Public', 'Should department information be made available to the public, via the Gibbon homepage?', 'N'),
(00145, 'System', 'sessionDuration', 'Session Duration', 'Time, in seconds, before system logs a user out. Should be less than PHP\'s session.gc_maxlifetime option.', '1200'),
(00146, 'Planner', 'makeUnitsPublic', 'Make Units Public', 'Enables a public listing of units, with teachers able to opt in to share units.', 'N'),
(00150, 'Students', 'enableStudentNotes', 'Enable Student Notes', 'Should student notes be turned on?', 'Y'),
(00151, 'Finance', 'budgetCategories', 'Budget Categories', 'Comma-separated list of budget categories.', 'Academic, Administration, Capital'),
(00153, 'Finance', 'expenseApprovalType', 'Expense Approval Type', 'How should expense approval be dealt with?', 'One Of'),
(00154, 'Finance', 'budgetLevelExpenseApproval', 'Budget Level Expense Approval', 'Should approval from a budget member with Full access be required?', 'Y'),
(00155, 'Finance', 'expenseRequestTemplate', 'Expense Request Template', 'An HTML template to be used in the description field of expense requests.', ''),
(00156, 'Finance', 'purchasingOfficer', 'Purchasing Officer', 'User responsible for purchasing for the school.', ''),
(00157, 'Finance', 'reimbursementOfficer', 'Reimbursement Officer', 'User responsible for reimbursing expenses.', ''),
(00158, 'Messenger', 'enableHomeScreenWidget', 'Enable Home Screen Widget', 'Adds a Message Wall widget to the home page, hihglighting current messages.', 'N'),
(00159, 'User Admin', 'enablePublicRegistration', 'Enable Public Registration', 'Allows members of the public to register to use the system.', 'N'),
(00160, 'User Admin', 'publicRegistrationMinimumAge', 'Public Registration Minimum Age', 'The minimum age, in years, permitted to register.', '13'),
(00161, 'User Admin', 'publicRegistrationDefaultStatus', 'Public Registration Default Status', 'Should new users be \'Full\' or \'Pending Approval\'?', 'Pending Approval'),
(00162, 'User Admin', 'publicRegistrationDefaultRole', 'Public Registration Default Role', 'System role to be assigned to registering members of the public.', '003'),
(00163, 'User Admin', 'publicRegistrationIntro', 'Public Registration Introductory Text', 'HTML text that will appear above the public registration form.', ''),
(00164, 'User Admin', 'publicRegistrationPrivacyStatement', 'Public Registration Privacy Statement', 'HTML text that will appear above the Submit button, explaining privacy policy.', 'By registering for this site you are giving permission for your personal data to be used and shared within this organisation and its websites. We will not share your personal data outside our organisation.'),
(00165, 'User Admin', 'publicRegistrationAgreement', 'Public Registration Agreement', 'Agreement that user must confirm before joining. Blank for no agreement.', 'In joining this site, and checking the box below, I agree to act lawfully, ethically and with respect for others. I agree to use this site for learning purposes only, and understand that access may be withdrawn at any time, at the discretion of the site\'s administrators.'),
(00166, 'User Admin', 'publicRegistrationPostscript', 'Public Registration Postscript', 'HTML text that will appear underneath the public registration form.', ''),
(00167, 'System', 'alarm', 'Alarm', 'Sound a system wide alarm to all staff.', 'None'),
(00168, 'Behaviour', 'enableDescriptors', 'Enable Descriptors', 'Setting to No reduces complexity of behaviour tracking.', 'Y'),
(00169, 'Behaviour', 'enableLevels', 'Enable Levels', 'Setting to No reduces complexity of behaviour tracking.', 'Y'),
(00170, 'Formal Assessment', 'internalAssessmentTypes', 'Internal Assessment Types', 'Comma-separated list of types to make available in Internal Assessments.', 'Expected Grade,Predicted Grade,Target Grade'),
(00171, 'System Admin', 'customAlarmSound', 'Custom Alarm Sound', 'A custom alarm sound file.', ''),
(00172, 'School Admin', 'facilityTypes', 'FacilityTypes', 'A comma-separated list of types for facilities.', 'Classroom,Hall,Laboratory,Library,Office,Outdoor,Performance,Staffroom,Storage,Study,Undercover,Other'),
(00173, 'Finance', 'allowExpenseAdd', 'Allow Expense Add', 'Allows privileged users to add expenses without going through request process.', 'Y'),
(00174, 'System', 'organisationAdministrator', 'System Administrator', 'The staff member who receives notifications for system events.', '1'),
(00175, 'System', 'organisationDBA', 'Database Administrator', 'The staff member who receives notifications for data events.', '1'),
(00176, 'System', 'organisationAdmissions', 'Admissions Administrator', 'The staff member who receives notifications for admissions events.', '1'),
(00177, 'Finance', 'hideItemisation', 'Hide Itemisation', 'Hide fee and payment details in receipts?', 'N'),
(00178, 'Application Form', 'autoHouseAssign', 'Auto House Assign', 'Attempt to automatically place student in a house?', 'N'),
(00179, 'Tracking', 'externalAssessmentDataPoints', 'External Assessment Data Points', 'Stores the external assessment choices for data points output in tracking.', ''),
(00180, 'Tracking', 'internalAssessmentDataPoints', 'Internal Assessment Data Points', 'Stores the internal assessment choices for data points output in tracking.', ''),
(00181, 'Behaviour', 'enableNegativeBehaviourLetters', 'Enable Negative Behaviour Letters', 'Should automated behaviour letter functionality be enabled?', 'N'),
(00182, 'Behaviour', 'behaviourLettersNegativeLetter1Count', 'Negative Letter 1 Count', 'After how many negative records should letter 1 be sent?', '3'),
(00183, 'Behaviour', 'behaviourLettersLetter1Text', 'Letter 1 Text', 'The contents of letter 1, as HTML.', 'Dear Parent/Guardian,<br><br>This letter has been automatically generated to alert you to the fact that your child, [studentName], has reached [behaviourCount] negative behaviour incidents. Please see the list below for the details of these incidents:<br><br>[behaviourRecord]<br><br>This letter represents the first communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br><br>If you would like more information on this matter, please contact your child\'s tutor.'),
(00184, 'Behaviour', 'behaviourLettersNegativeLetter2Count', 'Negative Letter 2 Count', 'After how many negative records should letter 2 be sent?', '6'),
(00185, 'Behaviour', 'behaviourLettersLetter2Text', 'Letter 2 Text', 'The contents of letter 2, as HTML.', 'Dear Parent/Guardian,<br><br>This letter has been automatically generated to alert you to the fact that your child, [studentName], has reached [behaviourCount] negative behaviour incidents. Please see the list below for the details of these incidents:<br><br>[behaviourRecord]<br><br>This letter represents the second communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br><br>If you would like more information on this matter, please contact your child\'s tutor.'),
(00186, 'Behaviour', 'behaviourLettersNegativeLetter3Count', 'Negative Letter 3 Count', 'After how many negative records should letter 3 be sent?', '9'),
(00187, 'Behaviour', 'behaviourLettersLetter3Text', 'Letter 3 Text', 'The contents of letter 3, as HTML.', 'Dear Parent/Guardian,<br><br>This letter has been automatically generated to alert you to the fact that your child, [studentName], has reached [behaviourCount] negative behaviour incidents. Please see the list below for the details of these incidents:<br><br>[behaviourRecord]<br><br>This letter represents the final communication in a sequence of 3 potential alerts, each of which is more critical than the last.<br><br>If you would like more information on this matter, please contact your child\'s tutor.'),
(00188, 'Markbook', 'enableColumnWeighting', 'Enable Column Weighting', 'Should column weighting and total scores be enabled in the Markbook?', 'N'),
(00189, 'System', 'firstDayOfTheWeek', 'First Day Of The Week', 'On which day should the week begin?', 'Monday'),
(00190, 'Application Form', 'usernameFormat', 'Username Format', 'How should usernames be formated? Choose from [preferredName], [preferredNameInitial], [surname].', '[preferredNameInitial][surname]'),
(00191, 'Staff', 'jobOpeningDescriptionTemplate', 'Job Opening Description Template', 'Default HTML contents for the Job Opening Description field.', '<table style=\"width: 100%\">\n	<tr>\n		<td colspan=\"2\" style=\"vertical-align: top\">\n			<span style=\"text-decoration: underline; font-weight: bold\">Job Description</span><br>\n			<br>\n		</td>\n	</tr>\n	<tr>\n		<td style=\"width: 50%; vertical-align: top\">\n			<span style=\"text-decoration: underline; font-weight: bold\">Responsibilities</span><br>\n			<ul style=\"margin-top:0px\">\n				<li>\n</li>\n				<li>\n</li>\n			</ul>\n		</td>\n		<td style=\"width: 50%; vertical-align: top\">\n			<span style=\"text-decoration: underline; font-weight: bold\">Required Skills/Characteristics</span><br>\n			<ul style=\"margin-top:0px\">\n				<li>\n</li>\n				<li>\n</li>\n			</ul>\n		</td>\n	</tr>\n	<tr>\n		<td style=\"width: 50%; vertical-align: top\">\n			<span style=\"text-decoration: underline; font-weight: bold\">Remuneration</span><br>\n			<ul style=\"margin-top:0px\">\n				<li>\n</li>\n				<li>\n</li>\n			</ul>\n		</td>\n		<td style=\"width: 50%; vertical-align: top\">\n			<span style=\"text-decoration: underline; font-weight: bold\">Other Details </span><br>\n			<ul style=\"margin-top:0px\">\n				<li>\n</li>\n				<li>\n</li>\n			</ul>\n		</td>\n	</tr>\n</table>'),
(00192, 'Staff', 'staffApplicationFormIntroduction', 'Introduction', 'Information to display before the form', ''),
(00193, 'Staff', 'staffApplicationFormPostscript', 'Postscript', 'Information to display at the end of the form', ''),
(00194, 'Staff', 'staffApplicationFormAgreement', 'Agreement', 'Without this text, which is displayed above the agreement, users will not be asked to agree to anything', 'In submitting this form, I confirm that all information provided above is accurate and complete to the best of my knowledge.'),
(00195, 'Staff', 'staffApplicationFormMilestones', 'Milestones', 'Comma-separated list of the major steps in the application process. Applicants can be tracked through the various stages.', 'Short List, First Interview, Second Interview, Offer Made, Offer Accepted, Contact Issued, Contact Signed'),
(00196, 'Staff', 'staffApplicationFormRequiredDocuments', 'Required Documents', 'Comma-separated list of documents which must be submitted electronically with the application form.', 'Curriculum Vitae'),
(00197, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory', 'Required Documents Compulsory?', 'Are the required documents compulsory?', 'Y'),
(00198, 'Staff', 'staffApplicationFormRequiredDocumentsText', 'Required Documents Text', 'Explanatory text to appear with the required documents?', 'Please submit the following document(s) to ensure your application can be processed without delay.'),
(00199, 'Staff', 'staffApplicationFormNotificationDefault', 'Notification Default', 'Should acceptance email be turned on or off by default.', 'Y'),
(00200, 'Staff', 'staffApplicationFormNotificationMessage', 'Notification Message', 'A custom message to add to the standard email on acceptance.', ''),
(00201, 'Staff', 'staffApplicationFormDefaultEmail', 'Default Email', 'Set default email on acceptance, using [username] to insert username.', ''),
(00202, 'Staff', 'staffApplicationFormDefaultWebsite', 'Default Website', 'Set default website on acceptance, using [username] to insert username.', ''),
(00203, 'Staff', 'staffApplicationFormUsernameFormat', 'Username Format', 'How should usernames be formated? Choose from [preferredName], [preferredNameInitial], [surname].', '[preferredNameInitial].[surname]'),
(00204, 'System', 'organisationHR', 'Human Resources Administrator', 'The staff member who receives notifications for staffing events.', '0000000001'),
(00205, 'Staff', 'staffApplicationFormQuestions', 'Application Questions', 'HTML text that will appear as questions for the applicant to answer.', '<span style=\"text-decoration: underline; font-weight: bold\">Why are you applying for this role?</span><br><p></p>'),
(00206, 'Staff', 'salaryScalePositions', 'Salary Scale Positions', 'Comma-separated list of salary scale positions, from lowest to highest.', '1,2,3,4,5,6,7,8,9,10'),
(00207, 'Staff', 'responsibilityPosts', 'Responsibility Posts', 'Comma-separated list of posts carrying extra responsibilities.', ''),
(00208, 'Students', 'applicationFormSENText', 'Application Form SEN Text', 'Text to appear with the Special Educational Needs section of the student application form.', 'Please indicate whether or not your child has any known, or suspected, special educational needs, or whether they have been assessed for any such needs in the past. Provide any comments or information concerning your child\'s development that may be relevant to your child\'s performance in the classroom or elsewhere? Incorrect or withheld information may affect continued enrolment.'),
(00209, 'Students', 'applicationFormRefereeLink', 'Application Form Referee Link', 'Link to an external form that will be emailed to a referee of the applicant\'s choosing.', ''),
(00210, 'User Admin', 'religions', 'Religions', 'Comma-separated list of ethnicities available in system', ',Nonreligious/Agnostic/Atheist,Buddhism,Christianity,Hinduism,Islam,Judaism,Other'),
(00211, 'Staff', 'applicationFormRefereeLink', 'Application Form Referee Link', 'Link to an external form that will be emailed to a referee of the applicant\'s choosing.', ''),
(00212, 'Markbook', 'enableRawAttainment', 'Enable Raw Attainment Marks', 'Should recording of raw marks be enabled in the Markbook?', 'N'),
(00213, 'Markbook', 'enableGroupByTerm', 'Group Columns by Term', 'Should columns and total scores be grouped by term?', 'N'),
(00214, 'Markbook', 'enableEffort', 'Enable Effort', 'Should columns have the Effort section enabled?', 'Y'),
(00215, 'Markbook', 'enableRubrics', 'Enable Rubrics', 'Should columns have Rubrics section enabled?', 'Y'),
(00216, 'School Admin', 'staffDashboardDefaultTab', 'Staff Dashboard Default Tab', 'The default landing tab for the staff dashboard.', ''),
(00217, 'School Admin', 'studentDashboardDefaultTab', 'Student Dashboard Default Tab', 'The default landing tab for the student dashboard.', ''),
(00218, 'School Admin', 'parentDashboardDefaultTab', 'Parent Dashboard Default Tab', 'The default landing tab for the parent dashboard.', ''),
(00219, 'System', 'enableMailerSMTP', 'Enable SMTP Mail', 'Adds PHPMailer settings for servers with an SMTP connection.', 'N'),
(00220, 'System', 'mailerSMTPHost', 'SMTP Host', 'Set the hostname of the mail server.', ''),
(00221, 'System', 'mailerSMTPPort', 'SMTP Port', 'Set the SMTP port number - likely to be 25, 465 or 587.', '25'),
(00222, 'System', 'mailerSMTPUsername', 'SMTP Username', 'Username to use for SMTP authentication. Leave blank for no authentication.', ''),
(00223, 'System', 'mailerSMTPPassword', 'SMTP Password', 'Password to use for SMTP authentication. Leave blank for no authentication.', ''),
(00229, 'System', 'mainMenuCategoryOrder', 'Main Menu Category Order', 'A comma separated list of module categories in display order.', 'Admin,Assess,Learn,People,Other'),
(00232, 'Attendance', 'attendanceReasons', 'Attendance Reasons', 'Comma-separated list of reasons which are available when taking attendance.', 'Pending,Education,Family,Medical,Other'),
(00233, 'Attendance', 'attendanceMedicalReasons', 'Medical Reasons', 'Comma-separated list of allowable medical reasons.', 'Medical'),
(00234, 'Attendance', 'attendanceEnableMedicalTracking', 'Enable Symptom Tracking', 'Attach a symptom report to attendance logs with a medical reason.', 'N'),
(00235, 'Students', 'medicalIllnessSymptoms', 'Predefined Illness Symptoms', 'Comma-separated list of illness symptoms.', 'Fever,Cough,Cold,Vomiting,Diarrhea'),
(00236, 'Staff Application Form', 'staffApplicationFormPublicApplications', 'Public Applications?', 'If yes, members of the public can submit staff applications', 'Y'),
(00237, 'Individual Needs', 'targetsTemplate', 'Targets Template', 'An HTML template to be used in the targets field.', ''),
(00238, 'Individual Needs', 'teachingStrategiesTemplate', 'Teaching Strategies Template', 'An HTML template to be used in the teaching strategies field.', ''),
(00239, 'Individual Needs', 'notesReviewTemplate', 'Notes & Review Template', 'An HTML template to be used in the notes and review field.', ''),
(00240, 'Attendance', 'attendanceCLINotifyByFormGroup', 'Enable Notifications by Form Group', '', 'Y'),
(00241, 'Attendance', 'attendanceCLINotifyByClass', 'Enable Notifications by Class', '', 'Y'),
(00242, 'Attendance', 'attendanceCLIAdditionalUsers', 'Additional Users to Notify', 'Send the school-wide daily attendance report to additional users. Restricted to roles with permission to access Form Groups Not Registered or Classes Not Registered.', ''),
(00243, 'Students', 'noteCreationNotification', 'Note Creation Notification', 'Determines who to notify when a new student note is created.', 'Tutors'),
(00244, 'Finance', 'invoiceeNameStyle', 'Invoicee Name Style', 'Determines how invoicee name appears on invoices and receipts.', 'Surname, Preferred Name'),
(00245, 'Planner', 'shareUnitOutline', 'Share Unit Outline', 'Allow users who do not have access to the unit planner to see Unit Outlines via the lesson planner?', 'N'),
(00246, 'Attendance', 'studentSelfRegistrationIPAddresses', 'Student Self Registration IP Addresses', 'Comma-separated list of IP addresses within which students can self register.', ''),
(00247, 'Application Form', 'internalDocuments', 'Internal Documents', 'Comma-separated list of documents for internal upload and use.', ''),
(00248, 'Attendance', 'countClassAsSchool', 'Count Class Attendance as School Attendance', 'Should attendance from the class context be used to prefill and inform school attendance?', 'N'),
(00251, 'Attendance', 'defaultFormGroupAttendanceType', 'Default Form Group Attendance Type', 'The default selection for attendance type when taking Form Group attendance', 'Present'),
(00252, 'Attendance', 'defaultClassAttendanceType', 'Default Class Attendance Type', 'The default selection for attendance type when taking Class attendance', 'Present'),
(00253, 'Students', 'academicAlertLowThreshold', 'Low Academic Alert Threshold', 'The number of Markbook concerns needed in the past 60 days to raise a low level academic alert on a student.', '3'),
(00254, 'Students', 'academicAlertMediumThreshold', 'Medium Academic Alert Threshold', 'The number of Markbook concerns needed in the past 60 days to raise a medium level academic alert on a student.', '5'),
(00255, 'Students', 'academicAlertHighThreshold', 'High Academic Alert Threshold', 'The number of Markbook concerns needed in the past 60 days to raise a high level academic alert on a student.', '9'),
(00256, 'Students', 'behaviourAlertLowThreshold', 'Low Behaviour Alert Threshold', 'The number of Behaviour concerns needed in the past 60 days to raise a low level alert on a student.', '3'),
(00257, 'Students', 'behaviourAlertMediumThreshold', 'Medium Behaviour Alert Threshold', 'The number of Behaviour concerns needed in the past 60 days to raise a medium level alert on a student.', '5'),
(00258, 'Students', 'behaviourAlertHighThreshold', 'High Behaviour Alert Threshold', 'The number of Behaviour concerns needed in the past 60 days to raise a high level alert on a student.', '9'),
(00259, 'Markbook', 'enableDisplayCumulativeMarks', 'Enable Display Cumulative Marks', 'Should cumulative marks be displayed on the View Markbook page for Students and Parents and in Student Profiles?', 'N'),
(00260, 'Application Form', 'scholarshipOptionsActive', 'Scholarship Options Active', 'Should the Scholarship Options section be turned on?', 'Y'),
(00261, 'Application Form', 'paymentOptionsActive', 'Payment Options Active', 'Should the Payment section be turned on?', 'Y'),
(00262, 'Application Form', 'senOptionsActive', 'Special Education Needs Active', 'Should the Special Education Needs section be turned on?', 'Y'),
(00263, 'Timetable Admin', 'autoEnrolCourses', 'Auto-Enrol Courses Default', 'Should auto-enrolment of new students into courses be turned on or off by default?', 'N'),
(00264, 'Application Form', 'availableYearsOfEntry', 'Available Years of Entry', 'Which school years should be available to apply to?', ''),
(00265, 'Application Form', 'enableLimitedYearsOfEntry', 'Enable Limited Years of Entry', 'If yes, applicants choices for Year of Entry can be limited to specific school years.', 'N'),
(00266, 'User Admin', 'uniqueEmailAddress', 'Unique Email Address', 'Are primary email addresses required to be unique?', 'N'),
(00267, 'School Admin', 'parentWeeklyEmailSummaryIncludeMarkbook', 'Parent Weekly Email Summary Include Markbook', 'Should Markbook information be included in the weekly planner email summary that goes out to parents?', 'N'),
(00268, 'System', 'nameFormatStaffFormal', 'Formal Name Format', '', '[title] [preferredName:1]. [surname]'),
(00269, 'System', 'nameFormatStaffFormalReversed', 'Formal Name Reversed', '', '[title] [surname], [preferredName:1].'),
(00270, 'System', 'nameFormatStaffInformal', 'Informal Name Format', '', '[preferredName] [surname]'),
(00271, 'System', 'nameFormatStaffInformalReversed', 'Informal Name Reversed', '', '[surname], [preferredName]'),
(00272, 'Attendance', 'selfRegistrationRedirect', 'Self Registration Redirect', 'Should self registration redirect to Message Wall?', 'N'),
(00273, 'Data Updater', 'cutoffDate', 'Cutoff Date', 'Earliest acceptable date when checking if data updates are required.', ''),
(00274, 'Data Updater', 'redirectByRoleCategory', 'Data Updater Redirect', 'Which types of users should be redirected to the Data Updater if updates are required.', 'Parent'),
(00275, 'Data Updater', 'requiredUpdates', 'Required Updates?', 'Should the data updater highlight updates that are required?', 'N'),
(00276, 'Data Updater', 'requiredUpdatesByType', 'Required Update Types', 'Which type of data updates should be required.', 'Personal,Family'),
(00277, 'Markbook', 'enableModifiedAssessment', 'Enable Modified Assessment', 'Allows teachers to specify \"Modified Assessment\" for students with individual needs.', 'N'),
(00278, 'Messenger', 'messageBcc', 'Message Bcc', 'Comma-separated list of recipients to bcc all messenger emails to.', ''),
(00279, 'System', 'organisationBackground', 'Background', 'Relative path to background image. Overrides theme background.', ''),
(00280, 'Messenger', 'smsGateway', 'SMS Gateway', '', ''),
(00281, 'Messenger', 'smsSenderID', 'SMS Sender ID', 'The sender name or phone number. Depends on the gateway used.', ''),
(00282, 'System Admin', 'exportDefaultFileType', 'Default Export File Type', '', 'Excel2007'),
(00283, 'System', 'mailerSMTPSecure', 'SMTP Encryption', 'Automatically sets the encryption based on the port, otherwise select one manually.', 'auto'),
(00284, 'Staff', 'substituteTypes', 'Substitute Types', 'A comma-separated list.', 'Internal Substitute,External Substitute'),
(00285, 'Staff', 'urgencyThreshold', 'Urgency Threshold', 'Notifications in this time-span are sent immediately, day or night.', '3'),
(00286, 'Staff', 'urgentNotifications', 'Urgent Notifications', 'If enabled, urgent notifications will be sent by SMS as well as email.', 'N'),
(00287, 'Staff', 'absenceApprovers', 'Absence Approvers', 'Users who can approve staff absences. Leave this blank if approval is not used.', ''),
(00288, 'Staff', 'absenceFullDayThreshold', 'Full Day Absence', 'The minumum number of hours for an absence to count as a full day (1.0)', '6.0'),
(00289, 'Staff', 'absenceHalfDayThreshold', 'Half Day Absence', 'The minumum number of hours for an absence to count as a half day (0.5). Absences less than this count as 0', '2.0'),
(00290, 'Staff', 'absenceNotificationGroups', 'Notification Groups', 'Which messenger groups can staff members send absence notifications to?', ''),
(00291, 'Attendance', 'crossFillClasses', 'Cross-Fill Classes', 'Should classes prefill with data from other classes?', 'N'),
(00292, 'Attendance', 'recordFirstClassAsSchool', 'Enable First Class as School Attendance', 'Should the first class attendance taken in a day have the option to record as school-wide attendance?', 'N'),
(00293, 'Behaviour', 'notifyEducationalAssistants', 'Enable Notifications for Educational Assistants', 'Should the Educational Assistants for a student be notified of new behaviour records?', 'N'),
(00294, 'Behaviour', 'notifyTutors', 'Enable Notifications for Form Group Tutors', 'Should the Form Group Tutors of a student be notified of new behaviour records?', 'Y'),
(00295, 'Staff', 'substituteInfo', 'Substitute Information', 'Text or HTML to display on the My Coverage page for substitutes, such as contact information or emergency procedures.', ''),
(00296, 'Staff', 'coverageFullDayThreshold', 'Full Day Coverage', 'The minumum number of hours for staff coverage to count as a full day (1.0), otherwise it will count as a half-day (0.5)', '4.0'),
(00297, 'Staff', 'absenceCommentTemplate', 'Absence Comment Template', 'Default text for the Comment field in New Absence.', ''),
(00298, 'Individual Needs', 'investigationNotificationRole', 'Investigations Notification Role', 'Users to notify when an Investigation is complete.', ''),
(00299, 'Students', 'firstAidDescriptionTemplate', 'First Aid Description Template', 'Template text to be included in the Description field of a First Aid Record.', ''),
(00300, 'Messenger', 'pinnedMessagesOnHome', 'Enable Pinned Messages on Home', 'Displays pinned messages on the home page, above the dashboard.', 'N'),
(00301, 'Staff', 'biographicalGroupingOrder', 'Biographical Grouping Order', 'Comma-separated list of biographical groupings displayed in the staff directory.', ''),
(00302, 'Reports', 'debugMode', 'Debug Mode', 'Enable additional options to inspect reporting data.', 'N'),
(00303, 'Reports', 'customAssetPath', 'Custom Asset Path', 'Relative to the Gibbon root directory.', '/uploads/reports'),
(00304, 'Reports', 'archiveInformation', 'Archive Information', 'Text to display before the Archive by Student section.', ''),
(00305, 'System', 'backgroundProcessing', 'Background Processing', 'Should the system start an external process for long-running commands?', 'Y'),
(00306, 'Students', 'applicationFormRefereeRequired', 'Application Form Referee Required', 'Should the referee email address field be required?', 'Y'),
(00307, 'Application Form', 'applicationProcessFee', 'Application Processing Fee', 'An optional fee that is paid before processing the application form. Sent by staff via the Manage Applications page.', '0'),
(00308, 'Application Form', 'applicationProcessFeeText', 'Application Processing Fee Text', 'A custom message sent to applicants by email when a processing fee needs to be paid.', 'Thank you for your application submission. Please pay the following processing fee before your application is complete. Payment can be made by credit card, using our secure online payment gateway. Click the button below to pay now.'),
(00309, 'School Admin', 'parentDailyEmailSummaryIntroduction', 'Parent Daily Email Summary Introduction', 'Information to display at the beginning of the email', ''),
(00310, 'School Admin', 'parentDailyEmailSummaryPostScript', 'Parent Daily Email Summary PostScript', 'Information to display at the end of the email', ''),
(00311, 'Finance', 'paymentTypeOptions', 'Payment Type Options', 'Which payment types are available for invoicing, as a csv list.', 'Online,Bank Transfer,Cash,Cheque,Credit Card,Other'),
(00312, 'Students', 'medicalConditionIntro', 'Medical Condition Introductory Text', 'HTML text that will appear above the medical conditions section.', ''),
(00313, 'System', 'cachePath', 'Cache Path', 'Relative to the Gibbon root directory.', '/uploads/cache'),
(00314, 'Planner', 'homeworkNameSingular', 'Homework Name - Singular', 'A name to use for \"Homework\" in the planner. This noun should be in a singular form.', 'Homework'),
(00315, 'Planner', 'homeworkNamePlural', 'Homework Name - Plural', 'A name to use for \"Homework\" in the planner. This noun should be in a plural form.', 'Homework'),
(00316, 'System Admin', 'cookieConsentEnabled', 'Ask Users for Cookie Consent?', 'Display a banner for users to accept the use of cookies.', 'Y'),
(00317, 'System Admin', 'cookieConsentText', 'Cookie Consent Text', 'The message diplayed to users when they click to give consent.', 'Gibbon uses cookies which are strictly necessary for user account login and basic session data. It does not track or analyze user behaviour. By continuing to use this platform, users accept the use of cookies.'),
(00318, 'System Admin', 'privacyPolicy', 'Privacy Policy', 'Display a privacy policy document and add a link to it from the homepage.', ''),
(00319, 'System', 'dataRetentionDomains', 'Data Retention Domains', 'A list of areas to pre-select when undertaking data retention work.', 'Student Personal Data,Medical Data,Finance Data,Behaviour Records,Individual Needs,Family Data,Parent Personal Data,Staff Personal Data,Other Users Personal Data,Student Application Forms,Staff Application Forms'),
(00320, 'System', 'cacheString', 'Front End Cache', '', '1611200873'),
(00321, 'User Admin', 'publicRegistrationAllowedDomains', 'Public Registration Allowed Domains', 'Comma-separated list of email address domains allowed when registering. Leave blank for no restriction.', ''),
(00322, 'System Admin', 'composerLockHash', 'Composer Update Required', '', 'fe4abccf405facac24e05de854d764a6'),
(00323, 'System', 'themeColour', 'Theme Colour', '', 'purple'),
(00324, 'User Admin', 'publicRegistrationAlternateEmail', 'Include Alternate Email?', 'Should the alternate email field be visible in the Public Registration form?', 'N'),
(00325, 'School Admin', 'staffDashboardEnable', 'Enable Staff Dashboard?', 'Should the Staff Dashboard be visible to users?', 'Y'),
(00326, 'School Admin', 'parentDashboardEnable', 'Enable Parent Dashboard?', 'Should the Parent Dashboard be visible to users?', 'Y'),
(00327, 'School Admin', 'studentDashboardEnable', 'Enable Student Dashboard?', 'Should the Student Dashboard be visible to users?', 'Y'),
(00328, 'System', 'registerGibbonSupport', 'Receive Support?', 'Join our mailing list and recieve a welcome email from the team.', '');
INSERT INTO `gibbonSetting` (`gibbonSettingID`, `scope`, `name`, `nameDisplay`, `description`, `value`) VALUES
(00329, 'System Admin', 'importCustomFolderLocation', 'Custom Imports Folder', 'Path to custom import types folder, relative to uploads.', '/imports'),
(00330, 'System', 'paymentAPIKey', 'API Key', 'API details are provided by the payment gateway provider', ''),
(00331, 'System', 'paymentGateway', 'Payment Gateway', 'Choose a payment gateway. You must create and configure an account with the selected service to get the required API details.', 'PayPal'),
(00332, 'System Admin', 'remoteCLIKey', 'Remote CLI Key', 'Allow command line scripts to be run remotely using a secure key. The key can be passed as a URL parameter called remoteCLIKey.', ''),
(00333, 'System Admin', 'maintenanceMode', 'Maintenance Mode', 'Only users with the Administrator role can login during maintenance mode. Enabling this will logout all other users.', 'N'),
(00334, 'System Admin', 'maintenanceModeMessage', 'Maintenance Mode Message', 'A message to display on all pages when maintenance mode is active.', 'The system is currently in maintenance mode. Only system administrators will be able to login at this time.'),
(00335, 'System Admin', 'ssoGoogle', 'Google Integration', '', '{\"enabled\":\"N\"}'),
(00336, 'System Admin', 'ssoMicrosoft', 'Microsoft Integration', '', '{\"enabled\":\"N\"}'),
(00337, 'System Admin', 'ssoOther', 'Generic OAuth2 Provider', '', '{\"enabled\":\"N\"}'),
(00338, 'Behaviour', 'enablePositiveBehaviourLetters', 'Enable Positive Behaviour Letters', 'Should automated behaviour letter functionality be enabled?', 'N'),
(00339, 'Behaviour', 'behaviourLettersPositiveLetter1Count', 'Positive Letter 1 Count', 'After how many positive records should letter 1 be sent?', '3'),
(00340, 'Behaviour', 'behaviourLettersPositiveLetter2Count', 'Positive Letter 2 Count', 'After how many positive records should letter 2 be sent?', '6'),
(00341, 'Behaviour', 'behaviourLettersPositiveLetter3Count', 'Positive Letter 3 Count', 'After how many positive records should letter 3 be sent?', '9'),
(00342, 'Installer', 'demoData', 'Install Demo Data?', '', 'N'),
(00343, 'Admissions', 'welcomeHeading', 'Admissions Welcome Heading', 'Displayed on the admissions landing page.', 'Welcome to Admissions at {organisationNameShort}'),
(00344, 'Admissions', 'welcomeText', 'Admissions Welcome Text', 'Displayed on the admissions landing page.', '<p>Our admissions system will help you to create new application forms as well as submit required documents and track your application status.</p><p>Select an application form below to begin the admissions process. If you already have one or more application forms in progress, you can choose to receive an email which will give you access to your existing forms.</p>'),
(00345, 'Admissions', 'admissionsEnabled', 'Enable New Admissions Page', 'Should the new admissions page show up on the homepage, in place of the original student application link?', 'Y'),
(00346, 'Admissions', 'admissionsLinkText', 'Admissions Link Text', 'Text to display on the homepage with the admissions link.', 'Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'),
(00347, 'Admissions', 'admissionsLinkName', 'Admissions Link Name', 'Title of the admissions link on the homepage.', 'Student Applications'),
(00348, 'Timetable Admin', 'enrolmentMinDefault', 'Minimum Enrolment Default', 'The default value for minimum class enrolment.', ''),
(00349, 'Timetable Admin', 'enrolmentMaxDefault', 'Maximum Enrolment Default', 'The default value for maximum class enrolment.', ''),
(00350, 'Staff', 'coverageMode', 'Coverage Mode', 'Should teachers send coverage requests directly to substitutes, or will it be assigned for them?', 'Requested'),
(00351, 'Staff', 'coverageInternal', 'Internal Coverage', 'If Yes, teachers can be assigned to cover each other. Otherwise, only users added through Manage Substitutes will be available for coverage.', 'N'),
(00352, 'Students', 'emergencyFollowUpGroup', 'Follow-up Contacts', 'An optional list of staff to include in the follow-up contacts section.', ''),
(00353, 'Messenger', 'signatureTemplate', 'Signature Template', 'An HTML and Twig template used to add signatures to messages.', '----<br /><span style=\"font-weight: bold; color: #447caa;\">{{ preferredName }} {{ surname }}</span><br />{% if jobTitle is not empty %}<span style=\"font-style: italic;\">{{ jobTitle }}</span><br />{% endif %}{{ organisationName }}<br /></span>'),
(00354, 'System', 'allowableIframeSources', 'Allowable iFrame Sources', 'A comma separated list of domains that are allowed in embedded iFrames. All others will be removed from HTML content.', 'youtube.com,youtu.be,m.youtube.com,google.com,docs.google.com,drive.google.com,vimeo.com,player.vimeo.com'),
(00355, 'System', 'notificationIntervalStaff', 'Staff Notification Interval', 'Frequency of notification icon update in status tray, for staff users. In thousands of a second.', '10000'),
(00356, 'System', 'notificationIntervalOther', 'Other Notification Interval', 'Frequency of notification icon update in status tray, for all non-staff users. In thousands of a second.', '60000'),
(00357, 'User Admin', 'privacyOptionVisibility', 'Display privacy options?', 'If enabled, privacy options can be selected by users through the Data Updater and Application Form. If not enabled, privacy options can only be changed by staff through Manage Users.', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSpace`
--

CREATE TABLE `gibbonSpace` (
  `gibbonSpaceID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `type` varchar(50) NOT NULL,
  `active` enum('N','Y') NOT NULL DEFAULT 'Y',
  `capacity` int(5) NOT NULL,
  `computer` enum('N','Y') NOT NULL,
  `computerStudent` int(3) NOT NULL DEFAULT '0',
  `projector` enum('N','Y') NOT NULL,
  `tv` enum('N','Y') NOT NULL,
  `dvd` enum('N','Y') NOT NULL,
  `hifi` enum('N','Y') NOT NULL,
  `speakers` enum('N','Y') NOT NULL,
  `iwb` enum('N','Y') NOT NULL,
  `phoneInternal` varchar(5) NOT NULL,
  `phoneExternal` varchar(20) NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSpacePerson`
--

CREATE TABLE `gibbonSpacePerson` (
  `gibbonSpacePersonID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSpaceID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `usageType` enum('','Teaching','Office','Other') COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaff`
--

CREATE TABLE `gibbonStaff` (
  `gibbonStaffID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` varchar(20) NOT NULL,
  `initials` varchar(4) DEFAULT NULL,
  `jobTitle` varchar(100) NOT NULL,
  `firstAidQualified` enum('','N','Y') NOT NULL DEFAULT '',
  `firstAidQualification` varchar(100) DEFAULT NULL,
  `firstAidExpiry` date DEFAULT NULL,
  `countryOfOrigin` varchar(80) NOT NULL,
  `qualifications` varchar(255) NOT NULL,
  `biography` text NOT NULL,
  `biographicalGrouping` varchar(100) NOT NULL COMMENT 'Used for group staff when creating a staff directory.',
  `biographicalGroupingPriority` int(3) NOT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffAbsence`
--

CREATE TABLE `gibbonStaffAbsence` (
  `gibbonStaffAbsenceID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffAbsenceTypeID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `reason` varchar(60) DEFAULT NULL,
  `comment` text,
  `commentConfidential` text,
  `status` enum('Pending Approval','Approved','Declined') DEFAULT 'Approved',
  `coverageRequired` enum('N','Y') DEFAULT 'N',
  `gibbonPersonIDApproval` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampApproval` timestamp NULL DEFAULT NULL,
  `notesApproval` text,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notificationSent` enum('N','Y') DEFAULT 'N',
  `notificationList` text,
  `gibbonGroupID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  `googleCalendarEventID` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffAbsenceDate`
--

CREATE TABLE `gibbonStaffAbsenceDate` (
  `gibbonStaffAbsenceDateID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffAbsenceID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `allDay` enum('N','Y') DEFAULT 'Y',
  `timeStart` time DEFAULT NULL,
  `timeEnd` time DEFAULT NULL,
  `value` decimal(3,2) NOT NULL DEFAULT '1.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffAbsenceType`
--

CREATE TABLE `gibbonStaffAbsenceType` (
  `gibbonStaffAbsenceTypeID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(60) DEFAULT NULL,
  `nameShort` varchar(10) DEFAULT NULL,
  `active` enum('N','Y') DEFAULT 'Y',
  `requiresApproval` enum('N','Y') DEFAULT 'N',
  `reasons` text,
  `sequenceNumber` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonStaffAbsenceType`
--

INSERT INTO `gibbonStaffAbsenceType` (`gibbonStaffAbsenceTypeID`, `name`, `nameShort`, `active`, `requiresApproval`, `reasons`, `sequenceNumber`) VALUES
(000001, 'Sick Leave', 'S', 'Y', 'N', '', 1),
(000002, 'Personal Leave', 'P', 'Y', 'N', '', 2),
(000003, 'Non-paid Leave', 'NP', 'Y', 'N', '', 3),
(000004, 'School Related', 'D', 'Y', 'N', 'PD,Sports Trip,Offsite Event,Other', 4);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffApplicationForm`
--

CREATE TABLE `gibbonStaffApplicationForm` (
  `gibbonStaffApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffJobOpeningID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `surname` varchar(60) DEFAULT NULL,
  `firstName` varchar(60) DEFAULT NULL,
  `preferredName` varchar(60) DEFAULT NULL,
  `officialName` varchar(150) DEFAULT NULL,
  `nameInCharacters` varchar(60) DEFAULT NULL,
  `gender` enum('M','F','Other','Unspecified') NOT NULL DEFAULT 'Unspecified',
  `status` enum('Pending','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  `dob` date DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `homeAddress` mediumtext,
  `homeAddressDistrict` varchar(255) DEFAULT NULL,
  `homeAddressCountry` varchar(255) DEFAULT NULL,
  `phone1Type` enum('','Mobile','Home','Work','Fax','Pager','Other') DEFAULT NULL,
  `phone1CountryCode` varchar(7) DEFAULT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `countryOfBirth` varchar(30) DEFAULT NULL,
  `languageFirst` varchar(30) DEFAULT NULL,
  `languageSecond` varchar(30) DEFAULT NULL,
  `languageThird` varchar(30) DEFAULT NULL,
  `agreement` enum('N','Y') DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` int(1) NOT NULL DEFAULT '0',
  `milestones` text NOT NULL,
  `notes` text NOT NULL,
  `dateStart` date DEFAULT NULL,
  `questions` text NOT NULL,
  `fields` text NOT NULL COMMENT 'Serialised array of custom field values',
  `staffFields` text,
  `referenceEmail1` varchar(100) NOT NULL,
  `referenceEmail2` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffApplicationFormFile`
--

CREATE TABLE `gibbonStaffApplicationFormFile` (
  `gibbonStaffApplicationFormFileID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffContract`
--

CREATE TABLE `gibbonStaffContract` (
  `gibbonStaffContractID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(100) NOT NULL,
  `status` enum('','Pending','Active','Expired') NOT NULL DEFAULT '',
  `dateStart` date NOT NULL,
  `dateEnd` date DEFAULT NULL,
  `salaryScale` varchar(255) DEFAULT NULL,
  `salaryAmount` decimal(12,2) DEFAULT NULL,
  `salaryPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `responsibility` varchar(255) DEFAULT NULL,
  `responsibilityAmount` decimal(12,2) DEFAULT NULL,
  `responsibilityPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `housingAmount` decimal(12,2) DEFAULT NULL,
  `housingPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `travelAmount` decimal(12,2) DEFAULT NULL,
  `travelPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `retirementAmount` decimal(12,2) DEFAULT NULL,
  `retirementPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `bonusAmount` decimal(12,2) DEFAULT NULL,
  `bonusPeriod` enum('','Week','Month','Year','Contract') DEFAULT NULL,
  `education` text NOT NULL,
  `notes` text NOT NULL,
  `contractUpload` varchar(255) DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffCoverage`
--

CREATE TABLE `gibbonStaffCoverage` (
  `gibbonStaffCoverageID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffAbsenceID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `status` enum('Requested','Accepted','Declined','Cancelled','Pending','Not Required') DEFAULT 'Requested',
  `requestType` enum('Individual','Broadcast','Assigned') DEFAULT 'Broadcast',
  `substituteTypes` varchar(255) DEFAULT NULL,
  `gibbonPersonIDStatus` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampStatus` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notesStatus` text,
  `gibbonPersonIDCoverage` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `timestampCoverage` timestamp NULL DEFAULT NULL,
  `notesCoverage` text,
  `attachmentType` enum('File','HTML','Link') DEFAULT NULL,
  `attachmentContent` text,
  `notificationSent` enum('N','Y') DEFAULT 'N',
  `notificationList` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffCoverageDate`
--

CREATE TABLE `gibbonStaffCoverageDate` (
  `gibbonStaffCoverageDateID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffCoverageID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonStaffAbsenceDateID` int(14) UNSIGNED ZEROFILL DEFAULT NULL,
  `foreignTable` varchar(60) DEFAULT NULL,
  `foreignTableID` int(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `allDay` enum('N','Y') DEFAULT 'Y',
  `timeStart` time DEFAULT NULL,
  `timeEnd` time DEFAULT NULL,
  `value` decimal(3,2) NOT NULL DEFAULT '1.00',
  `gibbonPersonIDUnavailable` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffDuty`
--

CREATE TABLE `gibbonStaffDuty` (
  `gibbonStaffDutyID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `gibbonDaysOfWeekIDList` varchar(30) DEFAULT NULL,
  `name` varchar(90) NOT NULL,
  `nameShort` varchar(20) NOT NULL,
  `timeStart` time NOT NULL,
  `timeEnd` time NOT NULL,
  `sequenceNumber` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffDutyPerson`
--

CREATE TABLE `gibbonStaffDutyPerson` (
  `gibbonStaffDutyPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStaffDutyID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonDaysOfWeekID` int(2) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffJobOpening`
--

CREATE TABLE `gibbonStaffJobOpening` (
  `gibbonStaffJobOpeningID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `type` varchar(20) NOT NULL,
  `jobTitle` varchar(100) NOT NULL,
  `dateOpen` date NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `description` text NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestampCreator` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStaffUpdate`
--

CREATE TABLE `gibbonStaffUpdate` (
  `gibbonStaffUpdateID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonStaffID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `type` varchar(20) NOT NULL,
  `initials` varchar(4) DEFAULT NULL,
  `jobTitle` varchar(100) NOT NULL,
  `firstAidQualified` enum('','N','Y') NOT NULL DEFAULT '',
  `firstAidQualification` varchar(100) DEFAULT NULL,
  `firstAidExpiry` date DEFAULT NULL,
  `countryOfOrigin` varchar(80) NOT NULL,
  `qualifications` varchar(255) NOT NULL,
  `biography` text NOT NULL,
  `fields` text NOT NULL,
  `gibbonPersonIDUpdater` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonString`
--

CREATE TABLE `gibbonString` (
  `gibbonStringID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `original` varchar(255) NOT NULL,
  `replacement` varchar(255) NOT NULL,
  `mode` enum('Whole','Partial') NOT NULL,
  `caseSensitive` enum('Y','N') NOT NULL,
  `priority` int(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStudentEnrolment`
--

CREATE TABLE `gibbonStudentEnrolment` (
  `gibbonStudentEnrolmentID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `rollOrder` int(2) DEFAULT NULL,
  `fields` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStudentNote`
--

CREATE TABLE `gibbonStudentNote` (
  `gibbonStudentNoteID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonStudentNoteCategoryID` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `title` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonStudentNoteCategory`
--

CREATE TABLE `gibbonStudentNoteCategory` (
  `gibbonStudentNoteCategoryID` int(5) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `template` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonStudentNoteCategory`
--

INSERT INTO `gibbonStudentNoteCategory` (`gibbonStudentNoteCategoryID`, `name`, `template`, `active`) VALUES
(00006, 'Academic', '', 'Y'),
(00007, 'Pastoral', '', 'Y'),
(00008, 'Behaviour', '', 'Y'),
(00009, 'Other', '', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonSubstitute`
--

CREATE TABLE `gibbonSubstitute` (
  `gibbonSubstituteID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `active` enum('Y','N') DEFAULT 'Y',
  `type` varchar(60) DEFAULT NULL,
  `details` varchar(255) DEFAULT NULL,
  `priority` int(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTheme`
--

CREATE TABLE `gibbonTheme` (
  `gibbonThemeID` int(4) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` varchar(100) NOT NULL,
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `version` varchar(6) NOT NULL,
  `author` varchar(40) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonTheme`
--

INSERT INTO `gibbonTheme` (`gibbonThemeID`, `name`, `description`, `active`, `version`, `author`, `url`) VALUES
(0013, 'Default', 'Gibbon\'s 2021 look and feel.', 'Y', '1.0.00', 'Sandra Kuipers', 'https://github.com/SKuipers');

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTT`
--

CREATE TABLE `gibbonTT` (
  `gibbonTTID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `nameShort` varchar(12) NOT NULL,
  `nameShortDisplay` enum('Day Of The Week','Timetable Day Short Name','') NOT NULL DEFAULT 'Day Of The Week',
  `gibbonYearGroupIDList` varchar(255) NOT NULL,
  `active` enum('Y','N') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTColumn`
--

CREATE TABLE `gibbonTTColumn` (
  `gibbonTTColumnID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(30) NOT NULL,
  `nameShort` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTColumnRow`
--

CREATE TABLE `gibbonTTColumnRow` (
  `gibbonTTColumnRowID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTColumnID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(12) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `timeStart` time NOT NULL,
  `timeEnd` time NOT NULL,
  `type` enum('Lesson','Pastoral','Sport','Break','Service','Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTDay`
--

CREATE TABLE `gibbonTTDay` (
  `gibbonTTDayID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTColumnID` int(6) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(12) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `color` varchar(7) NOT NULL COMMENT 'RGB Hex, leading #',
  `fontColor` varchar(7) NOT NULL COMMENT 'RGB Hex, leading #'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTDayDate`
--

CREATE TABLE `gibbonTTDayDate` (
  `gibbonTTDayDateID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTDayID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTDayRowClass`
--

CREATE TABLE `gibbonTTDayRowClass` (
  `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTColumnRowID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTDayID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSpaceID` int(10) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTDayRowClassException`
--

CREATE TABLE `gibbonTTDayRowClassException` (
  `gibbonTTDayRowClassExceptionID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTImport`
--

CREATE TABLE `gibbonTTImport` (
  `gibbonTTImportID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `courseNameShort` varchar(12) NOT NULL DEFAULT '',
  `classNameShort` varchar(8) NOT NULL DEFAULT '',
  `dayName` varchar(12) NOT NULL,
  `rowName` varchar(12) NOT NULL,
  `teacherUsernameList` text NOT NULL,
  `spaceName` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTSpaceBooking`
--

CREATE TABLE `gibbonTTSpaceBooking` (
  `gibbonTTSpaceBookingID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `foreignKey` enum('gibbonSpaceID','gibbonLibraryItemID') NOT NULL DEFAULT 'gibbonSpaceID',
  `foreignKeyID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `date` date NOT NULL,
  `timeStart` time NOT NULL,
  `timeEnd` time NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonTTSpaceChange`
--

CREATE TABLE `gibbonTTSpaceChange` (
  `gibbonTTSpaceChangeID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonSpaceID` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `date` date NOT NULL,
  `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUnit`
--

CREATE TABLE `gibbonUnit` (
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(40) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `description` text NOT NULL,
  `tags` text NOT NULL,
  `map` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT 'Should this unit be included in curriculum maps and other summaries?',
  `ordering` int(2) NOT NULL DEFAULT '0',
  `attachment` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `license` varchar(50) DEFAULT NULL,
  `sharedPublic` enum('Y','N') DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPersonIDLastEdit` int(10) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUnitBlock`
--

CREATE TABLE `gibbonUnitBlock` (
  `gibbonUnitBlockID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NOT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUnitClass`
--

CREATE TABLE `gibbonUnitClass` (
  `gibbonUnitClassID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `running` enum('N','Y') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUnitClassBlock`
--

CREATE TABLE `gibbonUnitClassBlock` (
  `gibbonUnitClassBlockID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitClassID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitBlockID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NOT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `complete` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUnitOutcome`
--

CREATE TABLE `gibbonUnitOutcome` (
  `gibbonUnitOutcomeID` int(12) UNSIGNED ZEROFILL NOT NULL,
  `gibbonUnitID` int(10) UNSIGNED ZEROFILL NOT NULL,
  `gibbonOutcomeID` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gibbonUsernameFormat`
--

CREATE TABLE `gibbonUsernameFormat` (
  `gibbonUsernameFormatID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `gibbonRoleIDList` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `format` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isDefault` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `isNumeric` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `numericValue` int(12) UNSIGNED NOT NULL DEFAULT '0',
  `numericIncrement` int(3) UNSIGNED NOT NULL DEFAULT '1',
  `numericSize` int(3) UNSIGNED NOT NULL DEFAULT '4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `gibbonUsernameFormat`
--

INSERT INTO `gibbonUsernameFormat` (`gibbonUsernameFormatID`, `gibbonRoleIDList`, `format`, `isDefault`, `isNumeric`, `numericValue`, `numericIncrement`, `numericSize`) VALUES
(001, '003', '[preferredName:1][surname]', 'Y', 'N', 0, 1, 4),
(002, '001,002,006', '[preferredName:1].[surname]', 'N', 'N', 0, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `gibbonYearGroup`
--

CREATE TABLE `gibbonYearGroup` (
  `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(15) NOT NULL,
  `nameShort` varchar(4) NOT NULL,
  `sequenceNumber` int(3) NOT NULL,
  `gibbonPersonIDHOY` int(10) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gibbonYearGroup`
--

INSERT INTO `gibbonYearGroup` (`gibbonYearGroupID`, `name`, `nameShort`, `sequenceNumber`, `gibbonPersonIDHOY`) VALUES
(001, 'Year 7', 'Y07', 1, NULL),
(002, 'Year 8', 'Y08', 2, NULL),
(003, 'Year 9', 'Y09', 3, NULL),
(004, 'Year 10', 'Y10', 4, NULL),
(005, 'Year 11', 'Y11', 5, NULL),
(006, 'Year 12', 'Y12', 6, NULL),
(007, 'Year 13', 'Y13', 7, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gibbonAction`
--
ALTER TABLE `gibbonAction`
  ADD PRIMARY KEY (`gibbonActionID`),
  ADD UNIQUE KEY `moduleActionName` (`name`,`gibbonModuleID`),
  ADD KEY `gibbonModuleID` (`gibbonModuleID`);

--
-- Indexes for table `gibbonActivity`
--
ALTER TABLE `gibbonActivity`
  ADD PRIMARY KEY (`gibbonActivityID`);

--
-- Indexes for table `gibbonActivityAttendance`
--
ALTER TABLE `gibbonActivityAttendance`
  ADD PRIMARY KEY (`gibbonActivityAttendanceID`);

--
-- Indexes for table `gibbonActivitySlot`
--
ALTER TABLE `gibbonActivitySlot`
  ADD PRIMARY KEY (`gibbonActivitySlotID`);

--
-- Indexes for table `gibbonActivityStaff`
--
ALTER TABLE `gibbonActivityStaff`
  ADD PRIMARY KEY (`gibbonActivityStaffID`),
  ADD KEY `gibbonActivityID` (`gibbonActivityID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonActivityStudent`
--
ALTER TABLE `gibbonActivityStudent`
  ADD PRIMARY KEY (`gibbonActivityStudentID`),
  ADD KEY `gibbonActivityID` (`gibbonActivityID`,`status`);

--
-- Indexes for table `gibbonActivityType`
--
ALTER TABLE `gibbonActivityType`
  ADD PRIMARY KEY (`gibbonActivityTypeID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gibbonAdmissionsAccount`
--
ALTER TABLE `gibbonAdmissionsAccount`
  ADD PRIMARY KEY (`gibbonAdmissionsAccountID`),
  ADD UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `accessID` (`accessID`);

--
-- Indexes for table `gibbonAdmissionsApplication`
--
ALTER TABLE `gibbonAdmissionsApplication`
  ADD PRIMARY KEY (`gibbonAdmissionsApplicationID`),
  ADD KEY `foreignTable` (`foreignTable`,`foreignTableID`);

--
-- Indexes for table `gibbonAlarm`
--
ALTER TABLE `gibbonAlarm`
  ADD PRIMARY KEY (`gibbonAlarmID`);

--
-- Indexes for table `gibbonAlarmConfirm`
--
ALTER TABLE `gibbonAlarmConfirm`
  ADD PRIMARY KEY (`gibbonAlarmConfirmID`),
  ADD UNIQUE KEY `gibbonAlarmID` (`gibbonAlarmID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonAlertLevel`
--
ALTER TABLE `gibbonAlertLevel`
  ADD PRIMARY KEY (`gibbonAlertLevelID`);

--
-- Indexes for table `gibbonApplicationForm`
--
ALTER TABLE `gibbonApplicationForm`
  ADD PRIMARY KEY (`gibbonApplicationFormID`);

--
-- Indexes for table `gibbonApplicationFormFile`
--
ALTER TABLE `gibbonApplicationFormFile`
  ADD PRIMARY KEY (`gibbonApplicationFormFileID`);

--
-- Indexes for table `gibbonApplicationFormLink`
--
ALTER TABLE `gibbonApplicationFormLink`
  ADD PRIMARY KEY (`gibbonApplicationFormLinkID`),
  ADD UNIQUE KEY `link` (`gibbonApplicationFormID1`,`gibbonApplicationFormID2`);

--
-- Indexes for table `gibbonApplicationFormRelationship`
--
ALTER TABLE `gibbonApplicationFormRelationship`
  ADD PRIMARY KEY (`gibbonApplicationFormRelationshipID`);

--
-- Indexes for table `gibbonAttendanceCode`
--
ALTER TABLE `gibbonAttendanceCode`
  ADD PRIMARY KEY (`gibbonAttendanceCodeID`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `nameShort` (`nameShort`);

--
-- Indexes for table `gibbonAttendanceLogCourseClass`
--
ALTER TABLE `gibbonAttendanceLogCourseClass`
  ADD PRIMARY KEY (`gibbonAttendanceLogCourseClassID`);

--
-- Indexes for table `gibbonAttendanceLogFormGroup`
--
ALTER TABLE `gibbonAttendanceLogFormGroup`
  ADD PRIMARY KEY (`gibbonAttendanceLogFormGroupID`);

--
-- Indexes for table `gibbonAttendanceLogPerson`
--
ALTER TABLE `gibbonAttendanceLogPerson`
  ADD PRIMARY KEY (`gibbonAttendanceLogPersonID`),
  ADD KEY `date` (`date`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`),
  ADD KEY `dateAndPerson` (`date`,`gibbonPersonID`) USING BTREE;

--
-- Indexes for table `gibbonBehaviour`
--
ALTER TABLE `gibbonBehaviour`
  ADD PRIMARY KEY (`gibbonBehaviourID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonBehaviourFollowUp`
--
ALTER TABLE `gibbonBehaviourFollowUp`
  ADD PRIMARY KEY (`gibbonBehaviourFollowUpID`),
  ADD KEY `gibbonBehaviourID` (`gibbonBehaviourID`);

--
-- Indexes for table `gibbonBehaviourLetter`
--
ALTER TABLE `gibbonBehaviourLetter`
  ADD PRIMARY KEY (`gibbonBehaviourLetterID`);

--
-- Indexes for table `gibbonCountry`
--
ALTER TABLE `gibbonCountry`
  ADD PRIMARY KEY (`printable_name`);

--
-- Indexes for table `gibbonCourse`
--
ALTER TABLE `gibbonCourse`
  ADD PRIMARY KEY (`gibbonCourseID`),
  ADD UNIQUE KEY `nameYear` (`gibbonSchoolYearID`,`name`),
  ADD KEY `gibbonSchoolYearID` (`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonCourseClass`
--
ALTER TABLE `gibbonCourseClass`
  ADD PRIMARY KEY (`gibbonCourseClassID`),
  ADD KEY `gibbonCourseID` (`gibbonCourseID`);

--
-- Indexes for table `gibbonCourseClassMap`
--
ALTER TABLE `gibbonCourseClassMap`
  ADD PRIMARY KEY (`gibbonCourseClassMapID`),
  ADD UNIQUE KEY `gibbonCourseClassID` (`gibbonCourseClassID`);

--
-- Indexes for table `gibbonCourseClassPerson`
--
ALTER TABLE `gibbonCourseClassPerson`
  ADD PRIMARY KEY (`gibbonCourseClassPersonID`),
  ADD KEY `gibbonCourseClassID` (`gibbonCourseClassID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`,`role`);

--
-- Indexes for table `gibbonCrowdAssessDiscuss`
--
ALTER TABLE `gibbonCrowdAssessDiscuss`
  ADD PRIMARY KEY (`gibbonCrowdAssessDiscussID`);

--
-- Indexes for table `gibbonCustomField`
--
ALTER TABLE `gibbonCustomField`
  ADD PRIMARY KEY (`gibbonCustomFieldID`);

--
-- Indexes for table `gibbonDataRetention`
--
ALTER TABLE `gibbonDataRetention`
  ADD PRIMARY KEY (`gibbonDataRetentionID`),
  ADD UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonDaysOfWeek`
--
ALTER TABLE `gibbonDaysOfWeek`
  ADD PRIMARY KEY (`gibbonDaysOfWeekID`),
  ADD UNIQUE KEY `name` (`name`,`nameShort`),
  ADD UNIQUE KEY `sequenceNumber` (`sequenceNumber`),
  ADD UNIQUE KEY `nameShort` (`nameShort`);

--
-- Indexes for table `gibbonDepartment`
--
ALTER TABLE `gibbonDepartment`
  ADD PRIMARY KEY (`gibbonDepartmentID`);

--
-- Indexes for table `gibbonDepartmentResource`
--
ALTER TABLE `gibbonDepartmentResource`
  ADD PRIMARY KEY (`gibbonDepartmentResourceID`);

--
-- Indexes for table `gibbonDepartmentStaff`
--
ALTER TABLE `gibbonDepartmentStaff`
  ADD PRIMARY KEY (`gibbonDepartmentStaffID`);

--
-- Indexes for table `gibbonDiscussion`
--
ALTER TABLE `gibbonDiscussion`
  ADD PRIMARY KEY (`gibbonDiscussionID`),
  ADD KEY `foreignTable` (`foreignTable`,`foreignTableID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonDistrict`
--
ALTER TABLE `gibbonDistrict`
  ADD PRIMARY KEY (`gibbonDistrictID`);

--
-- Indexes for table `gibbonEmailTemplate`
--
ALTER TABLE `gibbonEmailTemplate`
  ADD PRIMARY KEY (`gibbonEmailTemplateID`),
  ADD UNIQUE KEY `moduleTemplate` (`templateName`,`moduleName`) USING BTREE;

--
-- Indexes for table `gibbonExternalAssessment`
--
ALTER TABLE `gibbonExternalAssessment`
  ADD PRIMARY KEY (`gibbonExternalAssessmentID`);

--
-- Indexes for table `gibbonExternalAssessmentField`
--
ALTER TABLE `gibbonExternalAssessmentField`
  ADD PRIMARY KEY (`gibbonExternalAssessmentFieldID`),
  ADD KEY `gibbonExternalAssessmentID` (`gibbonExternalAssessmentID`);

--
-- Indexes for table `gibbonExternalAssessmentStudent`
--
ALTER TABLE `gibbonExternalAssessmentStudent`
  ADD PRIMARY KEY (`gibbonExternalAssessmentStudentID`),
  ADD KEY `gibbonExternalAssessmentID` (`gibbonExternalAssessmentID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonExternalAssessmentStudentEntry`
--
ALTER TABLE `gibbonExternalAssessmentStudentEntry`
  ADD PRIMARY KEY (`gibbonExternalAssessmentStudentEntryID`),
  ADD KEY `gibbonExternalAssessmentStudentID` (`gibbonExternalAssessmentStudentID`),
  ADD KEY `gibbonExternalAssessmentFieldID` (`gibbonExternalAssessmentFieldID`),
  ADD KEY `gibbonScaleGradeID` (`gibbonScaleGradeID`);

--
-- Indexes for table `gibbonFamily`
--
ALTER TABLE `gibbonFamily`
  ADD PRIMARY KEY (`gibbonFamilyID`);

--
-- Indexes for table `gibbonFamilyAdult`
--
ALTER TABLE `gibbonFamilyAdult`
  ADD PRIMARY KEY (`gibbonFamilyAdultID`),
  ADD KEY `gibbonFamilyID` (`gibbonFamilyID`,`contactPriority`),
  ADD KEY `gibbonPersonIndex` (`gibbonPersonID`);

--
-- Indexes for table `gibbonFamilyChild`
--
ALTER TABLE `gibbonFamilyChild`
  ADD PRIMARY KEY (`gibbonFamilyChildID`),
  ADD KEY `gibbonPersonIndex` (`gibbonPersonID`),
  ADD KEY `gibbonFamilyIndex` (`gibbonFamilyID`);

--
-- Indexes for table `gibbonFamilyRelationship`
--
ALTER TABLE `gibbonFamilyRelationship`
  ADD PRIMARY KEY (`gibbonFamilyRelationshipID`);

--
-- Indexes for table `gibbonFamilyUpdate`
--
ALTER TABLE `gibbonFamilyUpdate`
  ADD PRIMARY KEY (`gibbonFamilyUpdateID`),
  ADD KEY `gibbonFamilyIndex` (`gibbonFamilyID`,`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonFileExtension`
--
ALTER TABLE `gibbonFileExtension`
  ADD PRIMARY KEY (`gibbonFileExtensionID`);

--
-- Indexes for table `gibbonFinanceBillingSchedule`
--
ALTER TABLE `gibbonFinanceBillingSchedule`
  ADD PRIMARY KEY (`gibbonFinanceBillingScheduleID`);

--
-- Indexes for table `gibbonFinanceBudget`
--
ALTER TABLE `gibbonFinanceBudget`
  ADD PRIMARY KEY (`gibbonFinanceBudgetID`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `nameShort` (`nameShort`);

--
-- Indexes for table `gibbonFinanceBudgetCycle`
--
ALTER TABLE `gibbonFinanceBudgetCycle`
  ADD PRIMARY KEY (`gibbonFinanceBudgetCycleID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gibbonFinanceBudgetCycleAllocation`
--
ALTER TABLE `gibbonFinanceBudgetCycleAllocation`
  ADD PRIMARY KEY (`gibbonFinanceBudgetCycleAllocationID`);

--
-- Indexes for table `gibbonFinanceBudgetPerson`
--
ALTER TABLE `gibbonFinanceBudgetPerson`
  ADD PRIMARY KEY (`gibbonFinanceBudgetPersonID`);

--
-- Indexes for table `gibbonFinanceExpense`
--
ALTER TABLE `gibbonFinanceExpense`
  ADD PRIMARY KEY (`gibbonFinanceExpenseID`);

--
-- Indexes for table `gibbonFinanceExpenseApprover`
--
ALTER TABLE `gibbonFinanceExpenseApprover`
  ADD PRIMARY KEY (`gibbonFinanceExpenseApproverID`);

--
-- Indexes for table `gibbonFinanceExpenseLog`
--
ALTER TABLE `gibbonFinanceExpenseLog`
  ADD PRIMARY KEY (`gibbonFinanceExpenseLogID`);

--
-- Indexes for table `gibbonFinanceFee`
--
ALTER TABLE `gibbonFinanceFee`
  ADD PRIMARY KEY (`gibbonFinanceFeeID`);

--
-- Indexes for table `gibbonFinanceFeeCategory`
--
ALTER TABLE `gibbonFinanceFeeCategory`
  ADD PRIMARY KEY (`gibbonFinanceFeeCategoryID`);

--
-- Indexes for table `gibbonFinanceInvoice`
--
ALTER TABLE `gibbonFinanceInvoice`
  ADD PRIMARY KEY (`gibbonFinanceInvoiceID`);

--
-- Indexes for table `gibbonFinanceInvoicee`
--
ALTER TABLE `gibbonFinanceInvoicee`
  ADD PRIMARY KEY (`gibbonFinanceInvoiceeID`);

--
-- Indexes for table `gibbonFinanceInvoiceeUpdate`
--
ALTER TABLE `gibbonFinanceInvoiceeUpdate`
  ADD PRIMARY KEY (`gibbonFinanceInvoiceeUpdateID`),
  ADD KEY `gibbonInvoiceeIndex` (`gibbonFinanceInvoiceeID`,`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonFinanceInvoiceFee`
--
ALTER TABLE `gibbonFinanceInvoiceFee`
  ADD PRIMARY KEY (`gibbonFinanceInvoiceFeeID`);

--
-- Indexes for table `gibbonFirstAid`
--
ALTER TABLE `gibbonFirstAid`
  ADD PRIMARY KEY (`gibbonFirstAidID`);

--
-- Indexes for table `gibbonFirstAidFollowUp`
--
ALTER TABLE `gibbonFirstAidFollowUp`
  ADD PRIMARY KEY (`gibbonFirstAidFollowUpID`),
  ADD KEY `gibbonFirstAidID` (`gibbonFirstAidID`);

--
-- Indexes for table `gibbonForm`
--
ALTER TABLE `gibbonForm`
  ADD PRIMARY KEY (`gibbonFormID`);

--
-- Indexes for table `gibbonFormField`
--
ALTER TABLE `gibbonFormField`
  ADD PRIMARY KEY (`gibbonFormFieldID`);

--
-- Indexes for table `gibbonFormGroup`
--
ALTER TABLE `gibbonFormGroup`
  ADD PRIMARY KEY (`gibbonFormGroupID`);

--
-- Indexes for table `gibbonFormPage`
--
ALTER TABLE `gibbonFormPage`
  ADD PRIMARY KEY (`gibbonFormPageID`);

--
-- Indexes for table `gibbonFormSubmission`
--
ALTER TABLE `gibbonFormSubmission`
  ADD PRIMARY KEY (`gibbonFormSubmissionID`),
  ADD KEY `foreignTable` (`foreignTable`,`foreignTableID`);

--
-- Indexes for table `gibbonFormUpload`
--
ALTER TABLE `gibbonFormUpload`
  ADD PRIMARY KEY (`gibbonFormUploadID`),
  ADD KEY `foreignTable` (`foreignTable`,`foreignTableID`);

--
-- Indexes for table `gibbonGroup`
--
ALTER TABLE `gibbonGroup`
  ADD PRIMARY KEY (`gibbonGroupID`);

--
-- Indexes for table `gibbonGroupPerson`
--
ALTER TABLE `gibbonGroupPerson`
  ADD PRIMARY KEY (`gibbonGroupPersonID`),
  ADD UNIQUE KEY `gibbonGroupID` (`gibbonGroupID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonHook`
--
ALTER TABLE `gibbonHook`
  ADD PRIMARY KEY (`gibbonHookID`),
  ADD UNIQUE KEY `name` (`name`,`type`);

--
-- Indexes for table `gibbonHouse`
--
ALTER TABLE `gibbonHouse`
  ADD PRIMARY KEY (`gibbonHouseID`),
  ADD UNIQUE KEY `name` (`name`,`nameShort`);

--
-- Indexes for table `gibboni18n`
--
ALTER TABLE `gibboni18n`
  ADD PRIMARY KEY (`gibboni18nID`);

--
-- Indexes for table `gibbonIN`
--
ALTER TABLE `gibbonIN`
  ADD PRIMARY KEY (`gibbonINID`),
  ADD UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonINArchive`
--
ALTER TABLE `gibbonINArchive`
  ADD PRIMARY KEY (`gibbonINArchiveID`);

--
-- Indexes for table `gibbonINAssistant`
--
ALTER TABLE `gibbonINAssistant`
  ADD PRIMARY KEY (`gibbonINAssistantID`);

--
-- Indexes for table `gibbonINDescriptor`
--
ALTER TABLE `gibbonINDescriptor`
  ADD PRIMARY KEY (`gibbonINDescriptorID`);

--
-- Indexes for table `gibbonINInvestigation`
--
ALTER TABLE `gibbonINInvestigation`
  ADD PRIMARY KEY (`gibbonINInvestigationID`);

--
-- Indexes for table `gibbonINInvestigationContribution`
--
ALTER TABLE `gibbonINInvestigationContribution`
  ADD PRIMARY KEY (`gibbonINInvestigationContributionID`);

--
-- Indexes for table `gibbonINPersonDescriptor`
--
ALTER TABLE `gibbonINPersonDescriptor`
  ADD PRIMARY KEY (`gibbonINPersonDescriptorID`);

--
-- Indexes for table `gibbonInternalAssessmentColumn`
--
ALTER TABLE `gibbonInternalAssessmentColumn`
  ADD PRIMARY KEY (`gibbonInternalAssessmentColumnID`);

--
-- Indexes for table `gibbonInternalAssessmentEntry`
--
ALTER TABLE `gibbonInternalAssessmentEntry`
  ADD PRIMARY KEY (`gibbonInternalAssessmentEntryID`);

--
-- Indexes for table `gibbonLanguage`
--
ALTER TABLE `gibbonLanguage`
  ADD PRIMARY KEY (`gibbonLanguageID`);

--
-- Indexes for table `gibbonLibraryItem`
--
ALTER TABLE `gibbonLibraryItem`
  ADD PRIMARY KEY (`gibbonLibraryItemID`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `gibbonLibraryItemEvent`
--
ALTER TABLE `gibbonLibraryItemEvent`
  ADD PRIMARY KEY (`gibbonLibraryItemEventID`);

--
-- Indexes for table `gibbonLibraryShelf`
--
ALTER TABLE `gibbonLibraryShelf`
  ADD PRIMARY KEY (`gibbonLibraryShelfID`);

--
-- Indexes for table `gibbonLibraryShelfItem`
--
ALTER TABLE `gibbonLibraryShelfItem`
  ADD PRIMARY KEY (`gibbonLibraryShelfItemID`);

--
-- Indexes for table `gibbonLibraryType`
--
ALTER TABLE `gibbonLibraryType`
  ADD PRIMARY KEY (`gibbonLibraryTypeID`);

--
-- Indexes for table `gibbonLog`
--
ALTER TABLE `gibbonLog`
  ADD PRIMARY KEY (`gibbonLogID`);

--
-- Indexes for table `gibbonMarkbookColumn`
--
ALTER TABLE `gibbonMarkbookColumn`
  ADD PRIMARY KEY (`gibbonMarkbookColumnID`),
  ADD KEY `gibbonCourseClassID` (`gibbonCourseClassID`),
  ADD KEY `completeDate` (`completeDate`),
  ADD KEY `complete` (`complete`);

--
-- Indexes for table `gibbonMarkbookEntry`
--
ALTER TABLE `gibbonMarkbookEntry`
  ADD PRIMARY KEY (`gibbonMarkbookEntryID`),
  ADD KEY `gibbonPersonIDStudent` (`gibbonPersonIDStudent`),
  ADD KEY `gibbonMarkbookColumnID` (`gibbonMarkbookColumnID`);

--
-- Indexes for table `gibbonMarkbookTarget`
--
ALTER TABLE `gibbonMarkbookTarget`
  ADD PRIMARY KEY (`gibbonMarkbookTargetID`),
  ADD UNIQUE KEY `coursePerson` (`gibbonCourseClassID`,`gibbonPersonIDStudent`);

--
-- Indexes for table `gibbonMarkbookWeight`
--
ALTER TABLE `gibbonMarkbookWeight`
  ADD PRIMARY KEY (`gibbonMarkbookWeightID`);

--
-- Indexes for table `gibbonMedicalCondition`
--
ALTER TABLE `gibbonMedicalCondition`
  ADD PRIMARY KEY (`gibbonMedicalConditionID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gibbonMessenger`
--
ALTER TABLE `gibbonMessenger`
  ADD PRIMARY KEY (`gibbonMessengerID`);

--
-- Indexes for table `gibbonMessengerCannedResponse`
--
ALTER TABLE `gibbonMessengerCannedResponse`
  ADD PRIMARY KEY (`gibbonMessengerCannedResponseID`);

--
-- Indexes for table `gibbonMessengerReceipt`
--
ALTER TABLE `gibbonMessengerReceipt`
  ADD PRIMARY KEY (`gibbonMessengerReceiptID`);

--
-- Indexes for table `gibbonMessengerTarget`
--
ALTER TABLE `gibbonMessengerTarget`
  ADD PRIMARY KEY (`gibbonMessengerTargetID`),
  ADD KEY `gibbonMessengerID` (`gibbonMessengerID`,`gibbonMessengerTargetID`);

--
-- Indexes for table `gibbonMigration`
--
ALTER TABLE `gibbonMigration`
  ADD PRIMARY KEY (`gibbonMigrationID`);

--
-- Indexes for table `gibbonModule`
--
ALTER TABLE `gibbonModule`
  ADD PRIMARY KEY (`gibbonModuleID`),
  ADD UNIQUE KEY `gibbonModuleName` (`name`);

--
-- Indexes for table `gibbonNotification`
--
ALTER TABLE `gibbonNotification`
  ADD PRIMARY KEY (`gibbonNotificationID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonNotificationEvent`
--
ALTER TABLE `gibbonNotificationEvent`
  ADD PRIMARY KEY (`gibbonNotificationEventID`),
  ADD UNIQUE KEY `event` (`event`,`moduleName`);

--
-- Indexes for table `gibbonNotificationListener`
--
ALTER TABLE `gibbonNotificationListener`
  ADD PRIMARY KEY (`gibbonNotificationListenerID`);

--
-- Indexes for table `gibbonOutcome`
--
ALTER TABLE `gibbonOutcome`
  ADD PRIMARY KEY (`gibbonOutcomeID`);

--
-- Indexes for table `gibbonPayment`
--
ALTER TABLE `gibbonPayment`
  ADD PRIMARY KEY (`gibbonPaymentID`);

--
-- Indexes for table `gibbonPermission`
--
ALTER TABLE `gibbonPermission`
  ADD PRIMARY KEY (`permissionID`),
  ADD KEY `gibbonRoleID` (`gibbonRoleID`),
  ADD KEY `gibbonActionID` (`gibbonActionID`);

--
-- Indexes for table `gibbonPerson`
--
ALTER TABLE `gibbonPerson`
  ADD PRIMARY KEY (`gibbonPersonID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `username_2` (`username`,`email`);

--
-- Indexes for table `gibbonPersonalDocument`
--
ALTER TABLE `gibbonPersonalDocument`
  ADD PRIMARY KEY (`gibbonPersonalDocumentID`),
  ADD UNIQUE KEY `foreignTableID` (`gibbonPersonalDocumentTypeID`,`foreignTable`,`foreignTableID`);

--
-- Indexes for table `gibbonPersonalDocumentType`
--
ALTER TABLE `gibbonPersonalDocumentType`
  ADD PRIMARY KEY (`gibbonPersonalDocumentTypeID`);

--
-- Indexes for table `gibbonPersonMedical`
--
ALTER TABLE `gibbonPersonMedical`
  ADD PRIMARY KEY (`gibbonPersonMedicalID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonPersonMedicalCondition`
--
ALTER TABLE `gibbonPersonMedicalCondition`
  ADD PRIMARY KEY (`gibbonPersonMedicalConditionID`),
  ADD KEY `gibbonPersonMedicalID` (`gibbonPersonMedicalID`);

--
-- Indexes for table `gibbonPersonMedicalConditionUpdate`
--
ALTER TABLE `gibbonPersonMedicalConditionUpdate`
  ADD PRIMARY KEY (`gibbonPersonMedicalConditionUpdateID`);

--
-- Indexes for table `gibbonPersonMedicalUpdate`
--
ALTER TABLE `gibbonPersonMedicalUpdate`
  ADD PRIMARY KEY (`gibbonPersonMedicalUpdateID`),
  ADD KEY `gibbonMedicalIndex` (`gibbonPersonID`,`gibbonPersonMedicalID`,`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonPersonReset`
--
ALTER TABLE `gibbonPersonReset`
  ADD PRIMARY KEY (`gibbonPersonResetID`);

--
-- Indexes for table `gibbonPersonStatusLog`
--
ALTER TABLE `gibbonPersonStatusLog`
  ADD PRIMARY KEY (`gibbonPersonStatusLogID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonPersonUpdate`
--
ALTER TABLE `gibbonPersonUpdate`
  ADD PRIMARY KEY (`gibbonPersonUpdateID`),
  ADD KEY `gibbonPersonIndex` (`gibbonPersonID`,`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonPlannerEntry`
--
ALTER TABLE `gibbonPlannerEntry`
  ADD PRIMARY KEY (`gibbonPlannerEntryID`),
  ADD KEY `gibbonCourseClassID` (`gibbonCourseClassID`);

--
-- Indexes for table `gibbonPlannerEntryDiscuss`
--
ALTER TABLE `gibbonPlannerEntryDiscuss`
  ADD PRIMARY KEY (`gibbonPlannerEntryDiscussID`);

--
-- Indexes for table `gibbonPlannerEntryGuest`
--
ALTER TABLE `gibbonPlannerEntryGuest`
  ADD PRIMARY KEY (`gibbonPlannerEntryGuestID`);

--
-- Indexes for table `gibbonPlannerEntryHomework`
--
ALTER TABLE `gibbonPlannerEntryHomework`
  ADD PRIMARY KEY (`gibbonPlannerEntryHomeworkID`);

--
-- Indexes for table `gibbonPlannerEntryOutcome`
--
ALTER TABLE `gibbonPlannerEntryOutcome`
  ADD PRIMARY KEY (`gibbonPlannerEntryOutcomeID`);

--
-- Indexes for table `gibbonPlannerEntryStudentHomework`
--
ALTER TABLE `gibbonPlannerEntryStudentHomework`
  ADD PRIMARY KEY (`gibbonPlannerEntryStudentHomeworkID`),
  ADD KEY `gibbonPlannerEntryID` (`gibbonPlannerEntryID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonPlannerEntryStudentTracker`
--
ALTER TABLE `gibbonPlannerEntryStudentTracker`
  ADD PRIMARY KEY (`gibbonPlannerEntryStudentTrackerID`);

--
-- Indexes for table `gibbonPlannerParentWeeklyEmailSummary`
--
ALTER TABLE `gibbonPlannerParentWeeklyEmailSummary`
  ADD PRIMARY KEY (`gibbonPlannerParentWeeklyEmailSummaryID`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `gibbonReport`
--
ALTER TABLE `gibbonReport`
  ADD PRIMARY KEY (`gibbonReportID`);

--
-- Indexes for table `gibbonReportArchive`
--
ALTER TABLE `gibbonReportArchive`
  ADD PRIMARY KEY (`gibbonReportArchiveID`);

--
-- Indexes for table `gibbonReportArchiveEntry`
--
ALTER TABLE `gibbonReportArchiveEntry`
  ADD PRIMARY KEY (`gibbonReportArchiveEntryID`),
  ADD UNIQUE KEY `archiveEntry` (`gibbonReportArchiveID`,`gibbonSchoolYearID`,`reportIdentifier`,`type`,`gibbonYearGroupID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonReportingAccess`
--
ALTER TABLE `gibbonReportingAccess`
  ADD PRIMARY KEY (`gibbonReportingAccessID`);

--
-- Indexes for table `gibbonReportingCriteria`
--
ALTER TABLE `gibbonReportingCriteria`
  ADD PRIMARY KEY (`gibbonReportingCriteriaID`);

--
-- Indexes for table `gibbonReportingCriteriaType`
--
ALTER TABLE `gibbonReportingCriteriaType`
  ADD PRIMARY KEY (`gibbonReportingCriteriaTypeID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gibbonReportingCycle`
--
ALTER TABLE `gibbonReportingCycle`
  ADD PRIMARY KEY (`gibbonReportingCycleID`);

--
-- Indexes for table `gibbonReportingProgress`
--
ALTER TABLE `gibbonReportingProgress`
  ADD PRIMARY KEY (`gibbonReportingProgressID`),
  ADD UNIQUE KEY `gibbonReportingScopeID` (`gibbonReportingScopeID`,`gibbonCourseClassID`,`gibbonPersonIDStudent`);

--
-- Indexes for table `gibbonReportingProof`
--
ALTER TABLE `gibbonReportingProof`
  ADD PRIMARY KEY (`gibbonReportingProofID`),
  ADD UNIQUE KEY `gibbonReportingValueID` (`gibbonReportingValueID`);

--
-- Indexes for table `gibbonReportingScope`
--
ALTER TABLE `gibbonReportingScope`
  ADD PRIMARY KEY (`gibbonReportingScopeID`);

--
-- Indexes for table `gibbonReportingValue`
--
ALTER TABLE `gibbonReportingValue`
  ADD PRIMARY KEY (`gibbonReportingValueID`),
  ADD UNIQUE KEY `gibbonReportingCriteriaID` (`gibbonReportingCriteriaID`,`gibbonPersonIDStudent`,`gibbonCourseClassID`) USING BTREE;

--
-- Indexes for table `gibbonReportPrototypeSection`
--
ALTER TABLE `gibbonReportPrototypeSection`
  ADD PRIMARY KEY (`gibbonReportPrototypeSectionID`),
  ADD UNIQUE KEY `type` (`type`,`templateFile`);

--
-- Indexes for table `gibbonReportTemplate`
--
ALTER TABLE `gibbonReportTemplate`
  ADD PRIMARY KEY (`gibbonReportTemplateID`);

--
-- Indexes for table `gibbonReportTemplateFont`
--
ALTER TABLE `gibbonReportTemplateFont`
  ADD PRIMARY KEY (`gibbonReportTemplateFontID`),
  ADD UNIQUE KEY `fontTCPDF` (`fontTCPDF`);

--
-- Indexes for table `gibbonReportTemplateSection`
--
ALTER TABLE `gibbonReportTemplateSection`
  ADD PRIMARY KEY (`gibbonReportTemplateSectionID`);

--
-- Indexes for table `gibbonResource`
--
ALTER TABLE `gibbonResource`
  ADD PRIMARY KEY (`gibbonResourceID`);

--
-- Indexes for table `gibbonResourceTag`
--
ALTER TABLE `gibbonResourceTag`
  ADD PRIMARY KEY (`gibbonResourceTagID`),
  ADD UNIQUE KEY `tag` (`tag`),
  ADD KEY `tag_2` (`tag`);

--
-- Indexes for table `gibbonRole`
--
ALTER TABLE `gibbonRole`
  ADD PRIMARY KEY (`gibbonRoleID`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `nameShort` (`nameShort`);

--
-- Indexes for table `gibbonRubric`
--
ALTER TABLE `gibbonRubric`
  ADD PRIMARY KEY (`gibbonRubricID`);

--
-- Indexes for table `gibbonRubricCell`
--
ALTER TABLE `gibbonRubricCell`
  ADD PRIMARY KEY (`gibbonRubricCellID`),
  ADD KEY `gibbonRubricID` (`gibbonRubricID`),
  ADD KEY `gibbonRubricColumnID` (`gibbonRubricColumnID`),
  ADD KEY `gibbonRubricRowID` (`gibbonRubricRowID`);

--
-- Indexes for table `gibbonRubricColumn`
--
ALTER TABLE `gibbonRubricColumn`
  ADD PRIMARY KEY (`gibbonRubricColumnID`),
  ADD KEY `gibbonRubricID` (`gibbonRubricID`);

--
-- Indexes for table `gibbonRubricEntry`
--
ALTER TABLE `gibbonRubricEntry`
  ADD PRIMARY KEY (`gibbonRubricEntry`),
  ADD KEY `gibbonRubricID` (`gibbonRubricID`),
  ADD KEY `gibbonPersonID` (`gibbonPersonID`),
  ADD KEY `gibbonRubricCellID` (`gibbonRubricCellID`),
  ADD KEY `contextDBTable` (`contextDBTable`),
  ADD KEY `contextDBTableID` (`contextDBTableID`);

--
-- Indexes for table `gibbonRubricRow`
--
ALTER TABLE `gibbonRubricRow`
  ADD PRIMARY KEY (`gibbonRubricRowID`),
  ADD KEY `gibbonRubricID` (`gibbonRubricID`);

--
-- Indexes for table `gibbonScale`
--
ALTER TABLE `gibbonScale`
  ADD PRIMARY KEY (`gibbonScaleID`);

--
-- Indexes for table `gibbonScaleGrade`
--
ALTER TABLE `gibbonScaleGrade`
  ADD PRIMARY KEY (`gibbonScaleGradeID`);

--
-- Indexes for table `gibbonSchoolYear`
--
ALTER TABLE `gibbonSchoolYear`
  ADD PRIMARY KEY (`gibbonSchoolYearID`),
  ADD UNIQUE KEY `academicYearName` (`name`),
  ADD UNIQUE KEY `sequenceNumber` (`sequenceNumber`);

--
-- Indexes for table `gibbonSchoolYearSpecialDay`
--
ALTER TABLE `gibbonSchoolYearSpecialDay`
  ADD PRIMARY KEY (`gibbonSchoolYearSpecialDayID`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `gibbonSchoolYearTerm`
--
ALTER TABLE `gibbonSchoolYearTerm`
  ADD PRIMARY KEY (`gibbonSchoolYearTermID`),
  ADD UNIQUE KEY `sequenceNumber` (`sequenceNumber`,`gibbonSchoolYearID`);

--
-- Indexes for table `gibbonSession`
--
ALTER TABLE `gibbonSession`
  ADD PRIMARY KEY (`gibbonSessionID`);

--
-- Indexes for table `gibbonSetting`
--
ALTER TABLE `gibbonSetting`
  ADD PRIMARY KEY (`gibbonSettingID`),
  ADD UNIQUE KEY `scope` (`scope`,`nameDisplay`),
  ADD UNIQUE KEY `scope_2` (`scope`,`name`);

--
-- Indexes for table `gibbonSpace`
--
ALTER TABLE `gibbonSpace`
  ADD PRIMARY KEY (`gibbonSpaceID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gibbonSpacePerson`
--
ALTER TABLE `gibbonSpacePerson`
  ADD PRIMARY KEY (`gibbonSpacePersonID`);

--
-- Indexes for table `gibbonStaff`
--
ALTER TABLE `gibbonStaff`
  ADD PRIMARY KEY (`gibbonStaffID`),
  ADD UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`),
  ADD UNIQUE KEY `initials` (`initials`);

--
-- Indexes for table `gibbonStaffAbsence`
--
ALTER TABLE `gibbonStaffAbsence`
  ADD PRIMARY KEY (`gibbonStaffAbsenceID`);

--
-- Indexes for table `gibbonStaffAbsenceDate`
--
ALTER TABLE `gibbonStaffAbsenceDate`
  ADD PRIMARY KEY (`gibbonStaffAbsenceDateID`);

--
-- Indexes for table `gibbonStaffAbsenceType`
--
ALTER TABLE `gibbonStaffAbsenceType`
  ADD PRIMARY KEY (`gibbonStaffAbsenceTypeID`);

--
-- Indexes for table `gibbonStaffApplicationForm`
--
ALTER TABLE `gibbonStaffApplicationForm`
  ADD PRIMARY KEY (`gibbonStaffApplicationFormID`);

--
-- Indexes for table `gibbonStaffApplicationFormFile`
--
ALTER TABLE `gibbonStaffApplicationFormFile`
  ADD PRIMARY KEY (`gibbonStaffApplicationFormFileID`);

--
-- Indexes for table `gibbonStaffContract`
--
ALTER TABLE `gibbonStaffContract`
  ADD PRIMARY KEY (`gibbonStaffContractID`);

--
-- Indexes for table `gibbonStaffCoverage`
--
ALTER TABLE `gibbonStaffCoverage`
  ADD PRIMARY KEY (`gibbonStaffCoverageID`);

--
-- Indexes for table `gibbonStaffCoverageDate`
--
ALTER TABLE `gibbonStaffCoverageDate`
  ADD PRIMARY KEY (`gibbonStaffCoverageDateID`),
  ADD KEY `foreignTable` (`foreignTable`,`foreignTableID`);

--
-- Indexes for table `gibbonStaffDuty`
--
ALTER TABLE `gibbonStaffDuty`
  ADD PRIMARY KEY (`gibbonStaffDutyID`);

--
-- Indexes for table `gibbonStaffDutyPerson`
--
ALTER TABLE `gibbonStaffDutyPerson`
  ADD PRIMARY KEY (`gibbonStaffDutyPersonID`),
  ADD UNIQUE KEY `gibbonStaffDutyID` (`gibbonStaffDutyID`,`gibbonDaysOfWeekID`,`gibbonPersonID`);

--
-- Indexes for table `gibbonStaffJobOpening`
--
ALTER TABLE `gibbonStaffJobOpening`
  ADD PRIMARY KEY (`gibbonStaffJobOpeningID`);

--
-- Indexes for table `gibbonStaffUpdate`
--
ALTER TABLE `gibbonStaffUpdate`
  ADD PRIMARY KEY (`gibbonStaffUpdateID`);

--
-- Indexes for table `gibbonString`
--
ALTER TABLE `gibbonString`
  ADD PRIMARY KEY (`gibbonStringID`);

--
-- Indexes for table `gibbonStudentEnrolment`
--
ALTER TABLE `gibbonStudentEnrolment`
  ADD PRIMARY KEY (`gibbonStudentEnrolmentID`),
  ADD KEY `gibbonSchoolYearID` (`gibbonSchoolYearID`),
  ADD KEY `gibbonYearGroupID` (`gibbonYearGroupID`),
  ADD KEY `gibbonPersonIndex` (`gibbonPersonID`,`gibbonSchoolYearID`),
  ADD KEY `gibbonFormGroupID` (`gibbonFormGroupID`);

--
-- Indexes for table `gibbonStudentNote`
--
ALTER TABLE `gibbonStudentNote`
  ADD PRIMARY KEY (`gibbonStudentNoteID`);

--
-- Indexes for table `gibbonStudentNoteCategory`
--
ALTER TABLE `gibbonStudentNoteCategory`
  ADD PRIMARY KEY (`gibbonStudentNoteCategoryID`);

--
-- Indexes for table `gibbonSubstitute`
--
ALTER TABLE `gibbonSubstitute`
  ADD PRIMARY KEY (`gibbonSubstituteID`),
  ADD UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`);

--
-- Indexes for table `gibbonTheme`
--
ALTER TABLE `gibbonTheme`
  ADD PRIMARY KEY (`gibbonThemeID`);

--
-- Indexes for table `gibbonTT`
--
ALTER TABLE `gibbonTT`
  ADD PRIMARY KEY (`gibbonTTID`);

--
-- Indexes for table `gibbonTTColumn`
--
ALTER TABLE `gibbonTTColumn`
  ADD PRIMARY KEY (`gibbonTTColumnID`);

--
-- Indexes for table `gibbonTTColumnRow`
--
ALTER TABLE `gibbonTTColumnRow`
  ADD PRIMARY KEY (`gibbonTTColumnRowID`),
  ADD KEY `gibbonTTColumnID` (`gibbonTTColumnID`);

--
-- Indexes for table `gibbonTTDay`
--
ALTER TABLE `gibbonTTDay`
  ADD PRIMARY KEY (`gibbonTTDayID`);

--
-- Indexes for table `gibbonTTDayDate`
--
ALTER TABLE `gibbonTTDayDate`
  ADD PRIMARY KEY (`gibbonTTDayDateID`),
  ADD KEY `gibbonTTDayID` (`gibbonTTDayID`);

--
-- Indexes for table `gibbonTTDayRowClass`
--
ALTER TABLE `gibbonTTDayRowClass`
  ADD PRIMARY KEY (`gibbonTTDayRowClassID`),
  ADD KEY `gibbonCourseClassID` (`gibbonCourseClassID`),
  ADD KEY `gibbonSpaceID` (`gibbonSpaceID`),
  ADD KEY `gibbonTTColumnRowID` (`gibbonTTColumnRowID`);

--
-- Indexes for table `gibbonTTDayRowClassException`
--
ALTER TABLE `gibbonTTDayRowClassException`
  ADD PRIMARY KEY (`gibbonTTDayRowClassExceptionID`);

--
-- Indexes for table `gibbonTTImport`
--
ALTER TABLE `gibbonTTImport`
  ADD PRIMARY KEY (`gibbonTTImportID`);

--
-- Indexes for table `gibbonTTSpaceBooking`
--
ALTER TABLE `gibbonTTSpaceBooking`
  ADD PRIMARY KEY (`gibbonTTSpaceBookingID`);

--
-- Indexes for table `gibbonTTSpaceChange`
--
ALTER TABLE `gibbonTTSpaceChange`
  ADD PRIMARY KEY (`gibbonTTSpaceChangeID`),
  ADD KEY `gibbonTTDayRowClassID` (`gibbonTTDayRowClassID`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `gibbonUnit`
--
ALTER TABLE `gibbonUnit`
  ADD PRIMARY KEY (`gibbonUnitID`);

--
-- Indexes for table `gibbonUnitBlock`
--
ALTER TABLE `gibbonUnitBlock`
  ADD PRIMARY KEY (`gibbonUnitBlockID`);

--
-- Indexes for table `gibbonUnitClass`
--
ALTER TABLE `gibbonUnitClass`
  ADD PRIMARY KEY (`gibbonUnitClassID`);

--
-- Indexes for table `gibbonUnitClassBlock`
--
ALTER TABLE `gibbonUnitClassBlock`
  ADD PRIMARY KEY (`gibbonUnitClassBlockID`);

--
-- Indexes for table `gibbonUnitOutcome`
--
ALTER TABLE `gibbonUnitOutcome`
  ADD PRIMARY KEY (`gibbonUnitOutcomeID`);

--
-- Indexes for table `gibbonUsernameFormat`
--
ALTER TABLE `gibbonUsernameFormat`
  ADD PRIMARY KEY (`gibbonUsernameFormatID`);

--
-- Indexes for table `gibbonYearGroup`
--
ALTER TABLE `gibbonYearGroup`
  ADD PRIMARY KEY (`gibbonYearGroupID`),
  ADD UNIQUE KEY `name` (`name`,`nameShort`,`sequenceNumber`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gibbonAction`
--
ALTER TABLE `gibbonAction`
  MODIFY `gibbonActionID` int(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=999;

--
-- AUTO_INCREMENT for table `gibbonActivity`
--
ALTER TABLE `gibbonActivity`
  MODIFY `gibbonActivityID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonActivityAttendance`
--
ALTER TABLE `gibbonActivityAttendance`
  MODIFY `gibbonActivityAttendanceID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonActivitySlot`
--
ALTER TABLE `gibbonActivitySlot`
  MODIFY `gibbonActivitySlotID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonActivityStaff`
--
ALTER TABLE `gibbonActivityStaff`
  MODIFY `gibbonActivityStaffID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonActivityStudent`
--
ALTER TABLE `gibbonActivityStudent`
  MODIFY `gibbonActivityStudentID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonActivityType`
--
ALTER TABLE `gibbonActivityType`
  MODIFY `gibbonActivityTypeID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAdmissionsAccount`
--
ALTER TABLE `gibbonAdmissionsAccount`
  MODIFY `gibbonAdmissionsAccountID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAdmissionsApplication`
--
ALTER TABLE `gibbonAdmissionsApplication`
  MODIFY `gibbonAdmissionsApplicationID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAlarm`
--
ALTER TABLE `gibbonAlarm`
  MODIFY `gibbonAlarmID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAlarmConfirm`
--
ALTER TABLE `gibbonAlarmConfirm`
  MODIFY `gibbonAlarmConfirmID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAlertLevel`
--
ALTER TABLE `gibbonAlertLevel`
  MODIFY `gibbonAlertLevelID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gibbonApplicationForm`
--
ALTER TABLE `gibbonApplicationForm`
  MODIFY `gibbonApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonApplicationFormFile`
--
ALTER TABLE `gibbonApplicationFormFile`
  MODIFY `gibbonApplicationFormFileID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonApplicationFormLink`
--
ALTER TABLE `gibbonApplicationFormLink`
  MODIFY `gibbonApplicationFormLinkID` int(12) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonApplicationFormRelationship`
--
ALTER TABLE `gibbonApplicationFormRelationship`
  MODIFY `gibbonApplicationFormRelationshipID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAttendanceCode`
--
ALTER TABLE `gibbonAttendanceCode`
  MODIFY `gibbonAttendanceCodeID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `gibbonAttendanceLogCourseClass`
--
ALTER TABLE `gibbonAttendanceLogCourseClass`
  MODIFY `gibbonAttendanceLogCourseClassID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAttendanceLogFormGroup`
--
ALTER TABLE `gibbonAttendanceLogFormGroup`
  MODIFY `gibbonAttendanceLogFormGroupID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonAttendanceLogPerson`
--
ALTER TABLE `gibbonAttendanceLogPerson`
  MODIFY `gibbonAttendanceLogPersonID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonBehaviour`
--
ALTER TABLE `gibbonBehaviour`
  MODIFY `gibbonBehaviourID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonBehaviourFollowUp`
--
ALTER TABLE `gibbonBehaviourFollowUp`
  MODIFY `gibbonBehaviourFollowUpID` int(11) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonBehaviourLetter`
--
ALTER TABLE `gibbonBehaviourLetter`
  MODIFY `gibbonBehaviourLetterID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCourse`
--
ALTER TABLE `gibbonCourse`
  MODIFY `gibbonCourseID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCourseClass`
--
ALTER TABLE `gibbonCourseClass`
  MODIFY `gibbonCourseClassID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCourseClassMap`
--
ALTER TABLE `gibbonCourseClassMap`
  MODIFY `gibbonCourseClassMapID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCourseClassPerson`
--
ALTER TABLE `gibbonCourseClassPerson`
  MODIFY `gibbonCourseClassPersonID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCrowdAssessDiscuss`
--
ALTER TABLE `gibbonCrowdAssessDiscuss`
  MODIFY `gibbonCrowdAssessDiscussID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonCustomField`
--
ALTER TABLE `gibbonCustomField`
  MODIFY `gibbonCustomFieldID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDataRetention`
--
ALTER TABLE `gibbonDataRetention`
  MODIFY `gibbonDataRetentionID` int(11) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDaysOfWeek`
--
ALTER TABLE `gibbonDaysOfWeek`
  MODIFY `gibbonDaysOfWeekID` int(2) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `gibbonDepartment`
--
ALTER TABLE `gibbonDepartment`
  MODIFY `gibbonDepartmentID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDepartmentResource`
--
ALTER TABLE `gibbonDepartmentResource`
  MODIFY `gibbonDepartmentResourceID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDepartmentStaff`
--
ALTER TABLE `gibbonDepartmentStaff`
  MODIFY `gibbonDepartmentStaffID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDiscussion`
--
ALTER TABLE `gibbonDiscussion`
  MODIFY `gibbonDiscussionID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonDistrict`
--
ALTER TABLE `gibbonDistrict`
  MODIFY `gibbonDistrictID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonEmailTemplate`
--
ALTER TABLE `gibbonEmailTemplate`
  MODIFY `gibbonEmailTemplateID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `gibbonExternalAssessment`
--
ALTER TABLE `gibbonExternalAssessment`
  MODIFY `gibbonExternalAssessmentID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gibbonExternalAssessmentField`
--
ALTER TABLE `gibbonExternalAssessmentField`
  MODIFY `gibbonExternalAssessmentFieldID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `gibbonExternalAssessmentStudent`
--
ALTER TABLE `gibbonExternalAssessmentStudent`
  MODIFY `gibbonExternalAssessmentStudentID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonExternalAssessmentStudentEntry`
--
ALTER TABLE `gibbonExternalAssessmentStudentEntry`
  MODIFY `gibbonExternalAssessmentStudentEntryID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFamily`
--
ALTER TABLE `gibbonFamily`
  MODIFY `gibbonFamilyID` int(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFamilyAdult`
--
ALTER TABLE `gibbonFamilyAdult`
  MODIFY `gibbonFamilyAdultID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFamilyChild`
--
ALTER TABLE `gibbonFamilyChild`
  MODIFY `gibbonFamilyChildID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFamilyRelationship`
--
ALTER TABLE `gibbonFamilyRelationship`
  MODIFY `gibbonFamilyRelationshipID` int(9) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFamilyUpdate`
--
ALTER TABLE `gibbonFamilyUpdate`
  MODIFY `gibbonFamilyUpdateID` int(9) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFileExtension`
--
ALTER TABLE `gibbonFileExtension`
  MODIFY `gibbonFileExtensionID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `gibbonFinanceBillingSchedule`
--
ALTER TABLE `gibbonFinanceBillingSchedule`
  MODIFY `gibbonFinanceBillingScheduleID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceBudget`
--
ALTER TABLE `gibbonFinanceBudget`
  MODIFY `gibbonFinanceBudgetID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceBudgetCycle`
--
ALTER TABLE `gibbonFinanceBudgetCycle`
  MODIFY `gibbonFinanceBudgetCycleID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceBudgetCycleAllocation`
--
ALTER TABLE `gibbonFinanceBudgetCycleAllocation`
  MODIFY `gibbonFinanceBudgetCycleAllocationID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceBudgetPerson`
--
ALTER TABLE `gibbonFinanceBudgetPerson`
  MODIFY `gibbonFinanceBudgetPersonID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceExpense`
--
ALTER TABLE `gibbonFinanceExpense`
  MODIFY `gibbonFinanceExpenseID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceExpenseApprover`
--
ALTER TABLE `gibbonFinanceExpenseApprover`
  MODIFY `gibbonFinanceExpenseApproverID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceExpenseLog`
--
ALTER TABLE `gibbonFinanceExpenseLog`
  MODIFY `gibbonFinanceExpenseLogID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceFee`
--
ALTER TABLE `gibbonFinanceFee`
  MODIFY `gibbonFinanceFeeID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceFeeCategory`
--
ALTER TABLE `gibbonFinanceFeeCategory`
  MODIFY `gibbonFinanceFeeCategoryID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gibbonFinanceInvoice`
--
ALTER TABLE `gibbonFinanceInvoice`
  MODIFY `gibbonFinanceInvoiceID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceInvoicee`
--
ALTER TABLE `gibbonFinanceInvoicee`
  MODIFY `gibbonFinanceInvoiceeID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceInvoiceeUpdate`
--
ALTER TABLE `gibbonFinanceInvoiceeUpdate`
  MODIFY `gibbonFinanceInvoiceeUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFinanceInvoiceFee`
--
ALTER TABLE `gibbonFinanceInvoiceFee`
  MODIFY `gibbonFinanceInvoiceFeeID` int(15) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFirstAid`
--
ALTER TABLE `gibbonFirstAid`
  MODIFY `gibbonFirstAidID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFirstAidFollowUp`
--
ALTER TABLE `gibbonFirstAidFollowUp`
  MODIFY `gibbonFirstAidFollowUpID` int(11) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonForm`
--
ALTER TABLE `gibbonForm`
  MODIFY `gibbonFormID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gibbonFormField`
--
ALTER TABLE `gibbonFormField`
  MODIFY `gibbonFormFieldID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `gibbonFormGroup`
--
ALTER TABLE `gibbonFormGroup`
  MODIFY `gibbonFormGroupID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFormPage`
--
ALTER TABLE `gibbonFormPage`
  MODIFY `gibbonFormPageID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gibbonFormSubmission`
--
ALTER TABLE `gibbonFormSubmission`
  MODIFY `gibbonFormSubmissionID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonFormUpload`
--
ALTER TABLE `gibbonFormUpload`
  MODIFY `gibbonFormUploadID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonGroup`
--
ALTER TABLE `gibbonGroup`
  MODIFY `gibbonGroupID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonGroupPerson`
--
ALTER TABLE `gibbonGroupPerson`
  MODIFY `gibbonGroupPersonID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonHook`
--
ALTER TABLE `gibbonHook`
  MODIFY `gibbonHookID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonHouse`
--
ALTER TABLE `gibbonHouse`
  MODIFY `gibbonHouseID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibboni18n`
--
ALTER TABLE `gibboni18n`
  MODIFY `gibboni18nID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `gibbonIN`
--
ALTER TABLE `gibbonIN`
  MODIFY `gibbonINID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonINArchive`
--
ALTER TABLE `gibbonINArchive`
  MODIFY `gibbonINArchiveID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonINAssistant`
--
ALTER TABLE `gibbonINAssistant`
  MODIFY `gibbonINAssistantID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonINDescriptor`
--
ALTER TABLE `gibbonINDescriptor`
  MODIFY `gibbonINDescriptorID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gibbonINInvestigation`
--
ALTER TABLE `gibbonINInvestigation`
  MODIFY `gibbonINInvestigationID` int(11) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonINInvestigationContribution`
--
ALTER TABLE `gibbonINInvestigationContribution`
  MODIFY `gibbonINInvestigationContributionID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonINPersonDescriptor`
--
ALTER TABLE `gibbonINPersonDescriptor`
  MODIFY `gibbonINPersonDescriptorID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonInternalAssessmentColumn`
--
ALTER TABLE `gibbonInternalAssessmentColumn`
  MODIFY `gibbonInternalAssessmentColumnID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonInternalAssessmentEntry`
--
ALTER TABLE `gibbonInternalAssessmentEntry`
  MODIFY `gibbonInternalAssessmentEntryID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonLanguage`
--
ALTER TABLE `gibbonLanguage`
  MODIFY `gibbonLanguageID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `gibbonLibraryItem`
--
ALTER TABLE `gibbonLibraryItem`
  MODIFY `gibbonLibraryItemID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonLibraryItemEvent`
--
ALTER TABLE `gibbonLibraryItemEvent`
  MODIFY `gibbonLibraryItemEventID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonLibraryShelf`
--
ALTER TABLE `gibbonLibraryShelf`
  MODIFY `gibbonLibraryShelfID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonLibraryShelfItem`
--
ALTER TABLE `gibbonLibraryShelfItem`
  MODIFY `gibbonLibraryShelfItemID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonLibraryType`
--
ALTER TABLE `gibbonLibraryType`
  MODIFY `gibbonLibraryTypeID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `gibbonLog`
--
ALTER TABLE `gibbonLog`
  MODIFY `gibbonLogID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMarkbookColumn`
--
ALTER TABLE `gibbonMarkbookColumn`
  MODIFY `gibbonMarkbookColumnID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMarkbookEntry`
--
ALTER TABLE `gibbonMarkbookEntry`
  MODIFY `gibbonMarkbookEntryID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMarkbookTarget`
--
ALTER TABLE `gibbonMarkbookTarget`
  MODIFY `gibbonMarkbookTargetID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMarkbookWeight`
--
ALTER TABLE `gibbonMarkbookWeight`
  MODIFY `gibbonMarkbookWeightID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMedicalCondition`
--
ALTER TABLE `gibbonMedicalCondition`
  MODIFY `gibbonMedicalConditionID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `gibbonMessenger`
--
ALTER TABLE `gibbonMessenger`
  MODIFY `gibbonMessengerID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMessengerCannedResponse`
--
ALTER TABLE `gibbonMessengerCannedResponse`
  MODIFY `gibbonMessengerCannedResponseID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMessengerReceipt`
--
ALTER TABLE `gibbonMessengerReceipt`
  MODIFY `gibbonMessengerReceiptID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMessengerTarget`
--
ALTER TABLE `gibbonMessengerTarget`
  MODIFY `gibbonMessengerTargetID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonMigration`
--
ALTER TABLE `gibbonMigration`
  MODIFY `gibbonMigrationID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonModule`
--
ALTER TABLE `gibbonModule`
  MODIFY `gibbonModuleID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'This number is assigned at install, and is only unique to the installation', AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `gibbonNotification`
--
ALTER TABLE `gibbonNotification`
  MODIFY `gibbonNotificationID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonNotificationEvent`
--
ALTER TABLE `gibbonNotificationEvent`
  MODIFY `gibbonNotificationEventID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `gibbonNotificationListener`
--
ALTER TABLE `gibbonNotificationListener`
  MODIFY `gibbonNotificationListenerID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonOutcome`
--
ALTER TABLE `gibbonOutcome`
  MODIFY `gibbonOutcomeID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPayment`
--
ALTER TABLE `gibbonPayment`
  MODIFY `gibbonPaymentID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPermission`
--
ALTER TABLE `gibbonPermission`
  MODIFY `permissionID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54068;

--
-- AUTO_INCREMENT for table `gibbonPerson`
--
ALTER TABLE `gibbonPerson`
  MODIFY `gibbonPersonID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonalDocument`
--
ALTER TABLE `gibbonPersonalDocument`
  MODIFY `gibbonPersonalDocumentID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonalDocumentType`
--
ALTER TABLE `gibbonPersonalDocumentType`
  MODIFY `gibbonPersonalDocumentTypeID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gibbonPersonMedical`
--
ALTER TABLE `gibbonPersonMedical`
  MODIFY `gibbonPersonMedicalID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonMedicalCondition`
--
ALTER TABLE `gibbonPersonMedicalCondition`
  MODIFY `gibbonPersonMedicalConditionID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonMedicalConditionUpdate`
--
ALTER TABLE `gibbonPersonMedicalConditionUpdate`
  MODIFY `gibbonPersonMedicalConditionUpdateID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonMedicalUpdate`
--
ALTER TABLE `gibbonPersonMedicalUpdate`
  MODIFY `gibbonPersonMedicalUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonReset`
--
ALTER TABLE `gibbonPersonReset`
  MODIFY `gibbonPersonResetID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonStatusLog`
--
ALTER TABLE `gibbonPersonStatusLog`
  MODIFY `gibbonPersonStatusLogID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPersonUpdate`
--
ALTER TABLE `gibbonPersonUpdate`
  MODIFY `gibbonPersonUpdateID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntry`
--
ALTER TABLE `gibbonPlannerEntry`
  MODIFY `gibbonPlannerEntryID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryDiscuss`
--
ALTER TABLE `gibbonPlannerEntryDiscuss`
  MODIFY `gibbonPlannerEntryDiscussID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryGuest`
--
ALTER TABLE `gibbonPlannerEntryGuest`
  MODIFY `gibbonPlannerEntryGuestID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryHomework`
--
ALTER TABLE `gibbonPlannerEntryHomework`
  MODIFY `gibbonPlannerEntryHomeworkID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryOutcome`
--
ALTER TABLE `gibbonPlannerEntryOutcome`
  MODIFY `gibbonPlannerEntryOutcomeID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryStudentHomework`
--
ALTER TABLE `gibbonPlannerEntryStudentHomework`
  MODIFY `gibbonPlannerEntryStudentHomeworkID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerEntryStudentTracker`
--
ALTER TABLE `gibbonPlannerEntryStudentTracker`
  MODIFY `gibbonPlannerEntryStudentTrackerID` int(16) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonPlannerParentWeeklyEmailSummary`
--
ALTER TABLE `gibbonPlannerParentWeeklyEmailSummary`
  MODIFY `gibbonPlannerParentWeeklyEmailSummaryID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReport`
--
ALTER TABLE `gibbonReport`
  MODIFY `gibbonReportID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportArchive`
--
ALTER TABLE `gibbonReportArchive`
  MODIFY `gibbonReportArchiveID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gibbonReportArchiveEntry`
--
ALTER TABLE `gibbonReportArchiveEntry`
  MODIFY `gibbonReportArchiveEntryID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingAccess`
--
ALTER TABLE `gibbonReportingAccess`
  MODIFY `gibbonReportingAccessID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingCriteria`
--
ALTER TABLE `gibbonReportingCriteria`
  MODIFY `gibbonReportingCriteriaID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingCriteriaType`
--
ALTER TABLE `gibbonReportingCriteriaType`
  MODIFY `gibbonReportingCriteriaTypeID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gibbonReportingCycle`
--
ALTER TABLE `gibbonReportingCycle`
  MODIFY `gibbonReportingCycleID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingProgress`
--
ALTER TABLE `gibbonReportingProgress`
  MODIFY `gibbonReportingProgressID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingProof`
--
ALTER TABLE `gibbonReportingProof`
  MODIFY `gibbonReportingProofID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingScope`
--
ALTER TABLE `gibbonReportingScope`
  MODIFY `gibbonReportingScopeID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportingValue`
--
ALTER TABLE `gibbonReportingValue`
  MODIFY `gibbonReportingValueID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportPrototypeSection`
--
ALTER TABLE `gibbonReportPrototypeSection`
  MODIFY `gibbonReportPrototypeSectionID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportTemplate`
--
ALTER TABLE `gibbonReportTemplate`
  MODIFY `gibbonReportTemplateID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportTemplateFont`
--
ALTER TABLE `gibbonReportTemplateFont`
  MODIFY `gibbonReportTemplateFontID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonReportTemplateSection`
--
ALTER TABLE `gibbonReportTemplateSection`
  MODIFY `gibbonReportTemplateSectionID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonResource`
--
ALTER TABLE `gibbonResource`
  MODIFY `gibbonResourceID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonResourceTag`
--
ALTER TABLE `gibbonResourceTag`
  MODIFY `gibbonResourceTagID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonRole`
--
ALTER TABLE `gibbonRole`
  MODIFY `gibbonRoleID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `gibbonRubric`
--
ALTER TABLE `gibbonRubric`
  MODIFY `gibbonRubricID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonRubricCell`
--
ALTER TABLE `gibbonRubricCell`
  MODIFY `gibbonRubricCellID` int(11) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonRubricColumn`
--
ALTER TABLE `gibbonRubricColumn`
  MODIFY `gibbonRubricColumnID` int(9) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonRubricEntry`
--
ALTER TABLE `gibbonRubricEntry`
  MODIFY `gibbonRubricEntry` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonRubricRow`
--
ALTER TABLE `gibbonRubricRow`
  MODIFY `gibbonRubricRowID` int(9) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonScale`
--
ALTER TABLE `gibbonScale`
  MODIFY `gibbonScaleID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `gibbonScaleGrade`
--
ALTER TABLE `gibbonScaleGrade`
  MODIFY `gibbonScaleGradeID` int(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT for table `gibbonSchoolYear`
--
ALTER TABLE `gibbonSchoolYear`
  MODIFY `gibbonSchoolYearID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `gibbonSchoolYearSpecialDay`
--
ALTER TABLE `gibbonSchoolYearSpecialDay`
  MODIFY `gibbonSchoolYearSpecialDayID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonSchoolYearTerm`
--
ALTER TABLE `gibbonSchoolYearTerm`
  MODIFY `gibbonSchoolYearTermID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `gibbonSetting`
--
ALTER TABLE `gibbonSetting`
  MODIFY `gibbonSettingID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT for table `gibbonSpace`
--
ALTER TABLE `gibbonSpace`
  MODIFY `gibbonSpaceID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonSpacePerson`
--
ALTER TABLE `gibbonSpacePerson`
  MODIFY `gibbonSpacePersonID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaff`
--
ALTER TABLE `gibbonStaff`
  MODIFY `gibbonStaffID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffAbsence`
--
ALTER TABLE `gibbonStaffAbsence`
  MODIFY `gibbonStaffAbsenceID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffAbsenceDate`
--
ALTER TABLE `gibbonStaffAbsenceDate`
  MODIFY `gibbonStaffAbsenceDateID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffAbsenceType`
--
ALTER TABLE `gibbonStaffAbsenceType`
  MODIFY `gibbonStaffAbsenceTypeID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gibbonStaffApplicationForm`
--
ALTER TABLE `gibbonStaffApplicationForm`
  MODIFY `gibbonStaffApplicationFormID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffApplicationFormFile`
--
ALTER TABLE `gibbonStaffApplicationFormFile`
  MODIFY `gibbonStaffApplicationFormFileID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffContract`
--
ALTER TABLE `gibbonStaffContract`
  MODIFY `gibbonStaffContractID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffCoverage`
--
ALTER TABLE `gibbonStaffCoverage`
  MODIFY `gibbonStaffCoverageID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffCoverageDate`
--
ALTER TABLE `gibbonStaffCoverageDate`
  MODIFY `gibbonStaffCoverageDateID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffDuty`
--
ALTER TABLE `gibbonStaffDuty`
  MODIFY `gibbonStaffDutyID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffDutyPerson`
--
ALTER TABLE `gibbonStaffDutyPerson`
  MODIFY `gibbonStaffDutyPersonID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffJobOpening`
--
ALTER TABLE `gibbonStaffJobOpening`
  MODIFY `gibbonStaffJobOpeningID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStaffUpdate`
--
ALTER TABLE `gibbonStaffUpdate`
  MODIFY `gibbonStaffUpdateID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonString`
--
ALTER TABLE `gibbonString`
  MODIFY `gibbonStringID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStudentEnrolment`
--
ALTER TABLE `gibbonStudentEnrolment`
  MODIFY `gibbonStudentEnrolmentID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStudentNote`
--
ALTER TABLE `gibbonStudentNote`
  MODIFY `gibbonStudentNoteID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonStudentNoteCategory`
--
ALTER TABLE `gibbonStudentNoteCategory`
  MODIFY `gibbonStudentNoteCategoryID` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gibbonSubstitute`
--
ALTER TABLE `gibbonSubstitute`
  MODIFY `gibbonSubstituteID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTheme`
--
ALTER TABLE `gibbonTheme`
  MODIFY `gibbonThemeID` int(4) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `gibbonTT`
--
ALTER TABLE `gibbonTT`
  MODIFY `gibbonTTID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTColumn`
--
ALTER TABLE `gibbonTTColumn`
  MODIFY `gibbonTTColumnID` int(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTColumnRow`
--
ALTER TABLE `gibbonTTColumnRow`
  MODIFY `gibbonTTColumnRowID` int(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTDay`
--
ALTER TABLE `gibbonTTDay`
  MODIFY `gibbonTTDayID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTDayDate`
--
ALTER TABLE `gibbonTTDayDate`
  MODIFY `gibbonTTDayDateID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTDayRowClass`
--
ALTER TABLE `gibbonTTDayRowClass`
  MODIFY `gibbonTTDayRowClassID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTDayRowClassException`
--
ALTER TABLE `gibbonTTDayRowClassException`
  MODIFY `gibbonTTDayRowClassExceptionID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTImport`
--
ALTER TABLE `gibbonTTImport`
  MODIFY `gibbonTTImportID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTSpaceBooking`
--
ALTER TABLE `gibbonTTSpaceBooking`
  MODIFY `gibbonTTSpaceBookingID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonTTSpaceChange`
--
ALTER TABLE `gibbonTTSpaceChange`
  MODIFY `gibbonTTSpaceChangeID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUnit`
--
ALTER TABLE `gibbonUnit`
  MODIFY `gibbonUnitID` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUnitBlock`
--
ALTER TABLE `gibbonUnitBlock`
  MODIFY `gibbonUnitBlockID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUnitClass`
--
ALTER TABLE `gibbonUnitClass`
  MODIFY `gibbonUnitClassID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUnitClassBlock`
--
ALTER TABLE `gibbonUnitClassBlock`
  MODIFY `gibbonUnitClassBlockID` int(14) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUnitOutcome`
--
ALTER TABLE `gibbonUnitOutcome`
  MODIFY `gibbonUnitOutcomeID` int(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gibbonUsernameFormat`
--
ALTER TABLE `gibbonUsernameFormat`
  MODIFY `gibbonUsernameFormatID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gibbonYearGroup`
--
ALTER TABLE `gibbonYearGroup`
  MODIFY `gibbonYearGroupID` int(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
