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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\FormGroupTable;

if (isActionAccessible($guid, $connection2, '/modules/Form Groups/formGroups_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        //Fail 0
       $URL .= '&return=error0';
       header("Location: {$URL}");
    } else {
        $page->breadcrumbs
            ->add(__('View Form Groups'), 'formGroups.php');

        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        if ($gibbonFormGroupID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonFormGroupID' => $gibbonFormGroupID);
                if ($highestAction == "View Form Groups_all") {
                    $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonFormGroupID, gibbonSchoolYear.name as yearName, gibbonFormGroup.name, gibbonFormGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPersonIDEA, gibbonPersonIDEA2, gibbonPersonIDEA3, gibbonSpace.name AS space, website
                        FROM gibbonFormGroup
                            JOIN gibbonSchoolYear ON (gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                            LEFT JOIN gibbonSpace ON (gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                        WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonFormGroupID=:gibbonFormGroupID';
                }
                else {
                    $data['gibbonPersonID'] = $session->get('gibbonPersonID');
                    $data['today'] = date('Y-m-d');
                    $sql = "SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonFormGroup.gibbonFormGroupID, gibbonSchoolYear.name as yearName, gibbonFormGroup.name, gibbonFormGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPersonIDEA, gibbonPersonIDEA2, gibbonPersonIDEA3, gibbonSpace.name AS space, website
                        FROM gibbonFormGroup
                            JOIN gibbonSchoolYear ON (gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                            JOIN (
                                SELECT gibbonStudentEnrolment.gibbonPersonID, gibbonStudentEnrolment.gibbonFormGroupID FROM gibbonStudentEnrolment
                                JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
                                ORDER BY gibbonStudentEnrolment.gibbonYearGroupID
                            ) AS students ON (students.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=students.gibbonPersonID)
                            JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            LEFT JOIN gibbonSpace ON (gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                        WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonFormGroup.gibbonFormGroupID=:gibbonFormGroupID
                            AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $row = $result->fetch();

                $page->breadcrumbs->add($row['name']);

                $userGateway = $container->get(UserGateway::class);
                $primaryTutor240 = $userGateway->getByID($row['gibbonPersonIDTutor'])['image_240'];

                //Set up for foramtting
                $linkStaff = isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php');

                $formatStaff = function (&$staff) use ($userGateway, $linkStaff) {
                    $staff = $userGateway->getByID($staff);

                    if ($linkStaff) {
                        $staff = Format::nameLinked($staff['gibbonPersonID'], $staff['title'], $staff['preferredName'], $staff['surname'], 'Staff', false, true);
                    } else {
                        $staff = Format::name($staff['title'], $staff['preferredName'], $staff['surname'], 'Staff', false, true);
                    }
                };

                //Format Tutors
                $tutors = array_filter(array($row['gibbonPersonIDTutor'], $row['gibbonPersonIDTutor2'], $row['gibbonPersonIDTutor3']));
                array_walk($tutors, $formatStaff);

                if (count($tutors) > 1) {
                    $tutors[0] .= __(' (Main Tutor)');
                }

                $row['tutors'] = implode('<br/>', $tutors);

                //Format Educational Assistants
                $eduAssits = array_filter(array($row['gibbonPersonIDEA'], $row['gibbonPersonIDEA2'], $row['gibbonPersonIDEA3']));
                array_walk($eduAssits, $formatStaff);
                $row['educationalAssistants'] = implode('<br/>', $eduAssits);

                //Create Details Table
                $table = DataTable::createDetails('basicInfo');
                $table->setTitle(__('Basic Information'));

                $table->addColumn('name', __('Name'));

                $table->addColumn('tutors', __('Tutors'));

                $table->addColumn('educationalAssistants', __('Educational Assistants'));

                $table->addColumn('space', __('Location'))
                    ->width('100%');

                if (!empty($row['website'])) {
                    $table->addColumn('website', __('Website'))
                        ->format(Format::using('link', ['website', 'website']))
                        ->width('100%');
                }

                echo $table->render([$row]);

                //Create Form
                $sortBy = $_GET['sortBy'] ?? 'rollOrder, surname, preferredName';

                $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setTitle(__('Filters'));
                $form->setClass('noIntBorder fullWidth');

                $form->addHiddenValue('q', "/modules/".$session->get('module')."/formGroups_details.php");
                $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);

                $row = $form->addRow();
                    $row->addLabel('sortBy', __('Sort By'));
                    $row->addSelect('sortBy')->fromArray(array('rollOrder, surname, preferredName' => __('Roll Order'), 'surname, preferredName' => __('Surname'), 'preferredName, surname' => __('Preferred Name')))->selected($sortBy)->required();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit(__('Go'))->prepend(sprintf('<a href="%s" class="right">%s</a> &nbsp;', $session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonFormGroupID=$gibbonFormGroupID", __('Clear Form')));

                echo $form->getOutput();

                // Students
                $table = $container->get(FormGroupTable::class);
                $table->build($gibbonFormGroupID, true, true, $sortBy);

                echo $table->getOutput();

                //Set sidebar
                $session->set('sidebarExtra', Format::userPhoto($primaryTutor240, 240));
            }
        }
    }
}
?>
