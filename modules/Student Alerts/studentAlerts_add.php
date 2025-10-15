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
use Gibbon\Support\Facades\Access;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

if (!isActionAccessible($guid, $connection2, "/modules/Student Alerts/studentAlerts_add.php")) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    $action = Access::get('Student Alerts', 'studentAlerts_add');
    if (empty($action)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $page->breadcrumbs
        ->add(__('Manage Alerts'), 'studentAlerts_manage.php')
        ->add(__('Add'));

    $params = [
        'gibbonPersonID'      => $_REQUEST['gibbonPersonID'] ?? '',
        'gibbonFormGroupID'   => $_REQUEST['gibbonFormGroupID'] ?? '',
        'gibbonYearGroupID'   => $_REQUEST['gibbonYearGroupID'] ?? '',
        'gibbonCourseClassID' => $_REQUEST['gibbonCourseClassID'] ?? '',
        'source'              => $_REQUEST['source'] ?? '',
    ];

    if (!empty($params['source']) || !empty($params['gibbonPersonID']) || !empty($params['gibbonFormGroupID']) || !empty($params['gibbonYearGroupID'])) {
        $url = Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage');

        if (!empty($params['source']) && $params['source'] == 'class') {
            $url = Url::fromModuleRoute('Student Alerts', 'report_alertsByClass');
        } elseif (!empty($params['source']) && $params['source'] == 'formGroup') {
            $url = Url::fromModuleRoute('Student Alerts', 'report_alertsByFormGroup');
        }

        $page->navigator->addSearchResultsAction($url->withQueryParams($params));
    }

    $canManageAlerts = $action->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear');
    $isClassAlert = !empty($params['gibbonCourseClassID']) || (!empty($params['source']) && $params['source'] == 'class');

    $form = Form::create('addAlert', Url::fromModuleRoute('Student Alerts', 'studentAlerts_addProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('source', $params['source']);
    $form->addHiddenValue('gibbonFormGroupID', $params['gibbonFormGroupID']);
    $form->addHiddenValue('gibbonYearGroupID', $params['gibbonYearGroupID']);

    if ($isClassAlert) {
        $form->addRow()
            ->addHeading('Add Alert', __('Add Alert'))
            ->append(Format::alert(__('This will create a class alert. These students alerts will not automatically show up on a student profile, but can be collected and used to determine when a global alert may be necessary.'), 'message'));

        $row = $form->addRow();
            $row->addLabel('gibbonCourseClassID', __('Class'));
            $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'), ['allClasses' => $canManageAlerts])
                ->placeholder()
                ->selected($params['gibbonCourseClassID'])
                ->required()
                ->setAttribute('hx-post', Url::fromModuleRoute('Student Alerts', 'studentAlerts_addAjax')->directLink())
                ->setAttribute('hx-trigger', 'load,input from:#gibbonCourseClassID changed delay:500ms')
                ->setAttribute('hx-target', '.studentSelect .personSelect')
                ->setAttribute('hx-include', '[name="gibbonCourseClassID"],[name="gibbonPersonID"]');

        $form->toggleVisibilityByClass('studentSelect')->onSelect('gibbonCourseClassID')->whenNot('');

        $row = $form->addRow()->addClass('studentSelect');
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectPerson('gibbonPersonID')->fromArray([$params['gibbonPersonID'] => ''])->required();
    } else {
        $form->addRow()
            ->addHeading('Add Alert', __('Add Alert'))
            ->append(Format::alert(__('This will create a global alert. When approved, these student alerts will show up on a student profile. Global alerts can be used to draw attention to a particular concern or important status for a student.'), 'warning'));

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['byForm' => true, 'gibbonYearGroupID' => $params['gibbonYearGroupID']])->placeholder()->selected($params['gibbonPersonID'])->required();
    }

    $alertTypes = $container->get(AlertTypeGateway::class)->selectActiveAlertTypes($canManageAlerts)->fetchAll();
    $alertLevels = array_column(array_filter($alertTypes, function ($alert) {
        return $alert['useLevels'] == 'Y';
    }), 'name');

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')
            ->fromArray($alertTypes, 'name', 'name')
            ->placeholder()
            ->required();
    
    $form->toggleVisibilityByClass('useLevels')->onSelect('type')->when($alertLevels);

    $row = $form->addRow()->addClass('useLevels');
    $row->addLabel('level', __('Level'));
    $row->addSelect('level')->fromArray(['High' => __('High'), 'Medium' => __('Medium'), 'Low' => __('Low')])->placeholder()->required();

    if ($canManageAlerts) {
        $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray(['Approved' => __('Approved'), 'Pending' => __('Pending')])->required()->selected('Approved');
    } else if ($isClassAlert) {
        $form->addHiddenValue('status', 'Approved');
    }

    // $row = $form->addRow();
    //     $row->addLabel('dateStart', __('Start Date'))->description(__('If the alert is for a specified period'));
    //     $row->addDate('dateStart');

    // $row = $form->addRow();
    //     $row->addLabel('dateEnd', __('End Date'))->description(__('If the alert is for a specified period')); 
    //     $row->addDate('dateEnd');

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('comment', __('Comment'));
        $col->addTextArea('comment')->setRows(5);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
   
}
