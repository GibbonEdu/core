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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemCheck.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'System Check').'</div>';
    echo '</div>';

    $versionDB = getSettingByScope($connection2, 'System', 'version');

    $trueIcon = "<img title='" . __($guid, 'Yes'). "' src='".$_SESSION[$guid]["absoluteURL"]."/themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
    $falseIcon = "<img title='" . __($guid, 'No'). "' src='".$_SESSION[$guid]["absoluteURL"]."/themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

    $versionTitle = __($guid, '%s Version');
    $versionMessage = __($guid, '%s requires %s version %s or higher');

    $phpVersion = phpversion();
    $mysqlVersion = $pdo->selectOne("SELECT VERSION()");
    $mysqlCollation = $pdo->selectOne("SELECT COLLATION('gibbon')");

    $phpRequirement = $gibbon->getSystemRequirement('php');
    $mysqlRequirement = $gibbon->getSystemRequirement('mysql');
    $extensions = $gibbon->getSystemRequirement('extensions');
    $settings = $gibbon->getSystemRequirement('settings');
    ?>

    <table class='smallIntBorder fullWidth' cellspacing='0'>
        <tr class='break'>
            <td colspan=3>
                <h3><?php echo __($guid, 'System Requirements') ?></h3>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php printf($versionTitle, 'PHP'); ?></b><br/>
                <span class="emphasis small">
                    <?php printf($versionMessage, __($guid, 'Gibbon'), 'PHP', $phpRequirement ); ?>
                </span>
            </td>
            <td style="width:240px;">
                <?php echo $phpVersion; ?>
            </td>
            <td class="right" style="width:60px;">
                <?php echo (version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php printf($versionTitle, 'MySQL'); ?></b><br/>
                <span class="emphasis small">
                    <?php printf($versionMessage, __($guid, 'Gibbon'), 'MySQL', $mysqlRequirement ); ?>
                </span>
            </td>
            <td>
                <?php echo $mysqlVersion; ?>
            </td>
            <td class="right">
                <?php echo (version_compare($mysqlVersion, $mysqlRequirement, '>='))? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php echo __($guid, 'MySQL Collation'); ?></b><br/>
                <span class="emphasis small">
                    <?php printf( __($guid, 'Database collation should be set to %s'), 'utf8_general_ci'); ?>
                </span>
            </td>
            <td>
                <?php echo $mysqlCollation; ?>
            </td>
            <td class="right">
                <?php echo ($mysqlCollation == 'utf8_general_ci')? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php echo __($guid, 'MySQL PDO Support'); ?></b><br/>
            </td>
            <td>
                <?php echo (@extension_loaded('pdo_mysql'))? __($guid, 'Installed') : __($guid, 'Not Installed'); ?>
            </td>
            <td colspan=2 class="right">
                <?php echo (@extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
        <tr class='break'>
            <td colspan=3>
                <h3><?php echo __($guid, 'PHP Extensions') ?></h3>
                <?php echo __($guid, 'Gibbon requires you to enable the PHP extensions in the following list. The process to do so depends on your server setup.'); ?>
            </td>
        </tr>
        <?php 
            if (!empty($extensions) && is_array($extensions)) {
                foreach ($extensions as $extension) { 
                    $installed = @extension_loaded($extension);
                    ?>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Extension').' '. $extension; ?></b><br/>
                        </td>
                        <td>
                            <?php echo ($installed)? __($guid, 'Installed') : __($guid, 'Not Installed'); ?>
                        </td>
                        <td colspan=2 class="right">
                            <?php echo ($installed)? $trueIcon : $falseIcon; ?>
                        </td>
                    </tr>
                    <?php
                }
            }
        ?>
        <tr class='break'>
            <td colspan=3>
                <h3><?php echo __($guid, 'PHP Settings'); ?></h3>
                <?php printf(__($guid, 'Configuration values can be set in your system %s file. On shared host, use %s to set php settings.'), '<code>php.ini</code>', '.htaccess'); ?>
            </td>
        </tr>
        <?php 
            if (!empty($settings) && is_array($settings)) {
                foreach ($settings as $settingDetails) { 
                    if (!is_array($settingDetails) || count($settingDetails) != 3) continue;

                    list($setting, $operator, $compare) = $settingDetails;
                    $value = @ini_get($setting);
                    ?>
                    <tr>
                        <td>
                            <?php echo '<b>'.$setting.'</b> <small>'.$operator.' '.$compare.'</small>'; ?><br/>
                        </td>
                        <td>
                            <?php echo $value; ?>
                        </td>
                        <td class="right">
                            <?php 
                                if ($operator == '==' && $value == $compare) echo $trueIcon;
                                else if ($operator == '>=' && $value >= $compare) echo $trueIcon;
                                else if ($operator == '<=' && $value <= $compare) echo $trueIcon;
                                else if ($operator == '>' && $value > $compare) echo $trueIcon;
                                else if ($operator == '<' && $value < $compare) echo $trueIcon;
                                else echo $falseIcon;
                            ?>
                        </td>
                    </tr>
                    <?php
                }
            }
        ?>
        <tr class='break'>
            <td colspan=3>
                <h3><?php echo __($guid, 'File Permissions') ?></h3>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php echo __($guid, 'System not publicly writeable'); ?></b><br/>
            </td>
            <td>
                <?php 
                    $fileCount = 0;
                    $publicWriteCount = 0;
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SESSION[$guid]["absolutePath"])) as $filename)
                    {
                        if (pathinfo($filename, PATHINFO_EXTENSION) != 'php') continue;
                        if ( strpos(pathinfo($filename, PATHINFO_DIRNAME), '/uploads') !== false) continue;
                        
                        $fileCount++;
                        if (fileperms($filename) & 0x0002) $publicWriteCount++;
                    }
                    printf(__($guid, '%s files checked (%s publicly writeable)'), $fileCount, $publicWriteCount);
                ?>
            </td>
            <td class="right">
                <?php echo ($publicWriteCount == 0)? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php echo __($guid, 'Uploads folder server writeable'); ?></b><br/>
            </td>
            <td>
                <?php echo $_SESSION[$guid]["absoluteURL"].'/uploads'; ?>
            </td>
            <td class="right">
                <?php echo (is_writable($_SESSION[$guid]["absolutePath"].'/uploads') == true)? $trueIcon : $falseIcon; ?>
            </td>
        </tr>
    </table>
    <?php
}
?>