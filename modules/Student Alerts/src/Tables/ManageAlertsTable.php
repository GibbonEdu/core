<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Module\StudentAlerts\Tables;

use Gibbon\View\Component;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\UI\Components\Alert;
use Gibbon\Auth\Access\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\StudentAlerts\AlertGateway;

/**
 * ManageAlertsTable
 *  DataTable for listing student alerts and the actions available on each row.
 *
 * @version v30
 * @since   v30
 */
class ManageAlertsTable
{
    protected $session;
    protected $access;
    protected $alertGateway;

    public function __construct(Session $session, Access $access, AlertGateway $alertGateway)
    {
        $this->session = $session;
        $this->access = $access;
        $this->alertGateway = $alertGateway;
    }

    /**
     * Build the alerts table for a school year.
     *
     * @param string $gibbonSchoolYearID
     * @param string $gibbonPersonID
     * @param string $gibbonFormGroupID
     * @param string $gibbonYearGroupID
     * @param string $gibbonYearGroupIDHOY
     * @param bool   $includeHeaderActions
     * @param bool   $includeFilters Table filters.
     * @return DataTable
     */
    public function create($gibbonSchoolYearID, $gibbonPersonID = '', $gibbonFormGroupID = '', $gibbonYearGroupID = '', $gibbonYearGroupIDHOY = '', $includeHeaderActions = true, $includeFilters = true)
    {
        $action = $this->access->get('Student Alerts', 'studentAlerts_manage');
        $gibbonPersonIDCurrent = $this->session->get('gibbonPersonID');

        $criteria = $this->alertGateway->newQueryCriteria(true)
            ->sortBy('status')
            ->sortBy('timestampCreated', 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        $canManageAlerts = $action->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear');
        $createdBy = (!$canManageAlerts && empty($gibbonPersonID) && empty($gibbonFormGroupID) && empty($gibbonYearGroupID))
            ? $gibbonPersonIDCurrent
            : null;

        $alerts = $this->alertGateway->queryAlertsBySchoolYear($criteria, $gibbonSchoolYearID, $createdBy);

        $table = DataTable::createPaginated('manageAlerts', $criteria)->withData($alerts);

        if ($includeHeaderActions) {
            $table->addHeaderAction('add', __('Add Global Alert'))
                ->setURL('/modules/Student Alerts/studentAlerts_add.php')
                ->addParam('gibbonPersonID', $gibbonPersonID)
                ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                ->displayLabel();

            if ($this->access->allows('Student Alerts', 'report_alertsByClass')) {
                $table->addHeaderAction('addClass', __('Add Class Alert'))
                    ->setURL('/modules/Student Alerts/studentAlerts_add.php')
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                    ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                    ->addParam('source', 'class')
                    ->setIcon('add')
                    ->displayLabel();
            }
        }

        $table->modifyRows(function ($alert, $row) {
            if ($alert['status'] == 'Pending') $row->addClass('warning');
            elseif ($alert['status'] == 'Declined') $row->addClass('dull');
            elseif ($alert['status'] == 'Cancelled') $row->addClass('dull bg-stripe');
            return $row;
        });

        if ($includeFilters) {
            $table->addMetaData('filterOptions', [
                'scope:global'      => __('Scope').': '.__('Global'),
                'scope:class'       => __('Scope').': '.__('Class'),
                'status:approved'   => __('Status').': '.__('Approved'),
                'status:pending'    => __('Status').': '.__('Pending'),
                'status:declined'   => __('Status').': '.__('Declined'),
                'status:cancelled'  => __('Status').': '.__('Cancelled'),
                'context:automatic' => __('Automatic'),
                'context:manual'    => __('Manual'),
            ]);
        }

        $table->addColumn('tag', __('Tag'))
            ->context('primary')
            ->width('8%')
            ->format(function ($values) {
                return Component::render(Alert::class, [
                    'color'   => $values['levelColor'] ?? $values['color'],
                    'colorBG' => $values['levelColorBG'] ?? $values['colorBG'],
                    'title' => $values['type'] ?? '',
                    'large' => true,
                ] + $values);
            });

        $table->addColumn('student', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->context('primary')
            ->format(function ($values) {
                return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true, ['subpage' => 'Personal']);
            })
            ->formatDetails(function ($values) {
                return Format::small($values['formGroup']);
            });

        $table->addColumn('class', __('Class'))
            ->sortable(['courseName', 'className'])
            ->format(function ($values) {
                return !empty($values['gibbonCourseClassID'])
                    ? Format::courseClassName($values['courseName'], $values['className'])
                    : '';
            });

        $table->addColumn('type', __('Type'))
            ->description(__('Level'))
            ->formatDetails(function ($values) {
                return Format::small($values['level']);
            });

        $table->addColumn('teacher', __('Created By'))
            ->description(__('Status'))
            ->context('secondary')
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->format(function ($values) {
                if ($values['context'] == 'Automatic') return Format::tag(__('Automatic'), 'empty');
                return Format::name($values['titleCreator'], $values['preferredNameCreator'], $values['surnameCreator'], 'Staff');
            })
            ->formatDetails(function ($values) {
                return $values['context'] != 'Automatic'
                    ? Format::small($values['status'])
                    : '';
            });

        $table->addColumn('comment', __('Comment'))
            ->format(function ($values) {
                if (empty($values['comment'])) return '';
                return Format::tooltip(
                    icon('solid', 'chat-bubble-text', 'text-gray-500 size-5'),
                    '<div class="p-4 w-72">'.$values['comment'].'</div>',
                    'p-2', 'white');
            });

        $table->addColumn('timestampCreated', __('Date Recorded'))
            ->context('primary')
            ->format(function ($alert) {
                return Format::date($alert['timestampCreated']);
            });

        $accessAll = $action->allows('Manage Student Alerts_all');
        $accessHeadOfYear = $action->allows('Manage Student Alerts_headOfYear');
        $accessMy = $action->allows('Manage Student Alerts_my');

        $table->addActionColumn()
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('gibbonAlertID')
            ->format(function ($alert, $actions) use ($accessAll, $accessHeadOfYear, $accessMy, $gibbonPersonIDCurrent, $gibbonYearGroupIDHOY) {
                $accessHOY = $accessHeadOfYear && $alert['gibbonYearGroupID'] == $gibbonYearGroupIDHOY;
                $accessCreator = $accessMy && $alert['gibbonPersonIDCreated'] == $gibbonPersonIDCurrent;

                if (($accessAll || $accessHOY) && $alert['status'] == 'Pending') {
                    $actions->addAction('approve', __('Approve'))
                        ->setURL('/modules/Student Alerts/studentAlerts_manage_status.php')
                        ->addParam('status', 'Approved')
                        ->setIcon('accept');

                    $actions->addAction('decline', __('Decline'))
                        ->setURL('/modules/Student Alerts/studentAlerts_manage_status.php')
                        ->addParam('status', 'Declined')
                        ->setIcon('reject');
                }

                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Student Alerts/studentAlerts_manage_view.php');

                if ($alert['context'] == 'Automatic') return;

                if ($accessAll || $accessHOY || ($accessCreator && $alert['status'] == 'Pending')) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Student Alerts/studentAlerts_edit.php');
                }

                if ((($accessAll || $accessHOY) && $alert['status'] == 'Approved') || ($accessCreator && $alert['status'] == 'Pending') || ($accessCreator && $alert['status'] == 'Approved' && !empty($alert['gibbonCourseClassID']))) {
                    $actions->addAction('cancel', __('Cancel'))
                        ->setURL('/modules/Student Alerts/studentAlerts_manage_status.php')
                        ->addParam('status', 'Cancelled')
                        ->setIcon('reject');
                }

                if (($accessAll || $accessHOY) && ($alert['status'] == 'Declined' || $alert['status'] == 'Cancelled')) {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Student Alerts/studentAlerts_delete.php');
                }
            });

        return $table;
    }
}
