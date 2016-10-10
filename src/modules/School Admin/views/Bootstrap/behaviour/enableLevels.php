<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$("input[name=\'enableLevels\']").on("switchChange.bootstrapSwitch", function(event, state) {
		if (state) {
			$(".levelsRow").slideDown("fast", $(".levelsRow").css("display","'.$scriptDisplayMode.'")); 
			_levels.enable() ; 
		} else {
			$(".levelsRow").css("display","none");
			_levels.disable() ; 
		}
	});
</script>
');
