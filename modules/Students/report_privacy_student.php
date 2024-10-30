<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_privacy_student.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Privacy Choices by Student'));
    }


    $settingGateway = $container->get(SettingGateway::class);
    $privacy = $settingGateway->getSettingByScope('User Admin', 'privacy');
    $privacyOptions = array_map('trim', explode(',', $settingGateway->getSettingByScope('User Admin', 'privacyOptions')));

    if (count($privacyOptions) < 1 or $privacy == 'N') {
        $page->addMessage(__('There are no privacy options in place.'));
        return;
    }

    $reportGateway = $container->get(StudentReportGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria()
        ->sortBy(['gibbonYearGroup.sequenceNumber', 'gibbonFormGroup.nameShort'])
        ->fromPOST();

    $privacyChoices = $reportGateway->queryStudentPrivacyChoices($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = ReportTable::createPaginated('privacyByStudent', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Privacy Choices by Student'));

    $table->addRowCountColumn($privacyChoices->getPageFrom());
    $table->addColumn('formGroup', __('Form Group'))
        ->context('secondary')
        ->sortable(['gibbonYearGroup.sequenceNumber', 'formGroup']);

    $table->addColumn('image_240', __('Student'))
        ->context('primary')
        ->width('10%')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($student) use ($session) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
            $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'];

            return Format::userPhoto($student['image_240']).'<br/>'.Format::link($url, $name);
        });

    $privacyColumn = $table->addColumn('privacy', __('Privacy'));
    foreach ($privacyOptions as $index => $privacyOption) {
        $privacyColumn->addColumn('privacy'.$index, $privacyOption)
            ->context('primary')
            ->notSortable()
            ->format(function ($student) use ($privacyOption, $session) {
                $studentPrivacy = array_map('trim', explode(',', $student['privacy']));
                return in_array($privacyOption, $studentPrivacy) 
                    ? "<img src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/> ".__('Required')
                    : '';
            });
    }

    echo $table->render($privacyChoices);
}
