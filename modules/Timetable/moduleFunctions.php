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

use Gibbon\Domain\Activities\ActivityGateway;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\Location;
use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Http\Url;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\Staff\StaffDutyPersonGateway;

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

//Returns space bookings for the specified user for the 7 days on/after $startDayStamp, or for all users for the 7 days on/after $startDayStamp if no user specified
function getSpaceBookingEvents($guid, $connection2, $startDayStamp, $gibbonPersonID = '')
{
    $return = false;
    $startDayStamp = preg_replace('/[^0-9]/', '', $startDayStamp);

    try {
        if ($gibbonPersonID != '') {
            $dataSpaceBooking = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'startDay' => date('Y-m-d', $startDayStamp), 'endDay' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))));
            $sqlSpaceBooking = "(SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID1 AND date>=:startDay AND  date<=:endDay) UNION (SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID2 AND date>=:startDay AND  date<=:endDay) ORDER BY date, timeStart, name";
        } else {
            $dataSpaceBooking = array('startDay' => date('Y-m-d', $startDayStamp), 'endDay' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))));
            $sqlSpaceBooking = "(SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND  date>=:startDay AND  date<=:endDay) UNION (SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItem' AND  date>=:startDay AND  date<=:endDay) ORDER BY date, timeStart, name";
        }
        $resultSpaceBooking = $connection2->prepare($sqlSpaceBooking);
        $resultSpaceBooking->execute($dataSpaceBooking);
    } catch (PDOException $e) {
    }
    if ($resultSpaceBooking->rowCount() > 0) {
        $return = array();
        $count = 0;
        while ($rowSpaceBooking = $resultSpaceBooking->fetch()) {
            $return[$count][0] = $rowSpaceBooking['gibbonTTSpaceBookingID'];
            $return[$count][1] = $rowSpaceBooking['name'];
            $return[$count][2] = strtotime($rowSpaceBooking['date'].' '.$rowSpaceBooking['timeStart']);
            $return[$count][3] = strtotime($rowSpaceBooking['date'].' '.$rowSpaceBooking['timeEnd']);
            $return[$count][4] = $rowSpaceBooking['timeStart'];
            $return[$count][5] = $rowSpaceBooking['timeEnd'];
            $return[$count][6] = Format::name($rowSpaceBooking['title'], $rowSpaceBooking['preferredName'], $rowSpaceBooking['surname'], 'Staff');
            $return[$count][7] = $rowSpaceBooking['reason'];
            $return[$count][8] = $rowSpaceBooking['gibbonPersonID'];
            $return[$count][9] = $rowSpaceBooking['date'];
            ++$count;
        }
    }

    return $return;
}

//Returns space bookings for the specified space for the 7 days on/after $startDayStamp
function getSpaceBookingEventsSpace($guid, $connection2, $startDayStamp, $gibbonSpaceID)
{
    $return = false;
    $startDayStamp = preg_replace('/[^0-9]/', '', $startDayStamp);

        $dataSpaceBooking = array('gibbonSpaceID' => $gibbonSpaceID, 'startDay' => date('Y-m-d', $startDayStamp), 'endDay' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))));
        $sqlSpaceBooking = "SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBooking.foreignKeyID=:gibbonSpaceID AND date>=:startDay AND  date<=:endDay ORDER BY date, timeStart, name";
        $resultSpaceBooking = $connection2->prepare($sqlSpaceBooking);
        $resultSpaceBooking->execute($dataSpaceBooking);
    if ($resultSpaceBooking->rowCount() > 0) {
        $return = array();
        $count = 0;
        while ($rowSpaceBooking = $resultSpaceBooking->fetch()) {
            $return[$count][0] = $rowSpaceBooking['gibbonTTSpaceBookingID'];
            $return[$count][1] = $rowSpaceBooking['name'];
            $return[$count][2] = strtotime($rowSpaceBooking['date'].' '.$rowSpaceBooking['timeStart']);
            $return[$count][3] = strtotime($rowSpaceBooking['date'].' '.$rowSpaceBooking['timeEnd']);
            $return[$count][4] = $rowSpaceBooking['timeStart'];
            $return[$count][5] = $rowSpaceBooking['timeEnd'];
            $return[$count][6] = Format::name($rowSpaceBooking['title'], $rowSpaceBooking['preferredName'], $rowSpaceBooking['surname'], 'Staff');
            $return[$count][7] = $rowSpaceBooking['reason'];
            $return[$count][8] = $rowSpaceBooking['gibbonPersonID'];
            $return[$count][9] = $rowSpaceBooking['date'];
            ++$count;
        }
    }

    return $return;
}

//Returns events from a Google Calendar XML field, between the time and date specified
function getCalendarEvents($connection2, $guid, $xml, $startDayStamp, $endDayStamp)
{
    global $container, $session;

    $settingGateway = $container->get(SettingGateway::class);
    $ssoMicrosoft = $settingGateway->getSettingByScope('System Admin', 'ssoMicrosoft');
    $ssoMicrosoft = json_decode($ssoMicrosoft, true);

    if (!empty($ssoMicrosoft) && $ssoMicrosoft['enabled'] == 'Y' && $session->has('microsoftAPIAccessToken')) {
        $eventsSchool = array();

        // Create a Graph client
        $oauthProvider = $container->get('Microsoft_Auth');
        if (empty($oauthProvider)) return;

        $graph = new Graph();
        $graph->setAccessToken($session->get('microsoftAPIAccessToken'));

        $startOfWeek = new \DateTimeImmutable(date('Y-m-d H:i:s', $startDayStamp));
        $endOfWeek = new \DateTimeImmutable(date('Y-m-d H:i:s', $endDayStamp+ 86399));

        $queryParams = array(
            'startDateTime' => $startOfWeek->format(\DateTimeInterface::ISO8601),
            'endDateTime' => $endOfWeek->format(\DateTimeInterface::ISO8601),
            // Only request the properties used by the app
            '$select' => 'subject,start,end,location,webLink',
            // Sort them by start time
            '$orderby' => 'start/dateTime',
            // Limit results to 25
            '$top' => 25
          );

        $getEventsUrl = '/me/calendarView?'.http_build_query($queryParams);

        $events = $graph->createRequest('GET', $getEventsUrl)
            // Add the user's timezone to the Prefer header
            ->addHeaders(array(
            'Prefer' => 'outlook.timezone="'."China Standard Time".'"'
            ))
            ->setReturnType(Event::class)
            ->execute();

        foreach ($events as $event) {
            $properties = $event->getProperties();

            $allDay = substr($properties['start']['dateTime'], 11, 8) == '00:00:00' && substr($properties['end']['dateTime'], 11, 8) == '00:00:00';

            $eventsSchool[] = [
                $event->getSubject(),
                $allDay ? 'All Day' : 'Specified Time',
                strtotime($properties['start']['dateTime']),
                strtotime($properties['end']['dateTime']),
                $properties['location']['displayName'],
                $event->getWebLink(),
            ];
        }

        return $eventsSchool;
    }

    $ssoGoogle = $settingGateway->getSettingByScope('System Admin', 'ssoGoogle');
    $ssoGoogle = json_decode($ssoGoogle, true);

    if (!empty($ssoGoogle) && $ssoGoogle['enabled'] == 'Y' && $session->has('googleAPIAccessToken') && $session->has('googleAPICalendarEnabled')) {

        $eventsSchool = array();
        $start = date("Y-m-d\TH:i:s", strtotime(date('Y-m-d', $startDayStamp)));
        $end = date("Y-m-d\TH:i:s", (strtotime(date('Y-m-d', $endDayStamp)) + 86399));

        $service = $container->get('Google_Service_Calendar');
        $getFail = empty($service);

        $calendarListEntry = array();
        try {
            $optParams = array('timeMin' => $start.'+00:00', 'timeMax' => $end.'+00:00', 'singleEvents' => true);
            $calendarListEntry = $service->events->listEvents($xml, $optParams);
        } catch (Exception $e) {
            $getFail = true;
        }

        if ($getFail) {
            $eventsSchool = false;
        } else {
            $count = 0;
            foreach ($calendarListEntry as $entry) {
                $multiDay = false;
                if (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
                    $multiDay = true;
                }
                if ($entry['start']['dateTime'] == '') {
                    if ((strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24) > 1) {
                        $multiDay = true;
                    }
                }

                if ($multiDay) { //This event spans multiple days
                    if ($entry['start']['date'] != $entry['start']['end']) {
                        $days = (strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24);
                    } elseif (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
                        $days = (strtotime(substr($entry['end']['dateTime'], 0, 10)) - strtotime(substr($entry['start']['dateTime'], 0, 10))) / (60 * 60 * 24);
                        ++$days; //A hack for events that span multiple days with times set
                    }
                    for ($i = 0; $i < $days; ++$i) {
                        //WHAT
                        $eventsSchool[$count][0] = $entry['summary'];

                        //WHEN - treat events that span multiple days, but have times set, the same as those without time set
                        $eventsSchool[$count][1] = 'All Day';
                        $eventsSchool[$count][2] = strtotime($entry['start']['date']) + ($i * 60 * 60 * 24);
                        $eventsSchool[$count][3] = null;

                        //WHERE
                        $eventsSchool[$count][4] = $entry['location'];

                        //LINK
                        $eventsSchool[$count][5] = $entry['htmlLink'];

                        ++$count;
                    }
                } else {  //This event falls on a single day
                    //WHAT
                    $eventsSchool[$count][0] = $entry['summary'];

                    //WHEN
                    if ($entry['start']['dateTime'] != '') { //Part of day
                        $eventsSchool[$count][1] = 'Specified Time';
                        $eventsSchool[$count][2] = strtotime(substr($entry['start']['dateTime'], 0, 10).' '.substr($entry['start']['dateTime'], 11, 8));
                        $eventsSchool[$count][3] = strtotime(substr($entry['end']['dateTime'], 0, 10).' '.substr($entry['end']['dateTime'], 11, 8));
                    } else { //All day
                        $eventsSchool[$count][1] = 'All Day';
                        $eventsSchool[$count][2] = strtotime($entry['start']['date']);
                        $eventsSchool[$count][3] = null;
                    }
                    //WHERE
                    $eventsSchool[$count][4] = $entry['location'];

                    //LINK
                    $eventsSchool[$count][5] = $entry['htmlLink'];

                    ++$count;
                }
            }
        }
    } else {
        $eventsSchool = false;
    }

    return $eventsSchool;
}

