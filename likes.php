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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

$page->breadcrumbs->add(__('Likes'));

// data table definition
$table = DataTable::create('likes');
$table->setDescription(__('This page shows you a break down of all your likes in the current year, and they have been earned.'));

$table->addColumn('photo', __('Photo'))
    ->width('90px')
    ->format(Format::using('userPhoto', ['image_240', 75]));

$table->addColumn('giver', __('Giver'))
    ->description(__('Role'))
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
        $studentName = Format::name('', $data['preferredName'], $data['surname'], $roleCategory, false);
        $roleCategory = __($roleCategory);

        // format student name, if needed
        $studentName = $studentURL ?
            Format::link($studentURL, $studentName) :
            $studentName;

        // format output
        return "{$studentName}<br/><span style=\"font-size: 85%; font-style: italic\">{$roleCategory}</span>";
    });

$table->addColumn('title', __('Title'))
    ->description(__('Comment'))
    ->width('90px')
    ->format(function ($data) {
        return __($data['title']) . "<br/>\n" .
            '<span style="font-size: 85%; font-style: italic">' .
            $data['comment'] .
            '</span>';
    });

$table->addColumn('date', __('Date'))
    ->width('70px')
    ->format(Format::using('date', 'timestamp'));

// query for like counts
$resultLike = countLikesByRecipient(
    $connection2,
    $_SESSION[$guid]['gibbonPersonID'],
    'result',
    $_SESSION[$guid]['gibbonSchoolYearID']
);

echo $table->render($resultLike->toDataSet());
