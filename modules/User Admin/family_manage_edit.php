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

use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Http\Url;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Families'), 'family_manage.php')
        ->add(__('Edit Family'));        

    //Check if search and gibbonFamilyID specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'] ?? '';
    $search = $_GET['search'] ?? '';
    if ($search != '') {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('User Admin', 'family_manage.php')->withQueryParam('search', $search));
    }

    if (empty($gibbonFamilyID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } else {
        $familyGateway = $container->get(FamilyGateway::class);
        $family = $familyGateway->getByID($gibbonFamilyID);

        if (empty($family)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        } else {
            //Let's go!
            $form = Form::create('action1', $session->get('absoluteURL').'/modules/'.$session->get('module')."/family_manage_editProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $form->addRow()->addHeading('General Information', __('General Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Family Name'));
                $row->addTextField('name')->maxLength(100)->required();

            $row = $form->addRow();
        		$row->addLabel('status', __('Marital Status'));
        		$row->addSelectMaritalStatus('status')->required();

            $row = $form->addRow();
                $row->addLabel('languageHomePrimary', __('Home Language - Primary'));
                $row->addSelectLanguage('languageHomePrimary');

            $row = $form->addRow();
                $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
                $row->addSelectLanguage('languageHomeSecondary');

            $row = $form->addRow();
                $row->addLabel('nameAddress', __('Address Name'))->description(__('Formal name to address parents with.'));
                $row->addTextField('nameAddress')->maxLength(100)->required();

            $row = $form->addRow();
                $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                $row->addTextArea('homeAddress')->maxLength(255)->setRows(2);

            $row = $form->addRow();
                $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                $row->addTextFieldDistrict('homeAddressDistrict');

            $row = $form->addRow();
                $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                $row->addSelectCountry('homeAddressCountry');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($family);

            echo $form->getOutput();


            //Get children and prep array
            $dataChildren = array('gibbonFamilyID' => $gibbonFamilyID);
            $sqlChildren = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName';
            $resultChildren = $pdo->select($sqlChildren, $dataChildren);

            $children = array();
            $count = 0;
            while ($rowChildren = $resultChildren->fetch()) {
                $children[$count]['image_240'] = $rowChildren['image_240'];
                $children[$count]['gibbonPersonID'] = $rowChildren['gibbonPersonID'];
                $children[$count]['preferredName'] = $rowChildren['preferredName'];
                $children[$count]['surname'] = $rowChildren['surname'];
                $children[$count]['status'] = $rowChildren['status'];
                $children[$count]['comment'] = $rowChildren['comment'];

                $dataDetail = array('gibbonPersonID' => $rowChildren['gibbonPersonID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sqlDetail = 'SELECT * FROM gibbonFormGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultDetail = $pdo->select($sqlDetail, $dataDetail);

                if ($resultDetail->rowCount() == 1) {
                    $rowDetail = $resultDetail->fetch();
                    $children[$count]['formGroup'] = $rowDetail['name'];
                }

                ++$count;
            }
            //Get adults and prep array
            $dataAdults = array('gibbonFamilyID' => $gibbonFamilyID);
            $sqlAdults = 'SELECT * FROM gibbonFamilyAdult, gibbonPerson WHERE (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
            $resultAdults = $pdo->select($sqlAdults, $dataAdults);

            $adults = array();
            $count = 0;
            while ($rowAdults = $resultAdults->fetch()) {
                $adults[$count]['image_240'] = $rowAdults['image_240'];
                $adults[$count]['gibbonPersonID'] = $rowAdults['gibbonPersonID'];
                $adults[$count]['title'] = $rowAdults['title'];
                $adults[$count]['preferredName'] = $rowAdults['preferredName'];
                $adults[$count]['surname'] = $rowAdults['surname'];
                $adults[$count]['status'] = $rowAdults['status'];
                $adults[$count]['comment'] = $rowAdults['comment'];
                $adults[$count]['childDataAccess'] = $rowAdults['childDataAccess'];
                $adults[$count]['contactPriority'] = $rowAdults['contactPriority'];
                $adults[$count]['contactCall'] = $rowAdults['contactCall'];
                $adults[$count]['contactSMS'] = $rowAdults['contactSMS'];
                $adults[$count]['contactEmail'] = $rowAdults['contactEmail'];
                $adults[$count]['contactMail'] = $rowAdults['contactMail'];
                ++$count;
            }

            //Get relationships and prep array
            $dataRelationships = array('gibbonFamilyID' => $gibbonFamilyID);
            $sqlRelationships = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID';
            $resultRelationships = $pdo->select($sqlRelationships, $dataRelationships);

            $relationships = array();
            $count = 0;
            while ($rowRelationships = $resultRelationships->fetch()) {
                $relationships[$rowRelationships['gibbonPersonID1']][$rowRelationships['gibbonPersonID2']] = $rowRelationships['relationship'];
                ++$count;
            }

            // RELATIONSHIPS
            $form = Form::createTable('action2', $session->get('absoluteURL').'/modules/'.$session->get('module')."/family_manage_edit_relationshipsProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");
            $form->setTitle(__('Relationships'));
            $form->setDescription(__('Use the table below to show how each child is related to each adult in the family.'));

            if ($resultChildren->rowCount() < 1 or $resultAdults->rowCount() < 1) {
                $form->setDescription(Format::alert(__('There are not enough people in this family to form relationships.')));
            } else {
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('colorOddEven fullWidth');

                $form->addHiddenValue('address', $session->get('address'));

                $row = $form->addRow()->addClass('head break');
                    $row->addContent(__('Adults'));
                    foreach ($children as $child) {
                        $row->addContent(Format::name('', $child['preferredName'], $child['surname'], 'Student'));
                    }

                $count = 0;
                foreach ($adults as $adult) {
                    ++$count;
                    $row = $form->addRow();
                        $row->addContent(Format::name($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent'));
                        foreach ($children as $child) {
                            $form->addHiddenValue('gibbonPersonID1[]', $adult['gibbonPersonID']);
                            $form->addHiddenValue('gibbonPersonID2[]', $child['gibbonPersonID']);
                            $relationshipSet = (isset($relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']]) ? $relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] : null);
                            $row->addSelectRelationship('relationships['.$adult['gibbonPersonID'].']['.$child['gibbonPersonID'].']')->setClass('smallWidth floatNone')->selected($relationshipSet);
                        }
                }

                $row = $form->addRow();
                    $row->addSubmit();

                $form->loadAllValuesFrom($family);
            }

            echo $form->getOutput();

            // CHILDREN
            $table = DataTable::create('children');
            $table->setTitle(__('View Children'));

            $table->addColumn('photo', __('Photo'))
                ->format(Format::using('photo', ['image_240']));

            $table->addColumn('name', __('Name'))
                ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student']));

            $table->addColumn('status', __('Status'))->translatable();

            $table->addColumn('formGroup', __('Form Group'));
            $table->addColumn('comment', __('Comment'))
                ->format(function ($child) {
                    return nl2br($child['comment']);
                });

            $table->addActionColumn()
                ->addParam('search', $search)
                ->addParam('gibbonFamilyID', $gibbonFamilyID)
                ->addParam('gibbonPersonID')
                ->format(function($child, $actions) use ($session) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/' . $session->get('module') . '/family_manage_edit_editChild.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/' . $session->get('module') . '/family_manage_edit_deleteChild.php');

                    $actions->addAction('changePassword', __('Change Password'))
                        ->setIcon('key')
                        ->setURL('/modules/' . $session->get('module') . '/user_manage_password.php');
                });

            echo $table->render($children);

            // ADD CHILD
            $form = Form::create('action3', $session->get('absoluteURL').'/modules/'.$session->get('module')."/family_manage_edit_addChildProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $form->addRow()->addHeading('Add Child', __('Add Child'));

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Child\'s Name'));
                $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), array('allStudents' => true, 'byName' => true, 'byForm' => true, 'showForm' => true))->placeholder()->required();

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment')->setRows(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            // ADULTS
            $table = DataTable::create('adults');
            $table->setTitle(__('View Adults'));
            $table->setDescription(Format::alert(__('Logic exists to try and ensure that there is always one and only one parent with Contact Priority set to 1. This may result in values being set which are not exactly what you chose.'), 'warning'));

            $table->addColumn('name', __('Name'))
                ->format(function ($adult) {
                    $name = Format::name($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent');
                    return Format::link('./index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=' . $adult['gibbonPersonID'], $name);
                });

            $table->addColumn('status', __('Status'))->translatable();

            $table->addColumn('comment', __('Comment'))
                ->format(function ($adult) {
                    return nl2br($adult['comment']);
                });

            //Note: This is hacky, but will have to exist until rotating becomes built-in functionality
            $table->addColumn('childDataAccess', '<div class="transform -rotate-90"> ' . __('Data Access') . '</div>')
                ->width('50px')
                ->format(function($adult){
                    return Format::yesNo($adult['childDataAccess']);
                });

            $table->addColumn('contactPriority', '<div class="transform -rotate-90"> ' . __('Contact Priority') . '</div>')
                ->width('50px');

            $table->addColumn('contactCall', '<div class="transform -rotate-90"> ' . __('Contact By Phone') . '</div>')
                ->format(function($adult){
                    return Format::yesNo($adult['contactCall']);
                })
                ->width('50px');

            $table->addColumn('contactSMS', '<div class="transform -rotate-90"> ' . __('Contact By SMS') . '</div>')
                ->format(function($adult){
                    return Format::yesNo($adult['contactSMS']);
                })
                ->width('50px');

            $table->addColumn('contactEmail', '<div class="transform -rotate-90"> ' . __('Contact By Email') . '</div>')
                ->width('50px')
                ->format(function($adult){
                    return Format::yesNo($adult['contactEmail']);
                });

            $table->addColumn('contactMail', '<div class="transform -rotate-90"> ' . __('Contact By Mail') . '</div>')
                ->width('50px')
                ->format(function($adult){
                    return Format::yesNo($adult['contactMail']);
                });

            $table->addActionColumn()
                ->addParam('gibbonFamilyID', $gibbonFamilyID)
                ->addParam('gibbonPersonID')
                ->addParam('search', $search)
                ->format(function ($adult, $actions) use ($session) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/' . $session->get('module') . '/family_manage_edit_editAdult.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/' . $session->get('module') . '/family_manage_edit_deleteAdult.php');

                    $actions->addAction('changePassword', __('Change Password'))
                        ->setIcon('key')
                        ->setURL('/modules/' . $session->get('module') . '/user_manage_password.php');
                });

            echo $table->render($adults);


            $form = Form::create('action4', $session->get('absoluteURL').'/modules/'.$session->get('module')."/family_manage_edit_addAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $form->addRow()->addHeading('Add Adult', __('Add Adult'));

            $adults = array();

            $sqlSelect = "SELECT status, gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
            $resultSelect = $pdo->select($sqlSelect);

            while ($rowSelect = $resultSelect->fetch()) {
                $expected = (($rowSelect['status'] == 'Expected') ? ' ('.__('Expected').')' : '');
                $adults[$rowSelect['gibbonPersonID']] = Format::name('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Parent', true, true).' ('.$rowSelect['username'].')'.$expected;
            }

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID2', __('Adult\'s Name'));
                $row->addSelect('gibbonPersonID2')->fromArray($adults)->placeHolder()->required();

            $row = $form->addRow();
                $row->addLabel('comment2', __('Comment'))->description(__('Data displayed in full Student Profile'));
                $row->addTextArea('comment2')->setRows(8);

            $row = $form->addRow();
                $row->addLabel('childDataAccess', __('Data Access?'))->description(__('Access data on family\'s children?'));
                $row->addYesNo('childDataAccess')->required();

            $priorities = array(
                '1' => __('1'),
                '2' => __('2'),
                '3' => __('3')
            );
            $row = $form->addRow();
                $row->addLabel('contactPriority', __('Contact Priority'))->description(__('The order in which school should contact family members.'));
                $row->addSelect('contactPriority')->fromArray($priorities)->required();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactCall', __('Call?'))->description(__('Receive non-emergency phone calls from school?'));
                $row->addYesNo('contactCall')->required();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactSMS', __('SMS?'))->description(__('Receive non-emergency SMS messages from school?'));
                $row->addYesNo('contactSMS')->required();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactEmail', __('Email?'))->description(__('Receive non-emergency emails from school?'));
                $row->addYesNo('contactEmail')->required();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactMail', __('Mail?'))->description(__('Receive postage mail from school?'));
                $row->addYesNo('contactMail')->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo "<script type=\"text/javascript\">
                $(document).ready(function(){
                    $(\"#contactCall\").attr(\"disabled\", \"disabled\");
                    $(\"#contactSMS\").attr(\"disabled\", \"disabled\");
                    $(\"#contactEmail\").attr(\"disabled\", \"disabled\");
                    $(\"#contactMail\").attr(\"disabled\", \"disabled\");
                    $(\"#contactPriority\").change(function(){
                        if ($('#contactPriority').val()==\"1\" ) {
                            $(\"#contactCall\").attr(\"disabled\", \"disabled\");
                            $(\"#contactCall\").val(\"Y\");
                            $(\"#contactSMS\").attr(\"disabled\", \"disabled\");
                            $(\"#contactSMS\").val(\"Y\");
                            $(\"#contactEmail\").attr(\"disabled\", \"disabled\");
                            $(\"#contactEmail\").val(\"Y\");
                            $(\"#contactMail\").attr(\"disabled\", \"disabled\");
                            $(\"#contactMail\").val(\"Y\");
                        }
                        else {
                            $(\"#contactCall\").removeAttr(\"disabled\");
                            $(\"#contactSMS\").removeAttr(\"disabled\");
                            $(\"#contactEmail\").removeAttr(\"disabled\");
                            $(\"#contactMail\").removeAttr(\"disabled\");
                        }
                     });
                });
            </script>";
        }
    }
}
?>
