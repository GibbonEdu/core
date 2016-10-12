<?php
use Module\Timetable\Functions\functions as timeTable;

$this->h2($el->header);
if ($this->getSecurity()->isActionAccessible('/modules/User Admin/user_manage.php')) 
    $this->linkTop(array_merge($el->links, array('edit' => '/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$el->personID)));

//Medical alert!
$alert = $el->student->getHighestMedicalRisk($el->personID, $this);
if ($alert) {
    $alert[1] = strtolower(trans::__($alert[1]));
    $this->displayAlert('This student has one or more %1$s risk medical conditions.', $alert);
}

$el->student->getYearGroup();

$el->student->getRollGroup();

if (! $el->student->validRollGroup)
    $el->student->rollGroup->getTutors();

$el->student->getEnrolment();

$this->render('Students.family.overview.generalInformation', $el->student);

$this->render('Students.family.overview.teachers', $el->student->getTeachers());

//Show timetable
if ($this->getSecurity()->isActionAccessible('/modules/Timetable/tt_view.php')) {
    if ($this->getSecurity()->isActionAccessible('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')) {
        $role = $this->getSecurity()->getRoleCategory($el->student->getField('gibbonRoleIDPrimary'));
        if ($role == 'Student' || $role == 'Staff') 
            $this->linkTop(array('edit' => '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$el->personID&gibbonSchoolYearID='.$this->session->get('gibbonSchoolYearID').'&type='.$role));
    }
    $this->h3('Timetable');
    
    $ttFunc = new timeTable($this);
    $ttDate = null;
    if (isset($_POST['ttDate'])) 
        $ttDate = helper::dateConvertToTimestamp(helper::dateConvert($_POST['ttDate']));
    $tt = $ttFunc->renderTT($el->personID, '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$el->personID&search=$el->search&allStudents=$el->allStudents#timetable");
    if ($tt != false) {
        echo $tt;
    } else {
        $this->displayMessage('There are no records to display.');
    }
}


