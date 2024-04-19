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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Domain\Timetable\FacilityBookingGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
include './moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceBooking_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        $bookingGateway = $container->get(FacilityBookingGateway::class);
        $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);

        $data = [
            'foreignKey'     => $_POST['foreignKey'] ?? null,
            'foreignKeyID'   => $_POST['foreignKeyID'] ?? null,
            'timeStart'      => $_POST['timeStart'] ?? null,
            'timeEnd'        => $_POST['timeEnd'] ?? null,
            'reason'         => $_POST['reason'] ?? '',
            'gibbonPersonID' => $_POST['gibbonPersonID'] ?? $session->get('gibbonPersonID'),
        ];
        
        $dates = $_POST['dates'] ?? '';
        $repeat = $_POST['repeat'] ?? '';
        $repeatDaily = $repeat == 'Daily' ? $_POST['repeatDaily'] : null;
        $repeatWeekly = $repeat == 'Weekly' ? $_POST['repeatWeekly'] : null;

        // Validate Inputs
        if (empty($data['foreignKey']) || empty($data['foreignKeyID']) || empty($data['timeStart']) || empty($data['timeEnd']) || empty($repeat) || count($dates) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {

            $failCount = 0;
            $available = '';
            //Scroll through all dates
            foreach ($dates as $date) {
                $gibbonCourseClassID = null;
                $available = isSpaceFree($guid, $connection2, $data['foreignKey'], $data['foreignKeyID'], $date, $data['timeStart'], $data['timeEnd'], $gibbonCourseClassID);

                if (!$available && !empty($gibbonCourseClassID)) {
                    $offTimetable = $specialDayGateway->getIsClassOffTimetableByDate($session->get('gibbonSchoolYearID'), $gibbonCourseClassID, $date);

                    if ($offTimetable) {
                        $available = true;
                    }
                }

                if ($available == false) {
                    ++$failCount;
                } else {
                    $data['date'] = $date;

                    $gibbonTTSpaceBookingID = $bookingGateway->insert($data);
                    $failCount += (int)empty($gibbonTTSpaceBookingID);
                }
            }

            $successCount = count($dates) - $failCount;

            if ($successCount == 0) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } elseif ($successCount < count($dates)) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                // Redirect back to View Timetable by Facility if we started there
                if (isset($_POST['source']) && $_POST['source'] == 'tt') {
                    $ttDate = Format::date($dates[0]);
                    $URL = $session->get('absoluteURL').'/index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.$data['foreignKeyID'].'&ttDate='.$ttDate;
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
