<?php
include '../../gibbon.php';

date_default_timezone_set($session->get('timezone'));
$vCalendar = new \Eluceo\iCal\Component\Calendar('Calendar Export');

$data0 = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
$sql0 = 'SELECT * FROM gibbonTTDayDate JOIN gibbonTTDay on gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID WHERE date >= (SELECT firstDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) AND date <= (SELECT lastDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID)';
$result0 = $connection2->prepare($sql0);
$result0->execute($data0);
if ($result0->rowCount() < 1) {
    echo "<div class='error'>";
    echo __('There are no records to display.');
    echo '</div>';
} else {
    while ($values0 = $result0->fetch()) {
        $data1 = array( 'gibbonTTDayID' => $values0['gibbonTTDayID'], 'gibbonPersonID' => $session->get('gibbonPersonID') );
        $sql1 = 'SELECT timeStart, timeEnd, gibbonCourse.name FROM gibbonTTDayRowClass
                  JOIN gibbonTTColumnRow on gibbonTTDayRowClass.gibbonTTColumnRowID = gibbonTTColumnRow.gibbonTTColumnRowID
                  JOIN gibbonCourseClassPerson on gibbonTTDayRowClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
                  JOIN gibbonCourseClass on gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
                  JOIN gibbonCourse on gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
                  WHERE gibbonTTDayID=:gibbonTTDayID
                  AND gibbonPersonID=:gibbonPersonID';
        $result1 = $connection2->prepare($sql1);
        $result1->execute($data1);
        if ($result1->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            while ($values1 = $result1->fetch()) {
              $vEvent = new \Eluceo\iCal\Component\Event();
              if ((int)$_POST['options']!=(int)('options')) {
                // Alarm Creation
                $vAlarm = new \Eluceo\iCal\Component\Alarm();
                $vAlarm->setAction('DISPLAY');
                $vAlarm->setTrigger('-PT'.(int)$_POST['options'].'M'); // Set alert time
                $vAlarm->setDescription('Event description');
                $vEvent->addComponent($vAlarm);
              } else {}
                // Event Creation
              $vEvent->setDtStart(new \DateTime($values0['date'].' '.$values1['timeStart']));
              $vEvent->setDtEnd(new \DateTime($values0['date'].' '.$values1['timeEnd']));
              $vEvent->setSummary($values1['name']);
              $vEvent->setUseTimezone(true);
              $vCalendar->addComponent($vEvent);
            }
        }
    }
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="cal.ics"');
    echo $vCalendar->render();
}

