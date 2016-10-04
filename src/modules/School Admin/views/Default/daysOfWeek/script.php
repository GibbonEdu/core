<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		 $("#_'.$el->getField('name').'_schoolDay").click(function(){
			if ($("#_'.$el->getField('name').'_schoolDay option:selected").val()=="Y" ) {
				$(".schoolDay'.$el->getField('nameShort').'").slideDown("fast", $(".schoolDay'.$el->getField('nameShort').'").css("display","'.$scriptDisplayMode.'")); 
			} else {
				$(".schoolDay'.$el->getField('nameShort').'").css("display","none");
			}
		 });
	});
</script>
');
