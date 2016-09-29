<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$("input[name=\''.$el->getField('name').'[schoolDay]\']").on("switchChange.bootstrapSwitch", function(event, state) {
		if (state) {
			$(".schoolDay'.$el->getField('nameShort').'").slideDown("fast", $(".schoolDay'.$el->getField('nameShort').'").css("display","'.$scriptDisplayMode.'")); 
		} else {
			$(".schoolDay'.$el->getField('nameShort').'").css("display","none");
		}
	});
</script>
');
