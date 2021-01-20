<?php
include './gibbon.php';

$type = substr($_GET['fastFinderSearch'] ?? '', 0, 3);
$id = substr($_GET['fastFinderSearch'] ?? '', 4);
$URL = './index.php';

if ($gibbon->session->has('absoluteURL')) {
    if ($type == 'Stu') {
        $URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Act') {
        $URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$id;
    } elseif ($type == 'Sta') {
        $URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Cla') {
        $URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$id;
    }
}

header("Location: {$URL}");
