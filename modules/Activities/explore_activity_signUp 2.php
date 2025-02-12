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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity_signUp.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonActivityCategoryID = $_REQUEST['gibbonActivityCategoryID'] ?? '';
    $gibbonActivityID = $_REQUEST['gibbonActivityID'] ?? '';

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $choiceGateway = $container->get(ActivityChoiceGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    if (empty($gibbonActivityCategoryID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $activity = $activityGateway->getActivityDetailsByID($gibbonActivityID);
    $category = $categoryGateway->getCategoryDetailsByID($gibbonActivityCategoryID);

    if (empty($category)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check that sign up is open based on the date
    $signUpIsOpen = false;
    if (!empty($category['accessOpenDate']) && !empty($category['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    if (!$signUpIsOpen) {
        $page->addError(__m('Sign up is currently not available for this activity.'));
        return;
    }
    
    // Check the student's sign up access based on their year group
    $signUpCategory = $categoryGateway->getCategorySignUpAccess($gibbonActivityCategoryID, $session->get('gibbonPersonID'));
    $signUpActivity = $activityGateway->getActivitySignUpAccess($gibbonActivityID, $session->get('gibbonPersonID'));

    if (!$signUpCategory || (!empty($activity) && !$signUpActivity)) {
        $page->addError(__m('Sign up is currently not available for this activity.'));
        return;
    }

    // Get experiences
    $activities = $activityGateway->selectActivitiesByCategoryAndPerson($gibbonActivityCategoryID, $session->get('gibbonPersonID'))->fetchKeyPair();
    $choicesSelected = $choiceGateway->selectChoicesByPerson($gibbonActivityCategoryID, $session->get('gibbonPersonID'))->fetchGroupedUnique();

    $category = $categoryGateway->getByID($gibbonActivityCategoryID);
    $signUpChoices = $category['signUpChoices'] ?? 3;
    $signUpText = $settingGateway->getSettingByScope('Activities', 'signUpText');

    // Lower the choice limit if there are less options
    if (count($activities) < $signUpChoices) {
        $signUpChoices = count($activities);
    }

    $choiceList = [1 => __m('First Choice'), 2 => __m('Second Choice'), 3 => __m('Third Choice'), 4 => __m('Fourth Choice'), 5 => __m('Fifth Choice')];
    $choice = [];
    for ($i = 1; $i <= $signUpChoices; $i++) {
        $choice[$i] = $choicesSelected[$i]['gibbonActivityID'] ?? '';
        if ($i == 1 && empty($choice[$i])) $choice[$i] = $gibbonActivityID;
    }
    
    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/'.$session->get('module').'/explore_activity_signUpProcess.php');
    $form->setTitle(__('Activity Registration'));
    $form->setDescription($signUpText);

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonPersonID', $session->get('gibbonPersonID'));
    $form->addHiddenValue('gibbonActivityCategoryID', $gibbonActivityCategoryID);
    $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);

    for ($i = 1; $i <= $signUpChoices; $i++) {
        $row = $form->addRow();
        $row->addLabel("choices[{$i}]", $choiceList[$i] ?? $i);
        $row->addSelect("choices[{$i}]")
            ->fromArray($activities)
            ->setID("choices{$i}")
            ->addClass('signUpChoice')
            ->required()
            ->placeholder()
            ->selected($choice[$i] ?? '');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>

<script>
$(document).on('change input', '.signUpChoice', function () {
    var currentChoice = this;

    $('.signUpChoice').not(this).each(function() {
        if ($(currentChoice).val() == $(this).val()) {
            $(this).val($(this).find("option:first-child").val());
        }
    });
});
</script>
