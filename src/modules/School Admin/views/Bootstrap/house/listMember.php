			<tr>
				<td>
					<?php if (! empty($el->logo)  && is_file(GIBBON_ROOT.$el->logo)) { ?>
						<img class='user' style='max-width: 150px' src='<?php echo GIBBON_URL.$el->logo; ?>'/>
					<?php }
					else { ?>
						<img class='user' style='max-width: 150px' src='<?php echo GIBBON_URL; ?>themes/<?php echo $this->session->get("theme.Name"); ?>/img/anonymous_240_square.jpg'/>
					<?php } ?>
				</td>
				<td>
					<?php echo $el->name ; ?>
				</td>
				<td>
					<?php echo $el->nameShort ; ?>
				</td>
				<?php if (! isset($el->action) || (bool)$el->action) { ?>
				<td>
				<?php  
                    $this->getLink('edit', array('q' => '/modules/School Admin/house_manage_edit.php', 'gibbonHouseID'=>$el->gibbonHouseID));
                    $this->getLink('delete', array('q' => '/modules/School Admin/house_manage_delete.php', 'gibbonHouseID'=>$el->gibbonHouseID));
                ?>
				</td>
				<?php } ?>
			</tr>
