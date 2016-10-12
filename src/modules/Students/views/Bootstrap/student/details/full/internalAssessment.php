                        if (! $this->getSecurity()->isActionAccessible('/modules/Formal Assessment/internalAssessment_view.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            $details->highestAction = getHighestGroupedAction($guid, '/modules/Formal Assessment/internalAssessment_view.php', $connection2);
                            if ($details->highestAction == false) {
                                $this->displayMessage('The highest grouped action cannot be determined.');
                            } else {
                                //Module includes
                                include './modules/Formal Assessment/moduleFunctions.php';

                                if ($details->highestAction == 'View Internal Assessments_all') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $details->personID);
                                } elseif ($details->highestAction == 'View Internal Assessments_myChildrens') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $details->personID, 'parent');
                                }
                            }
                        }
