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

use Gibbon\Module\Messenger\Forms\MessageForm;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
        print __("You do not have access to this action.") ;
    print "</div>" ;
}
else {
    //Get action with highest precendence
    $highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
    if ($highestAction==FALSE) {
        print "<div class='error'>" ;
        print __("The highest grouped action cannot be determined.") ;
        print "</div>" ;
    }
    else {
        $search = $_GET['search'] ?? null;
        $updateReturn = $_GET["updateReturn"] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
            ->add(__('Edit Message'));

        $page->return->addReturns([
            'error4' => __('Your request was completed successfully, but some or all messages could not be delivered.'),
            'error5' => __('Your request failed due to an attachment error.'),
            'success1' => !empty($_GET['notification']) && $_GET['notification'] == 'Y'
                ? __("Your message has been dispatched to a team of highly trained gibbons for delivery: not all messages may arrive at their destination, but an attempt has been made to get them all out. You'll receive a notification once all messages have been sent.")
                : __('Your message has been posted successfully.'),
            'success2' => __('Your message has been saved as a draft. You can continue to edit and preview your message before sending.')
        ]);

        //Check if gibbonMessengerID specified
        $gibbonMessengerID=$_GET["gibbonMessengerID"] ;
        if ($gibbonMessengerID=="") {
            print "<div class='error'>" ;
                print __("You have not specified one or more required parameters.") ;
            print "</div>" ;
        }
        else {
            try {
                if ($highestAction=="Manage Messages_all") {
                    $data=array("gibbonMessengerID"=>$gibbonMessengerID);
                    $sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger LEFT JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID" ;
                }
                else {
                    $data=array("gibbonMessengerID"=>$gibbonMessengerID, "gibbonPersonID"=>$session->get('gibbonPersonID'));
                    $sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger LEFT JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessenger.gibbonPersonID=:gibbonPersonID" ;
                }
                $result=$connection2->prepare($sql);
                $result->execute($data);
            }
            catch(PDOException $e) {
                print "<div class='error'>" . $e->getMessage() . "</div>" ;
            }


            if ($result->rowCount()!=1) {
                print "<div class='error'>" ;
                    print __("The specified record cannot be found.") ;
                print "</div>" ;
            }
            else {
                //Let's go!
                $values=$result->fetch() ;

                // Confidential Check
                if ($values['confidential'] == 'Y' && $session->get('gibbonPersonID') != $values['gibbonPersonID']) {
                    $page->addError(__('You do not have access to this action.'));
                    return;
                }

                $page->addWarning('<b><u>'.__('Note').'</u></b>: '.__('Changes made here do not apply to emails and SMS messages (which have already been sent), but only to message wall messages.'));
                
                $form = $container->get(MessageForm::class)->createForm($gibbonMessengerID);
                echo $form->getOutput();
            }
        }
    }
}
