<?php
/**
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author	Craig Rayner
 *
 * @version	26th August 2016
 * @since	7th April 2016
 * @package	Gibbon
 * @subpackage	Controller
 */
namespace Gibbon\controller ;

$v13 = array();
$q = isset($_GET['q']) ? $_GET['q'] : '' ;
$v13 = array
	(
		'/modules/System Admin/',
		'/modules/Translation/',
		'/modules/Security/',
		'/modules/Notifications/',
		'/modules/User Admin/preferences',
		'/modules/School Admin/messengerSettings',
		'/modules/School Admin/fileExtensions_manage',
		'/modules/School Admin/formalAssessmentSettings',
		'/modules/School Admin/externalAssessments_',
		'/modules/School Admin/gradeScales_manage',
		'/modules/School Admin/markbookSettings',
		'/modules/School Admin/trackingSettings',
		'/modules/School Admin/department_manage', 
		'/modules/School Admin/house_manage',
		'/modules/School Admin/rollGroup_manage',
		'/modules/School Admin/yearGroup_manage',
		'/core/scripts/',
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
