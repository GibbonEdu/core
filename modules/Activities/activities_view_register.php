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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_register.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_view.php'>View Activities</a> > </div><div class='trailEnd'>".__($guid, 'Activity Registration').'</div>';
        echo '</div>';

        if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_register') == false) {
            //Acess denied
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Get current role category
            $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

            //Check access controls
            $access = getSettingByScope($connection2, 'Activities', 'access');

            $gibbonPersonID = $_GET['gibbonPersonID'];

            if ($access != 'Register') {
                echo "<div class='error'>";
                echo __($guid, 'Registration is closed, or you do not have permission to register.');
                echo '</div>';
            } else {
                //Check if school year specified
                $gibbonActivityID = $_GET['gibbonActivityID'];
                if ($gibbonActivityID == 'Y') {
                    echo "<div class='error'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    $mode = $_GET['mode'];

                    if ($_GET['search'] != '' or $gibbonPersonID != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_view.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    //Check Access
                    $continue = false;
                    //Student
                    if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                        try {
                            $dataStudent = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                            $resultStudent = $connection2->prepare($sqlStudent);
                            $resultStudent->execute($dataStudent);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultStudent->rowCount() == 1) {
                            $rowStudent = $resultStudent->fetch();
                            $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                            if ($gibbonYearGroupID != '') {
                                $continue = true;
                                $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                            }
                        }
                    }
                    //Parent
                    elseif ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '') {
                        try {
                            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'Access denied.');
                            echo '</div>';
                        } else {
                            $countChild = 0;
                            while ($row = $result->fetch()) {
                                try {
                                    $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName ";
                                    $resultChild = $connection2->prepare($sqlChild);
                                    $resultChild->execute($dataChild);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                while ($rowChild = $resultChild->fetch()) {
                                    ++$countChild;
                                    $gibbonYearGroupID = $rowChild['gibbonYearGroupID'];
                                }
                            }

                            if ($countChild > 0) {
                                if ($gibbonYearGroupID != '') {
                                    $continue = true;
                                    $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                                }
                            }
                        }
                    }

                    if ($mode == 'register') {
                        if ($continue == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed due to a database error.');
                            echo '</div>';
                        } else {
                            $today = date('Y-m-d');

                            //Should we show date as term or date?
                            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                            if ($dateType == 'Term') {
                                $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                            }

                            try {
                                if ($dateType != 'Date') {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
                                    $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND gibbonActivityID=:gibbonActivityID AND registration='Y' $and";
                                } else {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                    $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND gibbonActivityID=:gibbonActivityID AND registration='Y' $and";
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($result->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            } else {
                                $row = $result->fetch();

                                //Check for existing registration
                                try {
                                    $dataReg = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sqlReg = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                    $resultReg = $connection2->prepare($sqlReg);
                                    $resultReg->execute($dataReg);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultReg->rowCount() > 0) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'You are already registered for this activity and so cannot register again.');
                                    echo '</div>';
                                } else {
                                    if (isset($_GET['return'])) {
                                        returnProcess($guid, $_GET['return'], null, array('error3' => 'Registration failed because you are already registered in this activity.'));
                                    }

                                    //Check registration limit...
                                    $proceed = true;
                                    if ($dateType == 'Term' and $maxPerTerm > 0) {
                                        $termsList = explode(',', $row['gibbonSchoolYearTermIDList']);
                                        foreach ($termsList as $term) {
                                            try {
                                                $dataActivityCount = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$term.'%');
                                                $sqlActivityCount = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                                                $resultActivityCount = $connection2->prepare($sqlActivityCount);
                                                $resultActivityCount->execute($dataActivityCount);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultActivityCount->rowCount() >= $maxPerTerm) {
                                                $proceed = false;
                                            }
                                        }
                                    }

                                    if ($proceed == false) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'You have subscribed for the maximum number of activities in a term, and so cannot register for this activity.');
                                        echo '</div>';
                                    } else {
                                        ?>
										<p>
											<?php
                                            if (getSettingByScope($connection2, 'Activities', 'enrolmentType') == 'Selection') {
                                                echo __($guid, 'After you press the Register button below, your application will be considered by a member of staff who will decide whether or not there is space for you in this program.');
                                            } else {
                                                echo __($guid, 'If there is space on this program you will be accepted immediately upon pressing the Register button below. If there is not, then you will be placed on a waiting list.');
                                            }
                                        ?>
										</p>
										<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_view_registerProcess.php?search='.$_GET['search'] ?>">
											<table class='smallIntBorder fullWidth' cellspacing='0'>	
												<tr>
													<td style='width: 275px'> 
														<b><?php echo __($guid, 'Activity') ?></b><br/>
													</td>
													<td class="right">
														<input readonly name="name" id="name" maxlength=40 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
													</td>
												</tr>
												<?php
                                                if ($dateType != 'Date') {
                                                    ?>
													<tr>
														<td> 
															<b><?php echo __($guid, 'Terms') ?></b><br/>
														</td>
														<td class="right">
															<?php
                                                            $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                                                    $termList = '';
                                                    for ($i = 0; $i < count($terms); $i = $i + 2) {
                                                        if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                                            $termList .= $terms[($i + 1)].', ';
                                                        }
                                                    }
                                                    ?>
															<input readonly name="terms" id="terms" maxlength=10 value="<?php echo substr($termList, 0, -2) ?>" type="text" class="standardWidth">
														</td>
													</tr>
													<?php

                                                } else {
                                                    ?>
													<tr>
														<td> 
															<b><?php echo __($guid, 'Program Start Date') ?></b><br/>
														</td>
														<td class="right">
															<input readonly name="programStart" id="programStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['programStart']) ?>" type="text" class="standardWidth">
														</td>
													</tr>
													<tr>
														<td> 
															<b><?php echo __($guid, 'Program End Date') ?></b><br/>
														</td>
														<td class="right">
															<input readonly name="programEnd" id="programEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $row['programEnd']) ?>" type="text" class="standardWidth">
														</td>
													</tr>
													<?php
													}
													?>
												<tr>
													<td> 
														<b><?php echo __($guid, 'Cost') ?></b><br/>
														<span class="emphasis small"><?php echo __($guid, 'For entire programme').'. '.$_SESSION[$guid]['currency'].'.' ?><br/></span>
													</td>
													<td class="right">
														<?php
                                                            if (getSettingByScope($connection2, 'Activities', 'payment') != 'None' and getSettingByScope($connection2, 'Activities', 'payment') != 'Single') {
                                                                ?>
																<input readonly name="payment" id="payment" maxlength=7 value="<?php if (substr($_SESSION[$guid]['currency'], 4) != '') { echo substr($_SESSION[$guid]['currency'], 4); } echo $row['payment']; ?>" type="text" class="standardWidth">
																<?php
																}
                                        						?>
													</td>
												</tr>
												
												<?php
                                                if (getSettingByScope($connection2, 'Activities', 'backupChoice') == 'Y') {
                                                    ?>
													<tr>
														<td> 
															<b><?php echo __($guid, 'Backup Choice') ?> * </b><br/>
															<span class="emphasis small"><?php echo sprintf(__($guid, 'Incase %1$s is full.'), $row['name']) ?><br/></span>
														</td>
														<td class="right">
															<select name="gibbonActivityIDBackup" id="gibbonActivityIDBackup" class="standardWidth">
																<?php
                                                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';

                                                    try {
                                                        if ($dateType != 'Date') {
                                                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID);
                                                            $sqlSelect = "SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and ORDER BY name";
                                                        } else {
                                                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                                            $sqlSelect = "SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and ORDER BY name";
                                                        }
                                                        $resultSelect = $connection2->prepare($sqlSelect);
                                                        $resultSelect->execute($dataSelect);
                                                    } catch (PDOException $e) {
                                                    }

                                                    while ($rowSelect = $resultSelect->fetch()) {
                                                        echo "<option value='".$rowSelect['gibbonActivityID']."'>".htmlPrep($rowSelect['name']).'</option>';
                                                    }
                                                    ?>				
															</select>
															<script type="text/javascript">
																var gibbonActivityIDBackup=new LiveValidation('gibbonActivityIDBackup');
																gibbonActivityIDBackup.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
															</script>
														</td>
													</tr>
													<?php
													}
													?>
												<tr>
													<td>
														<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');?></span>
													</td>
													<td class="right">
														<input type="hidden" name="mode" value="<?php echo $mode ?>">
														<input type="hidden" name="gibbonPersonID" value="<?php echo $gibbonPersonID ?>">
														<input type="hidden" name="gibbonActivityID" value="<?php echo $gibbonActivityID ?>">
														<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
														<input style='width: 75px' type="submit" value="Register">
													</td>
												</tr>
											</table>
										</form>
										<?php

                                    }
                                }
                            }
                        }
                    } elseif ($mode = 'unregister') {
                        if ($continue == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed due to a database error.');
                            echo '</div>';
                        } else {
                            $today = date('Y-m-d');

                            //Should we show date as term or date?
                            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

                            try {
                                if ($dateType != 'Date') {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID);
                                    $sql = "SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and";
                                } else {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                    $sql = "SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and";
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($result->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            } else {
                                $row = $result->fetch();

                                //Check for existing registration
                                try {
                                    $dataReg = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlReg = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                    $resultReg = $connection2->prepare($sqlReg);
                                    $resultReg->execute($dataReg);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultReg->rowCount() < 1) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'You are not currently registered for this activity and so cannot unregister.');
                                    echo '</div>';
                                } else {
                                    if (isset($_GET['return'])) {
                                        returnProcess($guid, $_GET['return'], null, null);
                                    }

                                    ?>
									<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_view_registerProcess.php?search='.$_GET['search'] ?>">
										<table cellspacing='0' style="width: 100%">	
											<tr>
												<td> 
													<b><?php echo sprintf(__($guid, 'Are you sure you want to unregister from activity "%1$s"? If you try to reregister later you may lose a space already assigned to you.'), $row['name']) ?></b><br/>
												</td>
											</tr>
											<tr>
												<td class="right" colspan=2>
													<input type="hidden" name="mode" value="<?php echo $mode ?>">
													<input type="hidden" name="gibbonPersonID" value="<?php echo $gibbonPersonID ?>">
													<input type="hidden" name="gibbonActivityID" value="<?php echo $gibbonActivityID ?>">
													<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
													<input style='width: 75px' type="submit" value="Unregister">
												</td>
											</tr>
										</table>
									</form>
									<?php
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>