<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		 $("#_enableDescriptors").click(function(){
			if ($("#_enableDescriptors option:selected").val()=="Y" ) {
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
	});
</script>
	');
    	
