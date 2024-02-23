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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Staff\StaffContractGateway;
use Gibbon\Domain\Staff\StaffFacilityGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $search = $_GET['search'] ?? '';
        $allStaff = $_GET['allStaff'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Staff'), 'staff_manage.php', ['search' => $search, 'allStaff' => $allStaff])
            ->add(__('Edit Staff'), 'staff_manage_edit.php');

        //Check if gibbonStaffID specified
        $gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
        if ($gibbonStaffID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            $staffGateway = $container->get(StaffGateway::class);
            $staff = $staffGateway->selectStaffByStaffID($gibbonStaffID);

            if ($staff->isEmpty()) {
                $page->addError(__('The specified record cannot be found.'));
            } else {
                //Let's go!
                $values = $staff->fetch();
                $gibbonPersonID = $values['gibbonPersonID'];

                if ($search != '' or $allStaff != '') {
                    $params = [
                        "search" => $search,
                        "allStaff" => $allStaff,
                    ];
                    $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Staff', 'staff_manage.php')->withQueryParams($params));
                }

                $customFieldHandler = $container->get(CustomFieldHandler::class);

                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/staff_manage_editProcess.php?gibbonStaffID='.$values['gibbonStaffID']."&search=$search&allStaff=$allStaff");
                $form->setTitle(__('General Information'));

                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $session->get('address'));
                $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                $form->addHeaderAction('view', __('View'))
                    ->setURL('/modules/Staff/staff_view_details.php')
                    ->addParam('gibbonStaffID', $values['gibbonStaffID'])
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('search', $search)
                    ->addParam('allStaff', $allStaff)
                    ->displayLabel();

                $form->addRow()->addHeading('Basic Information', __('Basic Information'));

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

                $form->addRow()->addHeading('First Aid', __('First Aid'));

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

                $form->addRow()->addHeading('Biography', __('Biography'));

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

                // Custom Fields
                $customFieldHandler->addCustomFieldsToForm($form, 'Staff', [], $values['fields']);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                $form->loadAllValuesFrom($values);

                echo $form->getOutput();

                $staffFacilityGateway = $container->get(StaffFacilityGateway::class);
                $criteria = $staffFacilityGateway->newQueryCriteria();
                $facilities = $staffFacilityGateway->queryFacilitiesByPerson($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

                $table = DataTable::create('facilities');
                
                $table->setTitle(__('Facilities'));

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
                    ->format(function ($room, $actions) {
                        if ($room['usageType'] != 'Form Group' and $room['usageType'] != 'Timetable') {
                            $actions->addAction('delete', __('Delete'))
                                    ->setURL('/modules/Staff/staff_manage_edit_facility_delete.php');
                        }
                    });

                echo $table->render($facilities);

                if ($highestAction == 'Manage Staff_confidential') {
                    $contractsGateway = $container->get(StaffContractGateway::class);

                    $criteria = $contractsGateway->newQueryCriteria()
                        ->sortBy(["dateStart"], 'DESC');

                    $contracts = $contractsGateway->queryContractsByStaff($criteria, $gibbonStaffID);

                    $table = DataTable::create('contracts');
                    
                    $table->setTitle(__('Contracts'));

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
                    ->format(function ($staff, $actions) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Staff/staff_manage_edit_contract_edit.php');
                    });

                    echo $table->render($contracts);
                }
            }
        }
    }
}
