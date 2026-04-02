@echo off
echo.
echo =================================================
echo  NATURACOR -- Reporte de Cobertura de Tests
echo =================================================
echo.

cd /d "D:\ESCRITORIO\UNIVERSIDAD\7mo ciclo\PRUEBAS Y CALIDAD DE SOFTWARE\PROYECTO NATURACOR\naturacor"

echo Verificando Xdebug...
php -r "if(!extension_loaded('xdebug')){echo 'ERROR: Xdebug no esta instalado.'.PHP_EOL;exit(1);}"

if errorlevel 1 (
    echo.
    echo Para instalar Xdebug en XAMPP:
    echo 1. Ve a https://xdebug.org/wizard
    echo 2. Pega el output de: php -i
    echo 3. Descarga el .dll correspondiente
    echo 4. Ponlo en C:\xampp\php\ext\
    echo 5. En php.ini agrega: zend_extension=xdebug
    echo.
    pause
    exit /b 1
)

echo Xdebug OK!
echo Generando reporte de cobertura...
echo.

if not exist "storage\app\coverage" mkdir storage\app\coverage

set XDEBUG_MODE=coverage
php artisan test --coverage-html=storage/app/coverage --env=testing

echo.
echo =================================================
echo  Reporte generado en: storage\app\coverage\
echo  Abre: storage\app\coverage\index.html
echo =================================================
echo.

start storage\app\coverage\index.html
pause
