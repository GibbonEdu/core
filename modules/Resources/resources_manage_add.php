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

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_manage_add.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/resources_manage.php'>".__($guid, 'Manage Resources')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Resource').'</div>';
        echo '</div>';

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Resources/resources_manage_edit.php&gibbonResourceID='.$_GET['editID'].'&search='.$_GET['search'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, null);
        }

        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Resources/resources_manage.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }
        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/resources_manage_addProcess.php?search=$search" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr class='break'>
					<td colspan=2> 
						<h3><?php echo __($guid, 'Resource Contents') ?></h3>
					</td>
				</tr>
				<script type="text/javascript">
					$(document).ready(function(){
						$("#resourceFile").css("display","none");
						$("#resourceHTML").css("display","none");
						$("#resourceLink").css("display","none");
								
						$("#type").change(function(){
							if ($('select.type option:selected').val()=="Link" ) {
								$("#resourceFile").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceLink").slideDown("fast", $("#resourceLink").css("display","table-row")); 
								link.enable();
								file.disable();
								html.disable();
							} else if ($('select.type option:selected').val()=="File" ) {
								$("#resourceLink").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceFile").slideDown("fast", $("#resourceFile").css("display","table-row")); 
								file.enable();
								link.disable();
								html.disable();
							} else if ($('select.type option:selected').val()=="HTML" ) {
								$("#resourceLink").css("display","none");
								$("#resourceFile").css("display","none");
								$("#resourceHTML").slideDown("fast", $("#resourceHTML").css("display","table-row")); 
								html.enable();
								file.disable();
								link.disable();
							}
							else {
								$("#resourceFile").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceLink").css("display","none");
								file.disable();
								link.disable();
								html.disable();
							}
						 });
					});
				</script>
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'Type') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="type" id="type" class='type standardWidth'>
							<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
							<option id='type' name="type" value="File" /> <?php echo __($guid, 'File') ?>
							<option id='type' name="type" value="HTML" /> <?php echo __($guid, 'HTML') ?>
							<option id='type' name="type" value="Link" /> <?php echo __($guid, 'Link') ?>
						</select>
						<script type="text/javascript">
							var type=new LiveValidation('type');
							type.add(Validate.Inclusion, { within: ['File','HTML','Link'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr id="resourceFile">
					<td> 
						<b><?php echo __($guid, 'File') ?> *</b><br/>
					</td>
					<td class="right">
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
							file.add( Validate.Inclusion, { within: [<?php echo $ext;
        ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							file.add(Validate.Presence);
							file.disable();
						</script>	
						<?php
                        echo getMaxUpload($guid);
        ?>
					</td>
				</tr>
				<tr id="resourceHTML">
					<td colspan=2> 
						<b><?php echo __($guid, 'HTML') ?> *</b>
						<?php echo getEditor($guid,  true, 'html', '', 20, false, false, false, false) ?>
					</td>
				</tr>
				<tr id="resourceLink">
					<td> 
						<b><?php echo __($guid, 'Link') ?> *</b><br/>
					</td>
					<td class="right">
						<input name="link" id="link" maxlength=255 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var link=new LiveValidation('link');
							link.add(Validate.Presence);
							link.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
							link.disable();
						</script>	
					</td>
				</tr>
				
				
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
						<input name="name" id="name" maxlength=60 value="" type="text" class="standardWidth">
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
                                        ?>
										<option value="<?php echo trim($options[$i]) ?>"><?php echo trim($options[$i]) ?></option>
									<?php

                                    }
                ?>
								</select>
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
                                        ?>
										<option value="<?php echo trim($options[$i]) ?>"><?php echo trim($options[$i]) ?></option>
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
						<input type="text" id="tags" name="tags" />
						<script type="text/javascript">
							$(document).ready(function() {
								 $("#tags").tokenInput([
									<?php echo substr($list, 0, -1) ?>
								], 
									{theme: "facebook",
									hintText: "Start typing a tag...",
									allowCreation: true,
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
						<span class="emphasis small">Students year groups which may participate<br/></span>
					</td>
					<td class="right">
						<?php 
                        echo "<fieldset style='border: none'>";
        ?>
						<script type="text/javascript">
							$(function () {
								$('.checkall').click(function () {
									$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
								});
							});
						</script>
						<?php
                        echo __($guid, 'All/None')." <input type='checkbox' class='checkall' checked><br/>";
        $yearGroups = getYearGroups($connection2);
        if ($yearGroups == '') {
            echo '<i>'.__($guid, 'No year groups available.').'</i>';
        } else {
            for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
                $checked = 'checked ';
                echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
                echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
            }
        }
        echo '</fieldset>';
        ?>
						<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Description') ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<textarea name="description" id="description" rows=8 class="standardWidth"></textarea>
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
?>