//TIMETABLE FOR INDIVIUDAL
//$narrow can be "full", "narrow", or "trim" (between narrow and full)
function renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, $title = '', $startDayStamp = '', $q = '', $params = '', $narrow = 'full', $edit = false)
{
    global $session, $container;

    $zCount = 0;
    $output = '';
    $proceed = false;

    $highestAction = getHighestGroupedAction($guid, '/modules/Timetable/tt.php', $connection2);

    if ($highestAction == 'View Timetable by Person_allYears') {
        $proceed = true;
    } else if ($session->get('gibbonSchoolYearIDCurrent') == $session->get('gibbonSchoolYearID')) {

        if ($highestAction == 'View Timetable by Person') {
            $proceed = true;
        } else if ($highestAction == 'View Timetable by Person_my') {
            if ($gibbonPersonID == $session->get('gibbonPersonID')) {
                $proceed = true;
            }
        } else if ($highestAction == 'View Timetable by Person_myChildren') {

                $data = array('gibbonPersonID1' => $session->get('gibbonPersonID'), 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "SELECT gibbonFamilyChild.gibbonPersonID FROM gibbonFamilyChild
                    JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
                    WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID2 AND gibbonFamilyAdult.childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() > 0) {
                $proceed = true;
            }
        }
    }

    if ($proceed == false) {
        $output .= "<div class='error'>".__('You do not have permission to access this timetable at this time.').'</div>';
    } else {
        $self = false;
        if ($gibbonPersonID == $session->get('gibbonPersonID') and $edit == false) {
            $self = true;
            $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

            if (!empty($_POST['fromTT']) && $_POST['fromTT'] == 'Y') {
                $session->set('viewCalendarSchool', !empty($_POST['schoolCalendar']) ? 'Y' : 'N');
                $session->set('viewCalendarPersonal', !empty($_POST['personalCalendar']) ? 'Y' : 'N');
                $session->set('viewCalendarSpaceBooking', !empty($_POST['spaceBookingCalendar']) ? 'Y' : 'N');
            }

            //Update display choices
            if ($session->get('viewCalendarSchool') != false and $session->get('viewCalendarPersonal') != false and $session->get('viewCalendarSpaceBooking') != false) {
                try {
                    $dataDisplay = array('viewCalendarSchool' => $session->get('viewCalendarSchool'), 'viewCalendarPersonal' => $session->get('viewCalendarPersonal'), 'viewCalendarSpaceBooking' => $session->get('viewCalendarSpaceBooking'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sqlDisplay = 'UPDATE gibbonPerson SET viewCalendarSchool=:viewCalendarSchool, viewCalendarPersonal=:viewCalendarPersonal, viewCalendarSpaceBooking=:viewCalendarSpaceBooking WHERE gibbonPersonID=:gibbonPersonID';
                    $resultDisplay = $connection2->prepare($sqlDisplay);
                    $resultDisplay->execute($dataDisplay);
                } catch (PDOException $e) {
                }
            }
        } else {
            $dataRole = ['gibbonPersonID' => $gibbonPersonID];
            $sqlRole = "SELECT gibbonRole.category FROM gibbonPerson JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
            $resultRole = $connection2->prepare($sqlRole);
            $resultRole->execute($dataRole);
            $roleCategory = $resultRole && $resultRole->rowCount() > 0 ? $resultRole->fetch(\PDO::FETCH_COLUMN, 0) : 'Other';
        }

        $viewerIsStaff = $session->get('gibbonRoleIDCurrentCategory') == 'Staff';

        $blank = true;
        if ($startDayStamp == '') {
            $startDayStamp = time();
        }

        //Find out which timetables I am involved in this year
        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        //If I am not involved in any timetables display all within the year
        if ($result->rowCount() == 0) {
            try {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
        }

        //link to other TTs
        if ($result->rowcount() > 1) {
            $output .= "<table class='noIntBorder mt-2' cellspacing='0' style='width: 100%'>";
            $output .= '<tr>';
            $output .= '<td>';
            $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Timetable Chooser').'</span>: ';
            while ($row = $result->fetch()) {
                $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q&gibbonTTID=".$row['gibbonTTID']."$params'>";
                $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."' type='hidden'>";
                $output .= "<input name='schoolCalendar' value='".($session->get('viewCalendarSchool') == 'Y' ? 'Y' : '')."' type='hidden'>";
                $output .= "<input name='personalCalendar' value='".($session->get('viewCalendarPersonal') == 'Y' ? 'Y' : '')."' type='hidden'>";
                $output .= "<input name='spaceBookingCalendar' value='".($session->get('viewCalendarSpaceBooking') == 'Y' ? 'Y' : '')."' type='hidden'>";
                $output .= "<input name='fromTT' value='Y' type='hidden'>";
                $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".$row['name']."'>";
                $output .= '</form>';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);

            $output .= '</td>';
            $output .= '</tr>';
            $output .= '</table>';

            if ($gibbonTTID != '') {
                if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_master.php', 'View Master Timetable')) {
                    $data = array('gibbonTTID' => $gibbonTTID);
                    $sql = "SELECT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT WHERE gibbonTT.gibbonTTID=:gibbonTTID";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonTTID' => $gibbonTTID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID";
                }
                $ttResult = $connection2->prepare($sql);
                $ttResult->execute($data);
                if ($ttResult->rowCount() > 0) {
                    $result = &$ttResult;
                }
            }
        }

        //Display first TT
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $gibbonTTID = $row['gibbonTTID'];
            $nameShortDisplay = $row['nameShortDisplay']; //Store day short name display setting for later
            $thisWeek = time();

            if ($title != false) {
                $output .= '<h2>'.$row['name'].'</h2>';
            }
            $output .= "<table cellspacing='0' class='noIntBorder' style='width: 100%; margin: 10px 0 10px 0'>";
            $output .= '<tr>';
            $output .= "<td style='vertical-align: top;width:360px'>";
            $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q&gibbonTTID=".$row['gibbonTTID']."$params'>";
            $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp - (7 * 24 * 60 * 60)))."' type='hidden'>";
            $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
            $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
            $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
            $output .= "<input name='fromTT' value='Y' type='hidden'>";
            $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='< ".__('Last Week')."'>";
            $output .= '</form>';
            $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q&gibbonTTID=".$row['gibbonTTID']."$params'>";
            $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'],($thisWeek))."' type='hidden'>";
            $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
            $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
            $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
            $output .= "<input name='fromTT' value='Y' type='hidden'>";
            $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".__('This Week')."'>";
            $output .= '</form>';
            $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q&gibbonTTID=".$row['gibbonTTID']."$params'>";
            $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (7 * 24 * 60 * 60)))."' type='hidden'>";
            $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
            $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
            $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
            $output .= "<input name='fromTT' value='Y' type='hidden'>";
            $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".__('Next Week')." >'>";
            $output .= '</form>';
            $output .= '</td>';
            $output .= "<td style='vertical-align: top; text-align: right'>";
            $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q&gibbonTTID=".$row['gibbonTTID']."$params'>";
            $output .= '<span class="relative">';
            $output .= "<input name='ttDate' id='ttDate' aria-label='".__('Choose Date')."' maxlength=10 value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."' type='text' style='width:120px; margin-right: 0px; float: none'> ";
            $output .= '</span>';
            $output .= '<script type="text/javascript">';
            $output .= "var ttDate=new LiveValidation('ttDate');";
            $output .= 'ttDate.add( Validate.Format, {pattern:';
            if ($session->get('i18n')['dateFormatRegEx'] == '') {
                $output .= "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
            } else {
                $output .= $session->get('i18n')['dateFormatRegEx'];
            }
            $output .= ', failureMessage: "Use ';
            if ($session->get('i18n')['dateFormat'] == '') {
                $output .= 'dd/mm/yyyy';
            } else {
                $output .= $session->get('i18n')['dateFormat'];
            }
            $output .= '." } );';
            $output .= 'ttDate.add(Validate.Presence);';
            $output .= '$("#ttDate").datepicker();';
            $output .= '</script>';

            $output .= "<input style='margin-top: 0px; margin-right: -1px; padding-left: 1rem; padding-right: 1rem;' type='submit' value='".__('Go')."'>";
            $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
            $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
            $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
            $output .= "<input name='fromTT' value='Y' type='hidden'>";
            $output .= '</form>';
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '</table>';

            //Check which days are school days
            $daysInWeek = 0;
            $days = array();
            $timeStart = '';
            $timeEnd = '';
            try {
                $dataDays = array();
                $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            } catch (PDOException $e) {
            }
            $days = $resultDays->fetchAll();
            $daysInWeek = $resultDays->rowCount();
            foreach ($days as $day) {
                if ($timeStart == '' or $timeEnd == '') {
                    $timeStart = $day['schoolStart'];
                    $timeEnd = $day['schoolEnd'];
                } else {
                    if ($day['schoolStart'] < $timeStart) {
                        $timeStart = $day['schoolStart'];
                    }
                    if ($day['schoolEnd'] > $timeEnd) {
                        $timeEnd = $day['schoolEnd'];
                    }
                }
            }

            //Sunday week adjust for timetable on home page (so Sundays show next week if the week starts on Sunday or Monday—i.e. it's Sunday now and Sunday is not a school day)
            $homeSunday = true ;
            if ($q == '' && ($session->get('firstDayOfTheWeek') == 'Monday' || $session->get('firstDayOfTheWeek') == 'Sunday')) {
                try {
                    $dataDays = array();
                    $sqlDays = "SELECT nameShort FROM gibbonDaysOfWeek WHERE nameShort='Sun' AND schoolDay='N'";
                    $resultDays = $connection2->prepare($sqlDays);
                    $resultDays->execute($dataDays);
                } catch (PDOException $e) { }
                if ($resultDays->rowCount() == 1) {
                    $homeSunday = false ;
                }
            }

            //If school is closed on Sunday, and it is a Sunday, count forward, otherwise count back
            if (!$homeSunday AND date('D', $startDayStamp) == 'Sun') {
                $startDayStamp = $startDayStamp + 86400;
            }
            else {
                while (date('D', $startDayStamp) != $days[0]['nameShort']) {
                    $startDayStamp = $startDayStamp - 86400;
                }
            }

            //Count forward to the end of the week
            $endDayStamp = $startDayStamp + (86400 * ($daysInWeek - 1));

            $schoolCalendarAlpha = 0.85;
            $ttAlpha = 1.0;

            if ($session->get('viewCalendarSchool') != 'N' or $session->get('viewCalendarPersonal') != 'N' or $session->get('viewCalendarSpaceBooking') != 'N') {
                $ttAlpha = 0.75;
            }

            //Get school calendar array
            $allDay = false;
            $eventsSchool = false;
            if ($self == true and $session->get('viewCalendarSchool') == 'Y' && $session->has('googleAPIAccessToken')) {
                if ($session->get('calendarFeed') != '') {
                    $eventsSchool = getCalendarEvents($connection2, $guid,  $session->get('calendarFeed'), $startDayStamp, $endDayStamp);
                }
                //Any all days?
                if ($eventsSchool != false) {
                    foreach ($eventsSchool as $event) {
                        if ($event[1] == 'All Day') {
                            $allDay = true;
                        }
                    }
                }
            }

            // Get the date range to check for activities
            $dateRange = new DatePeriod(
                (new DateTime(date('Y-m-d H:i:s', $startDayStamp)))->modify('-1 day'),
                new DateInterval('P1D'),
                (new DateTime(date('Y-m-d H:i:s', $endDayStamp)))->modify('+1 day')
            );

            // Get all special days
            $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
            $specialDays = $specialDayGateway->selectSpecialDaysByDateRange($dateRange->start->format('Y-m-d'), $dateRange->end->format('Y-m-d'))->fetchGroupedUnique();

            // Get all activities for this student or staff
            $activityGateway = $container->get(ActivityGateway::class);
            $activities = [];

            $dateType = $container->get(SettingGateway::class)->getSettingByScope('Activities', 'dateType');
            $activityList = $activityGateway->selectActiveEnrolledActivities($session->get('gibbonSchoolYearID'), $gibbonPersonID, $dateType, date('Y-m-d', $startDayStamp))->fetchAll();

            foreach ($dateRange as $dateObject) {
                $date = $dateObject->format('Y-m-d');
                $weekday = $dateObject->format('l');
                foreach ($activityList as $activity) {
                    // Add activities that match the weekday and the school is open
                    if (empty($activity['dayOfWeek']) || $activity['dayOfWeek'] != $weekday) continue;
                    if ($date < $activity['dateStart'] || $date > $activity['dateEnd'] ) continue;

                    if (isSchoolOpen($guid, $date, $connection2)) {
                        $activities[] = [
                            0 => $activity['name'],
                            1 => '',
                            2 => strtotime($date.' '.$activity['timeStart']),
                            3 => strtotime($date.' '.$activity['timeEnd']),
                            4 => !empty($activity['space'])? $activity['space'] : $activity['locationExternal'] ?? '',
                            5 => Url::fromModuleRoute('Activities', 'activities_my.php'),
                            6 => $specialDays[$date] ?? [],
                            // 5 => Url::fromHandlerModuleRoute('fullscreen.php', 'Activities', 'activities_view_full.php')->withQueryParams(['gibbonActivityID' => $activity['gibbonActivityID'], 'width' => 1000, 'height' => 500]),
                        ];

                    }

                }

            }

            //Get personal calendar array
            $eventsPersonal = false;
            if ($self == true and $session->get('viewCalendarPersonal') == 'Y') {
                $eventsPersonal = getCalendarEvents($connection2, $guid,  $session->get('calendarFeedPersonal'), $startDayStamp, $endDayStamp);

                //Any all days?
                if ($eventsPersonal != false) {
                    foreach ($eventsPersonal as $event) {
                        if ($event[1] == 'All Day') {
                            $allDay = true;
                        }
                    }
                }
            }

            $spaceBookingAvailable = isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage.php');
            $eventsSpaceBooking = false;
            if ($spaceBookingAvailable) {
                //Get space booking array
                if ($self == true and $session->get('viewCalendarSpaceBooking') == 'Y') {
                    $eventsSpaceBooking = getSpaceBookingEvents($guid, $connection2, $startDayStamp, $session->get('gibbonPersonID'));
                }
            }

            if ($viewerIsStaff && $roleCategory == 'Staff') {
                // STAFF DUTY
                // Add duty to the timetable in the same way as activities
                $staffDutyGateway = $container->get(StaffDutyPersonGateway::class);
                $staffDutyList = $staffDutyGateway->selectDutyByPerson($gibbonPersonID)->fetchAll();
                $staffDuty = [];
                
                foreach ($dateRange as $dateObject) {
                    $date = $dateObject->format('Y-m-d');
                    $weekday = $dateObject->format('l');
                    foreach ($staffDutyList as $duty) {
                        // Add duty that matched the weekday and the school is open
                        if (empty($duty['dayOfWeek']) || $duty['dayOfWeek'] != $weekday) continue;

                        if (isSchoolOpen($guid, $date, $connection2)) {
                            $staffDuty[] = [
                                0 => $duty['name'],
                                1 => '',
                                2 => strtotime($date.' '.$duty['timeStart']),
                                3 => strtotime($date.' '.$duty['timeEnd']),
                                4 => '',
                                5 => Url::fromModuleRoute('Staff', 'staff_duty.php'),
                                6 => $specialDays[$date] ?? [],
                            ];

                        }
                    }
                }

                // STAFF COVERAGE
                // Add coverage as a space booking *for now*
                $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

                $criteria = $staffCoverageGateway->newQueryCriteria()
                    ->filterBy('dateStart', date('Y-m-d', $startDayStamp))
                    ->filterBy('dateEnd', date('Y-m-d', $endDayStamp))
                    ->filterBy('status', 'Accepted');
                $coverageList = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, false);
                $staffCoverage = [];

                foreach ($coverageList as $coverage) {
                    $fullName = !empty($coverage['surnameAbsence']) 
                        ? Format::name($coverage['titleAbsence'], $coverage['preferredNameAbsence'], $coverage['surnameAbsence'], 'Staff', false, true)
                        : Format::name($coverage['titleStatus'], $coverage['preferredNameStatus'], $coverage['surnameStatus'], 'Staff', false, true);

                    $staffCoverage[] = [
                        0 => 'Coverage',
                        1 => $coverage['allDay'],
                        2 => strtotime($date.' '.$coverage['timeStart']),
                        3 => strtotime($date.' '.$coverage['timeEnd']),
                        4 => $coverage['date'],
                        5 => $coverage,
                        6 => $fullName,
                    ];
                }

                // STAFF ABSENCE
                // Add an absence as a fake all-day personal event, so it doesn't overlap the calendar (which subs need to see!)
                $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);

                $criteria = $staffAbsenceGateway->newQueryCriteria()
                    ->filterBy('dateStart', date('Y-m-d', $startDayStamp))
                    ->filterBy('dateEnd', date('Y-m-d', $endDayStamp))
                    ->filterBy('status', 'Approved');
                $absenceList = $staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID, 'coverage');
                $canViewAbsences = isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php');

                // Group the absences by date and collect any coverage information separately
                $absenceList = array_reduce($absenceList->toArray(), function ($group, $item) {
                    $item['coverageList'] = $group[$item['date']]['coverageList'] ?? [];
                    $item['coverageList'][] = ['date' => $item['date'], 'gibbonTTDayRowClassID' => $item['gibbonTTDayRowClassID'], 'coverageName' => Format::name($item['titleCoverage'], $item['preferredNameCoverage'], $item['surnameCoverage'], 'Staff', false, true)];

                    $group[$item['date']] = $item;

                    return $group;
                }, []);
                
                foreach ($absenceList as $absence) {
                    $summary = __('Absent');
                    $allDay = true;
                    $url = $canViewAbsences
                        ? $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_details.php&gibbonStaffAbsenceID='.$absence['gibbonStaffAbsenceID']
                        : '';

                    $eventsPersonal[] = [$summary, 'All Day', strtotime($absence['date']), null, $absence['allDay'], $url, $absence['coverageList'], $absence['timeStart'], $absence['timeEnd']];
                }
            }

            //Count up max number of all day events in a day
            $eventsCombined = false;
            $maxAllDays = 0;
            if ($allDay == true) {
                if ($eventsPersonal != false and $eventsSchool != false) {
                    $eventsCombined = array_merge($eventsSchool, $eventsPersonal);
                } elseif ($eventsSchool != false) {
                    $eventsCombined = $eventsSchool;
                } elseif ($eventsPersonal != false) {
                    $eventsCombined = $eventsPersonal;
                }

                // Sort $eventsCombined by the value of their start timestamp (key = 2) ascendingly.
                // See getCalendarEvents() for field details of each events.
                usort($eventsCombined, fn($a, $b) => $a[2] <=> $b[2]);

                $currentAllDays = 0;
                $lastDate = '';
                $currentDate = '';
                foreach ($eventsCombined as $event) {
                    if ($event[1] == 'All Day') {
                        $currentDate = date('Y-m-d', $event[2]);
                        if ($lastDate != $currentDate) {
                            $currentAllDays = 0;
                        }
                        ++$currentAllDays;

                        if ($currentAllDays > $maxAllDays) {
                            $maxAllDays = $currentAllDays;
                        }

                        $lastDate = $currentDate;
                    }
                }
            }

            //Max diff time for week based on timetables
            try {
                $dataDiff = array('date1' => date('Y-m-d', ($startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($endDayStamp + (86400 * 1))), 'gibbonTTID' => $row['gibbonTTID']);
                $sqlDiff = 'SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID';
                $resultDiff = $connection2->prepare($sqlDiff);
                $resultDiff->execute($dataDiff);
            } catch (PDOException $e) {
            }
            while ($rowDiff = $resultDiff->fetch()) {
                try {
                    $dataDiffDay = array('gibbonTTColumnID' => $rowDiff['gibbonTTColumnID']);
                    $sqlDiffDay = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart';
                    $resultDiffDay = $connection2->prepare($sqlDiffDay);
                    $resultDiffDay->execute($dataDiffDay);
                } catch (PDOException $e) {
                }
                while ($rowDiffDay = $resultDiffDay->fetch()) {
                    if ($rowDiffDay['timeStart'] < $timeStart) {
                        $timeStart = $rowDiffDay['timeStart'];
                    }
                    if ($rowDiffDay['timeEnd'] > $timeEnd) {
                        $timeEnd = $rowDiffDay['timeEnd'];
                    }
                }
            }

            //Max diff time for week based on special days timing change
            foreach ($specialDays as $date => $specialDay) {
                if ($specialDay['type'] != 'Timing Change') continue;

                if (!empty($specialDay['schoolStart']) && $specialDay['schoolStart'] < $timeStart) {
                    $timeStart = $specialDay['schoolStart'];
                }
                if (!empty($specialDay['schoolEnd']) && $specialDay['schoolEnd'] > $timeEnd) {
                    $timeEnd = $specialDay['schoolEnd'];
                }
            }

            // Max diff based on all other calendar events, activities and duty
            $maxDiffEvents = function ($events) use (&$startDayStamp, &$timeStart, &$timeEnd) {
                foreach ($events as $event) {
                    if (date('Y-m-d', $event[2]) <= date('Y-m-d', ($startDayStamp + (86400 * 6)))) {
                        if ($event[1] != 'All Day') {
                            if (date('H:i:s', $event[2]) < $timeStart) {
                                $timeStart = date('H:i:s', $event[2]);
                            }
                            if (date('H:i:s', $event[3]) > $timeEnd) {
                                $timeEnd = date('H:i:s', $event[3]);
                            }
                            if (date('Y-m-d', $event[2]) != date('Y-m-d', $event[3])) {
                                $timeEnd = '23:59:59';
                            }
                        }
                    }
                }
            };
            
            if (!empty($eventsSchool) && $self == true) $maxDiffEvents($eventsSchool);
            if (!empty($eventsPersonal) && $self == true) $maxDiffEvents($eventsPersonal);
            if (!empty($eventsSpaceBooking) && $self == true) $maxDiffEvents($eventsSpaceBooking);
            if (!empty($activities)) $maxDiffEvents($activities);
            if (!empty($staffCoverage)) $maxDiffEvents($staffCoverage);
            if (!empty($staffDuty)) $maxDiffEvents($staffDuty);

            //Final calc
            $diffTime = strtotime($timeEnd) - strtotime($timeStart);

            if ($narrow == 'trim') {
                $width = (ceil(640 / $daysInWeek) - 20).'px';
            } elseif ($narrow == 'narrow') {
                $width = (ceil(515 / $daysInWeek) - 20).'px';
            } else {
                $width = (ceil(690 / $daysInWeek) - 20).'px';
            }

            $count = 0;

            $output .= '<div id="ttWrapper" class="overflow-x-scroll sm:overflow-x-auto overflow-y-hidden mb-6 p-1">';
            $output .= "<table cellspacing='0' class='mini mb-1' cellspacing='0' style='width: 100%; min-width: ";
            if ($narrow == 'trim') {
                $output .= '700px';
            } elseif ($narrow == 'narrow') {
                $output .= '575px';
            } else {
                $output .= '750px';
            }
            $output .= ";'>";
                //Spit out controls for displaying calendars
                if ($self == true and ($session->get('calendarFeed') != '' || $session->get('calendarFeedPersonal') != '' || $session->get('viewCalendarSpaceBooking') != '')) {
                    $output .= "<tr class='head' style='height: 37px;'>";
                    $output .= "<th class='ttCalendarBar' colspan=".($daysInWeek + 1).'>';
                    $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params."' style='padding: 5px 5px 0 0'>";

                    $displayCalendars = ($session->has('googleAPIAccessToken') && $session->has('googleAPICalendarEnabled')) || $session->has('microsoftAPIAccessToken');
                    if ($session->has('calendarFeed') && $session->has('googleAPIAccessToken') && $session->has('googleAPICalendarEnabled')) {
                        $checked = '';
                        if ($session->get('viewCalendarSchool') == 'Y') {
                            $checked = 'checked';
                        }
                        $output .= "<span class='ttSchoolCalendar' style='opacity: $schoolCalendarAlpha'>".__('School Calendar');
                        $output .= "<input $checked style='margin-left: 3px' type='checkbox' name='schoolCalendar' onclick='submit();'/>";
                        $output .= '</span>';
                    }
                    if ($displayCalendars) {
                        $checked = '';
                        if ($session->get('viewCalendarPersonal') == 'Y') {
                            $checked = 'checked';
                        }
                        $output .= "<span class='ttPersonalCalendar' style='opacity: $schoolCalendarAlpha'>".__('Personal Calendar');
                        $output .= "<input $checked style='margin-left: 3px' type='checkbox' name='personalCalendar' onclick='submit();'/>";
                        $output .= '</span>';
                    }
                    if ($spaceBookingAvailable) {
                        if ($session->get('viewCalendarSpaceBooking') != '') {
                            $checked = '';
                            if ($session->get('viewCalendarSpaceBooking') == 'Y') {
                                $checked = 'checked';
                            }
                            $output .= "<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'><a style='color: #fff' href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable/spaceBooking_manage.php'>".__('Bookings').'</a> ';
                            $output .= "<input $checked style='margin-left: 3px' type='checkbox' name='spaceBookingCalendar' aria-label='".__('Space Booking Calendar')."' onclick='submit();'/>";
                            $output .= '</span>';
                        }
                    }

                    $output .= "<input type='hidden' name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."'>";
                    $output .= "<input name='fromTT' value='Y' type='hidden'>";
                    $output .= '</form>';
                    $output .= '</th>';
                    $output .= '</tr>';
                }

            $output .= "<tr class='head'>";
            $output .= "<th style='vertical-align: top; width: 70px; text-align: center'>";
            //Calculate week number
            $week = getWeekNumber($startDayStamp, $connection2, $guid);
            if ($week != false) {
                $output .= sprintf(__('Week %1$s'), $week).'<br/>';
            }
            $output .= "<span style='font-weight: normal; font-style: italic;'>".__('Time').'<span>';
            $output .= '</th>';
            $count = 0;
            foreach ($days as $day) {
                if ($day['schoolDay'] == 'Y') {
                    if ($count == 0) {
                        $firstSequence = $day['sequenceNumber'];
                    }
                    $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                    unset($rowDay);
                    $color = '';

                    $dataDay = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))), 'gibbonTTID' => $gibbonTTID);
                    $sqlDay = 'SELECT nameShort, color, fontColor FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
                    $resultDay = $connection2->prepare($sqlDay);
                    $resultDay->execute($dataDay);
                    if ($resultDay->rowCount() == 1) {
                        $rowDay = $resultDay->fetch();
                        if ($rowDay['color'] != '') {
                            $color .= "; background-color: ".$rowDay['color']."; background-image: none";
                        }
                        if ($rowDay['fontColor'] != '') {
                            $color .= "; color: ".$rowDay['fontColor'];
                        }
                    }

                    $today = ((date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))) == date($session->get('i18n')['dateFormatPHP'])) ? "class='ttToday'" : '');
                    $output .= "<th $today style='vertical-align: top; text-align: center; width: ";

                    if ($narrow == 'trim') {
                        $output .= (550 / $daysInWeek);
                    }
                    elseif ($narrow == 'narrow') {
                        $output .= (375 / $daysInWeek);
                    }
                    else {
                        $output .= (550 / $daysInWeek);
                    }
                    $output .= "px".$color."'>";
                    if ($nameShortDisplay != 'Timetable Day Short Name') {
                        $output .= __($day['nameShort']).'<br/>';
                    }
                    else {
                        if (!empty($rowDay['nameShort']) && $rowDay['nameShort'] != '') {
                            $output .= $rowDay['nameShort'].'<br/>';
                        }
                        else {
                            $output .= __($day['nameShort']).'<br/>';
                        }
                    }
                    $output .= "<span style='font-size: 80%; font-style: italic'>".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))).'</span><br/>';

                    $dateCheck = date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection)));

                    if (!empty($specialDays[$dateCheck]) && $specialDays[$dateCheck]['type'] == 'Timing Change') {
                        $output .= "<span style='font-size: 80%; font-weight: bold'><u>".$specialDays[$dateCheck]['name'].'</u></span>';
                    }
                    $output .= '</th>';
                }
                $count ++;
            }
            $output .= '</tr>';

            //Space for all day events
            if (($eventsSchool == true or $eventsPersonal == true) and $allDay == true and $eventsCombined != null) {
                $output .= "<tr style='height: ".((31 * $maxAllDays) + 5)."px'>";
                $output .= "<td style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888; border-bottom: 1px solid #888'>";
                $output .= "<span style='font-size: 80%'><b>".sprintf(__('All Day%1$s Events'), '<br/>').'</b></span>';
                $output .= '</td>';
                $output .= "<td colspan=$daysInWeek style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888; border-bottom: 1px solid #888'>";
                $output .= '</td>';
                $output .= '</tr>';
            }

            $output .= "<tr style='height:".(ceil($diffTime / 60) + 14)."px'>";
            $output .= "<td class='ttTime' style='height: 300px; width: 75px; max-width: 75px; text-align: center; vertical-align: top'>";
            $output .= "<div style='position: relative;'>";
            $countTime = 0;
            $time = $timeStart;
            $output .= "<div $title style='z-index: ".$zCount."; position: absolute; top: -3px; width: 100%; min-width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
            $output .= substr($time, 0, 5).'<br/>';
            $output .= '</div>';
            $time = date('H:i:s', strtotime($time) + 3600);
            $spinControl = 0;
            while ($time <= $timeEnd and $spinControl < (23 - substr($timeStart, 0, 2))) {
                ++$countTime;
                $output .= "<div $title style='z-index: $zCount; position: absolute; top:".(($countTime * 60) - 5)."px ; width: 100%; min-width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
                $output .= substr($time, 0, 5).'<br/>';
                $output .= '</div>';
                $time = date('H:i:s', strtotime($time) + 3600);
                ++$spinControl;
            }

            $output .= '</div>';
            $output .= '</td>';

            //Run through days of the week
            foreach ($days as $day) {
                if ($day['schoolDay'] == 'Y') {
                    $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                    //Check to see if day is term time
                    $isDayInTerm = false;
                    try {
                        $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                        $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultTerm = $connection2->prepare($sqlTerm);
                        $resultTerm->execute($dataTerm);
                    } catch (PDOException $e) {
                    }
                    while ($rowTerm = $resultTerm->fetch()) {
                        if (date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) >= $rowTerm['firstDay'] and date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) <= $rowTerm['lastDay']) {
                            $isDayInTerm = true;
                        }
                    }

                    $isSchoolOpen = true;
                    $specialDayStart = '';
                    $specialDayEnd = '';
                    $specialDay = [];

                    if ($isDayInTerm == true) {
                        //Check for school closure day
                        $dateCheck = date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection)));

                        $specialDay = $specialDays[$dateCheck] ?? [];
                        

                        if (!empty($specialDay)) {
                            $specialDay['gibbonYearGroupIDList'] = explode(',', $specialDay['gibbonYearGroupIDList'] ?? '');
                            $specialDay['gibbonFormGroupIDList'] = explode(',', $specialDay['gibbonFormGroupIDList'] ?? '');

                            if ($specialDay['type'] == 'School Closure') {
                                $isSchoolOpen = false;
                            } elseif ($specialDay['type'] == 'Timing Change' || $specialDay['type'] == 'Off Timetable') {
                                $specialDayStart = $specialDay['schoolStart'];
                                $specialDayEnd = $specialDay['schoolEnd'];
                            } elseif ($specialDay['type'] == 'Off Timetable') {
                                $specialDayStart = $specialDay['schoolStart'];
                                $specialDayEnd = $specialDay['schoolEnd'];
                            }
                        }
                    } else {
                        $isSchoolOpen = false;
                    }

                    $dayOutput = renderTTDay($guid, $connection2, $row['gibbonTTID'], $isSchoolOpen, $startDayStamp, $dateCorrection, $daysInWeek, $gibbonPersonID, $timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $activities ?? [], $staffDuty ?? [], $staffCoverage ?? [], $diffTime, $maxAllDays, $narrow, $specialDayStart, $specialDayEnd, $specialDay, $roleCategory, $edit);

                    if ($dayOutput == '') {
                        $dayOutput .= "<td style='text-align: center; vertical-align: top; font-size: 11px'></td>";
                    }

                    $output .= $dayOutput;
                }
            }
            $output .= '</tr>';
            $output .= '</table>';
            $output .= '</div>';
        }
    }

    return $output;
}

