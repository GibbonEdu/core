<?php	
use Gibbon\trans ;
use Gibbon\helper ;
			
//Proceed!
$el->student->getEnrolment();


if (! $el->student->validEnrolment) {
	$this->displayMessage('The selected record does not exist, or you do not have access to it.');
} else {
	$studentImage = $el->student->getField('image_240') ;
	
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Student';
	$trail->addTrail('View Student Profiles', '/index.php?q=/modules/Students/student_view.php');
	
	$this->h2(array('Student: %1$s', array($el->student->formatName().' ('.$el->student->getField('username').')')));

	$this->render('student.brief.details', $el);

	$this->render('student.brief.adults', $el);

	//Set sidebar
	$this->session->set('sidebarExtra', helper::getUserPhoto($el->student->getField('image_240'), 240));
}


