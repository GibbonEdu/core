<?php
use Module\Students\functions\studentView ;

$studentImage = $el->student->getField('image_240') ;

$trail = $this->initiateTrail();
$trail->trailEnd = array('%1$s', array($el->student->formatName()));
$trail->addTrail('View Student Profiles', array('q'=>'/modules/Students/student_view.php', 'search'=>$el->search, 'allStudents'=>$el->allStudents, 'sort'=>$el->sort));
$trail->render($this);

if (empty($el->subpage) && (empty($hook) || empty($module) || empty($action))) {
    $el->subpage = 'Overview';
}

if (! empty($el->search) || ! empty($el->allStudents)) {
    $el->links = array_merge($el->links, array('Back to Search Results' => '/modules/Students/student_view.php&search='.$el->search.'&allStudents='.$el->allStudents));
}

$el->header = ! empty($el->subpage) ? $el->subpage : $el->hook ;
$el->header = array($el->header.'%1$s', array(': '.$el->student->formatName())) ;

if ($el->subpage == 'Overview') {
    $this->render('student.details.full.overview', $el);
} elseif ($el->subpage == 'Personal') {
    $this->render('student.details.full.personal', $el);
} elseif ($el->subpage == 'Family') {
    $this->render('student.details.full.family', $el);
} elseif ($el->subpage == 'Emergency Contacts') {
    $this->render('student.details.full.emergencyContact', $el);
} elseif ($el->subpage == 'Medical') {
    $this->render('student.details.full.medical', $el);
} elseif ($el->subpage == 'Notes') {
    $this->render('student.details.full.notes', $el);
} elseif ($el->subpage == 'School Attendance') {
    $this->render('student.details.full.schoolAttendance', $el);
} elseif ($el->subpage == 'Markbook') {
    $this->render('student.details.full.markbook', $el);
} elseif ($el->subpage == 'Internal Assessment') {
    $this->render('student.details.full.internalAssessment', $el);
} elseif ($el->subpage == 'External Assessment') {
    $this->render('student.details.full.externalAssessment', $el);
} elseif ($el->subpage == 'Individual Needs') {
    $this->render('student.details.full.individualNeeds', $el);
} elseif ($el->subpage == 'Library Borrowing') {
    $this->render('student.details.full.libraryBorrowing', $el);
} elseif ($el->subpage == 'Timetable') {
    $this->render('student.details.full.timetable', $el);
} elseif ($el->subpage == 'Activities') {
    $this->render('student.details.full.activities', $el);
} elseif ($el->subpage == 'Homework') {
    $this->render('student.details.full.homework', $el);
} elseif ($el->subpage == 'Behaviour') {
    $this->render('student.details.full.behaviour', $el);
}

//GET HOOK IF SPECIFIED
if (! empty($hook) && ! empty($module) && ! empty($action)) {
    //GET HOOKS AND DISPLAY LINKS
    //Check for hook
	$hObj = new hook($this, $_GET['gibbonHookID']);

    if ($hObj->rowCount() != 1) {
        $this->displayMessage('There are no records to display.');
    } else {
        $rowHook = $resultHook->fetch();
        $options = unserialize($rowHook['options']);

        //Check for permission to hook
        try {
            $dataHook = array('gibbonRoleIDCurrent' => $_SESSION['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
            $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonModule.name='".$options['sourceModuleName']."' AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Student Profile' ORDER BY name";
            $resultHook = $connection2->prepare($sqlHook);
            $resultHook->execute($dataHook);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultHook->rowcount() != 1) {
            $this->displayMessage('Your request failed because you do not have access to this action.');
        } else {
            $include = $_SESSION['absolutePath'].'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
            if (!file_exists($include)) {
                $this->displayMessage('The selected page cannot be displayed due to a hook error.');
            } else {
                include $include;
            }
        }
    }
}

$sv = new studentView($el->student);

$sv->sidebarExtra();
