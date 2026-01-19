@echo off
setlocal enabledelayedexpansion

set FTP_USER=teslimnushasi@teslimnushasi.com
set FTP_PASS=Konya1923*
set FTP_HOST=ftp://ftp.caygetir.net

echo Creating directories...
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD api" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD api/admin" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD assets" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD assets/css" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD includes" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD uploads" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD uploads/temp" %FTP_HOST%/ 2>nul
curl.exe --user "%FTP_USER%:%FTP_PASS%" --ftp-create-dirs -Q "MKD Example" %FTP_HOST%/ 2>nul

echo Uploading PHP files...
for %%f in (*.php) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/%%f"
)

echo Uploading SQL file...
curl.exe --upload-file "setup.sql" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/setup.sql"

echo Uploading htaccess...
curl.exe --upload-file ".htaccess" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/.htaccess"

echo Uploading api files...
for %%f in (api\*.php) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/api/%%~nxf"
)

echo Uploading api/admin files...
for %%f in (api\admin\*.php) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/api/admin/%%~nxf"
)

echo Uploading includes files...
for %%f in (includes\*.php) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/includes/%%~nxf"
)
curl.exe --upload-file "includes\.htaccess" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/includes/.htaccess"

echo Uploading assets/css files...
for %%f in (assets\css\*.css) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/assets/css/%%~nxf"
)

echo Uploading uploads htaccess...
curl.exe --upload-file "uploads\.htaccess" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/uploads/.htaccess"

echo Uploading Example files...
for %%f in (Example\*.*) do (
    echo Uploading %%f...
    curl.exe --upload-file "%%f" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/Example/%%~nxf"
)

echo Uploading Google API credentials...
curl.exe --upload-file "rare-chiller-210007-cb726fce6c22.json" --user "%FTP_USER%:%FTP_PASS%" "%FTP_HOST%/rare-chiller-210007-cb726fce6c22.json"

echo.
echo Upload complete!
pause
