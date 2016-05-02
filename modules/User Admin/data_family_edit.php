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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/data_family_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/data_family.php'>".__($guid, 'Family Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonFamilyUpdateID = $_GET['gibbonFamilyUpdateID'];
    if ($gibbonFamilyUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID);
            $sql = 'SELECT gibbonFamilyUpdate.gibbonFamilyID, gibbonFamily.name AS name, gibbonFamily.nameAddress AS nameAddress, gibbonFamily.homeAddress AS homeAddress, gibbonFamily.homeAddressDistrict AS homeAddressDistrict, gibbonFamily.homeAddressCountry AS homeAddressCountry, gibbonFamily.languageHomePrimary AS languageHomePrimary, gibbonFamily.languageHomeSecondary AS languageHomeSecondary, gibbonFamilyUpdate.nameAddress AS newnameAddress, gibbonFamilyUpdate.homeAddress AS newhomeAddress, gibbonFamilyUpdate.homeAddressDistrict AS newhomeAddressDistrict, gibbonFamilyUpdate.homeAddressCountry AS newhomeAddressCountry, gibbonFamilyUpdate.languageHomePrimary AS newlanguageHomePrimary, gibbonFamilyUpdate.languageHomeSecondary AS newlanguageHomeSecondary FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/data_family_editProcess.php?gibbonFamilyUpdateID=$gibbonFamilyUpdateID" ?>">
				<?php
                echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Field');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Current Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'New Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Accept');
            echo '</th>';
            echo '</tr>';

            $rowNum = 'even';

                    //COLOR ROW BY STATUS!
                    echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Address Name');
            echo '</td>';
            echo '<td>';
            echo $row['nameAddress'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['nameAddress'] != $row['newnameAddress']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newnameAddress'];
            echo '</td>';
            echo '<td>';
            if ($row['nameAddress'] != $row['newnameAddress']) {
                echo "<input checked type='checkbox' name='newnameAddressOn'><input name='newnameAddress' type='hidden' value='".htmlprep($row['newnameAddress'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'Home Address');
            echo '</td>';
            echo '<td>';
            echo $row['homeAddress'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['homeAddress'] != $row['newhomeAddress']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newhomeAddress'];
            echo '</td>';
            echo '<td>';
            if ($row['homeAddress'] != $row['newhomeAddress']) {
                echo "<input checked type='checkbox' name='newhomeAddressOn'><input name='newhomeAddress' type='hidden' value='".htmlprep($row['newhomeAddress'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Home Address (District)');
            echo '</td>';
            echo '<td>';
            echo $row['homeAddressDistrict'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['homeAddressDistrict'] != $row['newhomeAddressDistrict']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newhomeAddressDistrict'];
            echo '</td>';
            echo '<td>';
            if ($row['homeAddressDistrict'] != $row['newhomeAddressDistrict']) {
                echo "<input checked type='checkbox' name='newhomeAddressDistrictOn'><input name='newhomeAddressDistrict' type='hidden' value='".htmlprep($row['newhomeAddressDistrict'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'Home Address (Country)');
            echo '</td>';
            echo '<td>';
            echo $row['homeAddressCountry'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['homeAddressCountry'] != $row['newhomeAddressCountry']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newhomeAddressCountry'];
            echo '</td>';
            echo '<td>';
            if ($row['homeAddressCountry'] != $row['newhomeAddressCountry']) {
                echo "<input checked type='checkbox' name='newhomeAddressCountryOn'><input name='newhomeAddressCountry' type='hidden' value='".htmlprep($row['newhomeAddressCountry'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Home Language - Primary');
            echo '</td>';
            echo '<td>';
            echo $row['languageHomePrimary'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['languageHomePrimary'] != $row['newlanguageHomePrimary']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newlanguageHomePrimary'];
            echo '</td>';
            echo '<td>';
            if ($row['languageHomePrimary'] != $row['newlanguageHomePrimary']) {
                echo "<input checked type='checkbox' name='newlanguageHomePrimaryOn'><input name='newlanguageHomePrimary' type='hidden' value='".htmlprep($row['newlanguageHomePrimary'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Home Language - Secondary');
            echo '</td>';
            echo '<td>';
            echo $row['languageHomeSecondary'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['languageHomeSecondary'] != $row['newlanguageHomeSecondary']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newlanguageHomeSecondary'];
            echo '</td>';
            echo '<td>';
            if ($row['languageHomeSecondary'] != $row['newlanguageHomeSecondary']) {
                echo "<input checked type='checkbox' name='newlanguageHomeSecondaryOn'><input name='newlanguageHomeSecondary' type='hidden' value='".htmlprep($row['newlanguageHomeSecondary'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td class='right' colspan=4>";
            echo "<input name='gibbonFamilyID' type='hidden' value='".$row['gibbonFamilyID']."'>";
            echo "<input name='address' type='hidden' value='".$_GET['q']."'>";
            echo "<input type='submit' value='Submit'>";
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            ?>
			</form>
			<?php

        }
    }
}
?>