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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataUpdater\FamilyUpdateGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/report_family_dataUpdaterHistory.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Family Data Updater History').'</div>';
    echo '</div>';

    echo '<p>';
    echo __('This report allows a user to select a range of families, with at least one child enroled in the target year group, and check whether or not they have had their family and personal data updated after a specified date.');
    echo '</p>';

    echo '<h2>';
    echo __('Choose Options');
    echo '</h2>';

    $cutoffDate = getSettingByScope($connection2, 'Data Updater', 'cutoffDate');
    $cutoffDate = !empty($cutoffDate)? Format::date($cutoffDate) : Format::dateFromTimestamp(time() - (604800 * 26)); 

    $gibbonYearGroupIDList = isset($_POST['gibbonYearGroupIDList'])? $_POST['gibbonYearGroupIDList'] : array();
    $nonCompliant = isset($_POST['nonCompliant'])? $_POST['nonCompliant'] : '';
    $date = isset($_POST['date'])? $_POST['date'] : $cutoffDate;

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_family_dataUpdaterHistory.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList',__('Year Groups'));
        $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple()->selected($gibbonYearGroupIDList)->selectAll(true);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Earliest acceptable update'));
        $row->addDate('date')->setValue($date)->isRequired();

    $row = $form->addRow();
        $row->addLabel('nonCompliant', __('Show Only Non-Compliant?'))->description(__('If not checked, show all. If checked, show only non-compliant students.'));
        $row->addCheckbox('nonCompliant')->setValue('Y')->checked($nonCompliant);
    
    $row = $form->addRow();
        $row->addSubmit();
    
    echo $form->getOutput();

    if (!empty($gibbonYearGroupIDList)) {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $gateway = $container->get(FamilyUpdateGateway::class);

        // QUERY
        $criteria = $gateway->newQueryCriteria()
            ->sortBy(['gibbonFamily.name'])
            ->filterBy('cutoff', $nonCompliant == 'Y'? Format::dateConvert($date) : '')
            ->fromArray($_POST);

        $dataUpdates = $gateway->queryFamilyUpdaterHistory($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $gibbonYearGroupIDList);
        $families = $dataUpdates->getColumn('gibbonFamilyID');

        // Join a set of family adults & updater info
        $familyAdults = $gateway->selectFamilyAdultUpdatesByFamily($families)->fetchGrouped();
        $dataUpdates->joinColumn('gibbonFamilyID', 'familyAdults', $familyAdults);

        // Join a set of family children & updater info
        $familyChildren = $gateway->selectFamilyChildUpdatesByFamily($families, $_SESSION[$guid]['gibbonSchoolYearID'])->fetchGrouped();
        $dataUpdates->joinColumn('gibbonFamilyID', 'familyChildren', $familyChildren);

        // Function to display the updater info based on the cutoff date
        $dateCutoff = DateTime::createFromFormat('Y-m-d H:i:s', Format::dateConvert($date).' 00:00:00');
        $dataChecker = function($dateUpdated, $dateStart) use ($dateCutoff, $guid) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateUpdated);
            $dateDisplay = !empty($dateUpdated)? Format::dateTime($dateUpdated) : __('No data');

            if (DateTime::createFromFormat('Y-m-d', $dateStart) > $dateCutoff) {
                return "<img title='".__('Start Date').': '.Format::date($dateStart)."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick_light.png' width='18' />";
            }

            return empty($dateUpdated) || $dateCutoff > $date
                ? "<img title='".__('Update Required').': '.$dateDisplay."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png' width='18' />"
                : "<img title='".__('Up to date').': '.$dateDisplay."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png' width='18' />";
        };

        // DATA TABLE
        $table = DataTable::createPaginated('studentUpdaterHistory', $criteria);
        $table->addMetaData('post', ['gibbonYearGroupIDList' => $gibbonYearGroupIDList]);

        $count = $dataUpdates->getPageFrom();
        $table->addColumn('count', '')
            ->notSortable()
            ->width('5%')
            ->format(function ($row) use (&$count) {
                return $count++;
            });

        $table->addColumn('familyName', __('Family'))
              ->width('20%')
              ->format(function($row) use ($guid) {
                  return '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$row['gibbonFamilyID'].'">'.$row['familyName'].'</a>';
              });

        $table->addColumn('familyUpdate', __('Family Data'))
            ->width('5%')
            ->format(function($row) use ($dataChecker) {
                return $dataChecker($row['familyUpdate'], $row['earliestDateStart']);
            });

        $table->addColumn('familyAdults', __('Adults'))
            ->notSortable()
            ->format(function($row) use ($dataChecker) {
                $output = '<table class="smallIntBorder fullWidth colorOddEven" cellspacing=0>';
                foreach ($row['familyAdults'] as $adult) {
                    $output .= '<tr>';
                    $output .= '<td style="width:90%">'.Format::name($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent').'</td>';
                    $output .= '<td style="width:10%">'.$dataChecker($adult['personalUpdate'], $row['earliestDateStart']).'</td>';
                    $output .= '</tr>';
                }
                $output .= '</table>';
                return $output;
            });

        $table->addColumn('familyChildren', __('Children'))
            ->notSortable()
            ->format(function($row) use ($dataChecker) {
                $output = '<table class="smallIntBorder fullWidth colorOddEven" cellspacing=0>';
                foreach ($row['familyChildren'] as $child) {
                    $output .= '<tr>';
                    $output .= '<td style="width:80%">'.Format::name('', $child['preferredName'], $child['surname'], 'Student').'</td>';
                    $output .= '<td style="width:10%">'.$child['rollGroup'].'</td>';
                    $output .= '<td style="width:10%">'.$dataChecker($child['personalUpdate'], $child['dateStart']).'</td>';
                    $output .= '</tr>';
                }
                $output .= '</table>';
                return $output;
            });

        echo $table->render($dataUpdates);
    }
}
