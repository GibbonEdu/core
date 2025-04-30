<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Forms\FormFactory;
use Gibbon\Domain\Students\StudentGateway;

// Module includes
$scriptPath = __DIR__;
$gibbonRoot = realpath($scriptPath . '/../../');

// Include Gibbon core files
require_once $gibbonRoot . '/gibbon.php';
require_once $gibbonRoot . '/functions.php';
require_once __DIR__ . '/moduleFunctions.php';

// Setup page
$page = $container->get('page');
$session = $container->get('session');
$db = $container->get('db');
$connection2 = $db->getConnection();

// Initialize form factory
$container->add('form', \Gibbon\Forms\DatabaseFormFactory::class)
    ->addArgument($container->get('db'));

// Set page properties
$page->breadcrumbs
    ->add(__('Modules'))
    ->add(__('ChatBot'))
    ->add(__('Assessment Integration'));

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/assessment_integration.php')) {
    $URL = $session->get('absoluteURL') . '/index.php?q=unauthorised.php';
    header("Location: {$URL}");
    exit;
}

$absoluteURL = $session->get('absoluteURL');

// Add stylesheets
$page->stylesheets->add('chatbotStyles', 'modules/ChatBot/css/chatbot.css');
$page->stylesheets->add('assessmentStyles', 'modules/ChatBot/css/assessment-styles.css');

// Add JavaScript files
$page->scripts->add('dialog', 'modules/ChatBot/js/dialog.js');
$page->scripts->add('notifications', 'modules/ChatBot/js/notifications.js');
$page->scripts->add('select2Init', 'modules/ChatBot/js/select2-init.js');
$page->scripts->add('assessmentIntegration', 'modules/ChatBot/js/assessment-integration.js');

$page->addSidebarExtra('<div class="column-no-break">
    <h2>' . __('ChatBot Menu') . '</h2>
    <ul class="moduleMenu">
       <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/chatbot.php">' . __('AI Teaching Assistant') . '</a></li>
        <li class="selected"><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/assessment_integration.php">' . __('Assessment Integration') . '</a></li>
       <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/learning_management.php">' . __('Learning Management') . '</a></li>
        <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/settings.php">' . __('Settings') . '</a></li>
    </ul>
</div>');

echo '<div class="container">';
echo '<h2>' . __('STUDENT ASSESSMENTS') . '</h2>';

$studentGateway = $container->get(StudentGateway::class);

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'surname,preferredName';
$allStudents = $_GET['allStudents'] ?? '';

$criteria = $studentGateway->newQueryCriteria(true)
    ->searchBy($studentGateway->getSearchableColumns(), $search)
    ->sortBy(array_filter(explode(',', $sort)))
    ->filterBy('all', $allStudents)
    ->fromPOST();

$sortOptions = [
    'surname,preferredName' => __('Surname'),
    'preferredName' => __('Given Name'),
    'formGroup' => __('Form Group'),
    'yearGroup' => __('Year Group'),
];

$form = Form::create('filter', $absoluteURL.'/index.php', 'get');
$form->setTitle(__('Filter'));
$form->setClass('noIntBorder w-full');
$form->addHiddenValue('q', '/modules/ChatBot/assessment_integration.php');

$row = $form->addRow();
$row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username, student ID, email, phone number.'));
$row->addTextField('search')->setValue($criteria->getSearchText());

$row = $form->addRow();
$row->addLabel('sort', __('Sort By'));
$row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

$row = $form->addRow();
$row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment.'));
$row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);

$row = $form->addRow();
$row->addSearchSubmit($session, __('Clear Search'));

echo $form->getOutput();

$students = $studentGateway->queryStudentsBySchoolYear($criteria, $session->get('gibbonSchoolYearID'), true);

$table = DataTable::createPaginated('students', $criteria);
$table->setTitle(__('Choose A Student'));
$table->modifyRows($studentGateway->getSharedUserRowHighlighter());
$table->addMetaData('filterOptions', ['all:on' => __('All Students')]);

if ($criteria->hasFilter('all')) {
    $table->addMetaData('filterOptions', [
        'status:full' => __('Status') . ': ' . __('Full'),
        'status:expected' => __('Status') . ': ' . __('Expected'),
        'date:starting' => __('Before Start Date'),
        'date:ended' => __('After End Date'),
    ]);
}

$table->addColumn('student', __('Student'))
    ->sortable(['surname', 'preferredName'])
    ->format(fn($person) => Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>' . Format::userStatusInfo($person) . '</i></small>');

$table->addColumn('yearGroup', __('Year Group'))->sortable();
$table->addColumn('formGroup', __('Form Group'))->sortable();

$table->addActionColumn()
    ->addParam('gibbonPersonID')
    ->addParam('search', $criteria->getSearchText(true))
    ->addParam('sort', $sort)
    ->addParam('allStudents', $allStudents)
    ->format(function($row, $actions) use ($absoluteURL) {
        $actions->addAction('view', __('View'))
            ->setURL($absoluteURL . '/index.php')
            ->addParam('q', '/modules/ChatBot/assessment_integration.php')
            ->addParam('gibbonPersonID', $row['gibbonPersonID']);
    });

echo $table->render($students);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

if (!empty($gibbonPersonID)) {
    include __DIR__ . '/assessment_data_block.php';
}

echo '</div>';

// Add translations last
$translations = [
    'placeholder' => __('Type a student\'s name...'),
    'inputTooShort' => __('Please type at least 2 characters...'),
    'noResults' => __('No students found'),
    'searching' => __('Searching...')
];

$page->scripts->add('translations', 'var select2Translations = ' . json_encode($translations) . ';', ['type' => 'inline']);
