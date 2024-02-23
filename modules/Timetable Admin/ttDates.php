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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Tie Days to Dates'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

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

                $table = $form->addRow()->addTable()->setClass('fullWidth');
                $row = $table->addHeaderRow();

                for ($i = 1; $i < 8; ++$i) {
                    $dowLong = date('l', strtotime("Sunday +$i days"));
                    $dowShort = date('D', strtotime("Sunday +$i days"));

                    $script = '<script type="text/javascript">';
                    $script .= '$(function () {';
                    $script .= "$('#checkall".$dowShort.$values['nameShort']."').click(function () {";
                    $script .= "$('.".$dowShort.$values['nameShort'].":checkbox').attr('checked', this.checked);";
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
                            $column->addContent($formattedDate);
                            if (isset($specialDays[$date]) and $specialDays[$date]['type'] == 'Timing Change') {
                                $column->addContent(__('Timing Change'))->wrap('<span style="color: #f00" title="'.$specialDays[$date]['name'].'">', '</span>');
                            } else {
                                $column->addContent(__('School Day'));
                            }

                            $column->addCheckbox('dates[]')->setValue($i)->setClass($dayOfWeek.$values['nameShort'])->alignCenter();

                            $column->addContent("<br/><a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i."'><img style='margin-top: 3px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a><br/>");

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

            $form->addRow()->addHeading('Multi Add', __('Multi Add'));

            $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = "SELECT gibbonTTDay.gibbonTTDayID as value, CONCAT(gibbonTT.name, ': ', gibbonTTDay.nameShort) as name
                    FROM gibbonTTDay
                    JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)
                    WHERE gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonTT.name, gibbonTTDay.name";

            $table = $form->addRow()->addTable()->setClass('fullWidth smallIntBorder');
            $row = $table->addRow();
                $row->addLabel('gibbonTTDayID', __('Day'));
                $row->addSelect('gibbonTTDayID')->fromQuery($pdo, $sql, $data)->addClass('mediumWidth');

            $row = $table->addRow();
                $row->addLabel('overwrite', __('Overwrite'))->description(__('Should existing timetable days be replaced by the new ones?'));
                $row->addCheckbox('overwrite')->setValue('Y')->checked('N');

            $row = $table->addRow()->addClass('right');
                $row->addContent();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
