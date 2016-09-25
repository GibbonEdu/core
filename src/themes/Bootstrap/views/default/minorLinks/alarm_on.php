<?php
$this->addScript("
<script>
	if ($('div#TB_window').is(':visible')===true && $('div#TB_window').attr('class')!='alarm') {
		$('#TB_window').remove();
		$('body').append('<div id=\'TB_window\'></div>');
	}
	if ($('div#TB_window').is(':visible')===false) {
		var url = '".$this->convertGetArraytoURL(array('q' => '/modules/Notifications/index_notification_ajax_alarm.php', 'divert' => 'true', 'type' => $el->type, 'KeepThis' => 'true', 'TB_iframe'=> 'true', 'width'=>1000, 'height' => 500))."';
		$(document).ready(function() {
			tb_show('', url);
			$('div#TB_window').addClass('alarm') ;
		}) ;
	}
</script>
");


