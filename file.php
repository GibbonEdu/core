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

require_once './gibbon.php';

$path = ltrim((string) ($_GET['path'] ?? ''), '/');
if ($path === '' || str_contains($path, '..')) {
    http_response_code(403);
    exit;
}

// Rewrite from uploads/ passes a path relative to that directory
if (!str_starts_with($path, 'uploads/')) {
    $path = 'uploads/'.$path;
}

$absolutePath = $session->get('absolutePath');
$uploadsRoot = realpath($absolutePath.'/uploads');
$filePath = realpath($absolutePath.'/'.$path);

if ($uploadsRoot === false || $filePath === false || !str_starts_with($filePath, $uploadsRoot) || !is_file($filePath)) {
    http_response_code(403);
    exit;
}

$publicPaths = array_filter([
    ltrim((string) $session->get('organisationLogo'), '/'),
    ltrim((string) $session->get('organisationBackground'), '/'),
]);

$isPublic = in_array($path, $publicPaths, true);
$allowedUploads = $session->get('allowedUploads', []);
$isAllowed = $session->has('gibbonPersonID')
    && isset($allowedUploads[$path])
    && (time() - (int) $allowedUploads[$path]) <= 7200;

if (!$isPublic && !$isAllowed) {
    http_response_code(403);
    exit;
}

$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
$filename = basename($filePath);

header('Content-Type: '.$mimeType);
header('Content-Disposition: inline; filename="'.$filename.'"');
header('Content-Length: '.filesize($filePath));
header('Cache-Control: private');

$hasXSendfile = function_exists('apache_get_modules')
    && in_array('mod_xsendfile', apache_get_modules() ?: []);

if ($hasXSendfile) {
    header('X-Sendfile: '.$filePath);
} else {
    readfile($filePath);
}

exit;
