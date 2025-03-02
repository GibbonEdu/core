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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Module\FreeLearning\Tables\UnitHistory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->scripts->add('chart');

        $page->breadcrumbs
             ->add(__m('Unit History By Student'));

        $settingGateway = $container->get(SettingGateway::class);
        $bigDataSchool = $settingGateway->getSettingByScope('Free Learning', 'bigDataSchool');

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? (($bigDataSchool == "Y") ? $session->get('gibbonSchoolYearID') : null);
        $gibbonSchoolYearTermID = !empty($_GET['gibbonSchoolYearTermID']) ? $_GET['gibbonSchoolYearTermID'] : null;

        // FORM
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__m('Choose Student'));

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder w-full');
        $form->addHiddenValue('q', '/modules/Free Learning/report_unitHistory_byStudent.php');

        $disableParentEvidence = false;
        if ($highestAction == 'Unit History By Student_all') {
            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Person'));
                $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['allStudents' => false, 'byName' => true, 'byForm' => true])
                    ->required()
                    ->placeholder()
                    ->selected($gibbonPersonID);
		} elseif ($highestAction == 'Unit History By Student_myChildren') {
            $disableParentEvidence = ($settingGateway->getSettingByScope('Free Learning', 'disableParentEvidence') == "Y");
            $children = $container->get(StudentGateway::class)
                ->selectAnyStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
                ->fetchAll();
            $children = Format::nameListArray($children, 'Student', false, true);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Person'));
                $row->addSelectPerson('gibbonPersonID')
                    ->fromArray($children)
                    ->required()
                    ->placeholder()
                    ->selected(!empty($children[$gibbonPersonID]) ? $gibbonPersonID : null);
		}

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Recent')->selected($gibbonSchoolYearID);

        if ($bigDataSchool == "Y") {
            $dataSelect = [];
            $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as chainedTo, gibbonSchoolYearTerm.gibbonSchoolYearTermID as value, gibbonSchoolYearTerm.name FROM gibbonSchoolYearTerm JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYearTerm.sequenceNumber";
            $rowFilter = $form->addRow();
                $rowFilter->addLabel('gibbonSchoolYearTermID', __('Term'));
                $rowFilter->addSelect('gibbonSchoolYearTermID')
                    ->fromQueryChained($pdo, $sqlSelect, $dataSelect, 'gibbonSchoolYearID')
                    ->placeholder()
                    ->selected($gibbonSchoolYearTermID);
        }

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // Cancel out here if no student selected
        if (empty($gibbonPersonID)) {
            return;
        }

        // Check access for parents
        if ($highestAction == 'Unit History By Student_myChildren' && empty($children[$gibbonPersonID])) {
            echo Format::alert(__('You do not have access to this action.'));
            return;
        }

        $canBrowse = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php');

        // Convert term ID into start and end dates
        $dateStart = null;
        $dateEnd = null;
        if (!is_null($gibbonSchoolYearTermID)) {
            $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
            $term = $schoolYearTermGateway->getByID($gibbonSchoolYearTermID);
            $dateStart = $term['firstDay'];
            $dateEnd = $term['lastDay'];
        }

        echo $container->get(UnitHistory::class)->create($gibbonPersonID, false, $canBrowse, $disableParentEvidence, $gibbonSchoolYearID, $dateStart, $dateEnd);
    }
}
