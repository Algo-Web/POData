echo off

set PWD=%CD%

REM Folder containing the scaffold
set JVer=2.5.0



echo Cleaning up previous Joomla scaffolder files
 rmdir /S /Q %PWD%\build
 mkdir %PWD%\build

echo Building scaffold .phar file 
 call scaffolder build -in="%PWD%\Joomla" -out="%PWD%\build\Joomla.phar"

echo Creating project directories
call scaffolder run -out="%PWD%\build\Joomla" -s="%PWD%\build\Joomla.phar" -db jbase -user "demosql@xxxxxbujiw" -password "SamHoustonxxxx" -host "xxx4mbujxxw.database.windows.net"  -sample_data 1 -admin_user "adminXX" -admin_password "admxxin"


REM -out="%PWD%\build\Joomla"

echo Packaging project
call package create -in="%PWD%\build\Joomla" -out="%PWD%\build" -dev=false




