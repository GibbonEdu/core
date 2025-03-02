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
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Module\FreeLearning\Domain\MentorGroupGateway;
use Gibbon\Module\FreeLearning\Domain\MentorGroupPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorGroups_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $freeLearningMentorGroupID = $_GET['freeLearningMentorGroupID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Mentor Groups'), 'mentorGroups_manage.php')
        ->add(__m('Edit Mentor Group'));

    if (empty($freeLearningMentorGroupID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $mentorGroupGateway = $container->get(MentorGroupGateway::class);
    $mentorGroupPersonGateway = $container->get(MentorGroupPersonGateway::class);

    $values = $mentorGroupGateway->getByID($freeLearningMentorGroupID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get a list of current mentors
    $existingMentors = $mentorGroupPersonGateway->selectGroupPeopleByRole($freeLearningMentorGroupID, 'Mentor')->fetchAll();
    $existingMentors = Format::nameListArray($existingMentors, 'Staff', true, true);

    // Get a list of potential mentors
    $mentors = $container->get(UserGateway::class)->selectUserNamesByStatus('Full', 'Staff')->fetchAll();
    $mentors = Format::nameListArray($mentors, 'Staff', true, true);
    $mentors = array_diff_key($mentors, $existingMentors);

    // Get a list of potential students (can include any user)
    $existingStudents = $mentorGroupPersonGateway->selectGroupPeopleByRole($freeLearningMentorGroupID, 'Student')->fetchAll();
    $existingStudents = array_reduce($existingStudents, function ($group, $person) {
        $group[$person['gibbonPersonID']] = Format::name($person['title'] ?? '', $person['preferredName'], $person['surname'], 'Student', true, true).' ('.$person['roleCategory'].', '.$person['username'].')';
        return $group;
    }, []);

    // Get a list of potential students (can include any user)
    $students = $container->get(UserGateway::class)->selectUserNamesByStatus('Full')->fetchAll();
    $students = array_reduce($students, function ($group, $person) {
        $group[$person['gibbonPersonID']] = Format::name($person['title'] ?? '', $person['preferredName'], $person['surname'], 'Student', true, true).' ('.$person['roleCategory'].', '.$person['username'].')';
        return $group;
    }, []);

    // Get the available custom fields for automatic assignment
    $fields = $container->get(CustomFieldGateway::class)->selectBy(['active' => 'Y', 'context' => 'User'], ['gibbonCustomFieldID', 'name', 'type', 'options'])->fetchAll();
    $allFields = $selectFields = $selectOptions = $chainedOptions =  [];
    foreach ($fields as $field) {
        $allFields[$field['gibbonCustomFieldID']] = $field['name'];

        if ($field['type'] == 'select' || $field['type'] == 'checkboxes' || $field['type'] == 'radio') {
            $selectFields[$field['gibbonCustomFieldID']] = $field['name'];
            $options = array_map('trim', explode(',',  $field['options']));
            foreach ($options as $option) {
                if (substr($option, 0, 1) == '[') continue; // Skip optgroups

                $selectOptions[$option] = $option;
                $chainedOptions[$option] = $field['gibbonCustomFieldID'];
            }
        }
    }

    $form = Form::create('mentorship', $session->get('absoluteURL').'/modules/'.$session->get('module').'/mentorGroups_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('freeLearningMentorGroupID', $freeLearningMentorGroupID);

    $row = $form->addRow();
        $row->addLabel('name', __m('Group Name'));
        $row->addTextField('name')->maxLength(100)->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('mentors', __('Mentors'));
        $select = $col->addMultiSelect('mentors');
        $select->source()->fromArray($mentors);
        $select->destination()->fromArray($existingMentors);

    $assignments = ['Manual' => __m('Manual'), 'Automatic' => __m('Automatic')];
    $row = $form->addRow();
        $row->addLabel('assignment', __m('Group Assignment'))->description(__m('Determines how students are added to this group.'));
        $row->addSelect('assignment')->fromArray($assignments)->required()->placeholder();

    $form->toggleVisibilityByClass('automatic')->onSelect('assignment')->when('Automatic');
    $row = $form->addRow()->addClass('automatic');
        $row->addLabel('gibbonCustomFieldID', __('Custom Field'));
        $row->addSelect('gibbonCustomFieldID')->fromArray($allFields)->required()->placeholder();

    $row = $form->addRow()->addClass('fieldText');
        $row->addLabel('fieldValue', __('Custom Field Value'));
        $row->addTextField('fieldValue')->maxLength(100)->required()->setValue($values['fieldValue']);

    if (!empty($selectFields)) {
        $form->toggleVisibilityByClass('fieldText')->onSelect('gibbonCustomFieldID')->whenNot(array_keys($selectFields));
        $form->toggleVisibilityByClass('fieldSelect')->onSelect('gibbonCustomFieldID')->when(array_keys($selectFields));
        $row = $form->addRow()->addClass('fieldSelect');
            $row->addLabel('fieldValueSelect', __('Custom Field Value'));
            $row->addSelect('fieldValueSelect')->fromArray($selectOptions)->chainedTo('gibbonCustomFieldID', $chainedOptions)->selected($values['fieldValue']);
    } else {
        $form->toggleVisibilityByClass('fieldText')->onSelect('assignment')->when('Automatic');
    }

    $form->toggleVisibilityByClass('manual')->onSelect('assignment')->when('Manual');
    $col = $form->addRow()->addClass('manual')->addColumn();
        $col->addLabel('students', __('Students'));
        $select = $col->addMultiSelect('students');
        $select->source()->fromArray($students);
        $select->destination()->fromArray($existingStudents);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
