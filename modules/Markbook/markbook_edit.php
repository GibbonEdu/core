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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Get class variable
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        if ($gibbonCourseClassID == '') {
            $gibbonCourseClassID = $session->get('markbookClass') ?? '';
        }

        if ($gibbonCourseClassID == '') {
            $row = getAnyTaughtClass( $pdo, $session->get('gibbonPersonID'), $session->get('gibbonSchoolYearID') );
            $gibbonCourseClassID = $row['gibbonCourseClassID'] ?? '';
        }

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __('Edit Markbook');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            //Get class chooser
            echo classChooser($guid, $pdo, $gibbonCourseClassID);
            return;
        }
        //Check existence of and access to this class.
        else {

            $highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);

            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Edit Markbook');
                echo '</h1>';
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $row = $result->fetch();

                $page->breadcrumbs->add(__('Edit {courseClass} Markbook', [
                    'courseClass' => Format::courseClassName($row['course'], $row['class']),
                ]));

                //Add multiple columns
                if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                    if ($highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_multipleClassesInDepartment' or $highestAction2 == 'Edit Markbook_everything') {
                        //Check highest role in any department
                        $isCoordinator = isDepartmentCoordinator( $pdo, $session->get('gibbonPersonID') );
                        if ($isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything') {
                            $params = [
                                "gibbonCourseClassID" => $gibbonCourseClassID
                            ];
                            $page->navigator->addHeaderAction('addMulti', __('Add Multiple Columns'))
                                ->setURL('/modules/Markbook/markbook_edit_addMulti.php')
                                ->addParams($params)
                                ->setIcon('page_new_multi')
                                ->displayLabel();
                        }
                    }
                }

                //Get teacher list
                $teacherList = getTeacherList( $pdo, $gibbonCourseClassID );
                $teaching = (isset($teacherList[ $session->get('gibbonPersonID') ]) );

                $canEditThisClass = ($teaching == true || $isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything');

                if (!empty($teacherList)) {
                    echo '<h3>';
                    echo __('Teachers');
                    echo '</h3>';
                    echo '<ul>';
                    foreach ($teacherList as $teacher) {
                        echo '<li>'. $teacher . '</li>';
                    }
                    echo '</ul>';
                }

                //Print mark
                echo '<h3>';
                echo __('Markbook Columns');
                echo '</h3>';

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name';
                $result = $connection2->prepare($sql);
                $result->execute($data);

                if ($canEditThisClass) {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";

                    if ($container->get(SettingGateway::class)->getSettingByScope('Markbook', 'enableColumnWeighting') == 'Y') {
                        if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage.php') == true) {
                            echo " | <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Manage Weightings')."<img title='".__('Manage Weightings')."' src='./themes/".$session->get('gibbonThemeName')."/img/run.png'/></a>";
                        }
                    }

                    echo '</div>';
                }

                if ($result->rowCount() < 1) {
                    echo $page->getBlankSlate();
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __('Name/Unit');
                    echo '</th>';
                    echo '<th>';
                    echo __('Type');
                    echo '</th>';
                    echo '<th>';
                    echo __('Date<br/>Added');
                    echo '</th>';
                    echo '<th>';
                    echo __('Date<br/>Complete');
                    echo '</th>';
                    echo '<th style="width:80px">';
                    echo __('Viewable <br/>to Students');
                    echo '</th>';
                    echo '<th style="width:80px">';
                    echo __('Viewable <br/>to Parents');
                    echo '</th>';
                    echo '<th style="width:125px">';
                    echo __('Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($row = $result->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo '<b>'.$row['name'].'</b><br/>';
                        $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            echo $unit[0];
                        }
                        if (isset($unit[1])) {
                            echo '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo $row['type'];
                        echo '</td>';
                        echo '<td>';
                        if (!empty($row['date']) && $row['date'] != '0000-00-00') {
                            echo Format::date($row['date']);
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($row['complete'] == 'Y') {
                            echo Format::date($row['completeDate']);
                        }
                        echo '</td>';
                        echo '<td>';
                        echo Format::yesNo($row['viewableStudents']);
                        echo '</td>';
                        echo '<td>';
                        echo Format::yesNo($row['viewableParents']);
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                        echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__('Enter Data')."' src='./themes/".$session->get('gibbonThemeName')."/img/markbook.png'/></a> ";
                        echo "<a href='".$session->get('absoluteURL').'/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID='.$row['gibbonMarkbookColumnID']."&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_edit.php'><img title='".__('Export to Excel')."' src='./themes/".$session->get('gibbonThemeName')."/img/download.png'/></a>";
                        echo '</td>';
                        echo '</tr>';

                        ++$count;
                    }
                    echo '</table>';
                }

                echo '<br/>&nbsp;<br/>';

                if ($canEditThisClass) {
                    echo '<h3>';
                    echo __('Copy Markbook Columns');
                    echo '</h1>';

                    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php?q=/modules/Markbook/markbook_edit_copy.php&gibbonCourseClassID='.$gibbonCourseClassID);
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->setClass('noIntBorder fullWidth');

                    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/applicationForm_manage.php');

                    $col = $form->addRow()->addColumn()->addClass('inline right');
                        $col->addContent(__('Copy from').' '.__('Class').': &nbsp;');
                        $col->addSelectClass('gibbonMarkbookCopyClassID', $session->get('gibbonSchoolYearID'))->setClass('mediumWidth');
                        $col->addSubmit(__('Go'));

                    echo $form->getOutput();
                }
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'markbook_edit.php'));
}
