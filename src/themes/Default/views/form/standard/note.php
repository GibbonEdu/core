<tr<?php echo isset($params->row->style) ? ' style="'.$params->row->style . '"' : '' ; ?><?php echo isset($params->row->class) ? ' class="'.$params->row->class . '"' : '' ; ?><?php echo isset($el->row->id) ? ' id="'.$el->row->id . '"' : '' ; ?>>
    <td<?php echo isset($params->col2->style) ? ' style="'.$params->col2->style . '"' : '' ; ?><?php echo isset($params->col2->class) ? ' class="'.$params->col2->class . '"' : '' ; ?> colspan='2'>
    	<?php echo  isset($el->title) ? '<strong>'.Gibbon\core\trans::__($el->title, $el->titleParameters).'</strong><br />' : '' ;?>
    	<span<?php echo isset($params->span->style) ? ' style="'.$params->span->style . '"' : '' ; ?><?php echo isset($params->span->class) ? ' class="'.$params->span->class . '"' : '' ; ?>><?php echo  isset($el->value) ? Gibbon\core\trans::__($el->value, $el->valueParameters) : '' ;?></span>
    </td>
</tr><!-- form.standard.note -->