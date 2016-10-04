<?php
$input = "<div" . ($id = isset($el->element->class) ? ' class="'.$el->element->class . '"' : '' ) . ' id="div_'.$el->id.'" style="float: right; text-align: center; background-color: rgba('.$el->value.'); width: 298px; min-width: 298px; border: 1px SOLID rgba('.$el->value.'); ">';
$input .= '<input type="text" name="'.$el->name.'" id="'. ($id = isset($el->id) ? $el->id : $el->name) . '"';
$input .= $id = isset($el->maxLength) ? ' maxlength='.$el->maxLength : '';
$input .= $el->readOnly ? ' readonly' : '' ;
$input .= ' value="'. $el->value. '"';
$input .= $id = isset($el->element->style) ? ' ' . $el->element->style : '' ;
$input .= $id = (isset($el->required) AND $el->required) ? ' required' : "" ; 
$input .= $id = (isset($el->placeholder) AND $el->placeholder) ? ' placeholder="'.$el->placeholder.'"' : "" ;
$input .= ' /></div>';
echo $input;$this->addScript('
<script type="text/javascript">
$(document).ready(function(){
	$("#'.$el->id.'").on("change", function(){
		$("#div_'.$el->id.'").css("background-color", "rgba(" + $("#'.$el->id.'").val() + ")");
		$("#div_'.$el->id.'").css("border-color", "rgba(" + $("#'.$el->id.'").val() + ")");
	});
});
</script>
');
