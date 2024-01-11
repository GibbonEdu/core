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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Timetable\TimetableDayGateway;
use Gibbon\Domain\Timetable\TimetableColumnGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_byClass.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
	$settingGateway = $container->get(SettingGateway::class);
    $timetableGateway = $container->get(TimetableGateway::class);
    $timetableDayGateway = $container->get(TimetableDayGateway::class);
    $timetableColumnGateway = $container->get(TimetableColumnGateway::class);

    $gibbonCourseClassID = $_REQUEST['gibbonCourseClassID'] ?? '';
    $gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';

    $timetable = $timetableGateway->getByID($gibbonTTID);
    if (empty($timetable)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $page->breadcrumbs
        ->add(__('Manage Timetables'), 'tt.php', ['gibbonSchoolYearID' => $timetable['gibbonSchoolYearID']])
        ->add(__('Edit Timetable'), 'tt_edit.php', ['gibbonSchoolYearID' => $timetable['gibbonSchoolYearID'], 'gibbonTTID' => $gibbonTTID])
        ->add(__('Edit Timetable by Class'));
    
    // SELECT TIMETABLE & CLASS
    $form = Form::create('timetableByClass', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Timetable Admin/tt_edit_byClass.php');
    $form->addHiddenValue('gibbonSchoolYearID', $timetable['gibbonSchoolYearID']);
    $form->addHiddenValue('gibbonTTID', $gibbonTTID);

    $classResults = $timetableGateway->selectClassesByTimetable($gibbonTTID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelect('gibbonCourseClassID')
            ->fromResults($classResults)
            ->required()
            ->placeholder()
            ->selected($gibbonCourseClassID);

    $row = $form->addRow();
        $row->addSubmit('Next');

    echo $form->getOutput();

    if (!empty($gibbonCourseClassID)) {
        $form = Form::create('ttAdd', $session->get('absoluteURL').'/modules/Timetable Admin/tt_edit_byClassProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->setDescription(Format::alert(__('This is an administrative tool to assist with timetable changes. When changing facilities, it <b>does not</b> prevent timetabling into a facility that is already in use. Be sure to check availability before making such changes.'), 'message'));

        $form->addHiddenValue('gibbonSchoolYearID', $timetable['gibbonSchoolYearID']);
        $form->addHiddenValue('gibbonTTID', $gibbonTTID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

        $form->addHiddenValue('address', $session->get('address'));

        $dayResults = $timetableDayGateway->selectTTDaysByTimetable($gibbonTTID);
        $columnResults = $timetableColumnGateway->selectTTColumnsByTimetable($gibbonTTID);

        $columnRows = ($columnResults->rowCount() > 0)? $columnResults->fetchAll() : array();
        $columnRowsChained = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'gibbonTTDayID'));
        $columnRowsOptions = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'name'));

        $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
        $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
        $gibbonTTSpaceID = $_GET['gibbonTTSpaceID'] ?? '';

        $ttBlock = $form->getFactory()->createTable()->setClass('blank');
            $row = $ttBlock->addRow();
                $row->addLabel('gibbonTTDayID', __('Day'))->addClass('ml-4');
                $row->addSelect('gibbonTTDayID')
                    ->fromResults($dayResults)
                    ->required()
                    ->selected($gibbonTTDayID)
                    ->addClass('float-left')
                    ->append('<input type="hidden" id="gibbonTTDayRowClassID" name="gibbonTTDayRowClassID" value="">');

                $row->addLabel('gibbonTTColumnRowID', __('Period'))->addClass('ml-4');
                $row->addSelect('gibbonTTColumnRowID')
                    ->fromArray($columnRowsOptions)
                    ->required()
                    ->chainedTo('', $columnRowsChained)
                    ->selected($gibbonTTColumnRowID)
                    ->addClass('chainTo float-left');

            // $row = $ttBlock->addRow();
                $row->addLabel('gibbonTTSpaceID', __('Facility'))->addClass('ml-4');
                $row->addSelectSpace('gibbonTTSpaceID')->selected($gibbonTTSpaceID)->addClass('float-left');

        $addTTButton = $form->getFactory()->createButton(__('Add Timetable Entry'))->addClass('addBlock');

        $row = $form->addRow();
            $ttBlocks = $row->addCustomBlocks('ttBlocks', $session)
                ->fromTemplate($ttBlock)
                ->settings([
                    'placeholder' => __('Timetable Entries will appear here.')
                ])
                ->addToolInput($addTTButton);

        $ttResults = $timetableDayGateway->selectTTDayRowClassesByClass($gibbonTTID, $gibbonCourseClassID);

        while ($ttDay = $ttResults->fetch()) {
            $ttDay['gibbonTTColumnRowID'] .= '-' . $ttDay['gibbonTTDayID'];
            $ttDay['gibbonTTSpaceID'] = $ttDay['gibbonSpaceID'];
            $ttBlocks->addBlock($ttDay['gibbonTTDayRowClassID'], $ttDay);
        }

        $row = $form->addRow();
            $row->addSubmit(__('Submit'));

        echo $form->getOutput();

    }
}

?>
 <script>
    function chainSelects() {
            $('div.blocks').find('select.chainTo').each(function () {
            var index = $(this).attr('id').replace('gibbonTTColumnRowID' ,'');
            $(this).removeClass('chainTo').chainedTo('#gibbonTTDayID' + index);
        });
    }

    $(document).ready(chainSelects);

    $(document).on('click', '.addBlock', chainSelects);
</script>
