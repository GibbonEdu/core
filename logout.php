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

include 'functions.php';
include 'config.php';

$URL = './index.php';
if (isset($_GET['timeout'])) {
    if ($_GET['timeout'] == 'true') {
        $URL = './index.php?timeout=true';
    }
}

unset($_SESSION[$guid]['googleAPIAccessToken']);
unset($_SESSION[$guid]['gplusuer']);

session_destroy();

$_SESSION[$guid] = null;
header("Location: {$URL}");
