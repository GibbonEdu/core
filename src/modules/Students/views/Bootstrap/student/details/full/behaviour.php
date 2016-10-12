                        if (! $this->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_view.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            include './modules/Behaviour/moduleFunctions.php';

                            //Print assessments
                            getBehaviourRecord($guid, $details->personID, $connection2);
                        }