function renderTTDay($guid, $connection2, $gibbonTTID, $schoolOpen, $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $gridTimeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $activities, $staffDuty, $staffCoverage, $diffTime, $maxAllDays, $narrow, $specialDayStart = '', $specialDayEnd = '', $specialDay = [], $roleCategory = '', $edit = false)
{
    global $session;

    $schoolCalendarAlpha = 0.90;
    $ttAlpha = 1.0;

    if ($session->get('viewCalendarSchool') != 'N' or $session->get('viewCalendarPersonal') != 'N' or $session->get('viewCalendarSpaceBooking') != 'N') {
        $ttAlpha = 0.75;
    }

    $date = date('Y-m-d', ($startDayStamp + (86400 * $count)));

    $self = false;
    if ($gibbonPersonID == $session->get('gibbonPersonID') and $edit == false) {
        $self = true;
    }

    try {
        $dataDay = array('gibbonTTID' => $gibbonTTID, 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
        $sqlDay = 'SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date';
        $resultDay = $connection2->prepare($sqlDay);
        $resultDay->execute($dataDay);
    } catch (PDOException $e) {}

    $offTimetable = false;
    if ($roleCategory == 'Student') {
        // Display off-timetable days for students based on their year group and form group
        $dataEnrolment = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')];
        $sqlEnrolment = "SELECT gibbonYearGroupID, gibbonFormGroupID FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID";
        $resultEnrolment = $connection2->prepare($sqlEnrolment);
        $resultEnrolment->execute($dataEnrolment);

        $enrolment = $resultEnrolment && $resultEnrolment->rowCount() > 0 ? $resultEnrolment->fetch() : [];
        if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable' && !empty($enrolment)) {
            $offTimetable |= in_array($enrolment['gibbonYearGroupID'], $specialDay['gibbonYearGroupIDList'] ?? []);
            $offTimetable |= in_array($enrolment['gibbonFormGroupID'], $specialDay['gibbonFormGroupIDList'] ?? []);
        }
    } elseif ($roleCategory == 'Staff') {
        // Display off-timetable days for staff on days that have not been timetabled
        if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable' && $resultDay->rowCount() == 0) {
            $offTimetable = true;
        }
    }

    if ($narrow == 'trim') {
        $width = (ceil(640 / $daysInWeek) - 20).'px';
    } elseif ($narrow == 'narrow') {
        $width = (ceil(515 / $daysInWeek) - 20).'px';
    } else {
        $width = (ceil(690 / $daysInWeek) - 20).'px';
    }

    $output = '';
    $blank = true;

    $zCount = 0;
    $allDay = 0;

    if ($schoolOpen == false || $offTimetable == true) {
        $output .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
        $output .= "<div style='position: relative'>";
        $output .= "<div class='".($offTimetable ? 'bg-blue-200 border border-blue-700 text-blue-700' : 'ttClosure text-red-700')."' style='z-index: $zCount; position: absolute; width: 100%; min-width: $width ; height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; opacity: $ttAlpha'>";
        $output .= "<div style='position: relative; top: 50%' title='".($specialDay['description'] ?? '' )."'>";
        $output .= $offTimetable ? __('School Day').'<br/><br/>'.__('Off Timetable') : __('School Closed');
        $output .= '<br/><br/>'.($specialDay['name'] ?? '');
        $output .= '</div>';
        $output .= '</div>';

        $zCount = 1;

        //Draw periods from school calendar
        if ($eventsSchool != false) {
            $height = 0;
            $top = 0;
            $dayTimeStart = '';
            foreach ($eventsSchool as $event) {
                if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                    if ($event[1] == 'All Day') {
                        $label = $event[0];
                        $title = '';
                        if (strlen($label) > 20) {
                            $label = substr($label, 0, 20).'...';
                            $title = "title='".$event[0]."'";
                        }
                        $height = 30;
                        $top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
                        $output .= "<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                        $output .= "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>';
                        $output .= '</div>';
                        ++$allDay;
                    } else {
                        $label = $event[0];
                        $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
                        $height = ceil(($event[3] - $event[2]) / 60);
                        $charCut = 20;
                        if ($height < 20) {
                            $charCut = 12;
                        }
                        if (strlen($label) > $charCut) {
                            $label = substr($label, 0, $charCut).'...';
                            $title = "title='".$event[0].' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
                        }
                        $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                        $output .= "<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                        $output .= "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>';
                        $output .= '</div>';
                    }
                    ++$zCount;
                }
            }
        }

        //Draw periods from personal calendar
        if ($eventsPersonal != false) {
            $height = 0;
            $top = 0;
            $bg = "rgba(103,153,207,$schoolCalendarAlpha)";
            foreach ($eventsPersonal as $event) {
                if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                    if ($event[1] == 'All Day') {
                        $label = $event[0];
                        $title = '';
                        if (strlen($label) > 20) {
                            $label = substr($label, 0, 20).'...';
                            $title = "title='".htmlPrep($event[0])."'";
                        }
                        $height = 30;
                        $top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
                        $output .= "<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                        $output .= !empty($event[5])
                            ? "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>'
                            : $label;
                        $output .= '</div>';
                        ++$allDay;
                    } else {
                        $label = $event[0];
                        $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
                        $height = ceil(($event[3] - $event[2]) / 60);
                        $charCut = 20;
                        if ($height < 20) {
                            $charCut = 12;
                        }
                        if (strlen($label) > $charCut) {
                            $label = substr($label, 0, $charCut).'...';
                            $title = "title='".htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
                        }
                        $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                        $output .= "<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                        $output .= !empty($event[5])
                            ? "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>'
                            : $label;
                        $output .= '</div>';
                    }
                    ++$zCount;
                }
            }
        }

        //Draw space bookings and staff coverage
        if ($eventsSpaceBooking != false) {
            $dayTimeStart = $gridTimeStart;
            $startPad = 0;
            $output .= "<div style='position: relative'>";

            $height = 0;
            $width = (ceil(690 / $daysInWeek) - 20).'px';
            
            $top = 0;
            foreach ($eventsSpaceBooking as $event) {
                if ($event[9] == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                    $height = ceil((strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[5]) - strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[4])) / 60);
                    $top = (ceil((strtotime($event[9].' '.$event[4]) - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$dayTimeStart)) / 60 + ($startPad / 60))).'px';
                    if ($height < 45) {
                        $label = $event[1];
                        $title = "title='".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).' '.$event[6]."'";
                    } else {
                        $label = $event[1]."<br/><span style='font-weight: normal'>".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).'<br/>'.$event[6].'</span>';
                        $title = '';
                    }

                    if ($height > 56) {
                        $label .= '<br/>'.Format::small(Format::truncate($event[7], 60));
                    } 
                    
                    $output .= "<div class='ttSpaceBookingCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                    $output .= $label;
                    $output .= '</div>';
                    ++$zCount;
                }
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '</td>';
    } else {

        //Make array of space changes
        $spaceChanges = array();

            $dataSpaceChange = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
            $sqlSpaceChange = 'SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date';
            $resultSpaceChange = $connection2->prepare($sqlSpaceChange);
            $resultSpaceChange->execute($dataSpaceChange);
        while ($rowSpaceChange = $resultSpaceChange->fetch()) {
            $spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][0] = $rowSpaceChange['space'];
            $spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][1] = $rowSpaceChange['phoneInternal'];
        }

        //Get day start and end!
        $dayTimeStart = '';
        $dayTimeEnd = '';
        try {
            $dataDiff = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))), 'gibbonTTID' => $gibbonTTID);
            $sqlDiff = 'SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
            $resultDiff = $connection2->prepare($sqlDiff);
            $resultDiff->execute($dataDiff);
        } catch (PDOException $e) {
        }
        while ($rowDiff = $resultDiff->fetch()) {
            if ($dayTimeStart == '') {
                $dayTimeStart = $rowDiff['timeStart'];
            }
            if ($rowDiff['timeStart'] < $dayTimeStart) {
                $dayTimeStart = $rowDiff['timeStart'];
            }
            if ($dayTimeEnd == '') {
                $dayTimeEnd = $rowDiff['timeEnd'];
            }
            if ($rowDiff['timeEnd'] > $dayTimeEnd) {
                $dayTimeEnd = $rowDiff['timeEnd'];
            }
        }
        if ($specialDayStart != '') {
            $dayTimeStart = $specialDayStart;
        }
        if ($specialDayEnd != '') {
            $dayTimeEnd = $specialDayEnd;
        }

        $dayDiffTime = strtotime($dayTimeEnd) - strtotime($dayTimeStart);

        $startPad = strtotime($dayTimeStart) - strtotime($gridTimeStart);

        $today = ((date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $count))) == date($session->get('i18n')['dateFormatPHP'])) ? "class='ttToday'" : '');
        $output .= "<td $today style='text-align: center; vertical-align: top; font-size: 11px'>";

        if ($resultDay->rowCount() == 1) {
            $rowDay = $resultDay->fetch();
            $zCount = 0;
            $output .= "<div style='position: relative'>";

            //Draw outline of the day
            try {
                $dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
                $sqlPeriods = 'SELECT gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd';
                $resultPeriods = $connection2->prepare($sqlPeriods);
                $resultPeriods->execute($dataPeriods);
            } catch (PDOException $e) {
            }
            while ($rowPeriods = $resultPeriods->fetch()) {
                $isSlotInTime = false;
                if ($rowPeriods['timeStart'] <= $dayTimeStart and $rowPeriods['timeEnd'] > $dayTimeStart) {
                    $isSlotInTime = true;
                } elseif ($rowPeriods['timeStart'] >= $dayTimeStart and $rowPeriods['timeEnd'] <= $dayTimeEnd) {
                    $isSlotInTime = true;
                } elseif ($rowPeriods['timeStart'] < $dayTimeEnd and $rowPeriods['timeEnd'] >= $dayTimeEnd) {
                    $isSlotInTime = true;
                }

                if ($isSlotInTime == true) {
                    $effectiveStart = $rowPeriods['timeStart'];
                    $effectiveEnd = $rowPeriods['timeEnd'];
                    if ($dayTimeStart > $rowPeriods['timeStart']) {
                        $effectiveStart = $dayTimeStart;
                    }
                    if ($dayTimeEnd < $rowPeriods['timeEnd']) {
                        $effectiveEnd = $dayTimeEnd;
                    }

                    $height = ceil((strtotime($effectiveEnd) - strtotime($effectiveStart)) / 60);
                    $top = ceil(((strtotime($effectiveStart) - strtotime($dayTimeStart)) + $startPad) / 60).'px';
                    $title = '';
                    if ($rowPeriods['type'] != 'Lesson' and $height > 15 and $height < 30) {
                        $title = "title='".substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5)."'";
                    } elseif ($rowPeriods['type'] != 'Lesson' and $height <= 15) {
                        $title = "title='".$rowPeriods['name'].' ('.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).")'";
                    }
                    $class = 'ttGeneric';
                    if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
                        $class = 'ttCurrent';
                    }
                    $style = '';
                    if ($rowPeriods['type'] == 'Lesson') {
                        $class = 'ttLesson';
                    }

                    if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
                        $class = 'ttPeriodCurrent bg-green-100';
                    }

                    $output .= "<div class='$class' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width; height: {$height}px; margin: 0px; padding: 0px; opacity: $ttAlpha'>";
                    if ($height > 15 and $height < 30) {
                        $output .= $rowPeriods['name'].'<br/>';
                    } elseif ($height >= 30) {
                        $output .= $rowPeriods['name'].'<br/>';
                        $output .= '<i>'.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).'</i><br/>';
                    }
                    $output .= '</div>';
                    ++$zCount;
                }
            }

            //Draw periods from TT
            try {
                $dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => $date);
                $sqlPeriods = "SELECT gibbonTTDayRowClass.gibbonTTDayID, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.gibbonTTColumnRowID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonTTColumnRow.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonYearGroupIDList, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, phoneInternal, gibbonSpace.name AS roomName, (CASE WHEN gibbonStaffCoverage.gibbonPersonID=:gibbonPersonID THEN 1 ELSE 0 END) as coverageStatus
                FROM gibbonCourse 
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) 
                JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) 
                LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) 
                LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.foreignTableID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonStaffCoverageDate.foreignTable='gibbonTTDayRowClass' AND gibbonStaffCoverageDate.date=:date)
                LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                
                WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left' 
                GROUP BY gibbonTTDayRowClass.gibbonTTDayRowClassID 
                ORDER BY timeStart, timeEnd, FIND_IN_SET(gibbonCourseClassPerson.role, 'Teacher,Assistant,Student') DESC";
                $resultPeriods = $connection2->prepare($sqlPeriods);
                $resultPeriods->execute($dataPeriods);
            } catch (PDOException $e) {
            }

            $periods = $resultPeriods->rowCount() > 0 ? $resultPeriods->fetchAll() : [];

            if (!empty($staffCoverage)) {
                foreach ($staffCoverage as $coverageDetails) {
                    if ($coverageDetails[4] != $date) continue;
                    $coverage = $coverageDetails[5];
                    $coverage['coveragePerson'] = $coverageDetails[6];
                    $periods[] = $coverage;
                }
            }

            $periodCount = $periodIDs = [];
            foreach ($periods as $rowPeriods) {
                $rowPeriods['gibbonYearGroupIDList'] = explode(',', $rowPeriods['gibbonYearGroupIDList'] ?? '');
                $isSlotInTime = false;
                if ($rowPeriods['timeStart'] <= $dayTimeStart and $rowPeriods['timeEnd'] > $dayTimeStart) {
                    $isSlotInTime = true;
                } elseif ($rowPeriods['timeStart'] >= $dayTimeStart and $rowPeriods['timeEnd'] <= $dayTimeEnd) {
                    $isSlotInTime = true;
                } elseif ($rowPeriods['timeStart'] < $dayTimeEnd and $rowPeriods['timeEnd'] >= $dayTimeEnd) {
                    $isSlotInTime = true;
                }

                $isCovering = !empty($rowPeriods['coveragePerson']);
                $isCoveredBy = !empty($rowPeriods['coverageStatus']) && !empty($eventsPersonal);
                $isAbsent = false;

                if ($isSlotInTime == true) {

                    $offTimetableClass = false;

                    // Check for off timetabled classes by year group and by form group
                    if ($roleCategory == 'Staff' && !empty($specialDay) && $specialDay['type'] == 'Off Timetable' && !empty($rowPeriods['gibbonCourseClassID'])) {
                        try {
                            $dataClassCheck = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonCourseClassID' => $rowPeriods['gibbonCourseClassID'], 'gibbonFormGroupIDList' => implode(',', $specialDay['gibbonFormGroupIDList']), 'gibbonYearGroupIDList' => implode(',', $specialDay['gibbonYearGroupIDList']), 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count)))];
                            $sqlClassCheck = "SELECT count(CASE WHEN NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, :gibbonFormGroupIDList) AND NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList) THEN student.gibbonPersonID ELSE NULL END) as studentCount, count(*) as studentTotal, MAX(gibbonCourseClassMap.gibbonCourseClassMapID) as classMap
                                FROM gibbonCourseClassPerson 
                                JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID) 
                                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID) 
                                LEFT JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND FIND_IN_SET(gibbonCourseClassMap.gibbonFormGroupID, :gibbonFormGroupIDList))
                                WHERE role='Student' AND student.status='Full' 
                                AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                                AND (student.dateStart IS NULL OR student.dateStart<=:date) 
                                AND (student.dateEnd IS NULL OR student.dateEnd>=:date) 
                                ";
                            $resultClassCheck = $connection2->prepare($sqlClassCheck);
                            $resultClassCheck->execute($dataClassCheck);
                        } catch (PDOException $e) {
                        }
                        
                        // See if there are no students left in the class after year groups and form groups are checked
                        $classCheck = $resultClassCheck->fetch();
                        if (!empty($classCheck) && (($classCheck['studentTotal'] > 0 && $classCheck['studentCount'] <= 0) )) {
                            $offTimetableClass = true;
                        }
                    }

                    //Check for an exception for the current user
                    try {
                        $dataException = array('gibbonPersonID' => $gibbonPersonID, 'gibbonTTDayRowClassID' => $rowPeriods['gibbonTTDayRowClassID']);
                        $sqlException = 'SELECT * FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID AND gibbonPersonID=:gibbonPersonID';
                        $resultException = $connection2->prepare($sqlException);
                        $resultException->execute($dataException);
                    } catch (PDOException $e) {
                    }
                    if ($resultException->rowCount() < 1 || $isCovering) {
                        $className = !empty($rowPeriods['gibbonCourseClassID'])? $rowPeriods['course'].'.'.$rowPeriods['class'] : ($rowPeriods['contextName'] ?? '');

                        // Count how many classes are in this period
                        $periodCount[$rowPeriods['name']][] = $className;
                        $periodIDs[$rowPeriods['name']][] = $rowPeriods['gibbonCourseClassID'];

                        $effectiveStart = $rowPeriods['timeStart'];
                        $effectiveEnd = $rowPeriods['timeEnd'];
                        if ($dayTimeStart > $rowPeriods['timeStart']) {
                            $effectiveStart = $dayTimeStart;
                        }
                        if ($dayTimeEnd < $rowPeriods['timeEnd']) {
                            $effectiveEnd = $dayTimeEnd;
                        }

                        $blank = false;
                        if ($narrow == 'trim') {
                            $width = (ceil(640 / $daysInWeek) - 20).'px';
                        } elseif ($narrow == 'narrow') {
                            $width = (ceil(515 / $daysInWeek) - 20).'px';
                        } else {
                            $width = (ceil(690 / $daysInWeek) - 20).'px';
                        }
                        $height = ceil((strtotime($effectiveEnd) - strtotime($effectiveStart)) / 60);
                        $top = (ceil((strtotime($effectiveStart) - strtotime($dayTimeStart)) / 60 + ($startPad / 60))).'px';
                        $title = "title='";

                        if (!empty($eventsPersonal)) {
                            foreach ($eventsPersonal as $event) {
                                if (!empty($event[0]) && $event[0] == __('Absent') && date('Y-m-d', $event[2]) == $date) {
                                    if ($event[4] == 'Y' 
                                        || ($event[7] >= $effectiveStart && $event[7] < $effectiveEnd) 
                                        || ($effectiveStart >= $event[7] && $effectiveStart < $event[8])) {
                                        $isAbsent = true;
                                    }
                                }
                            }
                        }

                        if ($isCovering) {
                            $title .= $rowPeriods['gibbonPersonIDCoverage'] != $gibbonPersonID 
                                ? __('Covering for {name}', ['name' => $rowPeriods['coveragePerson']]).'<br/>' 
                                : __('Covering').' '.$className.'<br/>';
                        } elseif ($isCoveredBy) {
                            foreach ($eventsPersonal as $event) {
                                if (empty($event[6]) || !is_array($event[6])) continue;
                                foreach ($event[6] as $coverage) {
                                    if (empty($coverage['coverageName']) || $coverage['date'] != $date) continue;
                                    if ($coverage['gibbonTTDayRowClassID'] != $rowPeriods['gibbonTTDayRowClassID']) continue;

                                    $title .= __('Covered by {name}', ['name' => $coverage['coverageName']]).'<br/>';
                                }

                            }
                        } else {
                            try {
                                $dataTeacher = ['gibbonCourseClassID' => $rowPeriods['gibbonCourseClassID'], 'gibbonTTDayRowClassID' => $rowPeriods['gibbonTTDayRowClassID']];
                                $sqlTeacher = "SELECT gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.title 
                                    FROM gibbonPerson 
                                    JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID ) 
                                    LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonTTDayRowClassID=:gibbonTTDayRowClassID)
                                    WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                                    AND gibbonCourseClassPerson.role='Teacher'
                                    AND gibbonCourseClassPerson.reportable='Y'
                                    AND gibbonPerson.status='Full'
                                    AND gibbonTTDayRowClassExceptionID IS NULL
                                    ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                                $resultTeacher = $connection2->prepare($sqlTeacher);
                                $resultTeacher->execute($dataTeacher);
                            } catch (PDOException $e) {}

                            if ($resultTeacher->rowCount() > 0) {
                                $teachers = $resultTeacher->fetchAll();
                                $title .= __('Teacher').': '.Format::nameList($teachers, 'Staff', false, false, ', ').'<br/>' ;
                            }
                        }

                        if ($height < 45) {
                            $title .= __('Time:').' '.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).'<br/>';
                            $title .= __('Timeslot:').' '.$rowPeriods['name'].'<br/>';
                        }
                        if ($rowPeriods['roomName'] != '') {
                            if ($height < 30) {
                                // Handle room changes in the title
                                if (isset($spaceChanges[$rowPeriods['gibbonTTDayRowClassID']]) == false) {
                                    $title .= __('Room:').' '.$rowPeriods['roomName'].'<br/>';
                                } else {
                                    if ($spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][0] != '') {
                                        $title .= __('Room:').' ('.__('Changed').') '.$spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][0].'<br/>';
                                    } else {
                                        $title .= __('Room:').' ('.__('Changed').') '.__('No Facility').'<br/>';
                                    }
                                }
                            }
                            if ($rowPeriods['phoneInternal'] != '') {
                                if (isset($spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][0]) == false) {
                                    $title .= __('Phone:').' '.$rowPeriods['phoneInternal'].'<br/>';
                                } else {
                                    $title .= __('Phone:').' '.$spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][1].'<br/>';
                                }
                            }
                        }
                        $title = substr($title, 0, -3);
                        $title .= "'";
                        $class2 = 'ttPeriod';
                        $bg = '';

                        if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $date == date('Y-m-d')) {
                            $class2 = 'ttPeriodCurrent';
                        }

                        if ($offTimetableClass) {
                            $class2 = 'border bg-stripe-dark';
                            $bg = 'background-image: linear-gradient(45deg, #e6e6e6 25%, #f1f1f1 25%, #f1f1f1 50%, #e6e6e6 50%, #e6e6e6 75%, #f1f1f1 75%, #f1f1f1 100%); background-size: 23.0px 23.0px;';
                        }
                        else if ($isCoveredBy || $isAbsent) {
                            $bg = 'background-image: linear-gradient(45deg, #84acd9 25%, #96beea 25%, #96beea 50%, #84acd9 50%, #84acd9 75%, #96beea 75%, #96beea 100%); background-size: 23.0px 23.0px;';
                        }
                        else if ($isCovering) {
                            $bg = 'background-color: #a5f3fc !important; '; //outline: 2px solid rgb(136, 136, 136); outline-offset: -2px;
                        }

                        //Create div to represent period
                        $fontSize = '100%';
                        if ($height < 60) {
                            $fontSize = '85%';
                        }
                        $output .= "<div class='$class2' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width; height: {$height}px; margin: 0px; padding: 0px;  font-size: $fontSize; $bg'>";
                        if ($height >= 45) {
                            if ($isCovering) {
                                $output .= '<b>'.__('Covering').' '.$rowPeriods['nameShort'].'</b><br/>';
                            } elseif ($isCoveredBy) {
                                $output .= __('Absent').' '.$rowPeriods['nameShort'].'<br/>';
                            } else {
                                $output .= $rowPeriods['name'].'<br/>';
                            }
                            $output .= '<i>'.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).'</i><br/>';
                        }

                        $classCount = count($periodCount[$rowPeriods['name']] ?? []);
                        if ($classCount > 1) {
                            $exceptionID = $periodIDs[$rowPeriods['name']][0] ?? '';
                            $exceptionEdit = $session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_exception.php&gibbonTTDayID='.$rowPeriods['gibbonTTDayID']."&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID').'&gibbonTTColumnRowID='.$rowPeriods['gibbonTTColumnRowID'].'&gibbonTTDayRowClass='.$rowPeriods['gibbonTTDayRowClassID'].'&gibbonCourseClassID='.$exceptionID;

                            $tag = Format::tag("+".($classCount -1), 'error absolute top-0 right-0 mt-1 mr-1 p-1 text-xxs leading-none', implode(' & ', array_slice($periodCount[$rowPeriods['name']], 0, -1)));
                            $output .= $edit && !empty($exceptionID)
                                ? Format::link($exceptionEdit, $tag)
                                : $tag;
                        }

                        
                        if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') and $edit == false && !empty($rowPeriods['gibbonCourseClassID'])) {
                            $output .= "<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='".$session->get('absoluteURL').'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID']."&currentDate=".Format::date($date)."'>".$className.'</a><br/>';
                        } elseif (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') and $edit == true && !empty($rowPeriods['gibbonCourseClassID'])) {
                            $output .= "<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='".$session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_class_edit.php&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID'].'&gibbonSchoolYearID='.$session->get('gibbonSchoolYearID').'&gibbonCourseID='.$rowPeriods['gibbonCourseID']."'>".$className.'</a><br/>';
                        } elseif ($isCovering && isActionAccessible($guid, $connection2, '/modules/Staff/coverage_my.php')) {
                            $output .= "<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='".$session->get('absoluteURL')."/index.php?q=/modules/Staff/coverage_my.php'>".$className.'</a><br/>';
                        } else {
                            $output .= "<span style='font-size: 120%'><b>".$className.'</b></span><br/>';
                        }
                        if ($height >= 30) {
                            if ($offTimetableClass) {
                                $output .= "<span class=''><i>".($specialDay['name'] ?? __('Off Timetable')).'</i></span>';
                            }elseif ($edit == false) {
                                if (isset($spaceChanges[$rowPeriods['gibbonTTDayRowClassID']]) == false) {
                                    $output .= $rowPeriods['roomName'];
                                } else {
                                    if ($spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][0] != '') {
                                        $output .= "<span style='border: 1px solid #c00; padding: 0 2px'>".$spaceChanges[$rowPeriods['gibbonTTDayRowClassID']][0].'</span>';
                                    } else {
                                        $output .= "<span style='border: 1px solid #c00; padding: 0 2px'><i>".__('No Facility').'</i></span>';
                                    }
                                }
                            } else {
                                $output .= "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_edit.php&gibbonTTDayID='.$rowPeriods['gibbonTTDayID']."&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID').'&gibbonTTColumnRowID='.$rowPeriods['gibbonTTColumnRowID'].'&gibbonTTDayRowClass='.$rowPeriods['gibbonTTDayRowClassID'].'&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID']."'>".$rowPeriods['roomName'].'</a>';
                            }
                        }
                        $output .= '</div>';
                        ++$zCount;

                        if ($narrow == 'full' or $narrow == 'trim') {
                            if ($edit == false) {
                                //Add planner link icons for staff looking at own TT.
                                    if ($self == true and $roleCategory == 'Staff') {
                                        if ($height >= 30) {
                                            $output .= "<div $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: {$height}px; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>";
                                                //Check for lesson plan
                                                $bgImg = 'none';

                                            if (!empty($rowPeriods['gibbonCourseClassID'])) {
                                                try {
                                                    $dataPlan = array('gibbonCourseClassID' => $rowPeriods['gibbonCourseClassID'], 'date' => $date, 'timeStart' => $rowPeriods['timeStart'], 'timeEnd' => $rowPeriods['timeEnd']);
                                                    $sqlPlan = 'SELECT name, gibbonPlannerEntryID FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd GROUP BY name';
                                                    $resultPlan = $connection2->prepare($sqlPlan);
                                                    $resultPlan->execute($dataPlan);
                                                } catch (PDOException $e) {
                                                }

                                                if ($resultPlan->rowCount() == 1) {
                                                    $rowPlan = $resultPlan->fetch();
                                                    $output .= "<a style='pointer-events: auto' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$rowPlan['gibbonPlannerEntryID']."'><img style='float: right; margin: ".($height - 27)."px 2px 0 0' title='".__('Lesson planned: {name}',['name' => htmlPrep($rowPlan['name'])])."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/iconTick.png'/></a>";
                                                } elseif ($resultPlan->rowCount() == 0) {
                                                    $output .= "<a style='pointer-events: auto' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_add.php&viewBy=class&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID'].'&date='.$date.'&timeStart='.$effectiveStart.'&timeEnd='.$effectiveEnd."' ><img style='float: right; margin: ".($height - 27)."px 2px 0 0' alt='".__('Add lesson plan')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/page_new.png' title='".__('Add lesson plan')."'/></a>";
                                                } else {
                                                    $output .= "<a style='pointer-events: auto' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/planner.php&viewBy=class&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID'].'&date='.$date.'&timeStart='.$effectiveStart.'&timeEnd='.$effectiveEnd."'><div style='float: right; margin: ".($height - 17)."px 5px 0 0'>".__('Multiple').'</div></a>';
                                                }
                                            }
                                            $output .= '</div>';
                                            ++$zCount;
                                            
                                        }
                                    }
                                    //Add planner link icons for any one else's TT
                                    else {
                                        $output .= "<div $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: {$height}px; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>";
                                        //Check for lesson plan
                                        $bgImg = 'none';

                                        if (!empty($rowPeriods['gibbonCourseClassID'])) {
                                            try {
                                                $dataPlan = array('gibbonCourseClassID' => $rowPeriods['gibbonCourseClassID'], 'date' => $date, 'timeStart' => $rowPeriods['timeStart'], 'timeEnd' => $rowPeriods['timeEnd']);
                                                $sqlPlan = 'SELECT name, gibbonPlannerEntryID FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd GROUP BY name';
                                                $resultPlan = $connection2->prepare($sqlPlan);
                                                $resultPlan->execute($dataPlan);
                                            } catch (PDOException $e) {
                                            }
                                            if ($resultPlan->rowCount() == 1) {
                                                $rowPlan = $resultPlan->fetch();
                                                $output .= "<a style='pointer-events: auto' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$rowPlan['gibbonPlannerEntryID']."&search=$gibbonPersonID'><img style='float: right; margin: ".($height - 27)."px 2px 0 0' title='".__('View lesson:').' '.htmlPrep($rowPlan['name'])."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png'/></a>";
                                            } elseif ($resultPlan->rowCount() > 1) {
                                                $output .= "<div style='float: right; margin: ".($height - 17)."px 5px 0 0'>".__('Multiple').'</div>';
                                            }
                                        }
                                        $output .= '</div>';
                                        ++$zCount;
                                    }
                            }
                            //Show exception editing
                            elseif ($edit) {
                                $output .= "<div $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: {$height}px; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>";
                                    //Check for lesson plan
                                    $bgImg = 'none';

                                if (!empty($rowPeriods['gibbonCourseClassID'])) {
                                    $output .= "<a style='pointer-events: auto' href='".$session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_exception.php&gibbonTTDayID='.$rowPeriods['gibbonTTDayID']."&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID').'&gibbonTTColumnRowID='.$rowPeriods['gibbonTTColumnRowID'].'&gibbonTTDayRowClass='.$rowPeriods['gibbonTTDayRowClassID'].'&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID']."'><img style='float: right; margin: ".($height - 27)."px 2px 0 0' title='".__('Manage Exceptions')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/attendance.png'/></a>";
                                }
                                $output .= '</div>';
                                ++$zCount;
                            }
                        }
                    }
                }
            }

            //Draw activities
            if (!empty($activities)) {
                $height = 0;
                $top = 0;
                foreach ($activities as $event) {
                    if (empty($event[2])) continue;

                    if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                        $label = $event[0];
                        $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])." ".$event[4]."'";
                        $height = ceil(($event[3] - $event[2]) / 60);
                        $charCut = 40;
                        if ($height <= 60) {
                            $charCut = 18;
                        }
                        if (strlen($label) > $charCut) {
                            $label = substr($label, 0, $charCut).'...';
                            $title = "title='".htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")  ".$event[4]."'";
                        }

                        if (!empty($event[6]) && $event[6]['cancelActivities'] == 'Y') {
                            $class = 'ttActivities border bg-stripe-dark';
                            $bg = 'background-image: linear-gradient(45deg, #e6e6e6 25%, #f1f1f1 25%, #f1f1f1 50%, #e6e6e6 50%, #e6e6e6 75%, #f1f1f1 75%, #f1f1f1 100%); background-size: 23.0px 23.0px;';
                        } else {
                            $class = 'ttActivities ttPeriod';
                            $bg = 'background: #dfcbf6 !important;';
                        }
                        $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                        $output .= "<div class='{$class}' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; {$bg}'>";
                        if ($height >= 26) {
                            $output .= __('Activity').'<br/>';
                        }
                        if ($height >= 40) {
                            $output .= '<i>'.date('H:i', $event[2]).' - '.date('H:i', $event[3]).'</i><br/>';
                        }

                        $output .= "<a style='text-decoration: none; font-weight: bold; ' href='".$event[5]."'>".$label.'</a><br/>';

                        if (!empty($event[6]) && $event[6]['cancelActivities'] == 'Y') {
                            $output .= '<i>'.__('Cancelled').'</i><br/>';
                        } elseif (($height >= 55 && $charCut <= 20) || ($height >= 68 && $charCut >= 40)) {
                            $output .= $event[4].'<br/>';
                        }
                        $output .= '</div>';
                    }
                    ++$zCount;

                }
            }

            //Draw staff duty
            if (!empty($staffDuty)) {
                $height = 0;
                $top = 0;
                foreach ($staffDuty as $event) {
                    if (empty($event[2])) continue;

                    if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                        $label = $event[0];
                        $title = "title='".htmlPrep(__('Staff Duty')).'<br/>'.date('H:i', $event[2]).' to '.date('H:i', $event[3])." ".$event[4]."'";
                        $height = ceil(($event[3] - $event[2]) / 60);
                        $charCut = 40;
                        if ($height <= 60) {
                            $charCut = 18;
                        }
                        if (strlen($label) > $charCut) {
                            $label = substr($label, 0, $charCut).'...';
                            $title = "title='".htmlPrep(__('Staff Duty')).'<br/>'.htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")  ".$event[4]."'";
                        }

                        $class = 'ttStaffDuty ttPeriod';
                        $bg = 'background: #FDE68A !important;';
                        
                        $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                        $output .= "<div class='{$class}' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; {$bg}'>";
                        if ($height >= 26) {
                            $output .= __('Staff Duty').'<br/>';
                        }
                        if ($height >= 40) {
                            $output .= '<i>'.date('H:i', $event[2]).' - '.date('H:i', $event[3]).'</i><br/>';
                        }

                        $output .= "<a class='thickbox' style='text-decoration: none; font-weight: bold; ' href='".$event[5]."'>".$label.'</a><br/>';

                        if (($height >= 55 && $charCut <= 20) || ($height >= 68 && $charCut >= 40)) {
                            $output .= $event[4].'<br/>';
                        }
                        $output .= '</div>';
                    }
                    ++$zCount;

                }
            }

            //Draw periods from school calendar
            if ($eventsSchool != false) {
                $height = 0;
                $top = 0;
                foreach ($eventsSchool as $event) {
                    if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                        if ($event[1] == 'All Day') {
                            $label = $event[0];
                            $title = '';
                            if (strlen($label) > 20) {
                                $label = substr($label, 0, 20).'...';
                                $title = "title='".htmlPrep($event[0])."'";
                            }
                            $height = 30;
                            $top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
                            $output .= "<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                            $output .= "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>';
                            $output .= '</div>';
                            ++$allDay;
                        } else {
                            $label = $event[0];
                            $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
                            $height = ceil(($event[3] - $event[2]) / 60);
                            $charCut = 20;
                            if ($height < 20) {
                                $charCut = 12;
                            }
                            if (strlen($label) > $charCut) {
                                $label = substr($label, 0, $charCut).'...';
                                $title = "title='".htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
                            }
                            $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                            $output .= "<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                            $output .= "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>';
                            $output .= '</div>';
                        }
                        ++$zCount;
                    }
                }
            }

            //Draw periods from personal calendar
            if ($eventsPersonal != false) {
                $height = 0;
                $top = 0;
                $bg = "rgba(103,153,207,$schoolCalendarAlpha)";
                foreach ($eventsPersonal as $event) {
                    if ($event[0] == __('Absent') && $event[4] == 'N') continue;

                    if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                        if ($event[1] == 'All Day') {
                            $label = $event[0];
                            $title = '';
                            if (strlen($label) > 20) {
                                $label = substr($label, 0, 20).'...';
                                $title = "title='".htmlPrep($event[0])."'";
                            }
                            $height = 30;
                            $top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
                            $output .= "<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                            $output .= !empty($event[5])
                                ? "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>'
                                : $label;
                            $output .= '</div>';
                            ++$allDay;
                        } else {
                            $label = $event[0];
                            $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
                            $height = ceil(($event[3] - $event[2]) / 60);
                            $charCut = 20;
                            if ($height < 20) {
                                $charCut = 12;
                            }
                            if (strlen($label) > $charCut) {
                                $label = substr($label, 0, $charCut).'...';
                                $title = "title='".htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
                            }
                            $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                            $output .= "<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                            $output .= !empty($event[5])
                                ? "<a target=_blank style='color: #fff' href='".$event[5]."'>".$label.'</a>'
                                : $label;
                            $output .= '</div>';
                        }
                        ++$zCount;
                    }
                }
            }

            $output .= '</div>';
        }

        //Draw space bookings and staff coverage
        if ($eventsSpaceBooking != false) {
            $dayTimeStart = $gridTimeStart;
            $startPad = 0;
            $output .= "<div style='position: relative'>";

            $height = 0;
            $top = 0;
            foreach ($eventsSpaceBooking as $event) {
                if ($event[9] == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                    $height = ceil((strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[5]) - strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[4])) / 60);
                    $top = (ceil((strtotime($event[9].' '.$event[4]) - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$dayTimeStart)) / 60 + ($startPad / 60))).'px';
                    if ($height < 45) {
                        $label = $event[1];
                        $title = "title='".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).' '.$event[6]."'";
                    } else {
                        $label = $event[1]."<br/><span style='font-weight: normal'>".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).'<br/>'.$event[6].'</span>';
                        $title = '';
                    }

                    if ($height > 56) {
                        $label .= '<br/>'.Format::small(Format::truncate($event[7], 60));
                    } 
                    
                    $output .= "<div class='ttSpaceBookingCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width:100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                    $output .= $label;
                    $output .= '</div>';
                    ++$zCount;
                }
            }
            $output .= '</div>';
        }
    }

    $output .= '</td>';

    return $output;
}

