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

/**
 * Sets version information.
 */
$version = '21.0.01';

/**
 * System Requirements
 */
$systemRequirements = [
    'php'        => '7.0.0',
    'mysql'      => '5.6',
    'apache'     => ['mod_rewrite'],
    'extensions' => ['gettext', 'mbstring', 'curl', 'zip', 'xml', 'gd'],
    'settings'   => [
        ['max_input_vars', '>=', 5000],
        ['max_file_uploads', '>=', 20],
        ['allow_url_fopen', '==', 1],
        ['register_globals', '==', 0],
        ['session.gc_maxlifetime', '>=', 1200],
        ['post_max_size', '>', 0],
        ['upload_max_filesize', '>', 0],
    ],
];
