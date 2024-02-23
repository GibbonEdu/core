<?php

use Gibbon\Http\Url;

include './gibbon.php';

$type = substr($_GET['fastFinderSearch'] ?? '', 0, 3);
$id = substr($_GET['fastFinderSearch'] ?? '', 4);
$URL = Url::fromRoute();

if ($session->has('absoluteURL')) {
    if ($type == 'Stu') {
        $URL = Url::fromModuleRoute('Students', 'student_view_details')->withQueryParam('gibbonPersonID', $id);
    } elseif ($type == 'Act') {
        $URL = Url::fromModuleRoute(strstr($id, '/', true), trim(strstr($id, '/'), '/ '));
    } elseif ($type == 'Sta') {
        $URL = Url::fromModuleRoute('Staff', 'staff_view_details')->withQueryParam('gibbonPersonID', $id);
    } elseif ($type == 'Cla') {
        $URL = Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParam('gibbonCourseClassID', $id);
    } elseif ($type == 'Fac') {
        $URL = Url::fromModuleRoute('Timetable', 'tt_space_view')->withQueryParam('gibbonSpaceID', $id);
    } elseif ($type == 'Dep') {
        $URL = Url::fromModuleRoute('Departments', 'department')->withQueryParam('gibbonDepartmentID', $id);
    }
}

header("Location: {$URL}");
