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

include "functions.php" ;
include "config.php" ;

@session_start() ;

$URL="./index.php" ;

unset($_SESSION[$guid]['google_api_access_token']);
unset($_SESSION[$guid]['gplusuer']);
 
session_destroy();

$_SESSION[$guid]=NULL ;
header("Location: {$URL}");
?>
