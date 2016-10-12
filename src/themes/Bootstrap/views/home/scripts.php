<?php
if ($this->session->notEmpty('theme.settings') && $this->session->notEmpty('theme.settings.js'))
{
	foreach($this->session->get('theme.settings.js') as $js)
	{
		?>
		<script type="text/javascript" src="<?php echo rtrim(GIBBON_URL, '/') . str_replace(array('{{themeName}}'), array($this->session->get("theme.Name")), $js); ?>"></script>
		<?php
	}
}
if ($this->session->notEmpty("i18n.code") && $this->session->get('theme.settings.datepicker')) {
	if (is_file(GIBBON_ROOT . "lib/jquery-ui/i18n/jquery.ui.datepicker-" . substr($this->session->get("i18n.code"),0,2) . ".js")) {
		?><script type='text/javascript' src='<?php echo GIBBON_URL; ?>lib/jquery-ui/i18n/jquery.ui.datepicker-<?php echo substr($this->session->get("i18n.code"),0,2); ?>.js'></script>
		<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['<?php echo substr($this->session->get("i18n.code"),0,2); ?>']);</script><?php
	}
	else if (is_file(GIBBON_ROOT . "lib/jquery-ui/i18n/jquery.ui.datepicker-" . str_replace("_","-",$this->session->get("i18n.code")) . ".js")) {
		?><script type='text/javascript' src='<?php echo GIBBON_URL; ?>lib/jquery-ui/i18n/jquery.ui.datepicker-<?php echo str_replace("_","-",$this->session->get("i18n.code")); ?>.js'></script>
		<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['<?php echo str_replace("_","-",$this->session->get("i18n.code")); ?>']);</script><?php
	}
}

$this->addScript('
<script type="text/javascript">$(function () { $(".latex").latex();});</script>
');
?>
<script type="text/javascript"> var tb_pathToImage="<?php echo GIBBON_URL; ?>lib/thickbox/loadingAnimation.gif"</script>
<?php
if ($this->session->notEmpty("username")) {
	$sessionDuration = $this->config->getSettingByScope("System", "sessionDuration") ;
	if (! is_numeric($sessionDuration)) {
		$sessionDuration = 1200 ;
	}
	if ($sessionDuration < 1200)
		$sessionDuration = 1200 ;
	?>
<script type='text/javascript'>
	// Keep all submit buttons from working
	$('input:submit').live('click', function () {
		return false;
	});

	// After everything loads, remove the restriction on submit buttons.
	$(window).load(function(){
		$('input:submit').die();
	});
</script>

    <?php
	$this->addScript("
<script type='text/javascript'>
	$(document).ready(function(){
		$.sessionTimeout({
			message: '".$this->__("Your session is about to expire: you will be logged out shortly.")."',
			keepAliveUrl: 'index.php?q=/modules/Security/keepAlive.php&divert=true' ,
			redirUrl: '".GIBBON_URL."index.php?q=/modules/Security/logout.php&timeout=true&divert=true', 
			logoutUrl: '".GIBBON_URL."index.php?q=/modules/Security/logout.php&timeout=true&divert=true' , 
			warnAfter: ".($sessionDuration * 1000).",
			redirAfter: ".(($sessionDuration * 1000) + 600000)."
		});
	});
</script>
");
}
