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

// data table definition
$table = Gibbon\Tables\DataTable::create('likes');

$table->addColumn(
        'photo',
        __($guid, 'Photo')
    )
    ->width('90px')
    ->format(function ($data) {
        return Gibbon\Services\Format::userPhoto($data['image_240'], 75);
    });

$table->addColumn(
        'giver',
        __($guid, 'Giver') .
        '<span style="font-size: 85%; font-style: italic">' .
        __($guid, 'Role') .
        '</span>'
    )
    ->width('180px')
    ->format(function ($data) use ($guid, $connection2) {
        // determine if the user can view the student
        $roleCategory = getRoleCategory($data['gibbonRoleIDPrimary'], $connection2);
        $canViewStudent = (
            $roleCategory == 'Student'
            and isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php'));

        // student link if useful, of false
        $studentURL = $canViewStudent ?
            $_SESSION[$guid]['absoluteURL'].'/index.php?' .
            http_build_query([
                'q' => '/modules/Students/student_view_details.php',
                'gibbonPersonID' => $data['gibbonPersonID'],
            ]) : false;
        $studentName = formatName('', $data['preferredName'], $data['surname'], $roleCategory, false);
        $roleCategory = __($guid, $roleCategory);

        // format student name, if needed
        $studentName = $studentURL ?
            "<a href=\"{$studentURL}\">{$studentName}</a>" :
            $studentName;

        // format output
        return "{$studentName}<span style=\"font-size: 85%; font-style: italic\">{$roleCategory}</span>";
    });

$table->addColumn(
        'title',
        __($guid, 'Title') .
        '<span style="font-size: 85%; font-style: italic">' .
        __($guid, 'Comment') .
        '</span>'
    )
    ->width('90px')
    ->format(function ($data) use ($guid) {
        return __($guid, $data['title']) . "<br/>\n" .
            '<span style="font-size: 85%; font-style: italic">' .
            $data['comment'] .
            '</span>';
    });

$table->addColumn('date', __($guid, 'Date'))
    ->width('70px')
    ->format(function ($data) use ($guid) {
        return dateConvertBack($guid, substr($data['timestamp'], 0, 10));
    });

// query for like counts
$resultLike = countLikesByRecipient(
    $connection2,
    $_SESSION[$guid]['gibbonPersonID'],
    'result',
    $_SESSION[$guid]['gibbonSchoolYearID']
);

// check if the page has any result
$noResult = ($resultLike === false || $resultLike->rowCount() < 1) ?
    __($guid, 'There are no records to display.') :
    null;

?>

<div class='trail'>
    <div class='trailHead'><a href='<?php echo $_SESSION[$guid]['absoluteURL']; ?>'><?php echo __($guid, 'Home'); ?></a> ></div><div class='trailEnd'><?php echo __($guid, 'Likes'); ?></div>
</div>
<p>
    <?php echo __($guid, 'This page shows you a break down of all your likes in the current year, and they have been earned.'); ?>
</p>

<?php if ($noResult) { ?>
    <div class='error'><?php echo $noResult; ?></div>
<?php } else { ?>
    <?php echo $table->render($resultLike->toDataSet()); ?>
<?php } ?>
