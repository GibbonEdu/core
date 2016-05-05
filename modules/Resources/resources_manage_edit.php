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

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_manage_edit.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/resources_manage.php'>".__($guid, 'Manage Resources')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Resource').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonResourceID = $_GET['gibbonResourceID'];
        if ($gibbonResourceID == 'Y') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Resources_all') {
                    $data = array('gibbonResourceID' => $gibbonResourceID);
                    $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
                } elseif ($highestAction == 'Manage Resources_my') {
                    $data = array('gibbonResourceID' => $gibbonResourceID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
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
                //Let's go!
                $row = $result->fetch();

                if ($_GET['search'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Resources/resources_manage.php&search='.$_GET['search']."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/resources_manage_editProcess.php?gibbonResourceID=$gibbonResourceID&search=".$_GET['search'] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<input type="hidden" name="type" value="<?php echo $row['type'] ?>">
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'Resource Contents') ?></h3>
							</td>
						</tr>
						<?php
                        if ($row['type'] == 'File') {
                            ?>
							<tr id="resourceFile">
								<td style='width: 275px'> 
									<b><?php echo __($guid, 'File') ?></b><br/>
									<?php if ($row['content'] != '') { ?>
									<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
									<?php } ?>
								</td>
								<td class="right">
									<?php
                                    if ($row['content'] != '') {
                                        echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['content']."'>".$row['content'].'</a><br/><br/>';
                                    }
                            		?>
									<input type="file" name="file" id="file"><br/><br/>
									<script type="text/javascript">
										<?php
                                        //Get list of acceptable file extensions
                                        try {
                                            $dataExt = array();
                                            $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                            $resultExt = $connection2->prepare($sqlExt);
                                            $resultExt->execute($dataExt);
                                        } catch (PDOException $e) {
                                        }
										$ext = '';
										while ($rowExt = $resultExt->fetch()) {
											$ext = $ext."'.".$rowExt['extension']."',";
										}
										?>
										var file=new LiveValidation('file');
										file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
									</script>	
									<?php
                                    echo getMaxUpload($guid);
                            		?>
								</td>
							</tr>
							<?php

                        } elseif ($row['type'] == 'HTML') {
                            ?>
							<tr id="resourceHTML">
								<td colspan=2> 
									<b><?php echo __($guid, 'HTML') ?> *</b>
									<?php echo getEditor($guid,  true, 'html', $row['content'], 20, true, true, false, false) ?>
								</td>
							</tr>
							<?php

                        } elseif ($row['type'] == 'Link') {
                            ?>
							<tr id="resourceLink">
								<td> 
									<b><?php echo __($guid, 'Link') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="link" id="link" maxlength=255 value="<?php echo $row['content'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var link=new LiveValidation('link');
										link.add(Validate.Presence);
										link.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
									</script>	
								</td>
							</tr>
							<?php

                        }
                		?>
						
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'Resource Details') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Name') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input name="name" id="name" maxlength=30 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var name2=new LiveValidation('name');
									name2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<?php
                        try {
                            $dataCategory = array();
                            $sqlCategory = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'";
                            $resultCategory = $connection2->prepare($sqlCategory);
                            $resultCategory->execute($dataCategory);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

						if ($resultCategory->rowCount() == 1) {
							$rowCategory = $resultCategory->fetch();
							$options = $rowCategory['value'];

							if ($options != '') {
								$options = explode(',', $options);
								?>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Category') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select name="category" id="category" class="standardWidth">
											<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
											<?php
                                            for ($i = 0; $i < count($options); ++$i) {
                                                $selected = '';
                                                if ($row['category'] == $options[$i]) {
                                                    $selected = 'selected';
                                                }
                                                ?>
												<option <?php echo $selected ?> value="<?php echo trim($options[$i]) ?>"><?php echo trim($options[$i]) ?></option>
											<?php

                                            }
                        			?>										</select>
										<script type="text/javascript">
											var category=new LiveValidation('category');
											category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<?php

							}
						}

						try {
							$dataPurpose = array();
							$sqlPurpose = "(SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral')";
							if ($highestAction == 'Manage Resources_all') {
								$sqlPurpose .= " UNION (SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted')";
							}
							$resultPurpose = $connection2->prepare($sqlPurpose);
							$resultPurpose->execute($dataPurpose);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}

						if ($resultPurpose->rowCount() > 0) {
							$options = '';
							while ($rowPurpose = $resultPurpose->fetch()) {
								$options .= $rowPurpose['value'].',';
							}
							$options = substr($options, 0, -1);

							if ($options != '') {
								$options = explode(',', $options);
								?>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Purpose') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select name="purpose" id="purpose" class="standardWidth">
											<option value=""></option>
											<?php
                                            for ($i = 0; $i < count($options); ++$i) {
                                                $selected = '';
                                                if ($row['purpose'] == $options[$i]) {
                                                    $selected = 'selected';
                                                }
                                                ?>
												<option <?php echo $selected ?> value="<?php echo trim($options[$i]) ?>"><?php echo trim($options[$i]) ?></option>
											<?php

                                            }
                        				?>
                        				</select>
									</td>
								</tr>
								<?php

								}
							}
							?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Tags') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Use lots of tags!') ?></span>
							</td>
							<td class="right">
								<?php
                                //Get tag list
                                try {
                                    $dataList = array();
                                    $sqlList = 'SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag';
                                    $resultList = $connection2->prepare($sqlList);
                                    $resultList->execute($dataList);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

								$list = '';
								while ($rowList = $resultList->fetch()) {
									$list = $list.'{id: "'.$rowList['tag'].'", name: "'.$rowList['tag'].' <i>('.$rowList['count'].')</i>"},';
								}
								?>
								<style>
									td.right ul.token-input-list-facebook { width: 302px; float: right } 
									td.right div.token-input-dropdown-facebook { width: 120px } 
								</style>
								<input type="text" id="tags" name="tags" class='standardWidth' />
								<?php
                                    $prepopulate = '';
									$tags = explode(',', $row['tags']);
									foreach ($tags as $tag) {
										$prepopulate .= '{id: '.$tag.', name: '.$tag.'}, ';
									}
									$prepopulate = substr($prepopulate, 0, -2);
									?>
								<script type="text/javascript">
									$(document).ready(function() {
										 $("#tags").tokenInput([
												<?php echo substr($list, 0, -1) ?>
											], 
											{theme: "facebook",
											hintText: "Start typing a tag...",
											allowCreation: true,
											<?php
                                            if ($prepopulate != '{id: , name: }') {
                                                echo "prePopulate: [ $prepopulate ],";
                                            }
                						?>
											preventDuplicates: true});
									});
								</script>
								<script type="text/javascript">
									var tags=new LiveValidation('tags');
									tags.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Year Groups') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                echo "<fieldset style='border: none'>"; ?>
								<script type="text/javascript">
									$(function () {
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?php
                                echo __($guid, 'All/None')." <input type='checkbox' class='checkall'><br/>";
								$yearGroups = getYearGroups($connection2);
								if ($yearGroups == '') {
									echo '<i>'.__($guid, 'No year groups available.').'</i>';
								} else {
									$selectedYears = explode(',', $row['gibbonYearGroupIDList']);
									for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
										$checked = '';
										foreach ($selectedYears as $selectedYear) {
											if ($selectedYear == $yearGroups[$i]) {
												$checked = 'checked';
											}
										}

										echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
										echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
									}
								}
								echo '</fieldset>'; ?>
								<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Description') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<textarea name="description" id="description" rows=8 class="standardWidth"><?php echo $row['description'] ?></textarea>
							</td>
						</tr>
						
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
        }
    }
}
?>