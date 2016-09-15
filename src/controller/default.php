<?php
namespace Gibbon\controller ;

$v13 = array();
$q = isset($_GET['q']) ? $_GET['q'] : '' ;
$v13 = array
	(
		'/modules/System Admin/systemSettings',
		'/modules/Security/',
		'/modules/Notifications/',
		'/modules/User Admin/preferences',
	);

/*
if ($q === '')   //  This will render the home page..
{
	$path = pathinfo($_SERVER['SCRIPT_FILENAME']);
	if ($path['basename'] === 'index.php')
	{
		require __DIR__.'/gibbon.php';
		die(__FILE__.': '.__LINE__);  // if it dies here there is an issue
	}
	echo '<pre>';
	var_dump($path);
	die('</pre>');
} */

if (in_array($q, $v13))
{
	require __DIR__.'/gibbon.php';
	die(__FILE__.': '.__LINE__);  // if it dies here there is an issue
}

do
{
	$q = mb_substr($q, 0, -1);
	if (in_array($q, $v13))
	{
		require __DIR__.'/gibbon.php';
		die(__FILE__.': '.__LINE__);  // if it dies here there is an issue
	}
} while (mb_strlen($q) > 3);

// So do the old stuff.

//Prevent breakage of back button on POST pages
ini_set('session.cache_limiter', 'private');
session_cache_limiter(false);

//session_name('Gibbon-v13-Backwards');
session_start();
if (file_exists(rtrim(__DIR__, '/').'/../../config.php'))
{
	include rtrim(__DIR__, '/').'/../../config.php';
	//new stuff that needs to be set in the old here ...
	$_SESSION[$guid]['security']['lastPageTime'] = strtotime('now');
}