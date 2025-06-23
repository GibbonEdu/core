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

//Checks whether or not a space is free over a given period of time, returning true or false accordingly.
function isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd, &$gibbonCourseClassID = null)
{
    $return = true;

    //Check if school is open
    if (isSchoolOpen($guid, $date, $connection2) == false) {
        $return = false;
    } else {
        if ($foreignKey == 'gibbonSpaceID') {
            //Check timetable including classes moved out
            $ttClear = false;
            try {
                $dataSpace = array('gibbonSpaceID' => $foreignKeyID, 'date' => $date, 'timeStart1' => $timeStart, 'timeStart2' => $timeStart, 'timeStart3' => $timeStart, 'timeEnd1' => $timeEnd, 'timeStart4' => $timeStart, 'timeEnd2' => $timeEnd);
                $sqlSpace = 'SELECT gibbonTTDayRowClass.gibbonSpaceID, gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date, timeStart, timeEnd, gibbonTTSpaceChangeID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) WHERE gibbonTTDayRowClass.gibbonSpaceID=:gibbonSpaceID AND gibbonTTDayDate.date=:date AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))';
                $resultSpace = $connection2->prepare($sqlSpace);
                $resultSpace->execute($dataSpace);
            } catch (PDOException $e) {
                $return = false;
            }
            if ($resultSpace->rowCount() < 1) {
                $ttClear = true;
            } else {
                $ttClashFixed = true;

                while ($rowSpace = $resultSpace->fetch()) {
                    $gibbonCourseClassID = $rowSpace['gibbonCourseClassID'];
                    if ($rowSpace['gibbonTTSpaceChangeID'] == '') {
                        $ttClashFixed = false;
                    }
                }
                if ($ttClashFixed == true) {
                    $ttClear = true;
                }
            }

            if ($ttClear == false) {
                $return = false;
            } else {
                //Check room changes moving in
                try {
                    $dataSpace = array('gibbonSpaceID' => $foreignKeyID, 'date1' => $date, 'date2' => $date, 'timeStart1' => $timeStart, 'timeStart2' => $timeStart, 'timeStart3' => $timeStart, 'timeEnd1' => $timeEnd, 'timeStart4' => $timeStart, 'timeEnd2' => $timeEnd);
                    $sqlSpace = 'SELECT * FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID AND gibbonTTSpaceChange.date=:date1 AND gibbonTTDayDate.date=:date2 AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))';
                    $resultSpace = $connection2->prepare($sqlSpace);
                    $resultSpace->execute($dataSpace);
                } catch (PDOException $e) {
                    $return = false;
                }

                if ($resultSpace->rowCount() > 0) {
                    $return = false;
                } else {
                    //Check bookings
                    try {
                        $dataSpace = array('foreignKeyID' => $foreignKeyID, 'date' => $date, 'timeStart1' => $timeStart, 'timeStart2' => $timeStart, 'timeStart3' => $timeStart, 'timeEnd1' => $timeEnd, 'timeStart4' => $timeStart, 'timeEnd2' => $timeEnd);
                        $sqlSpace = "SELECT * FROM gibbonTTSpaceBooking WHERE foreignKey='gibbonSpaceID' AND foreignKeyID=:foreignKeyID AND date=:date AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))";
                        $resultSpace = $connection2->prepare($sqlSpace);
                        $resultSpace->execute($dataSpace);
                    } catch (PDOException $e) {
                        $return = false;
                    }
                    if ($resultSpace->rowCount() > 0) {
                        $return = false;
                    }
                }
            }
        } elseif ($foreignKey == 'gibbonLibraryItemID') {
            //Check bookings
            try {
                $dataSpace = array('foreignKeyID' => $foreignKeyID, 'date' => $date, 'timeStart1' => $timeStart, 'timeStart2' => $timeStart, 'timeStart3' => $timeStart, 'timeEnd1' => $timeEnd, 'timeStart4' => $timeStart, 'timeEnd2' => $timeEnd);
                $sqlSpace = "SELECT * FROM gibbonTTSpaceBooking WHERE foreignKey='gibbonLibraryItemID' AND foreignKeyID=:foreignKeyID AND date=:date AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))";
                $resultSpace = $connection2->prepare($sqlSpace);
                $resultSpace->execute($dataSpace);
            } catch (PDOException $e) {
                $return = false;
            }
            if ($resultSpace->rowCount() > 0) {
                $return = false;
            }
        }
    }

    return $return;
}
