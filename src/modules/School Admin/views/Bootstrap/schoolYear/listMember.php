				
                <tr>
					<td>
						<?php echo $el->getField('sequenceNumber') ; ?>
					</td>
					<td>
						<?php echo $el->getField('name') ; ?>
					</td>
					<td>
						<?php if ($el->getField('firstDay')!=NULL AND $el->getField('lastDay')!=NULL) {
							print $el->dateConvertBack($el->getField('firstDay')) . " - " . $el->dateConvertBack($el->getField('lastDay')) ;
						} ?>
					</td>
					<td>
						<?php echo $el->getField('status') ; ?>
					</td>
				<?php if (! isset($el->action) || $el->action) { ?>
					<td>
                    	<?php  
							$this->getLink('edit', array('q'=>'/modules/School Admin/schoolYear_manage_edit.php', 'gibbonSchoolYearID'=>$el->getField('gibbonSchoolYearID')));
							$this->getLink('delete', array('q'=>'/modules/School Admin/schoolYear_manage_delete.php', 'gibbonSchoolYearID'=>$el->getField('gibbonSchoolYearID')));
						?>
					</td>
				<?php } ?>
				</tr>
