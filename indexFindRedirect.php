<?php

@session_start();
include './config.php';

$type = substr($_GET['id'], 0, 3);
$id = substr($_GET['id'], 4);

if ($_SESSION[$guid]['absoluteURL'] == '') {
    $URL = './index.php';
} else {
    if ($type == 'Stu') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Act') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$id;
    } elseif ($type == 'Sta') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$id;
    } elseif ($type == 'Cla') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$id;
    }
}

header("Location: {$URL}");
