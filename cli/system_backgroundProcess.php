<?php
/*
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
*/

use Gibbon\Services\BackgroundProcessor;
use Gibbon\Services\Format;

$_POST['address'] = '/modules/'.($argv[3] ?? 'System Admin').'/index.php';

require __DIR__.'/../gibbon.php';

// Cancel out now if we're not running via CLI
if (!isCommandLineInterface()) {
    die(__('This script cannot be run from a browser, only via CLI.'));
}

// Setup some of the globals
Format::setupFromSession($container->get('session'));
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

// Incoming variables from command line
$processID = $argv[1] ?? '';
$processKey = $argv[2] ?? '';

// Run the process
$container->get(BackgroundProcessor::class)->runProcess($processID, $processKey);
