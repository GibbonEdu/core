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
use Gibbon\Session\SessionFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\BackgroundProcessor;
use Gibbon\Domain\School\SchoolYearGateway;

$_POST['address'] = '/modules/'.($argv[3] ?? 'System Admin').'/index.php';

require __DIR__.'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
if (!isCommandLineInterface()) {
    echo __('This script cannot be run from a browser, only via CLI.');
} else {
    // Override the ini to keep this process alive
    ini_set('memory_limit', '2048M');
    ini_set('max_execution_time', 1800);
    set_time_limit(1800);

    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
    ini_set('error_log', '/var/log/php-error.log');

    // Incoming variables from command line
    $processID = $argv[1] ?? '';
    $processKey = $argv[2] ?? '';

    // Run the process
    $container->get(BackgroundProcessor::class)->runProcess($processID, $processKey);
}