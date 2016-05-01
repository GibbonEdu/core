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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == false) {
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
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php'>".__($guid, 'Manage Staff')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Staff').'</div>';
        echo '</div>';

        $allStaff = '';
        if (isset($_GET['allStaff'])) {
            $allStaff = $_GET['allStaff'];
        }
        $search = '';
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonStaffID = $_GET['gibbonStaffID'];
        if ($gibbonStaffID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonStaffID' => $gibbonStaffID);
                $sql = 'SELECT gibbonStaff.*, surname, preferredName, initials, dateStart, dateEnd FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();

                if ($search != '' or $allStaff != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }
                echo '<h3>'.__($guid, 'General Information').'</h3>';
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staff_manage_editProcess.php?gibbonStaffID='.$row['gibbonStaffID']."&search=$search&allStaff=$allStaff" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'Basic Information') ?></h3>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Person') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="person" id="person" maxlength=255 value="<?php echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', false, true) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Initials') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Must be unique if set.') ?></span>
							</td>
							<td class="right">
								<input name="initials" id="initials" maxlength=4 value="<?php echo $row['initials'] ?>" type="text" class="standardWidth">
								<?php
                                $idList = '';
                try {
                    $dataSelect = array('initials' => $row['initials']);
                    $sqlSelect = 'SELECT initials FROM gibbonStaff WHERE NOT initials=:initials ORDER BY initials';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $idList .= "'".$rowSelect['initials']."',";
                }
                ?>
								<script type="text/javascript">
									var initials=new LiveValidation('initials');
									initials.add( Validate.Exclusion, { within: [<?php echo $idList;
                ?>], failureMessage: "Initials already in use!", partialMatch: false, caseSensitive: false } );
								</script>
							</td>
						</tr>
					
						<tr>
							<td> 
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="type" id="type" class="standardWidth">
									<?php
                                    echo '<option value="Please select...">'.__($guid, 'Please select...').'</option>';
                echo "<optgroup label='--".__($guid, 'Basic')."--'>";
                $selected = '';
                if ($row['type'] == 'Teaching') {
                    $selected = 'selected';
                }
                echo "<option $selected value=\"Teaching\">".__($guid, 'Teaching').'</option>';
                $selected = '';
                if ($row['type'] == 'Support') {
                    $selected = 'selected';
                }
                echo "<option $selected value=\"Support\">".__($guid, 'Support').'</option>';
                echo '</optgroup>';
                echo "<optgroup label='--".__($guid, 'System Roles')."--'>";
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $selected = '';
                    if ($rowSelect['name'] == $row['type']) {
                        $selected = 'selected';
                    }
                    echo "<option $selected value=\"".$rowSelect['name'].'">'.__($guid, $rowSelect['name']).'</option>';
                }
                echo '</optgroup>';
                ?>
								</select>
								<script type="text/javascript">
									var type=new LiveValidation('type');
									type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Job Title') ?></b><br/>
							</td>
							<td class="right">
								<input name="jobTitle" id="jobTitle" maxlength=100 value="<?php echo htmlPrep($row['jobTitle']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Start Date') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Users\'s first day at school.') ?><br/> <?php echo __($guid, 'Format:').' ';
                if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                    echo 'dd/mm/yyyy';
                } else {
                    echo $_SESSION[$guid]['i18n']['dateFormat'];
                }
                ?></span>
							</td>
							<td class="right">
								<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateStart']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var dateStart=new LiveValidation('dateStart');
									dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
    echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
} else {
    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
}
                ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
                ?>." } ); 
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#dateStart" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'End Date') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Users\'s last day at school.') ?><br/> <?php echo __($guid, 'Format:').' ';
                if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                    echo 'dd/mm/yyyy';
                } else {
                    echo $_SESSION[$guid]['i18n']['dateFormat'];
                }
                ?></span>
							</td>
							<td class="right">
								<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateEnd']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var dateEnd=new LiveValidation('dateEnd');
									dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
    echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
} else {
    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
}
                ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
                ?>." } ); 
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#dateEnd" ).datepicker();
									});
								</script>
							</td>
						</tr>
					
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'First Aid') ?></h3>
							</td>
						</tr>
						<!-- FIELDS & CONTROLS FOR TYPE -->
						<script type="text/javascript">
							$(document).ready(function(){
								$("#firstAidQualified").change(function(){
									if ($('select.firstAidQualified option:selected').val()=="Y" ) {
										$("#firstAidExpiryRow").slideDown("fast", $("#firstAidExpiryRow").css("display","table-row")); 
									} else {
										$("#firstAidExpiryRow").css("display","none");
									} 
								 });
							});
						</script>
						<tr>
							<td> 
								<b><?php echo __($guid, 'First Aid Qualified?') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select class="standardWidth" name="firstAidQualified" id="firstAidQualified" class="firstAidQualified">
									<option <?php if ($row['firstAidQualified'] == '') {
    echo 'selected';
}
                ?> value=""></option>
									<option <?php if ($row['firstAidQualified'] == 'Y') {
    echo 'selected';
}
                ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
									<option <?php if ($row['firstAidQualified'] == 'N') {
    echo 'selected';
}
                ?> value="N"><?php echo __($guid, 'No') ?></option>
								</select>
							</td>
						</tr>
						<tr id='firstAidExpiryRow' <?php if ($row['firstAidQualified'] != 'Y') {
    echo "style='display: none'";
}
                ?>>
							<td> 
								<b><?php echo __($guid, 'First Aid Expiry') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
                if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                    echo 'dd/mm/yyyy';
                } else {
                    echo $_SESSION[$guid]['i18n']['dateFormat'];
                }
                ?></span>
							</td>
							<td class="right">
								<input name="firstAidExpiry" id="firstAidExpiry" maxlength=10 value="<?php echo dateConvertBack($guid, $row['firstAidExpiry']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									$(function() {
										$( "#firstAidExpiry" ).datepicker();
									});
								</script>
							</td>
						</tr>
					
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'Biography') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Country Of Origin') ?></b><br/>
							</td>
							<td class="right">
								<select name="countryOfOrigin" id="countryOfOrigin" class="standardWidth">
									<?php
                                    echo "<option value=''></option>";
                try {
                    $dataSelect = array();
                    $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $selected = '';
                    if ($rowSelect['printable_name'] == $row['countryOfOrigin']) {
                        $selected = 'selected';
                    }
                    echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                }
                ?>				
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Qualifications') ?></b><br/>
							</td>
							<td class="right">
								<input name="qualifications" id="qualifications" maxlength=100 value="<?php echo htmlPrep($row['qualifications']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Grouping') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Used to group staff when creating a staff directory.') ?></span>
							</td>
							<td class="right">
								<input name="biographicalGrouping" id="biographicalGrouping" maxlength=100 value="<?php echo htmlPrep($row['biographicalGrouping']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Grouping Priority') ?></b><br/>
								<span style="font-size: 90%"><?php echo __($guid, '<i>Higher numbers move teachers up the order within their grouping.') ?></span>
							</td>
							<td class="right">
								<input name="biographicalGroupingPriority" id="biographicalGroupingPriority" maxlength=4 value="<?php echo htmlPrep($row['biographicalGroupingPriority']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var biographicalGroupingPriority=new LiveValidation('biographicalGroupingPriority');
									biographicalGroupingPriority.add(Validate.Numericality);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Biography') ?></b><br/>
							</td>
							<td class="right">
								<textarea name='biography' id='biography' rows=10 style='width: 300px'><?php echo htmlPrep($row['biography']) ?></textarea>
							</td>
						</tr>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
                ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Submit');
                ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
                if ($highestAction == 'Manage Staff_confidential') {
                    echo '<h3>'.__($guid, 'Contracts').'</h3>';
                    try {
                        $data = array('gibbonStaffID' => $gibbonStaffID);
                        $sql = 'SELECT * FROM gibbonStaffContract WHERE gibbonStaffID=:gibbonStaffID ORDER BY dateStart DESC';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_contract_add.php&gibbonStaffID=$gibbonStaffID&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</div>';

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Title');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Status').'<br/>';
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Dates');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($row = $result->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $row['title'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['status'];
                            echo '</td>';
                            echo '<td>';
                            if ($row['dateEnd'] == '') {
                                echo dateConvertBack($guid, $row['dateStart']);
                            } else {
                                echo dateConvertBack($guid, $row['dateStart']).' - '.dateConvertBack($guid, $row['dateEnd']);
                            }
                            echo '</td>';
                            echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_manage_edit_contract_edit.php&gibbonStaffContractID='.$row['gibbonStaffContractID']."&gibbonStaffID=$gibbonStaffID&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>