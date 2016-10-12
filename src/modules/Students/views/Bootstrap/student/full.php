<?php
use Gibbon\trans ;
use Gibbon\pagination ;
use Gibbon\Record\person ;
use Gibbon\Record\familyChild ;

//Proceed!
$trail = $this->initiateTrail();
$header = $trail->trailEnd = 'View Student Profiles';
$trail->render($this);

$this->render('default.flash');

$this->h2($header);


$personID = isset($_GET['gibbonPersonID']) ? $_GET['gibbonPersonID'] : null ;
$search = isset($_GET['search']) ?  $_GET['search'] : null;
$allStudents = isset($_GET['allStudents']) ? $_GET['allStudents'] : 'N';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'surname,preferredName';


$form = $this->getForm(null, array('q' => '/modules/Students/student_view.php'), false, 'searchForm');
$form->setStyle('noIntBorder');
$form->setMethod('get');

$form->addElement('hidden', 'q', '/modules/Students/student_view.php');

$el =  $form->addElement('text', 'search', $search);
$el->col1->style = 'width: 30%;';
$el->nameDisplay = 'Search for';
$el->description = 'Preferred, surname, username, email, phone number, vehicle registration, parent email.';
$el->setMaxLength(20);

$el = $form->addElement('select', 'sort', $sort);
$el->nameDisplay = 'Sort by';
$el->addOption(trans::__('Surname'), 'surname,preferredName');
$el->addOption(trans::__('Preferred Name'), 'preferredName,surname');
$el->addOption(trans::__('Roll Group'), 'rollGroup,surname,preferredName');
$el->addOption(trans::__('Year Group'), 'yearGroup,surname,preferredName');
$el->onChangeSubmit();

$el = $form->addElement('yesno', 'allStudents', $allStudents);
$el->description = 'Include all students, regardless of status and current enrolment. Some data may not display.';
$el->nameDisplay = 'All Students';

$el = $form->addElement('buttons', null);
$el->addButton('clear', 'Clear Search', 'resetBtn');
$el->addButton(null, 'Search', 'submitBtn');

$script = '
<script type="text/javascript">
	$(document).ready(function() {
		$("#_clear").click(function(){
			$("#_search").val(""); 
			$("#_sort").val("surname,preferredName"); 
			$("#_allStudents").val("N"); 
			$("#searchForm").submit();
		}); 
	});
</script>
';
$form->addElement('script', null, $script);

$form->render('noIntBorder', false);

//Set pagination variable
if ($allStudents === 'N') {
	$where = "`gibbonStudentEnrolment`.`gibbonSchoolYearID` = :schoolYearID
		AND (gibbonPerson.`dateStart` IS NULL OR gibbonPerson.`dateStart` <= :dateStart) 
		AND (gibbonPerson.`dateEnd` IS NULL  OR gibbonPerson.`dateEnd` >= :dateEnd) 
		AND `gibbonPerson`.`status` = 'Full'";
	$join = "
		JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
		JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) 
		JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) 
		LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
		LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
		LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent1Fam.contactPriority=1)
		LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full')
		LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent2Fam.contactPriority=2)
		LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full')";
	$select = "DISTINCT `gibbonPerson`.`gibbonPersonID`, `gibbonPerson`.`status`, `gibbonStudentEnrolmentID`, `gibbonPerson`.`surname`, `gibbonPerson`.`preferredName`, `gibbonYearGroup`.`nameShort` AS `yearGroup`, `gibbonRollGroup`.`nameShort` AS `rollGroup`";
	$data = array('schoolYearID' => $this->session->get('gibbonSchoolYearID'), 'dateStart' => date('Y-m-d'), 'dateEnd' => date('Y-m-d'));
}
else
{
	$where = "gibbonRole.category='Student'";
	$join = "
		JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE concat('%', gibbonRole.gibbonRoleID , '%'))
		LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
		LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
		LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent1Fam.contactPriority=1)
		LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full')
		LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent2Fam.contactPriority=2)
		LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full')";
	$select = "`gibbonPerson`.`gibbonPersonID`, `gibbonPerson`.`status`, NULL AS `gibbonStudentEnrolmentID`, `gibbonPerson`.`surname`, `gibbonPerson`.`preferredName`, NULL AS `yearGroup`, NULL AS `rollGroup`";
	$data = array();
}

$w = explode(',', $sort);
$order = array();
foreach($w as $name)
	$order[$name] = 'ASC';

if (! empty($search))
{
	$data['search1'] = '%'.$search.'%';
	$data['search2'] = '%'.$search.'%';
	$data['search3'] = '%'.$search.'%';
	$data['search4'] = '%'.$search.'%';
	$data['search5'] = '%'.$search.'%';
	$data['search6'] = '%'.$search.'%';
	$data['search7'] = '%'.$search.'%';
	$data['search8'] = '%'.$search.'%';
	$data['search9'] = '%'.$search.'%';
	$data['search10'] = '%'.$search.'%';
	$data['search11'] = '%'.$search.'%';
	$data['search12'] = '%'.$search.'%';
	$where .= '
	AND (gibbonPerson.preferredName LIKE :search1 OR gibbonPerson.surname LIKE :search2 OR gibbonPerson.username LIKE :search3 OR gibbonPerson.email LIKE :search4 OR gibbonPerson.emailAlternate LIKE :search5 OR gibbonPerson.phone1 LIKE :search6 OR gibbonPerson.phone2 LIKE :search7 OR gibbonPerson.phone3 LIKE :search8 OR gibbonPerson.phone4 LIKE :search9 OR gibbonPerson.vehicleRegistration LIKE :search10 OR parent1.email LIKE :search11 OR parent2.email LIKE :search11)';
}
$record = new person($this);
$pagin = new pagination($this, $where, $data, $order, $record, $join,  $select);

if ($pagin->get('total') < 1) {
	$this->displayMessage('There are no records to display.');
} else {
	$pagin->printPagination('top', "&search=".$search."&sort=".$sort);

	$this->h3('Choose A Student'); 
	$this->render('student.myChildren.listStart');


	foreach ($pagin->get('results') as $person)
	{
		$student = new familyChild($this);
		$student->findOneBy(array('gibbonPersonID' => $person->gibbonPersonID));
		if ($student->rowCount() === 1)
			$this->render('student.myChildren.listMember', $student);
	}
	
	$this->render('student.myChildren.listEnd');

	$pagin->printPagination('bottom', "&search=".$search."&sort=".$sort);

}
