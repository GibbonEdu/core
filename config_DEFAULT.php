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

//Sets database connection information
$databaseServer='';
$databaseUsername='';
$databasePassword='';
$databaseName='';

//Sets globally unique id, to allow multiple installs on the server server.
$guid="nosiqk9s-hm8-0e8r-ygrm-gyc04gdvnhe" ;

//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.
$caching=10 ;
?>
