					<tr class='<?php echo $el->rowNum; ?>'>
						<td>
							<?php echo Gibbon\core\trans::__($el->moduleObj->getField('name')) ; ?>
						</td>
						<td>
                        	<?php echo $this->getLink('delete', array('q'=>'/modules/System Admin/module_manage_uninstall.php', 'gibbonModuleID'=>$el->moduleObj->getField('gibbonModuleID'), 'orphaned'=>true)); ?>
						</td>
					</tr>