<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	 $("input[name=\'enableDescriptors\']").on("switchChange.bootstrapSwitch", function(event, state) {
		if (state) {
			$(".showRow").slideDown("fast", $(".showRow").css("display","'.$scriptDisplayMode.'")); 
			$("#negativeRow").slideDown("fast", $("#negativeRow").css("display","'.$scriptDisplayMode.'"));   
			_positiveDescriptors.enable() ;
			_negativeDescriptors.enable() ;
		} else {
			$(".showRow").css("display","none");
			$("#negativeRow").css("display","none");
			_positiveDescriptors.disable() ;
			_negativeDescriptors.disable() ;
		}
	 });
</script>
');
    	
