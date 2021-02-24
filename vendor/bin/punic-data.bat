@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../punic/punic/bin/punic-data
php "%BIN_TARGET%" %*
