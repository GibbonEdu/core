@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../doctrine/orm/bin/doctrine.php
php "%BIN_TARGET%" %*
