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

use Gibbon\Domain\FormalAssessment\InternalAssessmentColumnGateway;
use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Timetable\CourseClassGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonCourseClassID and gibbonInternalAssessmentColumnID specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'] ?? '';
    if ($gibbonCourseClassID == '' or $gibbonInternalAssessmentColumnID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $result = $container->get(CourseClassGateway::class)->getCourseClass($gibbonCourseClassID);

        if (empty($result)) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {

                $result2 = $container->get(InternalAssessmentColumnGateway::class)->getByID($gibbonInternalAssessmentColumnID);

            if (empty($result2)) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Let's go!
                $values = $result;
                $values2 = $result2;

                $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/internalAssessment_manage_deleteProcess.php?gibbonInternalAssessmentColumnID=$gibbonInternalAssessmentColumnID");
                echo $form->getOutput();
            }
        }
    }
}
?>
