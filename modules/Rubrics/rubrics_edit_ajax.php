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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

//Open Database connection
if (!($connection = mysql_connect($databaseServer, $databaseUsername, $databasePassword))) {
    showError();
}

//Select database
if (!(mysql_select_db($databaseName, $connection))) {
    showError();
}

mysql_set_charset('utf8');

$gibbonRubricID = $_GET['gibbonRubricID'];
echo rubricEdit($guid, $connection, $gibbonRubricID);
