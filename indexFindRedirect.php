<?php

use Gibbon\Url;

include './gibbon.php';

$type = substr($_GET['fastFinderSearch'] ?? '', 0, 3);
$id = substr($_GET['fastFinderSearch'] ?? '', 4);
$URL = Url::fromRoute();

if ($gibbon->session->has('absoluteURL')) {
    if ($type == 'Stu') {
        $URL = Url::fromModuleRoute('Students', 'student_view_details')->withQueryParam('gibbonPersonID', $id);
    } elseif ($type == 'Act') {
        $URL = Url::fromRoute()->withQueryParam('q', '/modules/'.$id);
    } elseif ($type == 'Sta') {
        $URL = Url::fromModuleRoute('Staff', 'staff_view_details')->withQueryParam('gibbonPersonID', $id);
    } elseif ($type == 'Cla') {
        $URL = Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParam('gibbonCourseClassID', $id);
    }
}

header("Location: {$URL}");
