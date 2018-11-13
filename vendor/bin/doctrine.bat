@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../doctrine/orm/bin/doctrine
php "%BIN_TARGET%" %*
