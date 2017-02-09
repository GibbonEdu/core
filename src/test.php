<?php

$form = new \Library\Form($pdo, 'schoolYearForm', 'modules/School Admin/schoolYear_manage_addProcess.php');

$form->addHeading('Foo')->append('Foo Bar');

$form->addSubheading('Bar');

$form->addGeneric('readonly', 'Read Only')->setValue('Something!');

$form->addTextField('name', 'Name')->setDescription('Must be unique.')->setValue('foo')->isRequired();

$form->addTextField('description', 'Description'); // OVERWRITTEN

$form->addTextArea('description', 'Description')->setDescription('Say something');

$form->addTextField('sequenceNumber', 'Sequence Number')->setDescription('Must be unique. Controls chronological ordering.')->isRequired();

$form->addSection('Lorem ipsum');

$form->addSelect('status', 'Status')->fromString('Past, Current, Upcoming')->selected('Upcoming')->isRequired();

$form->addHeading('Bar');

$form->addYesNo('active', 'Active')->selected('Yes');

$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
$sql = 'SELECT gibbonRollGroupID as `value`, name FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
$results = $pdo->executeQuery($data, $sql);

$form->addSelect('rollGroup', 'Roll Group')->fromQuery($results);

$form->addAlert('Lorem ipsum');

$form->addSelectSchoolYear('schoolYear', 'School Year')->selected($_SESSION[$guid]['gibbonSchoolYearID']);

$form->addSelectLanguage('language', 'Language')->selected('English')->isRequired();

$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('foo', 'bar');

$form->output();

?>