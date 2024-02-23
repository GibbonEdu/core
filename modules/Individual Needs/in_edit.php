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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

        if ($highestAction == 'Individual Needs Records_view') {
            $page->breadcrumbs
                ->add(__('View Student Records'), 'in_view.php')
                ->add(__('View Individual Needs Record'));
        } elseif ($highestAction == 'Individual Needs Records_viewContribute') {
            $page->breadcrumbs
                ->add(__('View Student Records'), 'in_view.php')
                ->add(__('View & Contribute To Individual Needs Record'));
        } elseif ($highestAction == 'Individual Needs Records_viewEdit') {
            $page->breadcrumbs
                ->add(__('View Student Records'), 'in_view.php')
                ->add(__('Edit Individual Needs Record'));
        }


            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.name AS yearGroup, gibbonFormGroup.nameShort AS formGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonFormGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $student = $result->fetch();

            $search = $_GET['search'] ?? null;
            $allStudents = $_GET['allStudents'] ?? null;
            $source = $_GET['source'] ?? null;
            $gibbonINDescriptorID = $_GET['gibbonINDescriptorID'] ?? null;
            $gibbonAlertLevelID = $_GET['gibbonAlertLevelID'] ?? null;
            $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? null;
            $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? null;

            
            if ($search != '' and $source == '') {
                $params = [
                    "search" => $search,
                    "allStudents" => $allStudents
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Individual Needs', 'in_view.php')->withQueryParams($params));
            } elseif (($gibbonINDescriptorID != '' or $gibbonAlertLevelID != '' or $gibbonFormGroupID != '' or $gibbonYearGroupID != '') and $source == 'summary') {
                 $params = [
                    "gibbonINDescriptorID" => $gibbonINDescriptorID,
                    "gibbonAlertLevelID" => $gibbonAlertLevelID,
                    "gibbonFormGroupID" => $gibbonFormGroupID,
                    "gibbonYearGroupID" => $gibbonYearGroupID
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Individual Needs', 'in_summary.php')->withQueryParams($params));
            }

            // Grab educational assistant data
            $educationalAssistants = $container->get(INAssistantGateway::class)->selectINAssistantsByStudent($gibbonPersonID)->fetchAll();

            // Grab IEP data
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID";
            $result = $pdo->executeQuery($data, $sql);
            $IEP = ($result->rowCount() > 0)? $result->fetch() : array();

            // Grab archived data
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonINArchiveID as groupBy, gibbonINArchive.* FROM gibbonINArchive WHERE gibbonPersonID=:gibbonPersonID ORDER BY archiveTimestamp DESC";
            $result = $pdo->executeQuery($data, $sql);
            $archivedIEPs = ($result->rowCount() > 0)? $result->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE) : array();

            $gibbonINArchiveID = !empty($_POST['gibbonINArchiveID'])? $_POST['gibbonINArchiveID'] : '';
            $archivedIEP = array('strategies' => '', 'targets' => '', 'notes' => '', 'descriptors' => '');

            if (!empty($archivedIEPs)) {
                // Load current selected archive if exists
                if (isset($archivedIEPs[$gibbonINArchiveID])) {
                    $archivedIEP = $archivedIEPs[$gibbonINArchiveID];
                }

                $archiveOptions = array_map(function($item) {
                    return $item['archiveTitle'].' ('.Format::date(substr($item['archiveTimestamp'], 0, 10)).')';
                }, $archivedIEPs);

                $form = Form::create('action', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/in_edit.php&gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID");
                $form->setClass('blank fullWidth');
                $form->addHiddenValue('address', $session->get('address'));

                $col = $form->addRow()->addColumn()->addClass('flex justify-end items-center');
                    $col->addLabel('gibbonINArchiveID', __('Archived Plans'))->addClass('mr-1');
                    $col->addSelect('gibbonINArchiveID')
                        ->fromArray(array('' => __('Current Plan')))
                        ->fromArray($archiveOptions)
                        ->setClass('mediumWidth')
                        ->selected($gibbonINArchiveID);
                    $col->addSubmit(__('Go'));

                echo "<div class='mb-2'>";
                echo $form->getOutput();
                echo '</div>';
            }

            // DISPLAY STUDENT DATA
            $table = DataTable::createDetails('personal');
            $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', 'true']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('formGroup', __('Form Group'));

            echo $table->render([$student]);

            $form = Form::create('individualNeeds', $session->get('absoluteURL').'/modules/'.$session->get('module')."/in_editProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('w-full blank');
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

            // IN STATUS TABLE - TODO: replace this with OO
            $form->addRow()->addSubheading(__('Individual Needs Status'))->setClass('mt-4 mb-2');

            $statusTableDisabled = (!empty($gibbonINArchiveID) || $highestAction == 'Individual Needs Records_view' || $highestAction == 'Individual Needs Records_viewContribute')? 'disabled' : '';
            $statusTableDescriptors = !empty($gibbonINArchiveID)? $archivedIEP['descriptors'] : '';
            $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID, $statusTableDisabled, $statusTableDescriptors);

            if (!empty($statusTable)) {
                $form->addRow()->addContent($statusTable);
            } else {
                $form->addRow()->addAlert(__('Your request failed due to a database error.'), 'error');
            }

            // LIST EDUCATIONAL ASSISTANTS
            if (empty($gibbonINArchiveID)) {
                $form->addRow()->addSubheading(__('Educational Assistants'))->setClass('mt-4 mb-2');

                if (!empty($educationalAssistants)) {
                    $table = $form->addRow()->addTable()->addClass('smallIntBorder fullWidth colorOddEven');
                    $header = $table->addHeaderRow();
                        $header->addContent(__('Name'));
                        $header->addContent(__('Comment'));
                        if ($highestAction == 'Individual Needs Records_viewEdit') {
                            $header->addContent(__('Action'));
                        }

                    foreach ($educationalAssistants as $ea) {
                        $row = $table->addRow();
                            $row->addContent(Format::name('', $ea['preferredName'], $ea['surname'], 'Staff', true, true));
                            $row->addContent($ea['comment']);

                        if ($highestAction == 'Individual Needs Records_viewEdit') {
                            $row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$session->get('gibbonThemeName').'/img/garbage.png"/></a>')
                                ->setURL($session->get('absoluteURL').'/modules/'.$session->get('module').'/in_edit_assistant_deleteProcess.php')
                                ->addParam('address', $_GET['q'])
                                ->addParam('gibbonPersonIDAssistant', $ea['gibbonPersonIDAssistant'])
                                ->addParam('gibbonPersonIDStudent', $gibbonPersonID)
                                ->addConfirmation(__('Are you sure you wish to delete this record?'));
                        }
                    }
                } else {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                }
            }

            // ADD EDUCATIONAL ASSISTANTS
            if (empty($gibbonINArchiveID) && $highestAction == 'Individual Needs Records_viewEdit') {
                $form->addRow()->addSubheading(__('Add New Assistants'))->setClass('mt-4 mb-2');

                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

                $row = $table->addRow();
                    $row->addLabel('staff', __('Staff'))->addClass('w-1/2');
                    $row->addSelectStaff('staff')->selectMultiple()->addClass('w-full');

                $row = $table->addRow();
                    $row->addLabel('comment', __('Comment'));
                    $row->addTextArea('comment')->setRows(4)->addClass('w-full');
            }

            // DISPLAY AND EDIT IEP


            $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth mt-2');

            $table->addRow()->addHeading('Individual Education Plan', __('Individual Education Plan'))->setClass('mt-4 mb-2');

            if (!empty($gibbonINArchiveID)) {
                // ARCHIVED IEP
                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Targets'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    $col->addContent($archivedIEP['targets'])->wrap('<p>', '</p>');

                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Teaching Strategies'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    $col->addContent($archivedIEP['strategies'])->wrap('<p>', '</p>');

                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Notes & Review'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    $col->addContent($archivedIEP['notes'])->wrap('<p>', '</p>');

                // CUSTOM FIELDS
                $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Individual Needs', ['table' => $table, 'readonly' => true], $archivedIEP['fields']);
            } else {
                if (empty($IEP)) { // New record, get templates if they exist
                    $settingGateway = $container->get(SettingGateway::class);
                    $IEP['targets'] = $settingGateway->getSettingByScope('Individual Needs', 'targetsTemplate');
                    $IEP['strategies'] = $settingGateway->getSettingByScope('Individual Needs', 'teachingStrategiesTemplate');
                    $IEP['notes'] = $settingGateway->getSettingByScope('Individual Needs', 'notesReviewTemplate');
                }

                // CURRENT IEP
                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Targets'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                        $col->addEditor('targets', $guid)->showMedia(true)->setRows(20)->setValue($IEP['targets']);
                    } else {
                        $col->addContent($IEP['targets'])->wrap('<p>', '</p>');
                    }

                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Teaching Strategies'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    if ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                        $col->addEditor('strategies', $guid)->showMedia(true)->setRows(20)->setValue($IEP['strategies']);
                    } else {
                        $col->addContent($IEP['strategies'])->wrap('<p>', '</p>');
                    }

                $col = $table->addRow()->addColumn();
                    $col->addContent(__('Notes & Review'))->wrap('<strong style="font-size: 135%;">', '</strong>');
                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                        $col->addEditor('notes', $guid)->showMedia(true)->setRows(20)->setValue($IEP['notes']);
                    } else {
                        $col->addContent($IEP['notes'])->wrap('<p>', '</p>');
                    }

                // CUSTOM FIELDS
                $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Individual Needs', ['table' => $table], $IEP['fields'] ?? []);
            }

            if (empty($gibbonINArchiveID) && ($highestAction == 'Individual Needs Records_viewEdit' || $highestAction == 'Individual Needs Records_viewContribute')) {
                $form->addRow()->addTable()->setClass('smallIntBorder fullWidth mt-2')->addRow()->addSubmit();
            }

            echo $form->getOutput();
        }
    }
    //Set sidebar
    $session->set('sidebarExtra', Format::userPhoto($student['image_240'] ?? '', 240));
}