//TIMETABLE FOR ROOM
function renderTTSpace($guid, $connection2, $gibbonSpaceID, $gibbonTTID, $title = '', $startDayStamp = '', $q = '', $params = '')
{
    global $session, $container;

    $output = '';

    $blank = true;
    if ($startDayStamp == '') {
        $startDayStamp = time();
    }
    $zCount = 0;
    $top = 0;

    //Find out which timetables I am involved in this year
    try {
        $data = array('gibbonSpaceID' => $gibbonSpaceID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSpaceID=:gibbonSpaceID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    //If I am not involved in any timetables display all within the year
    if ($result->rowCount() == 0) {
        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sql = "SELECT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
    }

    //link to other TTs
    if ($result->rowcount() > 1) {
        $output .= "<table class='noIntBorder mt-2' style='width: 100%'>";
        $output .= '<tr>';
        $output .= '<td>';
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Timetable Chooser').'</span>: ';
        while ($row = $result->fetch()) {
            $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params.'&gibbonTTID='.$row['gibbonTTID']."'>";
            $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."' type='hidden'>";
            $output .= "<input name='schoolCalendar' value='".($session->get('viewCalendarSchool') == 'Y' ? 'Y' : '')."' type='hidden'>";
            $output .= "<input name='personalCalendar' value='".($session->get('viewCalendarPersonal') == 'Y' ? 'Y' : '')."' type='hidden'>";
            $output .= "<input name='spaceBookingCalendar' value='".($session->get('viewCalendarSpaceBooking') == 'Y' ? 'Y' : '')."' type='hidden'>";
            $output .= "<input name='fromTT' value='Y' type='hidden'>";
            $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".$row['name']."'>";
            $output .= '</form>';
        }

        $result = $connection2->prepare($sql);
        $result->execute($data);

        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        if ($gibbonTTID != '') {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonTTID' => $gibbonTTID, 'gibbonSpaceID' => $gibbonSpaceID);
            $sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShortDisplay FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSpaceID=:gibbonSpaceID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID";

            $ttResult = $connection2->prepare($sql);
            $ttResult->execute($data);
            if ($ttResult->rowCount() > 0) {
                $result = &$ttResult;
            }

        }

    }

    
    //Get space booking array
    $eventsSpaceBooking = false;
    if ($session->get('viewCalendarSpaceBooking') == 'Y') {
        $eventsSpaceBooking = getSpaceBookingEventsSpace($guid, $connection2, $startDayStamp, $gibbonSpaceID);
    }

    //Display first TT
    if ($result->rowCount() > 0) {
        $row = $result->fetch();
        $gibbonTTID = $row['gibbonTTID'];
        $nameShortDisplay = $row['nameShortDisplay']; //Store day short name display setting for later

        if ($title != false) {
            $output .= '<h2>'.$row['name'].'</h2>';
        }

        $output .= "<table cellspacing='0' class='noIntBorder' cellspacing='0' style='width: 100%; margin: 10px 0 10px 0'>";
        $output .= '<tr>';
        $output .= "<td style='vertical-align: top;width:360px;'>";
        $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params.'&gibbonTTID='.$row['gibbonTTID']."'>";
        $output .= "<input name='ttDate' maxlength=10 value='".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp - (7 * 24 * 60 * 60)))."' type='hidden'>";
        $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
        $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
        $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
        $output .= "<input name='fromTT' value='Y' type='hidden'>";
        $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='< ".__('Last Week')."'>";
        $output .= '</form>';
        $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params.'&gibbonTTID='.$row['gibbonTTID']."'>";
        $output .= "<input name='ttDate' maxlength=10 value='".date($session->get('i18n')['dateFormatPHP'])."' type='hidden'>";
        $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
        $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
        $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
        $output .= "<input name='fromTT' value='Y' type='hidden'>";
        $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".__('This Week')."'>";
        $output .= '</form>';
        $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params.'&gibbonTTID='.$row['gibbonTTID']."'>";
        $output .= "<input name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (7 * 24 * 60 * 60)))."' type='hidden'>";
        $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
        $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
        $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
        $output .= "<input name='fromTT' value='Y' type='hidden'>";
        $output .= "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='".__('Next Week')." >'>";
        $output .= '</form>';
        $output .= '</td>';
        $output .= "<td style='vertical-align: top; text-align: right'>";
        $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params.'&gibbonTTID='.$row['gibbonTTID']."'>";
        $output .= "<input name='ttDate' id='ttDate' aria-label='".__('Choose Date')."' maxlength=10 value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."' type='text' style='height: 36px; width:120px; margin-right: 0px; float: none'>";
        $output .= '<script type="text/javascript">';
        $output .= "var ttDate=new LiveValidation('ttDate');";
        $output .= 'ttDate.add( Validate.Format, {pattern: ';
        if ($session->get('i18n')['dateFormatRegEx'] == '') {
            $output .= "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
        } else {
            $output .= $session->get('i18n')['dateFormatRegEx'];
        }
        $output .= ', failureMessage: "Use ';
        if ($session->get('i18n')['dateFormat'] == '') {
            $output .= 'dd/mm/yyyy';
        } else {
            $output .= $session->get('i18n')['dateFormat'];
        }
        $output .= '." } );';
        $output .= 'ttDate.add(Validate.Presence);';
        $output .= '</script>';
        $output .= '<script type="text/javascript">';
        $output .= '$(function() {';
        $output .= '$("#ttDate").datepicker();';
        $output .= '});';
        $output .= '</script>';
        $output .= "<input style='margin-top: 0px; margin-right: -1px;  padding-left: 1rem; padding-right: 1rem;' type='submit' value='".__('Go')."'>";
        $output .= "<input name='schoolCalendar' value='".$session->get('viewCalendarSchool')."' type='hidden'>";
        $output .= "<input name='personalCalendar' value='".$session->get('viewCalendarPersonal')."' type='hidden'>";
        $output .= "<input name='spaceBookingCalendar' value='".$session->get('viewCalendarSpaceBooking')."' type='hidden'>";
        $output .= "<input name='fromTT' value='Y' type='hidden'>";
        $output .= '</form>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        //Check which days are school days
        $daysInWeek = 0;
        $days = array();
        $timeStart = '';
        $timeEnd = '';
        try {
            $dataDays = array();
            $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";
            $resultDays = $connection2->prepare($sqlDays);
            $resultDays->execute($dataDays);
        } catch (PDOException $e) {
        }
        $days = $resultDays->fetchAll();
        $daysInWeek = $resultDays->rowCount();
        foreach ($days as $day) {
            if ($timeStart == '' or $timeEnd == '') {
                $timeStart = $day['schoolStart'];
                $timeEnd = $day['schoolEnd'];
            } else {
                if ($day['schoolStart'] < $timeStart) {
                    $timeStart = $day['schoolStart'];
                }
                if ($day['schoolEnd'] > $timeEnd) {
                    $timeEnd = $day['schoolEnd'];
                }
            }
        }

        //Count back to first dayOfWeek before specified calendar date
        while (date('D', $startDayStamp) != $days[0]['nameShort']) {
            $startDayStamp = $startDayStamp - 86400;
        }

        //Count forward to the end of the week
        $endDayStamp = $startDayStamp + (86400 * ($daysInWeek - 1));

        $schoolCalendarAlpha = 0.85;
        $ttAlpha = 1.0;

        if ($session->get('viewCalendarSpaceBooking') != 'N') {
            $ttAlpha = 0.75;
        }

        //Count forward to the end of the week
        $endDayStamp = $startDayStamp + (86400 * ($daysInWeek - 1));
        
        // Get the date range to check for activities
        $dateRange = new DatePeriod(
            (new DateTime(date('Y-m-d H:i:s', $startDayStamp)))->modify('-1 day'),
            new DateInterval('P1D'),
            (new DateTime(date('Y-m-d H:i:s', $endDayStamp)))->modify('+1 day')
        );

        // Get all special days
        $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
        $specialDays = $specialDayGateway->selectSpecialDaysByDateRange($dateRange->start->format('Y-m-d'), $dateRange->end->format('Y-m-d'))->fetchGroupedUnique();

        // Get all activities for this student or staff
        $activityGateway = $container->get(ActivityGateway::class);
        $activities = [];

        $dateType = $container->get(SettingGateway::class)->getSettingByScope('Activities', 'dateType');
        $activityList = $activityGateway->selectActivitiesByFacility($session->get('gibbonSchoolYearID'), $gibbonSpaceID, $dateType)->fetchAll();

        foreach ($dateRange as $dateObject) {
            $date = $dateObject->format('Y-m-d');
            $weekday = $dateObject->format('l');
            foreach ($activityList as $activity) {
                // Add activities that match the weekday and the school is open
                if (empty($activity['dayOfWeek']) || $activity['dayOfWeek'] != $weekday) continue;
                if ($date < $activity['dateStart'] || $date > $activity['dateEnd'] ) continue;

                if (isSchoolOpen($guid, $date, $connection2)) {
                    $activities[] = [
                        0 => $activity['name'],
                        1 => '',
                        2 => strtotime($date.' '.$activity['timeStart']),
                        3 => strtotime($date.' '.$activity['timeEnd']),
                        4 => !empty($activity['space'])? $activity['space'] : $activity['locationExternal'] ?? '',
                        5 => '',
                        6 => $specialDays[$date] ?? [],
                    ];

                }

            }

        }

        //Max diff time for week based on timetables
        try {
            $dataDiff = array('date1' => date('Y-m-d', ($startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($endDayStamp + (86400 * 1))), 'gibbonTTID' => $row['gibbonTTID']);
            $sqlDiff = 'SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID';
            $resultDiff = $connection2->prepare($sqlDiff);
            $resultDiff->execute($dataDiff);
        } catch (PDOException $e) {
        }
        while ($rowDiff = $resultDiff->fetch()) {
            try {
                $dataDiffDay = array('gibbonTTColumnID' => $rowDiff['gibbonTTColumnID']);
                $sqlDiffDay = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart';
                $resultDiffDay = $connection2->prepare($sqlDiffDay);
                $resultDiffDay->execute($dataDiffDay);
            } catch (PDOException $e) {
            }
            while ($rowDiffDay = $resultDiffDay->fetch()) {
                if ($rowDiffDay['timeStart'] < $timeStart) {
                    $timeStart = $rowDiffDay['timeStart'];
                }
                if ($rowDiffDay['timeEnd'] > $timeEnd) {
                    $timeEnd = $rowDiffDay['timeEnd'];
                }
            }
        }

        //Max diff time for week based on special days timing change
        try {
            $dataDiff = array('date1' => date('Y-m-d', ($startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($startDayStamp + (86400 * 6))));
            $sqlDiff = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date>=:date1 AND date<=:date2 AND type='Timing Change' AND NOT schoolStart IS NULL AND NOT schoolEnd IS NULL";
            $resultDiff = $connection2->prepare($sqlDiff);
            $resultDiff->execute($dataDiff);
        } catch (PDOException $e) {
        }

        while ($rowDiff = $resultDiff->fetch()) {
            if ($rowDiff['schoolStart'] < $timeStart) {
                $timeStart = $rowDiff['schoolStart'];
            }
            if ($rowDiff['schoolEnd'] > $timeEnd) {
                $timeEnd = $rowDiff['schoolEnd'];
            }
        }

        //Max diff based on space booking events
        if ($eventsSpaceBooking != false) {
            foreach ($eventsSpaceBooking as $event) {
                if ($event[3] <= date('Y-m-d', ($startDayStamp + (86400 * 6)))) {
                    if ($event[4] < $timeStart) {
                        $timeStart = $event[4];
                    }
                    if ($event[5] > $timeEnd) {
                        $timeEnd = $event[5];
                    }
                }
            }
        }

        //Final calc
        $diffTime = strtotime($timeEnd) - strtotime($timeStart);
        $width = (ceil(690 / $daysInWeek) - 20).'px';

        $count = 0;

        $output .= '<div id="ttWrapper">';
        $output .= "<table cellspacing='0' class='mini' cellspacing='0' style='width: 100%; min-width: 750px; margin: 0px 0px 30px 0px;'>";
            //Spit out controls for displaying calendars
            if ($session->get('viewCalendarSpaceBooking') != '') {
                $output .= "<tr class='head' style='height: 37px;'>";
                $output .= "<th class='ttCalendarBar' colspan=".($daysInWeek + 1).'>';
                $output .= "<form method='post' action='".$session->get('absoluteURL')."/index.php?q=$q".$params."' style='padding: 5px 5px 0 0'>";
                if ($session->get('viewCalendarSpaceBooking') != '') {
                    $checked = '';
                    if ($session->get('viewCalendarSpaceBooking') == 'Y') {
                        $checked = 'checked';
                    }
                    $output .= "<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'><a style='color: #fff' href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable/spaceBooking_manage.php'>".__('Bookings').'</a> ';
                    $output .= "<input $checked style='margin-left: 3px' type='checkbox' name='spaceBookingCalendar' aria-label='".__('Space Booking Calendar')."' onclick='submit();'/>";
                    $output .= '</span>';
                }

                $output .= "<input type='hidden' name='ttDate' value='".date($session->get('i18n')['dateFormatPHP'], $startDayStamp)."'>";
                $output .= "<input name='fromTT' value='Y' type='hidden'>";
                $output .= '</form>';
                $output .= '</th>';
                $output .= '</tr>';
            }

            $output .= "<tr class='head'>";
            $output .= "<th style='vertical-align: top; width: 70px; text-align: center'>";
            //Calculate week number
            $week = getWeekNumber($startDayStamp, $connection2, $guid);
            if ($week != false) {
                $output .= sprintf(__('Week %1$s'), $week).'<br/>';
            }
            $output .= "<span style='font-weight: normal; font-style: italic;'>".__('Time').'<span>';
            $output .= '</th>';
            $count = 0;

            foreach ($days as $day) {
                if ($day['schoolDay'] == 'Y') {
                    if ($count == 0) {
                        $firstSequence = $day['sequenceNumber'];
                    }
                    $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                    $color = '';
                    $dataDay = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))), 'gibbonTTID' => $gibbonTTID);
                    $sqlDay = 'SELECT nameShort, color, fontColor FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
                    $resultDay = $connection2->prepare($sqlDay);
                    $resultDay->execute($dataDay);
                    if ($rowDay = $resultDay->fetch()) {
                        if (!empty($rowDay['color'])) {
                            $color .= "; background-color: ".$rowDay['color']."; background-image: none";
                        }
                        if (!empty($rowDay['fontColor'])) {
                            $color .= "; color: ".$rowDay['fontColor'];
                        }
                    }

                    $today = ((date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))) == date($session->get('i18n')['dateFormatPHP'])) ? "class='ttToday'" : '');
                    $output .= "<th $today style='vertical-align: top; text-align: center; width: ";

                    $output .= (550 / $daysInWeek);
                    $output .= "px".$color."'>";
                    if ($nameShortDisplay != 'Timetable Day Short Name') {
                        $output .= __($day['nameShort']).'<br/>';
                    }
                    else {
                        $output .= ($rowDay['nameShort'] ?? Format::dayOfWeekName(date('Y-m-d', $startDayStamp + (86400 * $dateCorrection)))).'<br/>';
                    }
                    $output .= "<span style='font-size: 80%; font-style: italic'>".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))).'</span><br/>';
                    try {
                        $dataSpecial = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))));
                        $sqlSpecial = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'";
                        $resultSpecial = $connection2->prepare($sqlSpecial);
                        $resultSpecial->execute($dataSpecial);
                    } catch (PDOException $e) {
                    }
                    if ($resultSpecial->rowcount() == 1) {
                        $rowSpecial = $resultSpecial->fetch();
                        $output .= "<span style='font-size: 80%; font-weight: bold'><u>".$rowSpecial['name'].'</u></span>';
                    }
                    $output .= '</th>';
                }
                $count ++;
            }
            $output .= '</tr>';

            $output .= "<tr style='height:".(ceil($diffTime / 60) + 14)."px'>";
            $output .= "<td class='ttTime' style='height: 300px; width: 75px; max-width: 75px; text-align: center; vertical-align: top'>";
            $output .= "<div style='position: relative; width: 71px'>";
            $countTime = 0;
            $time = $timeStart;
            $output .= "<div $title style='position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
            $output .= substr($time, 0, 5).'<br/>';
            $output .= '</div>';
            $time = date('H:i:s', strtotime($time) + 3600);
            $spinControl = 0;
            while ($time <= $timeEnd and $spinControl < (23 - substr($timeStart, 0, 2))) {
                ++$countTime;
                $output .= "<div $title style='position: absolute; top:".(($countTime * 60) - 5)."px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
                $output .= substr($time, 0, 5).'<br/>';
                $output .= '</div>';
                $time = date('H:i:s', strtotime($time) + 3600);
                ++$spinControl;
            }

            $output .= '</div>';
            $output .= '</td>';

            //Check to see if week is at all in term time...if it is, then display the grid
            $isWeekInTerm = false;
            try {
                $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultTerm = $connection2->prepare($sqlTerm);
                $resultTerm->execute($dataTerm);
            } catch (PDOException $e) {
            }
            $weekStart = date('Y-m-d', ($startDayStamp + (86400 * 0)));
            $weekEnd = date('Y-m-d', ($startDayStamp + (86400 * 6)));
            while ($rowTerm = $resultTerm->fetch()) {
                if ($weekStart <= $rowTerm['firstDay'] and $weekEnd >= $rowTerm['firstDay']) {
                    $isWeekInTerm = true;
                } elseif ($weekStart >= $rowTerm['firstDay'] and $weekEnd <= $rowTerm['lastDay']) {
                    $isWeekInTerm = true;
                } elseif ($weekStart <= $rowTerm['lastDay'] and $weekEnd >= $rowTerm['lastDay']) {
                    $isWeekInTerm = true;
                }
            }
            if ($isWeekInTerm == true) {
                $blank = false;
            }

            //Run through days of the week
            foreach ($days as $day) {
                $dayOut = '';
                if ($day['schoolDay'] == 'Y') {
                    $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                    //Check to see if day is term time
                    $isDayInTerm = false;
                    try {
                        $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                        $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultTerm = $connection2->prepare($sqlTerm);
                        $resultTerm->execute($dataTerm);
                    } catch (PDOException $e) {
                    }
                    while ($rowTerm = $resultTerm->fetch()) {
                        if (date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) >= $rowTerm['firstDay'] and date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) <= $rowTerm['lastDay']) {
                            $isDayInTerm = true;
                        }
                    }

                    if ($isDayInTerm == true) {
                        //Check for school closure day
                        try {
                            $dataClosure = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))));
                            $sqlClosure = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date';
                            $resultClosure = $connection2->prepare($sqlClosure);
                            $resultClosure->execute($dataClosure);
                        } catch (PDOException $e) {
                        }
                        if ($resultClosure->rowCount() == 1) {

                            $rowClosure = $resultClosure->fetch();
                            if ($rowClosure['type'] == 'School Closure') {
                                $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
                                $dayOut .= "<div style='position: relative'>";
                                $dayOut .= "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: 100%; min-width: $width ; height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; opacity: $ttAlpha'>";
                                $dayOut .= "<div style='position: relative; top: 50%'>";
                                $dayOut .= '<span>'.$rowClosure['type'].'<br/><br/>'.$rowClosure['name'].'</span>';
                                $dayOut .= '</div>';
                                $dayOut .= '</div>';
                                $dayOut .= '</div>';
                                $dayOut .= '</td>';
                            } elseif ($rowClosure['type'] == 'Timing Change') {
                                $dayOut = renderTTSpaceDay($guid, $connection2, $row['gibbonTTID'], $startDayStamp, $dateCorrection, $daysInWeek, $gibbonSpaceID, $timeStart, $diffTime, $eventsSpaceBooking, $rowClosure, $rowClosure['schoolStart'], $rowClosure['schoolEnd'], $activities);
                            } elseif ($rowClosure['type'] == 'Off Timetable') {
                                $dayOut = renderTTSpaceDay($guid, $connection2, $row['gibbonTTID'], $startDayStamp, $dateCorrection, $daysInWeek, $gibbonSpaceID, $timeStart, $diffTime, $eventsSpaceBooking, $rowClosure, $rowClosure['schoolStart'], $rowClosure['schoolEnd'], $activities);
                            }
                        } else {
                            $dayOut = renderTTSpaceDay($guid, $connection2, $row['gibbonTTID'], $startDayStamp, $dateCorrection, $daysInWeek, $gibbonSpaceID, $timeStart, $diffTime, $eventsSpaceBooking, [], '', '', $activities);
                        }
                    }

                    if ($dayOut == '') {
                        $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
                        $dayOut .= "<div style='position: relative'>";
                        $dayOut .= "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: 100%; min-width: $width ; height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; opacity: $ttAlpha'>";
                        $dayOut .= "<div style='position: relative; top: 50%'>";
                        $dayOut .= "<span style='color: rgba(255,0,0,$ttAlpha);'>".__('School Closed').'</span>';
                        $dayOut .= '</div>';
                        $dayOut .= '</div>';
                        $dayOut .= '</div>';
                        $dayOut .= '</td>';
                    }

                    $output .= $dayOut;
                }
            }

        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</div>';
    }

    return $output;
}

