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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;


//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_summary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Individual Needs Summary').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    $gibbonINDescriptorID = null;
    if (isset($_GET['gibbonINDescriptorID'])) {
        $gibbonINDescriptorID = $_GET['gibbonINDescriptorID'];
    }
    $gibbonAlertLevelID = null;
    if (isset($_GET['gibbonAlertLevelID'])) {
        $gibbonAlertLevelID = $_GET['gibbonAlertLevelID'];
    }
    $gibbonRollGroupID = null;
    if (isset($_GET['gibbonRollGroupID'])) {
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
    }
    $gibbonYearGroupID = null;
    if (isset($_GET['gibbonYearGroupID'])) {
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
    }

    echo '<h3>';
    echo __($guid, 'Filter');
    echo '</h3>';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth standardForm');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('q', '/modules/Individual Needs/in_summary.php');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    //SELECT FROM ARRAY
    $sql = "SELECT gibbonINDescriptorID as value, name FROM gibbonINDescriptor ORDER BY sequenceNumber";
    $row = $form->addRow();
    	$row->addLabel('gibbonINDescriptorID', __('Descriptor'));
        $row->addSelect('gibbonINDescriptorID')->fromQuery($pdo, $sql)->selected($gibbonINDescriptorID)->placeholder();

    $sql = "SELECT gibbonAlertLevelID as value, name FROM gibbonAlertLevel ORDER BY sequenceNumber";
    $row = $form->addRow();
        $row->addLabel('gibbonAlertLevelID', __('Alert Level'));
        $row->addSelect('gibbonAlertLevelID')->fromQuery($pdo, $sql)->selected($gibbonAlertLevelID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder();
    
    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID)->placeholder();
    
    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));
        
    echo $form->getOutput();

    echo '<h3>';
    echo __($guid, 'Students With Records');
    echo '</h3>';
    echo '<p>';
    echo __($guid, 'Students only show up in this list if they have an Individual Needs record with descriptors set. If a student does not show up here, check in Individual Needs Records.');
    echo '</p>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlWhere = 'AND ';
        if ($gibbonINDescriptorID != '') {
            $data['gibbonINDescriptorID'] = $gibbonINDescriptorID;
            $sqlWhere .= 'gibbonINPersonDescriptor.gibbonINDescriptorID=:gibbonINDescriptorID AND ';
        }
        if ($gibbonAlertLevelID != '') {
            $data['gibbonAlertLevelID'] = $gibbonAlertLevelID;
            $sqlWhere .= 'gibbonINPersonDescriptor.gibbonAlertLevelID=:gibbonAlertLevelID AND ';
        }
        if ($gibbonRollGroupID != '') {
            $data['gibbonRollGroupID'] = $gibbonRollGroupID;
            $sqlWhere .= 'gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND ';
        }
        if ($gibbonYearGroupID != '') {
            $data['gibbonYearGroupID'] = $gibbonYearGroupID;
            $sqlWhere .= 'gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND ';
        }
        if ($sqlWhere == 'AND ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonINPersonDescriptor ON (gibbonINPersonDescriptor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' $sqlWhere ORDER BY rollGroup, surname, preferredName";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Year Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

			//Color rows based on start and end date
			if (!($row['dateStart'] == '' or $row['dateStart'] <= date('Y-m-d')) and ($row['dateEnd'] == '' or $row['dateEnd'] >= date('Y-m-d'))) {
				$rowNum = 'error';
			}

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            echo '<td>';
            echo __($guid, $row['yearGroup']);
            echo '</td>';
            echo '<td>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/in_edit.php&gibbonPersonID='.$row['gibbonPersonID']."&source=summary&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'><img title='Edit Individual Needs Details' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
        }
    }
}
?>
