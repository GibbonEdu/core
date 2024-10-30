<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
*/

use Gibbon\Services\Format;
use Gibbon\Database\Updater;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>
			Gibbon Database Updater
		</title>
		<meta charset="utf-8"/>
		<meta name="author" content="Ross Parker, International College Hong Kong"/>
		<meta name="robots" content="none"/>

		<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
		<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css' />
	</head>
	<body>
		<?php
        include './gibbon.php';
        include './config.php';
        include './version.php';

        require_once './modules/System Admin/moduleFunctions.php';

        $partialFail = false;

        $updater = $container->get(Updater::class);

        if (!$updater->isVersionValid()) {
            echo Format::alert(__('Your request failed because your inputs were invalid.'));
        }

        if (!$updater->isUpdateRequired()) {
            echo Format::alert(__('Your request failed because your inputs were invalid, or no update was required.'));
        } else {
            // Do the update
            $errors = $updater->update();

            if (!empty($errors)) {
                echo Format::alert(__('Some aspects of your update failed.'));
            } else {
                echo Format::alert(__('Your request was completed successfully.'), 'success');

                // Update DB version for existing languages
                i18nCheckAndUpdateVersion($container, $updater->versionDB);

                // Clear the templates cache folder
                removeDirectoryContents($session->get('absolutePath').'/uploads/cache');

                // Clear the var/log folder
                removeDirectoryContents($session->get('absolutePath').'/var', true);
            }
        }
        ?>
	</body>
</html>
