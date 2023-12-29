@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
@REM Install WSL with Ubuntu.
ECHO Installing Windows Subsystem for Linux Version 2 using Ubuntu.
call wsl --update
call wsl --install -d Ubuntu
call wsl --set-version Ubuntu 2
call wsl --set-default-version 2
call wsl --set-default Ubuntu
