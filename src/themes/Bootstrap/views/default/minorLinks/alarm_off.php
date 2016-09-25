<?php
$this->addScript("
<script>
	if ($('div#TB_window').is(':visible')===true && $('div#TB_window').attr('class')=='alarm') {
		tb_remove();
	}
</script>
");
