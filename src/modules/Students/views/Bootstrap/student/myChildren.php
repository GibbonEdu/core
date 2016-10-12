<?php
use Gibbon\Record\familyAdult ;
//Proceed!
$trail = $this->initiateTrail();
$header = $trail->trailEnd = 'View Student Profiles';
$trail->render($this);

$this->render('default.flash');

$this->h2($header);

$obj = new familyAdult($this);
$obj->findBy(array('gibbonPersonID' => $this->session->get('gibbonPersonID'), 'childDataAccess' => 'Y'));


//Test data access field for permission
if ($obj->rowCount() < 1) {
	$this->displayMesage('Access denied.');
} else {

	$students = $obj->getFamilyStudents();

	$this->render('student.myChildren.listStart');

	foreach ($students as $student)
		if ($student->isCurrent())
			$this->render('student.myChildren.listMember', $student);

	$this->render('student.myChildren.listEnd');
}
