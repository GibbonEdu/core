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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Module\Activities\EnrolmentGenerator;
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;



if (isActionAccessible($guid, $connection2, '/modules/Activities/enrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Enrolment'));
     
    $page->return->addReturns([
        'error4' => __(''),
    ]);

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $activityStudentGateway = $container->get(ActivityStudentGateway::class);

    $categories = $categoryGateway->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    
    $params = [
        'sidebar' => 'false',
        'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
    ];

    // FILTER
    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/enrolment_manage.php');
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
    $row->addLabel('gibbonActivityCategoryID', __('Category'));
    $row->addSelect('gibbonActivityCategoryID')->fromArray($categories)->required()->placeholder()->selected($params['gibbonActivityCategoryID']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if (empty($params['gibbonActivityCategoryID'])) return;

    // Get groups
    $category = $categoryGateway->getByID($params['gibbonActivityCategoryID']);
    $signUpChoices = $category['signUpChoices'] ?? 3;
    $choiceList = [1 => __('First Choice'), 2 => __('Second Choice'), 3 => __('Third Choice'), 4 => __('Fourth Choice'), 5 => __('Fifth Choice')];

    $activities = $activityGateway->selectActivityDetailsByCategory($params['gibbonActivityCategoryID'])->fetchGroupedUnique();
    $enrolments = $activityStudentGateway->selectEnrolmentsByCategory($params['gibbonActivityCategoryID'])->fetchGroupedUnique();

    $criteria = $activityStudentGateway->newQueryCriteria()->sortBy(['formGroup', 'preferredName', 'surname'])->pageSize(-1);
    $unenrolled = $activityStudentGateway->queryUnenrolledStudentsByCategory($criteria, $params['gibbonActivityCategoryID'])->toArray();

    $enrolments = array_merge($enrolments, $unenrolled);
    
    $groups = [];

    foreach ($enrolments as $person) {
        for ($i = 1; $i <= $signUpChoices; $i++) {
            if (empty($person["choice{$i}"])) continue;
            $person["choice{$i}"] = str_pad($person["choice{$i}"], 8, '0', STR_PAD_LEFT);
            $person["choice{$i}Name"] = $activities[$person["choice{$i}"]]['name'] ?? '';
        }

        $groups[$person['gibbonActivityID']][$person['gibbonPersonID']] = $person;
    }

    // FORM
    $form = MultiPartForm::create('groups', $session->get('absoluteURL').'/modules/Activities/enrolment_manageProcess.php');
    $form->setTitle(__('Manage Enrolment'));
    $form->setClass('blank w-full');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonActivityCategoryID', $params['gibbonActivityCategoryID']);

    $form->addHeaderAction('generate', __('Generate Enrolment'))
        ->setURL('/modules/Activities/choices_manage_generate.php')
        ->addParam('gibbonActivityCategoryID', $params['gibbonActivityCategoryID'])
        ->addParam('sidebar', 'false')
        ->setIcon('run')
        ->displayLabel();

    // Display the drag-drop group editor
    $form->addRow()->addContent($page->fetchFromTemplate('generate.twig.html', [
        'signUpChoices' => $signUpChoices,
        'activities' => $activities,
        'groups'      => $groups,
        'mode' => 'student',
    ]));

    $table = $form->addRow()->addTable()->setClass('smallIntBorder w-full');
    $row = $table->addRow()->addSubmit(__('Submit'));
    
    echo $form->getOutput();
}
