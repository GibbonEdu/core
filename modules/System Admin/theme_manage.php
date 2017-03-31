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

include './modules/System Admin/moduleFunctions.php';

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Themes').'</div>';
    echo '</div>';

    $returns = array();
    $returns['warning0'] = __($guid, "Uninstall was successful. You will still need to remove the theme's files yourself.");
    $returns['success0'] = __($guid, 'Uninstall was successful.');
    $returns['success1'] = __($guid, 'Install was successful.');
    $returns['error3'] = __($guid, 'Your request failed because your manifest file was invalid.');
    $returns['error4'] = __($guid, 'Your request failed because a theme with the same name is already installed.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Get themes from database, and store in an array
    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonTheme ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $themesSQL = array();
    while ($row = $result->fetch()) {
        $themesSQL[$row['name']][0] = $row;
        $themesSQL[$row['name']][1] = 'orphaned';
    }

    //Get list of themes in /themes directory
    $themesFS = glob($_SESSION[$guid]['absolutePath'].'/themes/*', GLOB_ONLYDIR);

    echo "<div class='warning'>";
    echo sprintf(__($guid, 'To install a theme, upload the theme folder to %1$s on your server and then refresh this page. After refresh, the theme should appear in the list below: use the install button in the Actions column to set it up.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/themes/</u></b>');
    echo '</div>';

    if (count($themesFS) < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/theme_manageProcess.php'>";
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Description');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Version');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Author');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Active');
        echo '</th>';
        echo "<th style='width: 50px'>";
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        foreach ($themesFS as $themesFS) {
            $themeName = substr($themesFS, strlen($_SESSION[$guid]['absolutePath'].'/themes/'));
            $themesSQL[$themeName][1] = 'present';

            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            $installed = true;
            if (isset($themesSQL[$themeName][0]) == false) {
                $installed = false;
                $rowNum = 'warning';
            }

            ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
            echo '<td>';
            echo __($guid, $themeName);
            echo '</td>';
            if ($installed) {
                echo '<td>';
                echo __($guid, 'Installed');
                echo '</td>';
            } else {
                //Check for valid manifest
                            $manifestOK = false;
                if (include $_SESSION[$guid]['absolutePath']."/themes/$themeName/manifest.php") {
                    if ($name != '' and $description != '' and $version != '') {
                        if ($name == $themeName) {
                            $manifestOK = true;
                        }
                    }
                }
                if ($manifestOK) {
                    echo '<td colspan=5>';
                    echo __($guid, 'Not Installed');
                    echo '</td>';
                } else {
                    echo '<td colspan=6>';
                    echo __($guid, 'Theme Error');
                    echo '</td>';
                }
            }
            if ($installed) {
                echo '<td>';
                echo $themesSQL[$themeName][0]['description'];
                echo '</td>';
                echo '<td>';
                if ($themesSQL[$themeName][0]['name'] == 'Default') {
                    echo 'v'.$version;
                } else {
                    $themeVerison = getThemeVersion($themeName, $guid);
                    if ($themeVerison > $themesSQL[$themeName][0]['version']) {
                        //Update database
						try {
							$data = array('version' => $themeVerison, 'gibbonThemeID' => $themesSQL[$themeName][0]['gibbonThemeID']);
							$sql = 'UPDATE gibbonTheme SET version=:version WHERE gibbonThemeID=:gibbonThemeID';
							$result = $connection2->prepare($sql);
							$result->execute($data);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
                    } else {
                        $themeVerison = $themesSQL[$themeName][0]['version'];
                    }

                    echo 'v'.$themeVerison;
                }
                echo '</td>';
                echo '<td>';
                if ($themesSQL[$themeName][0]['url'] != '') {
                    echo "<a href='".$themesSQL[$themeName][0]['url']."'>".$themesSQL[$themeName][0]['author'].'</a>';
                } else {
                    echo $themesSQL[$themeName][0]['author'];
                }
                echo '</td>';
                echo '<td>';
                if ($themesSQL[$themeName][0]['active'] == 'Y') {
                    echo "<input checked type='radio' name='gibbonThemeID' value='".$themesSQL[$themeName][0]['gibbonThemeID']."'>";
                } else {
                    echo "<input type='radio' name='gibbonThemeID' value='".$themesSQL[$themeName][0]['gibbonThemeID']."'>";
                }
                echo '</td>';
                echo '<td>';
                if ($themesSQL[$themeName][0]['name'] != 'Default') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/theme_manage_uninstall.php&gibbonThemeID='.$themesSQL[$themeName][0]['gibbonThemeID']."'><img title='".__($guid, 'Remove Record')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                }
                echo '</td>';
            } else {
                if ($manifestOK) {
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/theme_manage_installProcess.php?name='.urlencode($themeName)."'><img title='".__($guid, 'Install')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</td>';
                }
            }
            echo '</tr>';
        }
        echo '<tr>';
        echo "<td colspan=7 class='right'>";
        	?>
			<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
			<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
			<?php
		echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '</form>';
    }

    //Find and display orphaned themes
    $orphans = false;
    foreach ($themesSQL as $themeSQL) {
        if ($themeSQL[1] == 'orphaned') {
            $orphans = true;
        }
    }

    if ($orphans) {
        echo "<h2 style='margin-top: 40px'>";
        echo __($guid, 'Orphaned Themes');
        echo '</h2>';
        echo '<p>';
        echo __($guid, 'These themes are installed in the database, but are missing from within the file system.');
        echo '</p>';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo "<th style='width: 50px'>";
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        foreach ($themesSQL as $themeSQL) {
            if ($themeSQL[1] == 'orphaned') {
                $themeName = $themeSQL[0]['name'];

                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo __($guid, $themeName);
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/theme_manage_uninstall.php&gibbonThemeID='.$themesSQL[$themeName][0]['gibbonThemeID']."&orphaned=true'><img title='".__($guid, 'Remove Record')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '<tr>';
        echo "<td colspan=7 class='right'>";
        	?>
			<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
			<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
			<?php
		echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
}
?>
