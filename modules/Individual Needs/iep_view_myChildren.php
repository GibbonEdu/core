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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/iep_view_myChildren.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $entryCount = 0;
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Individual Education Plans').'</div>';
    echo '</div>';

    echo '<p>';
    echo __($guid, 'This section allows you to view individual education plans, where they exist, for children within your family.').'<br/>';
    echo '</p>';

    //Test data access field for permission
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'Access denied.');
        echo '</div>';
    } else {
        //Get child list
        $count = 0;
        $options = array();

        while ($row = $result->fetch()) {
            try {
                $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowChild = $resultChild->fetch()) {
                $options[$rowChild['gibbonPersonID']]=formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
            }
        }

        $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : null;

        if (count($options) == 0) {
            echo "<div class='error'>";
            echo __($guid, 'Access denied.');
            echo '</div>';
        } elseif (count($options) == 1) {
            $gibbonPersonID = key($options);
        } else {
            echo '<h2>';
            echo 'Choose Student';
            echo '</h2>';

            $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/iep_view_myChildren.php');
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder();

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session);

            echo $form->getOutput();
        }

        if ($gibbonPersonID != '' && count($options) > 0) {
            //Confirm access to this student
            try {
                $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultChild->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $rowChild = $resultChild->fetch();

                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo '<h3>';
                    echo __($guid, 'View');
                    echo '</h3>';

                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo '<h3>';
                    echo __($guid, 'View');
                    echo '</h3>';

                    $row = $result->fetch(); ?>	
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Targets') ?></span><br/>
								<?php
                                echo '<p>'.$row['targets'].'</p>'; ?>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Teaching Strategies') ?></span><br/>
								<?php
                                echo '<p>'.$row['strategies'].'</p>'; ?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Notes & Review') ?></span><br/>
								<?php
                                echo '<p>'.$row['notes'].'</p>'; ?>
							</td>
						</tr>
					</table>
					<?php

                }
            }
        }
    }
}
?>
