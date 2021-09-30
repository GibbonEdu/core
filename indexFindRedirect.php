<?php
include './gibbon.php';

$type = substr($_GET['fastFinderSearch'] ?? '', 0, 3);
$id = substr($_GET['fastFinderSearch'] ?? '', 4);
$URL = './index.php';

if ($session->has('absoluteURL')) {
    if ($type == 'Stu') {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Act') {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.$id;
    } elseif ($type == 'Sta') {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Cla') {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$id;
    }
}

header("Location: {$URL}");
