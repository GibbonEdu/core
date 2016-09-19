<?php
$input = "<div" . ($id = isset($params->element->class) ? ' class="'.$params->element->class . '"' : '' ) . ' id="div_'.$params->id.'" style="float: right; text-align: center; background-color: #'.$params->value.'; width: 298px; min-width: 298px; border: 1px SOLID #'.$params->value.'; ">';
$input .= '<input type="text" name="'.$params->name.'" id="'. ($id = isset($params->id) ? $params->id : $params->name) . '"';
$input .= $id = isset($params->maxLength) ? ' maxlength='.$params->maxLength : '';
$input .= $params->readOnly ? ' readonly' : '' ;
$input .= ' value="'. $params->value. '"';
$input .= $id = isset($params->element->style) ? ' ' . $params->element->style : '' ;
$input .= $id = (isset($params->required) AND $params->required) ? ' required' : "" ; 
$input .= $id = (isset($params->placeholder) AND $params->placeholder) ? ' placeholder="'.$params->placeholder.'"' : "" ;
$input .= ' /></div>';
echo $input;?>
<script>
$(document).ready(function(){
	$("#<?php echo $params->id; ?>").on('change', function(){
		$('#div_<?php echo $params->id; ?>').css("background-color", "#" + $("#<?php echo $params->id; ?>").val());
		$('#div_<?php echo $params->id; ?>').css("border-color", "#" + $("#<?php echo $params->id; ?>").val());
	});
});
</script><!-- form.colour -->