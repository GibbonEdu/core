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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityReportGateway;
use Gibbon\Domain\User\FamilyGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_participants.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonActivityID = isset($_GET['gibbonActivityID'])? $_GET['gibbonActivityID'] : null;
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Participants by Activity'));

        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

        $form->setTitle(__('Choose Activity'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_participants.php");

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonActivityID AS value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
        $row = $form->addRow();
            $row->addLabel('gibbonActivityID', __('Activity'));
            $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->isRequired()->placeholder();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    if (empty($gibbonActivityID)) return;

    $activityGateway = $container->get(ActivityReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria()
        ->searchBy($activityGateway->getSearchableColumns(), isset($_GET['search'])? $_GET['search'] : '')
        ->sortBy(['surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $participants = $activityGateway->queryParticipantsByActivity($criteria, $gibbonActivityID);

    // DATA TABLE
    $table = ReportTable::createPaginated('participants', $criteria)->setViewMode($viewMode, $gibbon->session);

    $table->setTitle(__('Participants by Activity'));

    $table->addColumn('rollGroup', __('Roll Group'))->width('10%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($student) use ($guid) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
            return Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&subpage=Activities', $name);
        });
    $table->addColumn('status', __('Status'));
    $table->addColumn('contacts', __('Parental Contacts'))
        ->notSortable()
        ->format(function($student) use ($familyGateway) {
            $output = '';
            $familyAdults = $familyGateway->selectFamilyAdultsByStudent($student['gibbonPersonID'])->fetchAll();

            foreach ($familyAdults as $index => $adult) {
                $output .= '<strong>'.Format::name($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent').'</strong><br/>';
                if ($adult['childDataAccess'] == 'N') {
                    $output .= '<strong style="color: #cc0000">'.__('Data Access').': '.__('No').'</strong><br/>';
                }
                if (!empty($adult['email'])) {
                    $output .= __('Email').': '.Format::link('mailto:'.$adult['email'], $adult['email']).'<br/>';
                }
                for ($i = 1; $i <= 4; ++$i) {
                    if (empty($adult["phone$i"])) continue;
                    $output .= Format::phone($adult["phone{$i}"], $adult["phone{$i}CountryCode"], $adult["phone{$i}Type"]).'<br/>';
                }
                if ($index + 1 < count($familyAdults)) $output .= '<br/>';
            }
            return $output;
        });

    echo $table->render($participants);
}
