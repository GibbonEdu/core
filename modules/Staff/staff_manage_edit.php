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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php'>".__($guid, 'Manage Staff')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Staff').'</div>';
        echo '</div>';

        $search = (isset($_GET['search']) ? $_GET['search'] : '');
        $allStaff = (isset($_GET['allStaff']) ? $_GET['allStaff'] : '');

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonStaffID = $_GET['gibbonStaffID'];
        if ($gibbonStaffID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonStaffID' => $gibbonStaffID);
                $sql = 'SELECT gibbonStaff.*, title, surname, preferredName, initials, dateStart, dateEnd FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $values = $result->fetch();
                $gibbonPersonID = $values['gibbonPersonID'];

                if ($search != '' or $allStaff != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }
                echo '<h3>'.__($guid, 'General Information').'</h3>';

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staff_manage_editProcess.php?gibbonStaffID='.$values['gibbonStaffID']."&search=$search&allStaff=$allStaff");

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('smallIntBorder fullWidth');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                $form->addRow()->addHeading(__('Basic Information'));

                $row = $form->addRow();
                    $row->addLabel('gibbonPersonName', __('Person'))->description(__('Must be unique.'));
                    $row->addTextField('gibbonPersonName')->readOnly()->setValue(formatName($values['title'], $values['preferredName'], $values['surname'], 'Staff', false, true));

                $row = $form->addRow();
                    $row->addLabel('initials', __('Initials'))->description(__('Must be unique if set.'));
                    $row->addTextField('initials')->maxlength(4);

                $types = array(__('Basic') => array ('Teaching' => __('Teaching'), 'Support' => __('Support')));
                $sql = "SELECT gibbonRoleID as value, name FROM gibbonRole WHERE category='Staff' ORDER BY name";
                $result = $pdo->executeQuery(array(), $sql);
                $types[__('System Roles')] = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_KEY_PAIR) : array();
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray($types)->placeholder()->isRequired();

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
                    $row->addLabel('biographicalGrouping', __('Grouping'));
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

                echo '<h3>'.__($guid, 'Facilities').'</h3>';
                try {
                    $data = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPersonID3' => $gibbonPersonID, 'gibbonPersonID4' => $gibbonPersonID, 'gibbonPersonID5' => $gibbonPersonID, 'gibbonPersonID6' => $gibbonPersonID, 'gibbonSchoolYearID1' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = '(SELECT gibbonSpace.*, gibbonSpacePersonID, usageType, NULL AS \'exception\' FROM gibbonSpacePerson JOIN gibbonSpace ON (gibbonSpacePerson.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonPersonID=:gibbonPersonID1)
                    UNION
                    (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Roll Group\' AS usageType, NULL AS \'exception\' FROM gibbonRollGroup JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE (gibbonPersonIDTutor=:gibbonPersonID2 OR gibbonPersonIDTutor2=:gibbonPersonID3 OR gibbonPersonIDTutor3=:gibbonPersonID4) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID1)
                    UNION
                    (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Timetable\' AS usageType, gibbonTTDayRowClassException.gibbonPersonID AS \'exception\' FROM gibbonSpace JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND (gibbonTTDayRowClassException.gibbonPersonID=:gibbonPersonID6 OR gibbonTTDayRowClassException.gibbonPersonID IS NULL)) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID5)
                    ORDER BY name';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_facility_add.php&gibbonPersonID=$gibbonPersonID&gibbonStaffID=$gibbonStaffID&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                echo '</div>';

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Usage').'<br/>';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($row = $result->fetch()) {
                        if ($row['exception'] == null) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $row['name'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['usageType'];
                            echo '</td>';
                            echo '<td>';
                            if ($row['usageType'] != 'Roll Group' and $row['usageType'] != 'Timetable')
                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_manage_edit_facility_delete.php&gibbonSpacePersonID='.$row['gibbonSpacePersonID']."&gibbonStaffID=$gibbonStaffID&search=$search&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</table>';
                }


                if ($highestAction == 'Manage Staff_confidential') {
                    echo '<h3>'.__($guid, 'Contracts').'</h3>';
                    try {
                        $data = array('gibbonStaffID' => $gibbonStaffID);
                        $sql = 'SELECT * FROM gibbonStaffContract WHERE gibbonStaffID=:gibbonStaffID ORDER BY dateStart DESC';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_contract_add.php&gibbonStaffID=$gibbonStaffID&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</div>';

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Title');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Status').'<br/>';
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Dates');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Actions');
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
                            ++$count;

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $row['title'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['status'];
                            echo '</td>';
                            echo '<td>';
                            if ($row['dateEnd'] == '') {
                                echo dateConvertBack($guid, $row['dateStart']);
                            } else {
                                echo dateConvertBack($guid, $row['dateStart']).' - '.dateConvertBack($guid, $row['dateEnd']);
                            }
                            echo '</td>';
                            echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_manage_edit_contract_edit.php&gibbonStaffContractID='.$row['gibbonStaffContractID']."&gibbonStaffID=$gibbonStaffID&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>
