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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearTerm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/schoolYearTerm_manage.php'>".__($guid, 'Manage Terms')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Term').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'];
    if ($gibbonSchoolYearTermID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
            $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
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
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/schoolYearTerm_manage_editProcess.php?gibbonSchoolYearTermID=$gibbonSchoolYearTermID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
            try {
                $dataSelect = array();
                $sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                if ($rowSelect['gibbonSchoolYearID'] == $row['gibbonSchoolYearID']) {
                    echo "<option selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
                } else {
                    echo "<option value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }
            }
            ?>				
							</select>
							<script type="text/javascript">
								var gibbonSchoolYearTermID=new LiveValidation('gibbonSchoolYearTermID');
								gibbonSchoolYearTermID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=3 value="<?php echo $row['sequenceNumber'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Numericality);
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=20 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="<?php echo htmlPrep($row['nameShort']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'First Day') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="firstDay" id="firstDay" maxlength=10 value="<?php echo dateConvertBack($guid, $row['firstDay']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var firstDay=new LiveValidation('firstDay');
								firstDay.add(Validate.Presence);
								firstDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#firstDay" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Last Day') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="lastDay" id="lastDay" maxlength=10 value="<?php echo dateConvertBack($guid, $row['lastDay']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var lastDay=new LiveValidation('lastDay');
								lastDay.add(Validate.Presence);
								lastDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#lastDay" ).datepicker();
								});
							</script>
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

        }
    }
}
?>