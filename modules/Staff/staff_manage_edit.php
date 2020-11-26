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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Staff\StaffContractGateway;
use Gibbon\Domain\Staff\StaffFacilityGateway;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        $search = $_GET['search'] ?? '';
        $allStaff = $_GET['allStaff'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Staff'), 'staff_manage.php', ['search' => $search, 'allStaff' => $allStaff])
            ->add(__('Edit Staff'), 'staff_manage_edit.php');

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonStaffID = $_GET['gibbonStaffID'];
        if ($gibbonStaffID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $staffGateway = $container->get(StaffGateway::class);
            $staff = $staffGateway->selectStaffByStaffID($gibbonStaffID);

            if ($staff->isEmpty()) {
                echo "<div class='error'>";
                echo __('The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $values = $staff->fetch();
                $gibbonPersonID = $values['gibbonPersonID'];

                if ($search != '' or $allStaff != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>".__('Back to Search Results').'</a>';
                    echo '</div>';
                }

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staff_manage_editProcess.php?gibbonStaffID='.$values['gibbonStaffID']."&search=$search&allStaff=$allStaff");
                $form->setTitle(__('General Information'));

                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                $form->addHeaderAction('view', __('View'))
                    ->setURL('/modules/Staff/staff_view_details.php')
                    ->addParam('gibbonStaffID', $values['gibbonStaffID'])
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('search', $search)
                    ->addParam('allStaff', $allStaff)
                    ->displayLabel();

                $form->addRow()->addHeading(__('Basic Information'));

                $row = $form->addRow();
                    $row->addLabel('gibbonPersonName', __('Person'))->description(__('Must be unique.'));
                    $row->addTextField('gibbonPersonName')->readOnly()->setValue(Format::name($values['title'], $values['preferredName'], $values['surname'], 'Staff', false, true));

                $row = $form->addRow();
                    $row->addLabel('initials', __('Initials'))->description(__('Must be unique if set.'));
                    $row->addTextField('initials')->maxlength(4);

                $types = array('Teaching' => __('Teaching'), 'Support' => __('Support'));
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray($types)->placeholder()->required();

                $row = $form->addRow();
                    $row->addLabel('jobTitle', __('Job Title'));
                    $row->addTextField('jobTitle')->maxlength(100);

                $row = $form->addRow();
                    $row->addLabel('dateStart', __('Start Date'))->description(__("Users's first day at school."));
                    $row->addDate('dateStart');

                $row = $form->addRow();
                    $row->addLabel('dateEnd', __('End Date'))->description(__("Users's last day at school."));
                    $row->addDate('dateEnd');

                $form->addRow()->addHeading(__('First Aid'));

                $row = $form->addRow();
                    $row->addLabel('firstAidQualified', __('First Aid Qualified?'));
                    $row->addYesNo('firstAidQualified')->placeHolder();

                $form->toggleVisibilityByClass('firstAid')->onSelect('firstAidQualified')->when('Y');

                $row = $form->addRow()->addClass('firstAid');
                    $row->addLabel('firstAidQualification', __('First Aid Qualification'));
                    $row->addTextField('firstAidQualification')->maxlength(100);

                $row = $form->addRow()->addClass('firstAid');
                    $row->addLabel('firstAidExpiry', __('First Aid Expiry'));
                    $row->addDate('firstAidExpiry');

                $form->addRow()->addHeading(__('Biography'));

                $row = $form->addRow();
                    $row->addLabel('countryOfOrigin', __('Country Of Origin'));
                    $row->addSelectCountry('countryOfOrigin')->placeHolder();

                $row = $form->addRow();
                    $row->addLabel('qualifications', __('Qualifications'));
                    $row->addTextField('qualifications')->maxlength(80);

                $row = $form->addRow();
                    $row->addLabel('biographicalGrouping', __('Grouping'))->description(__('Used to group staff when creating a staff directory.'));
                    $row->addTextField('biographicalGrouping')->maxlength(100);

                $row = $form->addRow();
                    $row->addLabel('biographicalGroupingPriority', __('Grouping Priority'))->description(__('Higher numbers move teachers up the order within their grouping.'));
                    $row->addNumber('biographicalGroupingPriority')->decimalPlaces(0)->maximum(99)->maxLength(2)->setValue('0');

                $row = $form->addRow();
                    $row->addLabel('biography', __('Biography'));
                    $row->addTextArea('biography')->setRows(10);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                $form->loadAllValuesFrom($values);

                echo $form->getOutput();

                $staffFacilityGateway = $container->get(StaffFacilityGateway::class);
                $criteria = $staffFacilityGateway->newQueryCriteria();
                $facilities = $staffFacilityGateway->queryFacilitiesByPerson($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID);

                $table = DataTable::create('facilities');

                $table->addHeaderAction('add', __('Add'))
                    ->setURL('/modules/Staff/staff_manage_edit_facility_add.php')
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('gibbonStaffID', $gibbonStaffID)
                    ->addParam('search', $search)
                    ->displayLabel();

                $table->addColumn('name', __('Name'));
                $table->addColumn('usageType', __('Usage'))->translatable();

                $table->addActionColumn()
                    ->addParam('gibbonSpacePersonID')
                    ->addParam('gibbonStaffID', $gibbonStaffID)
                    ->addParam('search', $search)
                    ->format(function ($room, $actions) use ($guid) {
                        if ($room['usageType'] != 'Roll Group' and $room['usageType'] != 'Timetable') {
                            $actions->addAction('delete', __('Delete'))
                                    ->setURL('/modules/Staff/staff_manage_edit_facility_delete.php');
                        }
                    });

                echo $table->render($facilities);

                if ($highestAction == 'Manage Staff_confidential') {
                    echo '<h3>'.__('Contracts').'</h3>';

                    $contractsGateway = $container->get(StaffContractGateway::class);

                    $criteria = $contractsGateway->newQueryCriteria()
                        ->sortBy(["dateStart"], 'DESC');

                    $contracts = $contractsGateway->queryContractsByStaff($criteria, $gibbonStaffID);

                    $table = DataTable::create('contracts');

                    $table->addHeaderAction('add', __('Add'))
                        ->setURL('/modules/Staff/staff_manage_edit_contract_add.php')
                        ->addParam('gibbonStaffID', $gibbonStaffID)
                        ->addParam('search', $search)
                        ->displayLabel();

                    $table->addColumn('title', __('Title'));
                    $table->addColumn('status', __('Status'));
                    $table->addColumn('dates', __('Dates'))
                        ->format(function ($row) {
                            if ($row["dateEnd"] == '') {
                                return Format::date($row['dateStart']);
                            } else {
                                return Format::dateRange($row['dateStart'], $row['dateEnd']);
                            }
                        });;

                    $table->addActionColumn()
                    ->addParam('gibbonStaffContractID')
                    ->addParam('gibbonStaffID', $gibbonStaffID)
                    ->addParam('search', $search)
                    ->format(function ($staff, $actions) use ($guid) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Staff/staff_manage_edit_contract_edit.php');
                    });

                    echo $table->render($contracts);
                }
            }
        }
    }
}
