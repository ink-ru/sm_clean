# sm_clean
This script will clean site map from tags and disalowed urls

Works both in browser and in command line


// ===================================================== //
// 						CLI usage						 //
// ===================================================== //

Usage in windows .bat file:
@echo OFF
@chcp 65001
"C:\OpenServer\modules\php\PHP-5.6\php.exe" smap_clean.php %*
pause


Usage in Linux crone:
php sm_clean.php


more info about php cli usage: http://php.net/manual/ru/features.commandline.usage.php
