<?php

echo '<br/>';

// TEST 1 ------------------

$form = new \Library\Forms\Form('testForm', 'index.php?q=/src/test.php');

$form->setClass('noIntBorder fullWidth');

$row = $form->addRow();
	$row->addLabel('search', 'Search')->description('Preferred, surname, username.');
	$row->addTextField('search')->isRequired();

$form->addRow()->addSubmit();

echo $form->getOutput();

echo '<br/>';



// TEST 2 ------------------

$form = new \Library\Forms\Form('testForm2', 'index.php?q=/src/test.php');

$form->addRow()->addHeading('Foo Bar');

$form->addRow()->addSubheading('Foo Bar')->append('some text');

$row = $form->addRow();
	$row->addLabel('name', 'Name')->description('Must be unique.');
	$row->addTextField('name')->isRequired()->setClass('standardWidth');

$row = $form->addRow();
	$row->addLabel('nameShort', 'Short Name');
	$row->addTextField('nameShort')->isRequired()->maxLength(8);

$row = $form->addRow();
	$row->addLabel('description', 'Description');
	$row->addTextArea('description');

$form->addRow()->addHeading('Foo Bar');

$form->addRow()->addContent('This is an example of random content.');

$row = $form->addRow()->setClass('toggled');
	$row->addLabel('schoolYear', 'School Year');
	$row->addSelectSchoolYear('schoolYear', $pdo)->isRequired();

$row = $form->addRow()->setClass('toggled');
	$row->addLabel('language', 'Language');
	$row->addSelectLanguage('language', $pdo)->isRequired();

$row = $form->addRow()->setClass('toggled');
	$row->addLabel('status', 'Status');
	$row->addSelect('status')->fromString('Past, Current, Upcoming')->selected('Upcoming')->isRequired();

$form->addRow()->addAlert('Lorem ipsum', 'message');

$row = $form->addRow();
	$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
	$sql = 'SELECT gibbonRollGroupID as `value`, name FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
	$results = $pdo->executeQuery($data, $sql);

	$row->addLabel('rollGroup', 'Roll Group');
	$row->addSelect('rollGroup')->fromQuery($results)->isRequired();

$row = $form->addRow();
	$row->addLabel('active', 'Active');
	$row->addYesNo('active')->selected('Yes');

$row = $form->addRow();
	$row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
	$row->addSubmit();

	
$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('foo', 'bar');

echo $form->getOutput();



// TEST 3 ------------------

$form = new \Library\Forms\Form('testForm3', 'index.php?q=/src/test.php');

$form->setClass('noIntBorder fullWidth');

$form->addRow()->addLabel('search1', 'Search')->description('Preferred, surname, username.');
	
$row = $form->addRow();
	$row->addTextField('search')->setClass('')->isRequired()->placeholder('Type something ...');
	$row->addSubmit('Go');

$_SESSION[$guid]['sidebarExtra'] .= $form->getOutput();




// TEST 4 ------------------

$form = new \Library\Forms\Form('testForm4', 'index.php?q=/src/test.php');

$row = $form->addRow();
	$row->addSelectSchoolYear('schoolYear', $pdo)->setClass('')->placeholder('Select a school year ...');
	$row->addSubmit('Go');

$_SESSION[$guid]['sidebarExtra'] .= '<br/>';
$_SESSION[$guid]['sidebarExtra'] .= '<h2>'.__('Example 4').'</h2>';
$_SESSION[$guid]['sidebarExtra'] .= $form->getOutput();

?>