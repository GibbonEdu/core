<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		 $("#_enableLevels").click(function(){
			if ($("#_enableLevels option:selected").val()=="Y" ) {
				$(".levelsRow").slideDown("fast", $(".levelsRow").css("display","'.$scriptDisplayMode.'")); 
				_levels.enable() ; 

			} else {
				$(".levelsRow").css("display","none");
				_levels.disable() ; 
			}
		 });
	});
</script>
	');
