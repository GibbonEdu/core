<?php
$scriptDisplayMode = $this->session->get('theme.settings.script.display');

$this->addScript('
<script type="text/javascript">
	$("input[name=\'enableBehaviourLetters\']").on("switchChange.bootstrapSwitch", function(event, state) {
		if (state) {
			$(".behaviourLetters").slideDown("fast", $(".behaviourLetters").css("display","'.$scriptDisplayMode.'"));  
			_behaviourLettersLetter1Count.enable() ;
			_behaviourLettersLetter1Text.enable() ; 
			_behaviourLettersLetter2Count.enable() ;
			_behaviourLettersLetter2Text.enable() ; 
			_behaviourLettersLetter3Count.enable() ;
			_behaviourLettersLetter3Text.enable() ;
		} else {
			$(".behaviourLetters").css("display","none");
			_behaviourLettersLetter1Count.disable() ;
			_behaviourLettersLetter1Text.disable() ;
			_behaviourLettersLetter2Count.disable() ;
			_behaviourLettersLetter2Text.disable() ;
			_behaviourLettersLetter3Count.disable() ;
			_behaviourLettersLetter3Text.disable() ;
		}
	});
</script>
');
