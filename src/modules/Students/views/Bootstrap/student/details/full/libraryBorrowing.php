                        if (! $this->getSecurity()->isActionAccessible('/modules/Library/report_studentBorrowingRecord.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            include './modules/Library/moduleFunctions.php';

                            //Print borrowing record
                            $output = getBorrowingRecord($guid, $connection2, $details->personID);
                            if ($output == false) {
                                $this->displayMessage('Your request failed due to a database error.');
                            } else {
                                echo $output;
                            }
                        }
