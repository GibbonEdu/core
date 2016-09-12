<!-- home.default.style -->
<?php 
if ($this->session->notEmpty('theme.settings') && $this->session->notEmpty('theme.settings.css'))
{
	foreach($this->session->get('theme.settings.css') as $css)
	{
		?>
		<link rel="stylesheet" href="<?php echo GIBBON_URL . str_replace(array('{{themeName}}'), array($this->session->get("gibbonThemeName")), $css); ?>" type="text/css" media="screen" />
		<?php
	}
}
	
//Set module CSS & JS
if ($this->session->notEmpty('module')) {
	$moduleCSS = '';
	$moduleJS = '';
	if (file_exists(GIBBON_ROOT . 'src/modules/'.$this->session->get('module').'/css/' . $this->session->get("gibbonThemeName") . '/module.css'))
		$moduleCSS = "<link rel='stylesheet' type='text/css' href='".GIBBON_URL."src/modules/".$this->session->get('module').'/css/' . $this->session->get("gibbonThemeName") . "/module.css' />\n" ;
	elseif (file_exists(GIBBON_ROOT . 'src/modules/'.$this->session->get('module').'/css/module.css'))
		$moduleCSS = "<link rel='stylesheet' type='text/css' href='".GIBBON_URL."src/modules/".$this->session->get('module')."/css/module.css' />\n" ;
	if (file_exists(GIBBON_ROOT . 'src/modules/'.$this->session->get('module').'/js/' . $this->session->get("gibbonThemeName") . '/common.js'))
		$moduleJS = "<link rel='stylesheet' type='text/css' href='".GIBBON_URL."src/modules/'.$this->session->get('module').'/js/" . $this->session->get("gibbonThemeName") . "/common.js' />\n" ;
	elseif (file_exists(GIBBON_ROOT . 'src/modules/'.$this->session->get('module').'/js/common.js'))
		$moduleJS = "<link rel='stylesheet' type='text/css' href='".GIBBON_URL."src/modules/".$this->session->get('module')."/js/common.js' />\n" ;
	echo $moduleCSS  ;
	echo $moduleJS ;
}

//Set personalised background, if permitted
if ($personalBackground = $this->config->getSettingByScope("User Admin", "personalBackground")=="Y" && $this->session->notEmpty("personalBackground")) { ?>
	<style type="text/css">
		body {
			background: url("<?php echo $this->session->get("personalBackground"); ?>") repeat scroll center top #A88EDB!important;
		}
	</style><?php
} ?>
<style>
	div.mce-listbox button, div.mce-menubtn button { padding-top: 2px!important ; padding-bottom: 2px!important }
</style>
