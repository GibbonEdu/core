			<tr>
				<td colspan="2"> 
                    <?php $params->titleDetails = isset($params->titleDetails) ? $params->titleDetails : array() ; ?>
                   	<?php $this->render('default.h4', $params); ?>
        			<?php echo isset($params->note) ? '<p>'.$this->__($params->note, isset($params->noteDetails) ? $params->noteDetails : array()).'</p>' : null ; ?>                                
				</td>
			</tr>
