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

$personID = isset($_GET['gibbonPersonID']) ?  $_GET['gibbonPersonID'] : null ;
$search = isset($_GET['search']) ? $_GET['search'] : null ;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'surname,preferredName';

$form = $this->getForm(null, array('q' => '/modules/Students/student_view.php'), false, 'searchForm');
$form->setStyle('noIntBorder');
$form->setMethod('get');

$form->addElement('hidden', 'q', '/modules/Students/student_view.php');

$el =  $form->addElement('text', 'search', $search);
$el->col1->style = 'width: 30%;';
$el->nameDisplay = 'Search for';
$el->description = 'Preferred, surname, username.';
$el->setMaxLength(20);

$el = $form->addElement('select', 'sort', $sort);
$el->nameDisplay = 'Sort by';
$el->addOption(trans::__('Surname'), 'surname,preferredName');
$el->addOption(trans::__('Preferred Name'), 'preferredName,surname');
$el->addOption(trans::__('Roll Group'), 'rollGroup,surname,preferredName');
$el->addOption(trans::__('Year Group'), 'yearGroup,surname,preferredName');
$el->onChangeSubmit();

$el = $form->addElement('buttons', null);
$el->addButton('clear', 'Clear Search', 'resetBtn');
$el->addButton(null, null, 'submitBtn');

$script = '
<script type="text/javascript">
	$(document).ready(function() {
		$("#_clear").click(function(){
			$("#_search").val(""); 
			$("#_sort").val("surname,preferredName"); 
			$("#searchForm").submit();
		}); 
	});
</script>
';
$form->addElement('script', null, $script);

$form->renderForm('noIntBorder', false);

//Set pagination variable
$where = '`gibbonStudentEnrolment`.`gibbonSchoolYearID` = :schoolYearID
	AND (`dateStart` IS NULL OR `dateStart` <= :dateStart) 
	AND (`dateEnd` IS NULL  OR `dateEnd` >= :dateEnd) 
	AND `gibbonPerson`.`status` = :status';
$data = array('schoolYearID' => $this->session->get('gibbonSchoolYearID'), 'dateStart' => date('Y-m-d'), 'dateEnd' => date('Y-m-d'), 'status' => 'Full');
$w = explode(',', $sort);
$order = array();
foreach($w as $name)
	$order[$name] = 'ASC';
if (! empty($search))
{
	$data['search1'] = '%'.$search.'%';
	$data['search2'] = '%'.$search.'%';
	$data['search3'] = '%'.$search.'%';
	$where .= '
	AND (`preferredName` LIKE :search1 OR `surname` LIKE :search2 OR `username` LIKE :search3)';
}
$record = new person($this);
$join = '
	JOIN `gibbonStudentEnrolment` ON `gibbonPerson`.`gibbonPersonID` = `gibbonStudentEnrolment`.`gibbonPersonID` 
	JOIN `gibbonYearGroup` ON `gibbonStudentEnrolment`.`gibbonYearGroupID` = `gibbonYearGroup`.`gibbonYearGroupID`
	JOIN `gibbonRollGroup` ON `gibbonStudentEnrolment`.`gibbonRollGroupID` = `gibbonRollGroup`.`gibbonRollGroupID` ';
$select = '`gibbonPerson`.`gibbonPersonID`, `gibbonYearGroup`.`nameShort` AS `yearGroup`, `gibbonRollGroup`.`nameShort` AS `rollGroup`';
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
