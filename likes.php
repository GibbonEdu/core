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

$resultLike = countLikesByRecipient(
    $connection2,
    $_SESSION[$guid]['gibbonPersonID'],
    'result',
    $_SESSION[$guid]['gibbonSchoolYearID']
);

?>

<div class='trail'>
    <div class='trailHead'><a href='<?php echo $_SESSION[$guid]['absoluteURL']; ?>'><?php echo __($guid, 'Home'); ?></a> ></div><div class='trailEnd'><?php echo __($guid, 'Likes'); ?></div>
</div>
<p>
    <?php echo __($guid, 'This page shows you a break down of all your likes in the current year, and they have been earned.'); ?>
</p>

<?php if ($resultLike == false) { ?>
    <div class='error'><?php echo __($guid, 'An error has occurred.'); ?></div>
<?php } elseif ($resultLike->rowCount() < 1) { ?>
    <div class='error'>
        <?php echo __($guid, 'There are no records to display.'); ?>
    </div>
<?php } else { ?>
    <table cellspacing='0' style='width: 100%'>
        <tr class='head'>
            <th style='width: 90px'>
                <?php echo __($guid, 'Photo'); ?>
            </th>
            <th style='width: 180px'>
                <?php echo __($guid, 'Giver'); ?><br/>
                <span style='font-size: 85%; font-style: italic'><?php echo __($guid, 'Role'); ?></span>
            </th>
            <th>
                <?php echo __($guid, 'Title'); ?>
                <span style='font-size: 85%; font-style: italic'><?php __($guid, 'Comment'); ?></span>
            </th>
            <th style='width: 70px'>
                <?php echo __($guid, 'Date'); ?>
            </th>
        </tr>
        <?php $count = 0; while ($row = $resultLike->fetch()) { ?>
            <tr class='<?php echo ($count++ % 2 == 0) ? 'even' : 'odd'; ?>'>
                <td>
                    <?php echo getUserPhoto($guid, $row['image_240'], 75); ?>
                </td>
                <td>
                    <?php
                    $roleCategory = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                    if ($roleCategory == 'Student' and isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php')) {
                    ?>
                        <a href='<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']; ?>'
                          ><?php formatName('', $row['preferredName'], $row['surname'], $roleCategory, false); ?></a><br/>
                        <span style='font-size: 85%; font-style: italic'><?php echo __($guid, $roleCategory); ?></i>
                    <?php } else { ?>
                        <?php echo formatName('', $row['preferredName'], $row['surname'], $roleCategory, false); ?><br/>
                        <span style='font-size: 85%; font-style: italic'><?php echo __($guid, $roleCategory); ?></i>
                    <?php } ?>
                </td>
                <td>
                    <?php echo __($guid, $row['title']); ?><br/>
                    <span style='font-size: 85%; font-style: italic'><?php $row['comment']; ?></span>
                </td>
                <td>
                    <?php echo dateConvertBack($guid, substr($row['timestamp'], 0, 10)); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>
