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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/trackingSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Tracking Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/trackingSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<?php
            $yearGroups = getYearGroups($connection2);
			if ($yearGroups == '') {
				echo "<tr class='break'>";
				echo '<td colspan=2>';
				echo "<div class='error'>";
				echo __($guid, 'There are no records to display.');
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			} else {
        	?>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Data Points').' - '.__($guid, 'External Assessment') ?></h3>
					<?php echo __($guid, 'Use the options below to select the external assessments that you wish to include in your Data Points export.').' '.__($guid, 'If duplicates of any assessment exist, only the most recent entry will be shown.') /*. " " . __($guid, 'Year 13 settings will be applied to recent grauates, who will be shown in the Last Graduating Cohort tab in the export.')*/ ;
        		?>
				</td>
			</tr>
			<?php
			try {
				$data = array();
				$sql = "SELECT DISTINCT gibbonExternalAssessment.gibbonExternalAssessmentID, gibbonExternalAssessment.nameShort, gibbonExternalAssessmentField.category FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE active='Y' ORDER BY nameShort, category";
				$result = $connection2->prepare($sql);
				$result->execute($data);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}
			$count = 0;
			if ($result->rowCount() < 1) {
				echo "<tr class='break'>";
				echo '<td colspan=2>';
				echo "<div class='error'>";
				echo __($guid, 'There are no records to display.');
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			} else {
				$externalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'externalAssessmentDataPoints'));
				$externalAssessmentDataPoints = is_array($externalAssessmentDataPoints) ? $externalAssessmentDataPoints : array() ;
				while ($row = $result->fetch()) {
					?>
						<tr>
							<td>
								<b><?php echo __($guid, $row['nameShort']).' - '.__($guid, substr($row['category'], (strpos($row['category'], '_') + 1))) ?></b><br/>
							</td>
							<td class="right">
								<?php
								for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
									$checked = '';
									foreach ($externalAssessmentDataPoints as $externalAssessmentDataPoint) {
										if ($externalAssessmentDataPoint['gibbonExternalAssessmentID'] == $row['gibbonExternalAssessmentID'] and $externalAssessmentDataPoint['category'] == $row['category']) {
											if (isset($externalAssessmentDataPoint['gibbonYearGroupIDList'])) {
												if (!(strpos($externalAssessmentDataPoint['gibbonYearGroupIDList'], $yearGroups[$i]) === false)) {
													$checked = 'checked';
												}
											}
										}
									}
									echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='external_gibbonExternalAssessmentID_".$count.'_gibbonYearGroupID_'.($i) / 2 ."' value='".$yearGroups[$i]."'><br/>";
								}
								echo "<input type='hidden' name='external_gibbonExternalAssessmentID_".$count."' value='".$row['gibbonExternalAssessmentID']."'/>";
								echo "<input type='hidden' name='external_category_".$count."' value='".$row['category']."'/>"; ?>
						</td>
					</tr>
					<?php
					++$count;
				}
			}
			echo "<input type='hidden' name='external_gibbonExternalAssessmentID_count' value='".$count."'/>";
			echo "<input type='hidden' name='external_year_count' value='".count($yearGroups) / 2 ."'/>";
			?>

			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Data Points').' - '.__($guid, 'Interal Assessment') ?></h3>
					<?php echo __($guid, 'Use the options below to select the internal assessments that you wish to include in your Data Points export.').' '.__($guid, 'If duplicates of any assessment exist, only the most recent entry will be shown.') /*. " " . __($guid, 'Year 13 settings will be applied to recent grauates, who will be shown in the Last Graduating Cohort tab in the export.')*/ ; ?>
					</td>
				</tr>
				<?php
				$count = 0;
				?>
				<tr>
					<?php
                    $internalAssessmentTypes = explode(',', getSettingByScope($connection2, 'Formal Assessment', 'internalAssessmentTypes'));
					$internalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'internalAssessmentDataPoints'));
					foreach ($internalAssessmentTypes as $internalAssessmentType) {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, $internalAssessmentType) ?></b>
							</td>
							<td class="right">
								<?php
								for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
									$checked = '';
									foreach ($internalAssessmentDataPoints as $internalAssessmentDataPoint) {
										if ($internalAssessmentDataPoint['type'] == $internalAssessmentType) {
											if (!(strpos($internalAssessmentDataPoint['gibbonYearGroupIDList'], $yearGroups[$i]) === false)) {
												$checked = 'checked';
											}
										}
									}
									echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='internal_type_".$count.'_gibbonYearGroupID_'.($i) / 2 ."' value='".$yearGroups[$i]."'><br/>";
								}
								echo "<input type='hidden' name='internal_type_".$count."' value='$internalAssessmentType'/>"; ?>
							</td>
						</tr>
						<?php
						++$count;
					}
				?>
				</tr>
				<?php
			}
			echo "<input type='hidden' name='internal_type_count' value='".$count."'/>";
			echo "<input type='hidden' name='internal_year_count' value='".count($yearGroups) / 2 ."'/>"; ?>

			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
<?php

}
?>
