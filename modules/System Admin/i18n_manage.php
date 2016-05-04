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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Language Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = 'SELECT * FROM gibboni18n ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo '<p>';
    echo __($guid, 'Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.');
    echo '</p>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/i18n_manageProcess.php'>";
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Code');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Active');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Maintainer');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Default');
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

            if ($row['active'] == 'N') {
                $rowNum = 'error';
            }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['name'].'<b/>';
            echo '</td>';
            echo '<td>';
            echo $row['code'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $row['active']);
            echo '</td>';
            echo '<td>';
            if ($row['maintainerWebsite'] != '') {
                echo "<a href='".$row['maintainerWebsite']."'>".$row['maintainerName'].'</a>';
            } else {
                echo $row['maintainerName'];
            }
            echo '</td>';
            echo '<td>';
            if ($row['active'] == 'Y') {
                if ($row['systemDefault'] == 'Y') {
                    echo "<input checked type='radio' name='gibboni18nID' value='".$row['gibboni18nID']."'>";
                } else {
                    echo "<input type='radio' name='gibboni18nID' value='".$row['gibboni18nID']."'>";
                }
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '<tr>';
        echo "<td colspan=6 class='right'>";
        ?>
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						<?php
                    echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '</form>';
    }
}
?>