function renderTTSpaceDay($guid, $connection2, $gibbonTTID, $startDayStamp, $count, $daysInWeek, $gibbonSpaceID, $gridTimeStart, $diffTime, $eventsSpaceBooking, $specialDay = [], $specialDayStart = '', $specialDayEnd = '', $activities = [])
{
    global $session;

    $schoolCalendarAlpha = 0.85;
    $ttAlpha = 1.0;

    $date = date('Y-m-d', ($startDayStamp + (86400 * $count)));

    $output = '';
    $blank = true;

    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

    //Make array of space changes
    $spaceChanges = array();

        $dataSpaceChange = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
        $sqlSpaceChange = 'SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date';
        $resultSpaceChange = $connection2->prepare($sqlSpaceChange);
        $resultSpaceChange->execute($dataSpaceChange);
    while ($rowSpaceChange = $resultSpaceChange->fetch()) {
        $spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][0] = $rowSpaceChange['space'];
        $spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][1] = $rowSpaceChange['phoneInternal'];
    }

    //Get day start and end!
    $dayTimeStart = '';
    $dayTimeEnd = '';
    try {
        $dataDiff = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))), 'gibbonTTID' => $gibbonTTID);
        $sqlDiff = 'SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
        $resultDiff = $connection2->prepare($sqlDiff);
        $resultDiff->execute($dataDiff);
    } catch (PDOException $e) {
    }
    while ($rowDiff = $resultDiff->fetch()) {
        if ($dayTimeStart == '') {
            $dayTimeStart = $rowDiff['timeStart'];
        }
        if ($rowDiff['timeStart'] < $dayTimeStart) {
            $dayTimeStart = $rowDiff['timeStart'];
        }
        if ($dayTimeEnd == '') {
            $dayTimeEnd = $rowDiff['timeEnd'];
        }
        if ($rowDiff['timeEnd'] > $dayTimeEnd) {
            $dayTimeEnd = $rowDiff['timeEnd'];
        }
    }
    if ($specialDayStart != '') {
        $dayTimeStart = $specialDayStart;
    }
    if ($specialDayEnd != '') {
        $dayTimeEnd = $specialDayEnd;
    }

    $dayDiffTime = strtotime($dayTimeEnd) - strtotime($dayTimeStart);
    $startPad = !empty($dayTimeStart) ? strtotime($dayTimeStart) - strtotime($gridTimeStart) : 0;

    $width = (ceil(690 / $daysInWeek) - 20).'px';
    $zCount = 0;

    $canAddBookings = isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php');
    $canAddChanges = isActionAccessible($guid, $connection2, '/modules/Timetable/spaceChange_manage_add.php');
    $canEditTTDays = isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_edit.php');

    $today = (date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $count))) == date($session->get('i18n')['dateFormatPHP']) ? "class='ttToday'" : '');
    $output .= "<td $today style='text-align: center; vertical-align: top; font-size: 11px'>";

    try {
        $dataDay = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $count))), 'gibbonTTID' => $gibbonTTID);
        $sqlDay = 'SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date';
        $resultDay = $connection2->prepare($sqlDay);
        $resultDay->execute($dataDay);
    } catch (PDOException $e) {
    }

    if ($resultDay->rowCount() == 1) {
        $rowDay = $resultDay->fetch();
        $zCount = 0;
        $output .= "<div style='position: relative'>";

        //Draw outline of the day
        try {
            $dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
            $sqlPeriods = 'SELECT gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd';
            $resultPeriods = $connection2->prepare($sqlPeriods);
            $resultPeriods->execute($dataPeriods);
        } catch (PDOException $e) {
        }
        while ($rowPeriods = $resultPeriods->fetch()) {
            $isSlotInTime = false;
            if ($rowPeriods['timeStart'] <= $dayTimeStart and $rowPeriods['timeEnd'] > $dayTimeStart) {
                $isSlotInTime = true;
            } elseif ($rowPeriods['timeStart'] >= $dayTimeStart and $rowPeriods['timeEnd'] <= $dayTimeEnd) {
                $isSlotInTime = true;
            } elseif ($rowPeriods['timeStart'] < $dayTimeEnd and $rowPeriods['timeEnd'] >= $dayTimeEnd) {
                $isSlotInTime = true;
            }

            if ($isSlotInTime == true) {
                $effectiveStart = $rowPeriods['timeStart'];
                $effectiveEnd = $rowPeriods['timeEnd'];
                if ($dayTimeStart > $rowPeriods['timeStart']) {
                    $effectiveStart = $dayTimeStart;
                }
                if ($dayTimeEnd < $rowPeriods['timeEnd']) {
                    $effectiveEnd = $dayTimeEnd;
                }

                $width = (ceil(690 / $daysInWeek) - 20).'px';
                $height = ceil((strtotime($effectiveEnd) - strtotime($effectiveStart)) / 60);
                $top = ceil(((strtotime($effectiveStart) - strtotime($dayTimeStart)) + $startPad) / 60).'px';
                $title = '';
                if ($rowPeriods['type'] != 'Lesson' and $height > 15 and $height < 30) {
                    $title = "title='".substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5)."'";
                } elseif ($rowPeriods['type'] != 'Lesson' and $height <= 15) {
                    $title = "title='".$rowPeriods['name'].' ('.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).")'";
                }
                $class = 'ttGeneric';
                if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
                    $class = 'ttCurrent';
                }
                $style = '';
                if ($rowPeriods['type'] == 'Lesson') {
                    $class = 'ttLesson';
                }

                if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
                    $class = 'ttPeriodCurrent';
                }

                $output .= "<div class='$class' $title style='z-index: $zCount; position: absolute; top: $top; min-width: $width; width: 100%; height: {$height}px; margin: 0px; padding: 0px; opacity: $ttAlpha'>";
                if ($height > 15 and $height < 30) {
                    $output .= $rowPeriods['name'].'<br/>';
                } elseif ($height >= 30) {
                    $output .= $rowPeriods['name'].'<br/>';
                    $output .= '<i>'.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).'</i><br/>';

                    if ($session->get('viewCalendarSpaceBooking') == 'Y' && $canAddBookings && $date >= date('Y-m-d')) {
                        $overlappingBookings = array_filter(is_array($eventsSpaceBooking)? $eventsSpaceBooking : [],
                            function ($event) use ($date, $effectiveStart, $effectiveEnd) {
                                return ($event[3] == $date) && ( ($event[4] >= $effectiveStart && $event[4] < $effectiveEnd) || ($effectiveStart >= $event[4] && $effectiveStart < $event[5]) );
                            });

                        if (empty($overlappingBookings)) {
                            $output .= "<a style='pointer-events: auto; position: absolute; right: 5px; bottom: 5px;' href='".$session->get('absoluteURL').'/index.php?q=/modules/Timetable/spaceBooking_manage_add.php&gibbonSpaceID='.$gibbonSpaceID.'&date='.$date.'&timeStart='.$effectiveStart.'&timeEnd='.$effectiveEnd."&source=tt'><img style='' title='".__('Add Facility Booking')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/page_new.png' width=20 height=20/></a>";
                        }
                    }
                }
                $output .= '</div>';
                ++$zCount;
            }
        }

        //Draw periods from TT
        try {
            $dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'gibbonSpaceID' => $gibbonSpaceID, 'gibbonTTDayID1' => $rowDay['gibbonTTDayID'], 'gibbonSpaceID1' => $gibbonSpaceID, 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count))));
            $sqlPeriods = "(SELECT 'Normal' AS type, gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName,
             gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTDay.gibbonTTDayID, gibbonTTDay.gibbonTTID
             FROM gibbonCourse
            JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
            JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID)
            JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
            LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayRowClass.gibbonTTDayID=:gibbonTTDayID AND gibbonSpace.gibbonSpaceID=:gibbonSpaceID)
            UNION
            (SELECT 'Change' AS type, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName,
            gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTDay.gibbonTTDayID, gibbonTTDay.gibbonTTID
            FROM gibbonCourse
            JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
            JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID)
            JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
            JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=:date) LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayRowClass.gibbonTTDayID=:gibbonTTDayID1 AND gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID1)
            ORDER BY timeStart, timeEnd";
            $resultPeriods = $connection2->prepare($sqlPeriods);
            $resultPeriods->execute($dataPeriods);
        } catch (PDOException $e) {
        }

        $periodCount = [];
        $periodData = [];
        while ($rowPeriods = $resultPeriods->fetch()) {
            $isSlotInTime = false;
            if ($rowPeriods['timeStart'] <= $dayTimeStart and $rowPeriods['timeEnd'] > $dayTimeStart) {
                $isSlotInTime = true;
            } elseif ($rowPeriods['timeStart'] >= $dayTimeStart and $rowPeriods['timeEnd'] <= $dayTimeEnd) {
                $isSlotInTime = true;
            } elseif ($rowPeriods['timeStart'] < $dayTimeEnd and $rowPeriods['timeEnd'] >= $dayTimeEnd) {
                $isSlotInTime = true;
            }

            if ($isSlotInTime == true) {
                if ((isset($spaceChanges[str_pad($rowPeriods['gibbonTTDayRowClassID'], 12, '0', STR_PAD_LEFT)]) == false and $rowPeriods['type'] == 'Normal') or $rowPeriods['type'] == 'Change') {

                    $offTimetableClass = false;

                    // Check for off timetabled classes by year group and by form group
                    if ($roleCategory == 'Staff' && !empty($specialDay) && $specialDay['type'] == 'Off Timetable') {

                        try {
                            $dataClassCheck = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonCourseClassID' => $rowPeriods['gibbonCourseClassID'], 'gibbonFormGroupIDList' => $specialDay['gibbonFormGroupIDList'], 'gibbonYearGroupIDList' => $specialDay['gibbonYearGroupIDList'], 'date' => date('Y-m-d', ($startDayStamp + (86400 * $count)))];
                            $sqlClassCheck = "SELECT count(CASE WHEN NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, :gibbonFormGroupIDList) AND NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList) THEN student.gibbonPersonID ELSE NULL END) as studentCount, count(*) as studentTotal, MAX(gibbonCourseClassMap.gibbonCourseClassMapID) as classMap
                                FROM gibbonCourseClassPerson 
                                JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID) 
                                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID) 
                                LEFT JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND FIND_IN_SET(gibbonCourseClassMap.gibbonFormGroupID, :gibbonFormGroupIDList))
                                WHERE role='Student' AND student.status='Full' 
                                AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                                AND (student.dateStart IS NULL OR student.dateStart<=:date) 
                                AND (student.dateEnd IS NULL OR student.dateEnd>=:date) 
                                ";
                            $resultClassCheck = $connection2->prepare($sqlClassCheck);
                            $resultClassCheck->execute($dataClassCheck);
                        } catch (PDOException $e) {
                        }
                        
                        // See if there are no students left in the class after year groups and form groups are checked
                        $classCheck = $resultClassCheck->fetch();
                        if (!empty($classCheck) && (($classCheck['studentTotal'] > 0 && $classCheck['studentCount'] <= 0) || !empty($classCheck['classMap']))) {
                            $offTimetableClass = true;
                        }
                    }

                    // Count how many classes are in this period
                    $periodCount[$rowPeriods['name']][] = $rowPeriods['course'].'.'.$rowPeriods['class'];
                    $periodData[$rowPeriods['name']][] = $rowPeriods;

                    $effectiveStart = $rowPeriods['timeStart'];
                    $effectiveEnd = $rowPeriods['timeEnd'];
                    if ($dayTimeStart > $rowPeriods['timeStart']) {
                        $effectiveStart = $dayTimeStart;
                    }
                    if ($dayTimeEnd < $rowPeriods['timeEnd']) {
                        $effectiveEnd = $dayTimeEnd;
                    }

                    $blank = false;
                    $width = (ceil(690 / $daysInWeek) - 20).'px';
                    $height = ceil((strtotime($effectiveEnd) - strtotime($effectiveStart)) / 60);
                    $top = (ceil((strtotime($effectiveStart) - strtotime($dayTimeStart)) / 60 + ($startPad / 60))).'px';
                    $title = "title='";
                    if ($height < 45) {
                        $title .= __('Time:').' '.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).' | ';
                        $title .= __('Timeslot:').' '.$rowPeriods['name'].' | ';
                    }
                    if ($rowPeriods['roomName'] != '') {
                        if ($height < 60) {
                            $title .= __('Room:').' '.$rowPeriods['roomName'].' | ';
                        }
                        if ($rowPeriods['phoneInternal'] != '') {
                            $title .= __('Phone:').' '.$rowPeriods['phoneInternal'].' | ';
                        }
                    }

                    $title = substr($title, 0, -3);
                    $title .= "'";
                    $class2 = 'ttPeriod';
                    $bg = '';

                    if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $date == date('Y-m-d')) {
                        $class2 = 'ttPeriodCurrent';
                    }

                    if ($offTimetableClass) {
                        $class2 = 'border bg-stripe-dark';
                        $bg = 'background-image: linear-gradient(45deg, #e6e6e6 25%, #f1f1f1 25%, #f1f1f1 50%, #e6e6e6 50%, #e6e6e6 75%, #f1f1f1 75%, #f1f1f1 100%); background-size: 23.0px 23.0px; border: 1px solid rgb(136, 136, 136);';
                    }

                    //Create div to represent period
                    $output .= "<div class='$class2' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width; height: {$height}px; margin: 0px; padding: 0px; opacity: $ttAlpha; {$bg}'>";

                    if ($height >= 45) {
                        $output .= $rowPeriods['name'].'<br/>';
                        $output .= '<i>'.substr($effectiveStart, 0, 5).' - '.substr($effectiveEnd, 0, 5).'</i><br/>';
                    }

                    $targetDate = date('Y-m-d', ($startDayStamp + (86400 * $count)));

                    $classCount = count($periodCount[$rowPeriods['name']] ?? []);
                    if ($classCount > 1) {
                        $spaceChangeID = $periodData[$rowPeriods['name']][0]['gibbonCourseClassID'] ?? '';
                        $spaceChangeTTID = $periodData[$rowPeriods['name']][0]['gibbonTTDayRowClassID'] ?? '';
                        $spaceChangeTTID = str_pad($spaceChangeTTID, 12, "0", STR_PAD_LEFT);

                        $spaceChangeEdit = $session->get('absoluteURL')."/index.php?q=/modules/Timetable/spaceChange_manage_add.php&step=2&gibbonTTDayRowClassID={$spaceChangeTTID}-{$targetDate}&gibbonCourseClassID={$spaceChangeID}&source={$gibbonSpaceID}";

                        $tag = Format::tag("+".($classCount -1), 'error absolute top-0 right-0 mt-1 mr-1 p-1 text-xxs leading-none', implode(' & ', array_slice($periodCount[$rowPeriods['name']], 0, -1)));
                        $output .= $targetDate >= date('Y-m-d') && $canAddChanges && !empty($spaceChangeID)
                            ? Format::link($spaceChangeEdit, $tag)
                            : $tag;
                    }

                    if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php')) {
                        $output .= "<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='".$session->get('absoluteURL').'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$rowPeriods['gibbonCourseClassID']."'>".$rowPeriods['course'].'.'.$rowPeriods['class'].'</a><br/>';
                    } else {
                        $output .= "<span style='font-size: 120%'><b>".$rowPeriods['course'].'.'.$rowPeriods['class'].'</b></span><br/>';
                    }
                    if ($height >= 60) {
                        if ($offTimetableClass) {
                            $output .= "<span class=''><i>".($specialDay['name'] ?? __('Off Timetable')).'</i></span>';
                        } elseif ($rowPeriods['type'] == 'Normal') {
                            $output .= $rowPeriods['roomName'];
                        } else {
                            $output .= "<span style='border: 1px solid #c00; padding: 0 2px'>".$rowPeriods['roomName'].'</span>';
                        }
                    }

                    $gibbonTTDayRowClassID = str_pad($rowPeriods['gibbonTTDayRowClassID'], 12, "0", STR_PAD_LEFT);
                    
                    if ($targetDate >= date('Y-m-d') && $canAddChanges) {
                        if ($offTimetableClass) {
                            $output .= "<a style='pointer-events: auto; position: absolute; right: 5px; bottom: 5px;' href='".$session->get('absoluteURL').'/index.php?q=/modules/Timetable/spaceBooking_manage_add.php&gibbonSpaceID='.$gibbonSpaceID.'&date='.$targetDate.'&timeStart='.$effectiveStart.'&timeEnd='.$effectiveEnd."&source=tt'><img style='' title='".__('Add Facility Booking')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/page_new.png' width=20 height=20/></a>";
                        } else {
                            $output .= "<a style='pointer-events: auto; position: absolute; right: 5px; bottom: 5px;' href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable/spaceChange_manage_add.php&step=2&gibbonTTDayRowClassID={$gibbonTTDayRowClassID}-{$targetDate}&gibbonCourseClassID={$rowPeriods['gibbonCourseClassID']}&source={$gibbonSpaceID}'><img style='' title='".__('Add Facility Change')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/copyforward.png' width=20 height=20/></a>";
                        }
                        
                    }

                    if ($canEditTTDays) {
                        $output .= "<a style='pointer-events: auto; position: absolute; left: 5px; bottom: 5px;' href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_edit.php&gibbonSchoolYearID={$session->get('gibbonSchoolYearID')}&gibbonTTID={$rowPeriods['gibbonTTID']}&gibbonTTDayID={$rowDay['gibbonTTDayID']}&gibbonTTColumnRowID={$rowPeriods['gibbonTTColumnRowID']}&gibbonTTDayRowClassID={$gibbonTTDayRowClassID}&gibbonCourseClassID={$rowPeriods['gibbonCourseClassID']}'><img style='' title='".__('Edit Class in Period')."' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/config.png' width=20 height=20/></a>";
                    }

                    $output .= '</div>';
                    ++$zCount;
                }
            }
        }

        //Draw activities
        if (!empty($activities)) {
            $height = 0;
            $top = 0;
            foreach ($activities as $event) {
                if (empty($event[2])) continue;

                if (date('Y-m-d', $event[2]) == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                    $label = $event[0];
                    $title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])." ".$event[4]."'";
                    $height = ceil(($event[3] - $event[2]) / 60);
                    $charCut = 40;
                    if ($height <= 60) {
                        $charCut = 18;
                    }
                    if (strlen($label) > $charCut) {
                        $label = substr($label, 0, $charCut).'...';
                        $title = "title='".htmlPrep($event[0]).' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")  ".$event[4]."'";
                    }

                    if (!empty($event[6]) && $event[6]['cancelActivities'] == 'Y') {
                        $class = 'ttActivities border bg-stripe-dark';
                        $bg = 'background-image: linear-gradient(45deg, #e6e6e6 25%, #f1f1f1 25%, #f1f1f1 50%, #e6e6e6 50%, #e6e6e6 75%, #f1f1f1 75%, #f1f1f1 100%); background-size: 23.0px 23.0px;';
                    } else {
                        $class = 'ttActivities ttPeriod';
                        $bg = 'background: #dfcbf6 !important;';
                    }
                    $top = (ceil(($event[2] - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
                    $output .= "<div class='{$class}' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; {$bg}'>";
                    if ($height >= 26) {
                        $output .= __('Activity').'<br/>';
                    }
                    if ($height >= 40) {
                        $output .= '<i>'.date('H:i', $event[2]).' - '.date('H:i', $event[3]).'</i><br/>';
                    }

                    $output .= "<div style='text-decoration: none; font-weight: bold; '>".$label.'</div>';

                    if (!empty($event[6]) && $event[6]['cancelActivities'] == 'Y') {
                        $output .= '<i>'.__('Cancelled').'</i><br/>';
                    } elseif (($height >= 55 && $charCut <= 20) || ($height >= 68 && $charCut >= 40)) {
                        $output .= $event[4].'<br/>';
                    }
                    $output .= '</div>';
                }
                ++$zCount;

            }
        }
        

        $output .= '</div>';
    }

    //Draw space bookings
    if ($eventsSpaceBooking != false) {
        $height = 0;
        $top = 0;
        
        if (empty($dayTimeStart)) {
            $dayTimeStart = $gridTimeStart;
        }

        $output .= '<div class="relative">';
        foreach ($eventsSpaceBooking as $event) {
            if ($event[9] == date('Y-m-d', ($startDayStamp + (86400 * $count)))) {
                $height = ceil((strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[5]) - strtotime(date('Y-m-d', ($startDayStamp + (86400 * $count))).' '.$event[4])) / 60);
                $top = (ceil((strtotime($event[9].' '.$event[4]) - strtotime(date('Y-m-d', $startDayStamp + (86400 * $count)).' '.$dayTimeStart)) / 60 + ($startPad / 60))).'px';
                if ($height < 45) {
                    $label = $event[1];
                    $title = "title='".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).' '.__('by').' '.$event[6]."'";
                } else {
                    $label = $event[1]."<br/><span style='font-weight: normal'>(".substr($event[4], 0, 5).' - '.substr($event[5], 0, 5).')<br/>'.__('by').' '.$event[6].'</span>';
                    $title = '';
                }

                if ($height > 56) {
                    $label .= '<br/>'.Format::small(Format::truncate($event[7], 60));
                } 
                $output .= "<div class='ttSpaceBookingCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: 100%; min-width: $width ; border: 1px solid rgb(136, 136, 136); height: {$height}px; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>";
                $output .= $label;
                $output .= '</div>';
                ++$zCount;
            }
        }
        $output .= '</div>';
    }

    $output .= '</td>';

    return $output;
}
