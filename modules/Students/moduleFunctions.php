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

function renderStudentCourseMarks($gibbon, $pdo, $gibbonPersonID, $gibbonCourseClassID) {

    require_once $gibbon->session->get('absolutePath').'/modules/Markbook/src/markbookView.php';

    // Build the markbook object for this class & student
    $markbook = new Module\Markbook\markbookView($gibbon, $pdo, $gibbonCourseClassID);
    $assessmentScale = $markbook->getDefaultAssessmentScale();

    // Cancel our now if this isnt a percent-based mark
    if (empty($assessmentScale) || (stripos($assessmentScale['name'], 'percent') === false && $assessmentScale['nameShort'] !== '%')) {
        return;
    }

    // Calculate & get the cumulative average
    $markbook->cacheWeightings($gibbonPersonID);
    $courseMark = round($markbook->getCumulativeAverage($gibbonPersonID));

    // Only display if there are marks
    if (!empty($courseMark)) {
        // Divider
        echo '<tr class="break">';
            echo '<th colspan="7" style="height: 4px; padding: 0px;"></th>';
        echo '</tr>';

        // Display the cumulative average
        echo '<tr>';
            echo '<td style="width:120px;">';
                echo '<b>'.__('Cumulative Average').'</b>';
            echo '</td>';
            echo '<td style="padding: 10px !important; text-align: center;">';
                echo round( $courseMark ).'%';
            echo '</td>';
            echo '<td colspan="3" class="dull"></td>';
         echo '</tr>';
    }
}
