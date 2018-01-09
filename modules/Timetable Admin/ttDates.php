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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Tie Days to Dates').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $schoolYear = $result->fetch();
            $gibbonSchoolYearID = $schoolYear['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $schoolYear['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';
        echo '<p>';
        echo __('To multi-add a single timetable day to multiple dates, use the checkboxes in the relevant dates, and then press the Submit button at the bottom of the page.');
        echo '</p>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {

            $form = Form::create('ttDates', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/ttDates_addMultiProcess.php?gibbonSchoolYearID='.$gibbonSchoolYearID);
            
            $form->addHiddenValue('q', $_SESSION[$guid]['address']);
            $form->getRenderer()->setWrapper('form', 'div');
            $form->getRenderer()->setWrapper('row', 'div');
            $form->getRenderer()->setWrapper('cell', 'div');

            while ($values = $result->fetch()) {
                $row = $form->addRow()->addHeading($values['name']);

                list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $values['firstDay']);
                $firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
                list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $values['lastDay']);
                $lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);

                //Count back to first Monday before first day
                $startDayStamp = $firstDayStamp;
                while (date('D', $startDayStamp) != 'Mon') {
                    $startDayStamp = $startDayStamp - 86400;
                }

                //Count forward to first Sunday after last day
                $endDayStamp = $lastDayStamp;
                while (date('D', $endDayStamp) != 'Sun') {
                    $endDayStamp = $endDayStamp + 86400;
                }

                //Get the special days
                try {
                    $dataSpecial = array('gibbonSchoolYearTermID' => $values['gibbonSchoolYearTermID']);
                    $sqlSpecial = 'SELECT date, type, name FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID ORDER BY date';
                    $resultSpecial = $connection2->prepare($sqlSpecial);
                    $resultSpecial->execute($dataSpecial);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $specialDays = $resultSpecial->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

                // Get the TT day names
                try {
                    $dataDay = array();
                    $sqlDay = 'SELECT date, gibbonTTDay.nameShort AS dayName, gibbonTT.nameShort AS ttName FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)';
                    $resultDay = $connection2->prepare($sqlDay);
                    $resultDay->execute($dataDay);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $ttDays = $resultDay->fetchAll(\PDO::FETCH_GROUP);

				//Check which days are school days
                try {
                    $dataDays = array();
                    $sqlDays = "SELECT nameShort, schoolDay FROM gibbonDaysOfWeek";
                    $resultDays = $connection2->prepare($sqlDays);
                    $resultDays->execute($dataDays);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

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
                    $row->addCheckbox('checkall'.$dowShort.$values['nameShort'])->prepend(__($dowLong).'<br/>')->append($script)->addClass('textCenter');
                }

                for ($i = $startDayStamp; $i <= $endDayStamp; $i = $i + 86400) {
                    $date = date('Y-m-d', $i);
                    $dayOfWeek = date('D', $i);
                    $formattedDate = date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i);

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

                            $column->addCheckbox('dates[]')->setValue($i)->addClass($dayOfWeek.$values['nameShort']);

                            $column->addContent("<br/><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i."'><img style='margin-top: 3px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a><br/>");

                            if (isset($ttDays[$date])) {
                                foreach ($ttDays[$date] as $day) {
                                    $column->addContent($day['ttName'].' '.$day['dayName'])->wrap('<b>', '</b>');
                                }
                            }
                        }
                    }
                    ++$count;
                }
            }

            $form->addRow()->addHeading(__('Multi Add'));

            $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = "SELECT gibbonTTDay.gibbonTTDayID as value, CONCAT(gibbonTT.name, ': ', gibbonTTDay.nameShort) as name
                    FROM gibbonTTDay 
                    JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) 
                    WHERE gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonTT.name, gibbonTTDay.name";

            $table = $form->addRow()->addTable()->setClass('fullWidth smallIntBorder');
            $row = $table->addRow();
                $row->addLabel('gibbonTTDayID', __('Day'));
                $row->addSelect('gibbonTTDayID')->fromQuery($pdo, $sql, $data)->addClass('mediumWidth');

            $row = $table->addRow()->addClass('right');
                $row->addContent();
                $row->addSubmit();
            
            echo $form->getOutput();
        }
    }
}
