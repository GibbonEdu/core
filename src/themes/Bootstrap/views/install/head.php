	<head>
		<title>
			Gibbon Installer
		</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<meta http-equiv="content-language" content="en"/>
		<meta name="author" content="Ross Parker, International College Hong Kong"/>
		<meta name="robots" content="none"/>
		
		<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
<?php 
use Symfony\Component\Yaml\Yaml ;

$theme = Yaml::parse(file_get_contents(GIBBON_ROOT . 'src/themes/Bootstrap/settings.yml'));

if (isset($theme['js']))
{
	foreach($theme['js'] as $js)
	{
		?>
		<script type="text/javascript" src="<?php echo trim(GIBBON_URL, '/') . str_replace(array('{{themeName}}'), array('Bootstrap'), $js); ?>"></script>
		<?php
	}
}
if (isset($theme['css']))
{
	foreach($theme['css'] as $css)
	{
		?>
		<link rel="stylesheet" href="<?php echo trim(GIBBON_URL, '/') . str_replace(array('{{themeName}}'), array('Bootstrap'), $css); ?>" type="text/css" media="screen" />
		<?php
	}
} 
?>
	</head>
