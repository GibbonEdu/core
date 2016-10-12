                        if (! $this->getSecurity()->isActionAccessible('/modules/Attendance/report_studentHistory.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            include './modules/Attendance/moduleFunctions.php';
                            report_studentHistory($guid, $details->personID, true, $_SESSION['absoluteURL']."/report.php?q=/modules/Attendance/report_studentHistory_print.php&gibbonPersonID=$details->personID", $connection2, $details->student->getField('dateStart'), $details->student->getField('dateEnd'));
                        }
