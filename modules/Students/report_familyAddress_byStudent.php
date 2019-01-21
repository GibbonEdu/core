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

$_SESSION[$guid]['report_student_emergencySummary.php_choices'] = '';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_familyAddress_byStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Family Address by Student'));

    echo '<p>';
    echo __('This report attempts to print the family address(es) based on parents who are labelled as Contract Priority 1.');
    echo '</p>';

    $choices = null;
    if (isset($_POST['gibbonPersonID'])) {
        $choices = $_POST['gibbonPersonID'];
    }

    echo '<h2>';
    echo __('Choose Students');
    echo '</h2>';

    $form = Form::create('action',  $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/report_familyAddress_byStudent.php");

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array("allStudents" => false, "byName" => true, "byRoll" => true))->isRequired()->placeholder()->selectMultiple()->selected($choices);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (count($choices) > 0) {
        $_SESSION[$guid]['report_student_emergencySummary.php_choices'] = $choices;

        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        try {
            $data = array();
            $sqlWhere = '(';
            for ($i = 0; $i < count($choices); ++$i) {
                $data[$choices[$i]] = $choices[$i];
                $sqlWhere = $sqlWhere.'gibbonFamilyChild.gibbonPersonID=:'.$choices[$i].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -4);
            $sqlWhere = $sqlWhere.')';
            $sql = "SELECT gibbonFamily.gibbonFamilyID, name, surname, preferredName, nameAddress, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE $sqlWhere ORDER BY name, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $array = array();
        $count = 0;
        while ($row = $result->fetch()) {
            $array[$count]['gibbonFamilyID'] = $row['gibbonFamilyID'];
            $array[$count]['name'] = $row['name'];
            $array[$count]['nameAddress'] = $row['nameAddress'];
            $array[$count]['surname'] = $row['surname'];
            $array[$count]['preferredName'] = $row['preferredName'];
            $array[$count]['homeAddress'] = $row['homeAddress'];
            $array[$count]['homeAddressDistrict'] = $row['homeAddressDistrict'];
            $array[$count]['homeAddressCountry'] = $row['homeAddressCountry'];
            ++$count;
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Family');
        echo '</th>';
        echo '<th>';
        echo __('Selected Students');
        echo '</th>';
        echo '<th>';
        echo __('Home Address');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        $students = '';
        for ($i = 0; $i < count($array); ++$i) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            $current = $array[$i]['gibbonFamilyID'];
            $next = '';
            if (isset($array[($i + 1)]['gibbonFamilyID'])) {
                $next = $array[($i + 1)]['gibbonFamilyID'];
            }
            if ($current == $next) {
                $students .= Format::name('', $array[$i]['preferredName'], $array[$i]['surname'], 'Student').'<br/>';
            } else {
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $array[$i]['name'];
                echo '</td>';
                echo '<td>';
                echo $students;
                echo Format::name('', $array[$i]['preferredName'], $array[$i]['surname'], 'Student').'<br/>';
                echo '</td>';
                echo '<td>';
				//Print Name
				if ($array[$i]['nameAddress'] != '') {
					echo $array[$i]['nameAddress'].'<br/>';
				} elseif ($array[$i]['name'] != '') {
					echo $array[$i]['name'].'<br/>';
				}

				//Print address
				$addressBits = explode(',', trim($array[$i]['homeAddress']));
				$addressBits = array_diff($addressBits, array(''));
				$charsInLine = 0;
				$buffer = '';
				foreach ($addressBits as $addressBit) {
					if ($buffer == '') {
						$buffer = $addressBit;
					} else {
						if (strlen($buffer.', '.$addressBit) > 26) {
							echo $buffer.'<br/>';
							$buffer = $addressBit;
						} else {
							$buffer .= ', '.$addressBit;
						}
					}
				}
				echo $buffer.'<br/>';

				//Print district and country
				if ($array[$i]['homeAddressDistrict'] != '') {
					echo $array[$i]['homeAddressDistrict'].'<br/>';
				}
                if ($array[$i]['homeAddressCountry'] != '') {
                    echo $array[$i]['homeAddressCountry'].'<br/>';
                }
                echo '</td>';
                echo '</tr>';
                $students = '';
                ++$count;
            }
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=3>';
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
