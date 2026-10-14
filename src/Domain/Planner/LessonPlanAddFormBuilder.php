<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
    10|the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
    20|*/

namespace Gibbon\Domain\Planner;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use League\Container\Container;

/**
 * Builds the Add Lesson Plan form as the web UI does, including custom fields
 * and Lesson Planner Add hooks from other modules.
 */
class LessonPlanAddFormBuilder
{
    /**
     * Build a canonical add-lesson form (class view) for import column discovery.
     *
     * @param Container $container
     * @return Form
     */
    public static function createForImport(Container $container): Form
    {
        global $guid, $connection2;

        $session = $container->get(Session::class);
        $pdo = $container->get(Connection::class);
        $settingGateway = $container->get(SettingGateway::class);

        $homeworkNameSingular = $settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');

        $form = Form::create('plannerLessonImport', '');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addHiddenValue('address', '/modules/Planner/planner_add.php');

        $form->addRow()->addHeading('Basic Information', __('Basic Information'));

        $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')];
        $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
        $row = $form->addRow();
            $row->addLabel('gibbonCourseClassID', __('Class'));
            $row->addSelect('gibbonCourseClassID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

        $sql = "SELECT GROUP_CONCAT(gibbonCourseClassID SEPARATOR ' ') AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE active='Y' AND running='Y'  GROUP BY gibbonUnit.gibbonUnitID ORDER BY ordering, name";
        $row = $form->addRow();
            $row->addLabel('gibbonUnitID', __('Unit'));
            $row->addSelect('gibbonUnitID')->fromQueryChained($pdo, $sql, [], 'gibbonCourseClassID')->placeholder();

        $row = $form->addRow();
            $row->addLabel('name', __('Lesson Name'));
            $row->addTextField('name')->maxLength(50)->required();

        $row = $form->addRow();
            $row->addLabel('summary', __('Summary'));
            $row->addTextField('summary')->maxLength(255);

        $row = $form->addRow();
            $row->addLabel('date', __('Date'));
            $row->addDate('date')->required();

        $row = $form->addRow();
            $row->addLabel('timeStart', __('Start Time'))->description(__("Format: hh:mm (24hr)"));
            $row->addTime('timeStart')->required();

        $row = $form->addRow();
            $row->addLabel('timeEnd', __('End Time'))->description(__("Format: hh:mm (24hr)"));
            $row->addTime('timeEnd')->required();

        $row = $form->addRow();
            $row->addLabel('gibbonSpaceID', __('Location'));
            $row->addSelectSpace('gibbonSpaceID')->placeholder();

        $form->addRow()->addHeading('Lesson Content', __('Lesson Content'));

        $description = $settingGateway->getSettingByScope('Planner', 'lessonDetailsTemplate');
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('description', __('Lesson Details'));
            $column->addEditor('description', $guid)->setRows(20)->showMedia()->setValue($description);

        $teachersNotes = $settingGateway->getSettingByScope('Planner', 'teachersNotesTemplate');
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('teachersNotes', __('Teacher\'s Notes'));
            $column->addEditor('teachersNotes', $guid)->setRows(5)->showMedia()->setValue($teachersNotes);

        $form->addRow()->addHeading('Homework', __($homeworkNameSingular));

        $form->toggleVisibilityByClass('homework')->onClick('homework')->when('Y');
        $row = $form->addRow();
            $row->addLabel('homework', __('Add {homeworkName}?', ['homeworkName' => __($homeworkNameSingular)]));
            $row->addYesNo('homework')->required()->checked('N');

        $row = $form->addRow()->addClass('homework');
            $row->addLabel('homeworkDueDate', __('Due Date'))->description(__('Date is required, time is optional.'));
            $col = $row->addColumn('homeworkDueDate')->addClass('homework');
            $col->addDate('homeworkDueDate')->addClass('mr-2')->required();
            $col->addTime('homeworkDueDateTime');

        $row = $form->addRow()->addClass('homework');
            $row->addLabel('homeworkTimeCap', __('Time Cap?'))->description(__('The maximum time, in minutes, for students to work on this.'));
            $row->addNumber('homeworkTimeCap');

        $row = $form->addRow()->addClass('homework');
            $column = $row->addColumn();
            $column->addLabel('homeworkDetails', __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]));
            $column->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->required();

        $form->toggleVisibilityByClass('homeworkSubmission')->onClick('homeworkSubmission')->when('Y');
        $row = $form->addRow()->addClass('homework');
            $row->addLabel('homeworkSubmission', __('Online Submission?'));
            $row->addYesNo('homeworkSubmission')->required()->checked('N');

        $row = $form->addRow()->setClass('homeworkSubmission');
            $row->addLabel('homeworkSubmissionDateOpen', __('Submission Open Date'));
            $row->addDate('homeworkSubmissionDateOpen')->required();

        $row = $form->addRow()->setClass('homeworkSubmission');
            $row->addLabel('homeworkSubmissionDrafts', __('Drafts'));
            $row->addSelect('homeworkSubmissionDrafts')->fromArray(['' => __('None'), '1' => __('1'), '2' => __('2'), '3' => __('3')]);

