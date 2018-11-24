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

use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_privacy_student.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Privacy Choices by Student'));
    }

    
    $privacy = getSettingByScope($connection2, 'User Admin', 'privacy');
    $privacyOptions = array_map('trim', explode(',', getSettingByScope($connection2, 'User Admin', 'privacyOptions')));

    if (count($privacyOptions) < 1 or $privacy == 'N') {
        $page->addMessage(__('There are no privacy options in place.'));
        return;
    }

    $reportGateway = $container->get(StudentReportGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria()
        ->sortBy(['gibbonYearGroup.sequenceNumber', 'gibbonRollGroup.nameShort'])
        ->pageSize(0)
        ->fromPOST();

    $privacyChoices = $reportGateway->queryStudentPrivacyChoices($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = ReportTable::createPaginated('privacyByStudent', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Privacy Choices by Student'));

    $count = 1;
    $table->addColumn('count', __('Count'))
        ->notSortable()
        ->format(function ($student) use (&$count) {
            return $count++;
        });
    $table->addColumn('rollGroup', __('Roll Group'))
        ->sortable(['gibbonYearGroup.sequenceNumber', 'rollGroup']);

    $table->addColumn('image_240', __('Student'))
        ->width('10%')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($student) use ($guid) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'];

            return Format::userPhoto($student['image_240']).'<br/>'.Format::link($url, $name);
        });

    $privacyColumn = $table->addColumn('privacy', __('Privacy'));
    foreach ($privacyOptions as $index => $privacyOption) {
        $privacyColumn->addColumn('privacy'.$index, $privacyOption)
            ->notSortable()
            ->format(function ($student) use ($privacyOption, $guid) {
                $studentPrivacy = array_map('trim', explode(',', $student['privacy']));
                return in_array($privacyOption, $studentPrivacy) 
                    ? "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ".__('Required')
                    : '';
            });
    }

    echo $table->render($privacyChoices);
}
