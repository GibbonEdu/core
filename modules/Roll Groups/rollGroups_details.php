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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\RollGroupTable;
use Gibbon\Domain\Students\StudentGateway;


if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
    if ($gibbonRollGroupID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonRollGroupID, gibbonSchoolYear.name as yearName, gibbonRollGroup.name, gibbonRollGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPersonIDEA, gibbonPersonIDEA2, gibbonPersonIDEA3, gibbonSpace.name AS space, website FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) LEFT JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID ORDER BY sequenceNumber, gibbonRollGroup.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
            echo '</div>';
        } else {
            $row = $result->fetch();

            $page->breadcrumbs
                ->add(__('View Roll Groups'), 'rollGroups.php')
                ->add($row['name']);

            echo '<h3>';
            echo __('Basic Information');
            echo '</h3>';

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
            echo '<i>'.$row['name'].'</i>';
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Tutors').'</span><br/>';
            try {
                $dataTutor = array('gibbonPersonID1' => $row['gibbonPersonIDTutor'], 'gibbonPersonID2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonID3' => $row['gibbonPersonIDTutor3']);
                $sqlTutor = 'SELECT gibbonPersonID, surname, preferredName, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3';
                $resultTutor = $connection2->prepare($sqlTutor);
                $resultTutor->execute($dataTutor);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $primaryTutor240 = '';
            while ($rowTutor = $resultTutor->fetch()) {
                if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                    echo "<i><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowTutor['gibbonPersonID']."'>".formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', false, true).'</a></i>';
                } else {
                    echo '<i>'.formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', false, true);
                }
                if ($rowTutor['gibbonPersonID'] == $row['gibbonPersonIDTutor']) {
                    $primaryTutor240 = $rowTutor['image_240'];
                    if ($resultTutor->rowCount() > 1) {
                        echo ' ('.__('Main Tutor').')';
                    }
                }
                echo '</i><br/>';
            }
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Educational Assistants').'</span><br/>';
            try {
                $dataTutor = array('gibbonPersonID1' => $row['gibbonPersonIDEA'], 'gibbonPersonID2' => $row['gibbonPersonIDEA2'], 'gibbonPersonID3' => $row['gibbonPersonIDEA3']);
                $sqlTutor = 'SELECT gibbonPersonID, surname, preferredName, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3';
                $resultTutor = $connection2->prepare($sqlTutor);
                $resultTutor->execute($dataTutor);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowTutor = $resultTutor->fetch()) {
                if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                    echo "<i><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowTutor['gibbonPersonID']."'>".formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', false, true).'</a></i>';
                } else {
                    echo '<i>'.formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', false, true);
                }
                echo '</i><br/>';
            }
            echo '</td>';
            echo '</tr>';
            echo "<td style='width: 33%; vertical-align: top' colspan=3>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Location').'</span><br/>';
            echo '<i>'.$row['space'].'</i>';
            echo '</td>';
            echo '</tr>';
            if ($row['website'] != '') {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                echo "<a target='_blank' href='".$row['website']."'>".$row['website'].'</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            $sortBy = $_GET['sortBy'] ?? 'gibbonPersonID, rollOrder, surname, preferredName'; //GS//
            $listView = $_GET['listView'] ?? 'grid, list'; //GS//

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setTitle(__('Filters'));
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/rollGroups_details.php");
            $form->addHiddenValue('gibbonRollGroupID', $gibbonRollGroupID);

            $row = $form->addRow();
                $row->addLabel('sortBy', __('Sort By'));
                $row->addSelect('sortBy')->fromArray(array('gibbonPersonID, rollOrder, surname, preferredName' => __('ID'), 'rollOrder, surname, preferredName' => __('Roll Order'), 'surname, preferredName' => __('Surname'), 'preferredName, surname' => __('Preferred Name')))->selected($sortBy)->required(); //GS//

            $row = $form->addRow(); //GS//
                $row->addLabel('listView', __('Change View')); //GS//
                $row->addSelect('listView')->fromArray(array('grid' => __('Grid'), 'list' => __('List')))->selected($listView)->required(); //GS//

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit(__('Go'))->prepend(sprintf('<a href="%s" class="right">%s</a> &nbsp;', $_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonRollGroupID=$gibbonRollGroupID", __('Clear Form')));

            echo $form->getOutput();

            // Students //ADAPTED TO //GS//
            if ($listView == 'grid') {
                $table = $container->get(RollGroupTable::class);
                $table->build($gibbonRollGroupID, true, true, $sortBy);
                echo $table->getOutput();
            } else {
                echo '<h2>';
                echo __('Students');
                echo '</h2>';

                // PREPARE QUERY
                $studentGateway = $container->get(StudentGateway::class);

                $criteria = $studentGateway->newQueryCriteria()
                    ->searchBy($studentGateway->getSearchableColumns(), $search)
                    ->sortBy(array_filter(explode(',', $sortBy)))
                    ->filterBy('all', true)
                    ->fromPOST();

                $students = $studentGateway->queryStudentEnrolmentByRollGroup($criteria, $gibbonRollGroupID);

                // DATA TABLE
                $table = DataTable::createPaginated('students', $criteria);

                // COLUMNS
                $table->addColumn('username', __('ID'));
                $table->addColumn('student', __('Student'))
                    ->sortable(['surname', 'preferredName'])
                    ->format(function ($person) {
                        return Format::name('', $person['preferredName'], $person['surname'], 'Student', false, true) . '<br/>'.Format::userStatusInfo($person).'';
                    });
                $table->addColumn('yearGroupName', __('Year Group'));
                //$table->addColumn('rollGroup', __('Roll Group'));

                $table->addActionColumn()
                    ->addParam('gibbonPersonID')
/*DELETE            ->addParam('search', $criteria->getSearchText(true))
                    ->addParam('sort', $sort)
                    ->addParam('allStudents', $canViewFullProfile ? $allStudents : '')
*/                    ->format(function ($row, $actions) {
                        $actions->addAction('view', __('View Details'))
                            ->setURL('/modules/Students/student_view_details.php');
                    });

                echo $table->render($students);

            }

            //Set sidebar
            $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $primaryTutor240, 240);
        }
    }
}
?>
