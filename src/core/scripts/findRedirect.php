<?php

$type = substr($_POST['id'], 0, 3);
$id = substr($_POST['id'], 4);


if ($type == 'Stu') {
	$URL = array('q'=>'/modules/Students/student_view_details.php', 'gibbonPersonID'=>$id);
} elseif ($type == 'Act') {
	$URL = array('q'=>'/modules/'.$id);
} elseif ($type == 'Sta') {
	$URL = array('q'=>'/modules/Staff/staff_view_details.php', 'gibbonPersonID'=>$id);
} elseif ($type == 'Cla') {
	$URL = array('q'=>'/modules/Departments/department_course_class.php', 'gibbonCourseClassID'=>$id);
}

$this->redirect($URL);
