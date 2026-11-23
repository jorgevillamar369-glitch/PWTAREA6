@echo off
REM ============================================================
REM  A la Carta · arranque local en Windows
REM  Levanta MariaDB (XAMPP) en el puerto 3307 y el servidor
REM  de PHP en el 8080, y abre el sitio en el navegador.
REM ============================================================
title A la Carta - servidor local
cd /d "%~dp0"

REM 1) Base de datos: solo arranca si el puerto 3307 no responde.
netstat -ano | findstr /R /C:":3307 .*LISTENING" >nul
if errorlevel 1 (
    echo Iniciando MariaDB en el puerto 3307...
    start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --port=3307 --standalone
    timeout /t 6 /nobreak >nul
) else (
    echo MariaDB ya esta escuchando en el puerto 3307.
)

REM 2) Servidor de PHP con la carpeta del proyecto.
netstat -ano | findstr /R /C:":8080 .*LISTENING" >nul
if errorlevel 1 (
    echo Iniciando PHP en http://localhost:8080 ...
    start "" "C:\xampp\php\php.exe" -S localhost:8080 -t .
    timeout /t 2 /nobreak >nul
) else (
    echo PHP ya esta escuchando en el puerto 8080.
)

start "" http://localhost:8080
exit
