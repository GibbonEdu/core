<tr class='break<?php echo isset($el->row->class) ? ' '.$el->row->class : ''; ?>' >
    <td colspan='2'> 
		<?php $params->titleDetails = isset($params->titleDetails) ? $params->titleDetails : array() ; ?>
        <?php $this->render('default.h3', $params); ?>
        <?php echo isset($params->note) ? '<p>'.Gibbon\core\trans::__($params->note, isset($params->noteDetails) ? $params->noteDetails : array()).'</p>' : NULL ; ?>                                
    </td>
</tr>