        $row = $form->addRow()->setClass('homeworkSubmission');
            $row->addLabel('homeworkSubmissionType', __('Submission Type'));
            $row->addSelect('homeworkSubmissionType')->fromArray(['Link' => __('Link'), 'File' => __('File'), 'Link/File' => __('Link/File')])->required();

        $row = $form->addRow()->setClass('homeworkSubmission');
            $row->addLabel('homeworkSubmissionRequired', __('Submission Required'));
            $row->addSelect('homeworkSubmissionRequired')->fromArray(['Optional' => __('Optional'), 'Required' => __('Required')])->required();

        if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php')) {
            $form->toggleVisibilityByClass('homeworkCrowdAssess')->onClick('homeworkCrowdAssess')->when('Y');
            $row = $form->addRow()->addClass('homeworkSubmission');
                $row->addLabel('homeworkCrowdAssess', __('Crowd Assessment?'));
                $row->addYesNo('homeworkCrowdAssess')->required()->checked('N');

            $row = $form->addRow()->addClass('homeworkCrowdAssess');
                $row->addLabel('homeworkCrowdAssessControl', __('Access Controls?'))->description(__('Decide who can see this {homeworkName}.', ['homeworkName' => __($homeworkNameSingular)]));
                $column = $row->addColumn()->setClass('flex-col items-end');
                    $column->addCheckbox('homeworkCrowdAssessClassTeacher')->checked(true)->description(__('Class Teacher'))->disabled();
                    $column->addCheckbox('homeworkCrowdAssessClassSubmitter')->checked(true)->description(__('Submitter'))->disabled();
                    $column->addCheckbox('homeworkCrowdAssessClassmatesRead')->description(__('Classmates'));
                    $column->addCheckbox('homeworkCrowdAssessOtherStudentsRead')->description(__('Other Students'));
                    $column->addCheckbox('homeworkCrowdAssessOtherTeachersRead')->description(__('Other Teachers'));
                    $column->addCheckbox('homeworkCrowdAssessSubmitterParentsRead')->description(__('Submitter\'s Parents'));
                    $column->addCheckbox('homeworkCrowdAssessClassmatesParentsRead')->description(__('Classmates\'s Parents'));
                    $column->addCheckbox('homeworkCrowdAssessOtherParentsRead')->description(__('Other Parents'));
        }

        $sharingDefaultStudents = $settingGateway->getSettingByScope('Planner', 'sharingDefaultStudents');
        $sharingDefaultParents = $settingGateway->getSettingByScope('Planner', 'sharingDefaultParents');
        $form->addHiddenValue('viewableStudents', $sharingDefaultStudents);
        $form->addHiddenValue('viewableParents', $sharingDefaultParents);

        $form->addRow()->addHeading('Advanced Options', __('Advanced Options'));

        $form->toggleVisibilityByClass('advanced')->onCheckbox('advanced')->when('Y');
        $row = $form->addRow();
            $row->addCheckbox('advanced')->setValue('Y')->description(__('Show Advanced Options'));

        $form->addRow()->addClass('advanced')->addHeading('Access', __('Access'));

        $row = $form->addRow()->addClass('advanced');
            $row->addLabel('viewableStudents', __('Viewable by Students'));
            $row->addYesNo('viewableStudents')->required()->selected($sharingDefaultStudents);

        $row = $form->addRow()->addClass('advanced');
            $row->addLabel('viewableParents', __('Viewable by Parents'));
            $row->addYesNo('viewableParents')->required()->selected($sharingDefaultParents);

        $row = $form->addRow()->addClass('advanced');
            $row->addLabel('videoLink', __('Online Lesson'))->description(__('Displays a video link for online lessons'));
            $row->addURL('videoLink');

        self::applyLessonPlannerAddHooks($container, $form);

        $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Lesson Plan', [], '');

        return $form;
    }

    /**
     * Let modules add inputs to the add-lesson form (same $form object the UI uses).
     *
     * @param Container $container
     * @param Form $form
     */
    public static function applyLessonPlannerAddHooks(Container $container, Form $form)
    {
        global $guid, $connection2, $session, $pdo, $page;

        $session = $container->get(Session::class);
        $hookGateway = $container->get(HookGateway::class);
        $hooks = $hookGateway->selectHooksByType('Lesson Planner Add')->fetchGroupedUnique();

        foreach ($hooks as $hook) {
            $options = @unserialize($hook['options']);
            if (empty($options) || !is_array($options)) {
                continue;
            }

            $hookPermission = $hookGateway->getHookPermission(
                $hook['gibbonHookID'],
                $session->get('gibbonRoleIDCurrent'),
                $options['sourceModuleName'] ?? '',
                $options['sourceModuleAction'] ?? ''
            );

            if (empty($hookPermission)) {
                continue;
            }

            $include = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
            if (is_file($include)) {
                include $include;
            }
        }
    }
}
