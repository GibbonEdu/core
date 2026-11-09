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
use Gibbon\Domain\Timetable\TimetableDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Tie Days to Dates'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        if (isset($_GET['deleted'])) {
            $deleted = is_numeric($_GET['deleted']) ? (int) $_GET['deleted'] : 0;
            echo '<div class="success">'.sprintf(__('Deleted timetable days for %s selected date(s).'), $deleted).'</div>';
        }

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() < 1) {
            echo $page->getBlankSlate();
        } else {

            $page->addData('preventOverflow', true);

            $form = Form::createTable('ttDates', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttDates_addMultiProcess.php?gibbonSchoolYearID='.$gibbonSchoolYearID);
            $form->setClass('w-full blank');

            $form->addHiddenValue('q', $session->get('address'));

            while ($values = $result->fetch()) {
                $row = $form->addRow()->addHeading($values['name']);

                list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $values['firstDay']);
                $firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
                list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $values['lastDay']);
                $lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);

                //Count back to first Monday before first day
                $startDayStamp = $firstDayStamp;
                while (date('D', $startDayStamp) != 'Mon') {
					$startDayStamp = strtotime('-1 day', $startDayStamp);
                }

                //Count forward to first Sunday after last day
                $endDayStamp = $lastDayStamp;
                while (date('D', $endDayStamp) != 'Sun') {
					$endDayStamp = strtotime('+1 day', $endDayStamp);
                }

                //Get the special days
                $dataSpecial = array('gibbonSchoolYearTermID' => $values['gibbonSchoolYearTermID']);
                $sqlSpecial = 'SELECT date, type, name FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID ORDER BY date';
                $resultSpecial = $connection2->prepare($sqlSpecial);
                $resultSpecial->execute($dataSpecial);

                $specialDays = $resultSpecial->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

                // Get the TT day names
                $dataDay = array();
                $sqlDay = 'SELECT date, gibbonTTDay.nameShort AS dayName, gibbonTT.nameShort AS ttName, color, fontColor FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)';
                $resultDay = $connection2->prepare($sqlDay);
                $resultDay->execute($dataDay);

                $ttDays = $resultDay->fetchAll(\PDO::FETCH_GROUP);

				//Check which days are school days
                $dataDays = array();
                $sqlDays = "SELECT nameShort, schoolDay FROM gibbonDaysOfWeek";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);

                $days = $resultDays->fetchAll(\PDO::FETCH_KEY_PAIR);

                $count = 1;

                $table = $form->addRow()->addTable()->setClass('w-full');
                $row = $table->addHeaderRow();

                for ($i = 1; $i < 8; ++$i) {
                    $dowLong = date('l', strtotime("Sunday +$i days"));
                    $dowShort = date('D', strtotime("Sunday +$i days"));

                    $script = '<script type="text/javascript">';
                    $script .= 'htmx.onLoad(function (content) {';
                    $script .= "$(document).on('click', '[name=\"checkall".$dowShort.$values['nameShort']."\"]', function () {";
                    $script .= "$('.".$dowShort.$values['nameShort']." :checkbox').attr('checked', this.checked);";
                    $script .= '});';
                    $script .= '});';
                    $script .= '</script>';

                    // $column = $row->addColumn();
                    // $column->addContent()->addClass('textCenter');
                    $row->addCheckbox('checkall'.$dowShort.$values['nameShort'])->prepend(__($dowLong).'<br/>')->append($script)->alignCenter()->addClass('sticky top-0 z-10');
                }

                for ($i = $startDayStamp; $i <= $endDayStamp;$i = strtotime('+1 day', $i)) {
                    $date = date('Y-m-d', $i);
                    $dayOfWeek = date('D', $i);
                    $formattedDate = date($session->get('i18n')['dateFormatPHP'], $i);

                    if ($dayOfWeek == 'Mon') {
                        $row = $table->addRow();
                    }

                    if ($i < $firstDayStamp or $i > $lastDayStamp or $days[$dayOfWeek] == 'N') {
                        $row->addContent('')->addClass('ttDates textCenter');
                    } else {
                        if (isset($specialDays[$date]) and $specialDays[$date]['type'] == 'School Closure') {
                            $row->addContent($formattedDate)
                                ->append('<br/>')
                                ->append($specialDays[$date]['name'])
                                ->addClass('ttDates textCenter dull');
                        } else {
                            $column = $row->addColumn()->addClass('ttDates textCenter');
                            $column->addContent($formattedDate)->append('<br/>');
                            if (isset($specialDays[$date]) and $specialDays[$date]['type'] == 'Timing Change') {
                                $column->addContent(__('Timing Change'))->wrap('<span style="color: #f00" title="'.$specialDays[$date]['name'].'">', '</span>');
                            } else {
                                $column->addContent(__('School Day'));
                            }

                            $column->addCheckbox('dates[]')->setValue($i)->setClass($dayOfWeek.$values['nameShort'])->alignCenter();

                            $column->addContent("<br/><a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i."'>".icon('solid', 'edit', 'size-6 mb-2 text-gray-600 hover:text-blue-500')."</a><br/>");

                            if (isset($ttDays[$date])) {
                                foreach ($ttDays[$date] as $day) {
                                    if (empty($day['color'])) {
                                        $column->addContent($day['ttName'].' '.$day['dayName'])->wrap('<b>', '</b>');
                                    }
                                    else {
                                        $column->addContent($day['ttName'].' '.$day['dayName'])->wrap('<div class=\'h-8\'style=\'background-color: '.$day['color'].'; color: '.$day['fontColor'].'\'><b>', '</b></div>');
                                    }

                                }
                                $column->addClass('success');
                            }
                        }
                    }
                    ++$count;
                }
            }

            $form->addRow()->addHeading('Add', __('Add'));

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = "SELECT gibbonTTDay.gibbonTTDayID as value, gibbonTTDay.name AS dayName, gibbonTTDay.nameShort AS dayShort, gibbonTT.name AS ttName, gibbonTT.nameShortDisplay
                    FROM gibbonTTDay
                    JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)
                    WHERE gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY gibbonTT.name, gibbonTTDay.name";

            $stmt = $connection2->prepare($sql);
            $stmt->execute($data);
            $ttDayOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $ttDayArray = [];
            foreach ($ttDayOptions as $opt) {
                $dayLabel = $opt['nameShortDisplay'] === 'Timetable Day Short Name'
                    ? $opt['dayShort']
                    : $opt['dayName'];
                $ttDayArray[$opt['value']] = $opt['ttName'].': '.$dayLabel;
            }

            $ttDayGroups = $container->get(TimetableDayGateway::class)->selectTTDayGroupsBySchoolYear($gibbonSchoolYearID);
            $groupLabels = [];
            $groupPreview = [];
            foreach ($ttDayGroups as $key => $group) {
                $groupLabels[$key] = $group['label'].' ('.count($group['ids']).')';
                $groupPreview[$key] = array_values(array_filter(array_map(function ($id) use ($ttDayArray) {
                    return $ttDayArray[$id] ?? '';
                }, $group['ids'])));
            }

            $previewData = [
                'groups' => $groupPreview,
            ];
            $form->addRow()->addContent('<script type="application/json" id="ttDatesPreviewData">'.json_encode($previewData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).'</script>');

            $table = $form->addRow()->addTable()->setClass('w-full smallIntBorder');

            $row = $table->addRow();
                $row->addLabel('ttDatesMode', __('Add Mode'))->addClass('w-1/4');
                $row->addContent(
                    '<div class="flex-grow relative gap-2 inline-flex justify-between items-center w-full">'
                    .'<input type="radio" id="ttDatesModeCustom" name="ttDatesMode" value="custom" checked>'
                    .'<label for="ttDatesModeCustom" class="flex-1 text-left text-sm">'.__('Custom').'</label>'
                    .'<input type="radio" id="ttDatesModeSmart" name="ttDatesMode" value="smart">'
                    .'<label for="ttDatesModeSmart" class="flex-1 text-left text-sm">'.__('Smart filter').'</label>'
                    .'</div>'
                );

            $row = $table->addRow()->setID('customDayRow');
                $row->addLabel('gibbonTTDayID', __('Custom'))->description(__('Select one or more specific timetable days.'))->addClass('w-1/4');
                $row->addSelect('gibbonTTDayID[]')->fromArray($ttDayArray)->selectMultiple()->addClass('w-full');

            $row = $table->addRow()->setID('smartDayRow')->addClass('hidden');
                $row->addLabel('smartDayGroup', __('Filter day'))->description(__('Select a configured day. Every timetable day using the same configured day setting will be added.'))->addClass('w-1/4');
                $row->addSelect('smartDayGroup')->fromArray($groupLabels)->placeholder(__('— Select a day —'))->addClass('mediumWidth');

            $row = $table->addRow()->setID('smartPreviewRow')->addClass('hidden');
                $row->addLabel('preview', __('Preview'))->description(__('These timetable days will be added to each selected date.'))->addClass('w-1/4');
                $row->addContent('<div id="ttSmartPreviewDays" class="flex flex-wrap gap-2 text-sm"></div>');

            $row = $table->addRow();
                $row->addLabel('overwrite', __('Overwrite'))->description(__('When checked, existing timetable days for the selected dates are reset and replaced. When unchecked, new timetable days are appended.'))->addClass('w-1/4');
                $row->addCheckbox('overwrite')->setValue('Y')->checked('N');

            $row = $table->addRow()->addClass('right');
                $row->addButton(__('Delete'), null, 'ttDatesDeleteBtn')
                    ->setType('submit')
                    ->setColor('red')
                    ->setIcon('solid', 'delete')
                    ->setName('deleteDates')
                    ->onClick("return confirm('".__('Are you sure you want to delete all timetable days for the selected dates? This cannot be undone.')."');");
                $row->addSubmit(__('Apply'));

            $page->scripts->add('timetableTTDates', '/modules/Timetable Admin/js/ttDates.js', ['version' => filemtime(__DIR__.'/js/ttDates.js')]);

            echo $form->getOutput();
        }
    }
}
