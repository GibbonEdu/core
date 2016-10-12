                        if (! $this->getSecurity()->isActionAccessible('/modules/Attendance/report_studentHistory.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            //Module includes
                            include './modules/Individual Needs/moduleFunctions.php';

                            $statusTable = printINStatusTable($connection2, $guid, $details->personID, 'disabled');
                            if ($statusTable == false) {
                                $this->displayMessage('Your request failed due to a database error.');
                            } else {
                                echo $statusTable;
                            }

                            echo '<h3>';
                            echo trans::__('Individual Education Plan');
                            echo '</h3>';
                            try {
                                $dataIN = array('gibbonPersonID' => $details->personID);
                                $sqlIN = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                                $resultIN = $connection2->prepare($sqlIN);
                                $resultIN->execute($dataIN);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultIN->rowCount() != 1) {
                                $this->displayMessage('There are no records to display.');
                            } else {
                                $rowIN = $resultIN->fetch();

                                echo "<div style='font-weight: bold'>".trans::__('Targets').'</div>';
                                echo '<p>'.$rowIN['targets'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".trans::__('Teaching Strategies').'</div>';
                                echo '<p>'.$rowIN['strategies'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".trans::__('Notes & Review').'s</div>';
                                echo '<p>'.$rowIN['notes'].'</p>';
                            }
                        }
