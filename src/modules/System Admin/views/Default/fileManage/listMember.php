				<tr class="<?php echo $params->rowNum; ?>">
					<td>
						<?php echo $params->name.'.yml' ; ?>
					</td>
					<td>
						<?php echo $params->status ; ?>
					</td>
					<td>
						<?php if (substr($params->status, 0, 6) === 'Update') new Gibbon\Form\checkbox('update-'.$params->name, $params->name, $this);
						elseif ($params->status == 'Unknown') echo '?';
						else echo 'Ok'; ?>
					</td>
				</tr>