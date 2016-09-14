<script>
	if ($('div#TB_window').is(':visible')===true && $('div#TB_window').attr('class')!='alarm') {
		$("#TB_window").remove();
		$("body").append("<div id='TB_window'></div>");
	}
	if ($('div#TB_window').is(':visible')===false) {
		var url = '<?php echo GIBBON_URL; ?>index.php?q=/modules/Notifications/index_notification_ajax_alarm.php?type=<?php echo $el->type; ?>&KeepThis=true&TB_iframe=true&width=1000&height=500&divert=true';
		tb_show('', url);
		$('div#TB_window').addClass('alarm') ;
	}
</script>
