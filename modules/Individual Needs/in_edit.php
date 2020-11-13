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
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
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
        $gibbonPersonID = $_GET['gibbonPersonID'];

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

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return']);
        }

        
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $student = $result->fetch();

            $search = isset($_GET['search'])? $_GET['search'] : null;
            $source = isset($_GET['source'])? $_GET['source'] : null;
            $gibbonINDescriptorID = isset($_GET['gibbonINDescriptorID'])? $_GET['gibbonINDescriptorID'] : null;
            $gibbonAlertLevelID = isset($_GET['gibbonAlertLevelID'])? $_GET['gibbonAlertLevelID'] : null;
            $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : null;
            $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : null;

            if ($search != '' and $source == '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/in_view.php&search='.$search."'>".__('Back to Search Results').'</a>';
                echo '</div>';
            } elseif (($gibbonINDescriptorID != '' or $gibbonAlertLevelID != '' or $gibbonRollGroupID != '' or $gibbonYearGroupID != '') and $source == 'summary') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/in_summary.php&gibbonINDescriptorID='.$gibbonINDescriptorID.'&gibbonAlertLevelID='.$gibbonAlertLevelID.'&=gibbonRollGroupID'.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID."'>".__('Back to Search Results').'</a>';
                echo '</div>';
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

                $archiveOptions = array_map(function($item) use ($guid) {
                    return $item['archiveTitle'].' ('.dateConvertBack($guid, substr($item['archiveTimestamp'], 0, 10)).')';
                }, $archivedIEPs);

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/in_edit.php&gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
                $form->setClass('blank fullWidth');
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $col = $form->addRow()->addColumn()->addClass('flex justify-end items-center');
                    $col->addLabel('gibbonINArchiveID', __('Archived Plans'))->addClass('mr-1');
                    $col->addSelect('gibbonINArchiveID')
                        ->fromArray(array('' => __('Current Plan')))
                        ->fromArray($archiveOptions)
                        ->setClass('mediumWidth')
                        ->selected($gibbonINArchiveID);
                    $col->addSubmit(__('Go'));

                echo "<div class='linkTop'>";
                echo $form->getOutput();
                echo '</div>';
            }
            
            // DISPLAY STUDENT DATA
            $table = DataTable::createDetails('personal');
            $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', 'true']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('rollGroup', __('Roll Group'));

            echo $table->render([$student]);

            $form = Form::create('individualNeeds', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/in_editProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('w-full blank');
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
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
                            $row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/></a>')
                                ->setURL($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/in_edit_assistant_deleteProcess.php')
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
            $form->addRow()->addSubheading(__('Individual Education Plan'))->setClass('mt-4 mb-2');

            $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

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
            } else {
                if (empty($IEP)) { // New record, get templates if they exist
                    $IEP['targets'] = getSettingByScope($connection2, 'Individual Needs', 'targetsTemplate');
                    $IEP['strategies'] = getSettingByScope($connection2, 'Individual Needs', 'teachingStrategiesTemplate');
                    $IEP['notes'] = getSettingByScope($connection2, 'Individual Needs', 'notesReviewTemplate');
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
            }

            if (empty($gibbonINArchiveID) && ($highestAction == 'Individual Needs Records_viewEdit' || $highestAction == 'Individual Needs Records_viewContribute')) {
                $table->addRow()->addSubmit();
            }

            echo $form->getOutput();
        }
    }
    //Set sidebar
    $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $student['image_240'] ?? '', 240);
}
