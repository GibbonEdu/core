                <tr>
					<td>
						<?php echo $el->extension ; ?>
					</td>
					<td>
						<?php echo $this->__($el->name); ?>
					</td>
					<td>
						<?php echo $this->__($el->type); ?>
					</td>
				<?php if ((bool) $el->action) { ?>
					<td>
					<?php  
                        $this->getLink('edit', array('q'=>'/modules/School Admin/fileExtensions_manage_edit.php', 'gibbonFileExtensionID'=>$el->gibbonFileExtensionID, 'page'=>$el->page));
                        $this->getLink('delete', array('q'=>'/modules/School Admin/fileExtensions_manage_delete.php', 'gibbonFileExtensionID'=>$el->gibbonFileExtensionID, 'page'=>$el->page));
                  	?>
					</td>
				<?php } ?>
				</tr>