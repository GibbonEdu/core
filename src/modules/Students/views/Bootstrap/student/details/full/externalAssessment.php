                        if (! $this->getSecurity()->isActionAccessible('/modules/Formal Assessment/externalAssessment_details.php') && ! $this->getSecurity()->isActionAccessible('/modules/Formal Assessment/externalAssessment_view.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            //Module includes
                            include './modules/Formal Assessment/moduleFunctions.php';

                            //Print assessments
                            $gibbonYearGroupID = '';
                            if (! empty($details->student->getField('gibbonYearGroupID'))) {
                                $gibbonYearGroupID = $details->student->getField('gibbonYearGroupID');
                            }
                            externalAssessmentDetails($guid, $details->personID, $connection2, $gibbonYearGroupID);
                        }
