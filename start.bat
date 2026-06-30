@echo off
cd /d "%~dp0"
echo =========================================================
echo  TECHZONE - KHOI DONG HE THONG
echo =========================================================
echo  Dang kiem tra lien ket thu muc...

:: Tu dong tao lien ket thu muc 'bainhom' neu chua co de load assets
if not exist "bainhom" (
    echo  Tao lien ket 'bainhom' de website load CSS/Anh dung duong dan...
    mklink /j "bainhom" "."
)

echo  Dang mo trinh duyet va khoi dong Local Web Server tai http://localhost:8000/bainhom/index.php...
echo  Vui long giu nguyen cua so nay de duy tri Server.
echo =========================================================
echo.

:: Mo truc tiep trang chu voi tien to /bainhom/ tren trinh duyet
start http://localhost:8000/bainhom/index.php

:: Chay PHP built-in web server
"C:\xampp\php\php.exe" -S localhost:8000
