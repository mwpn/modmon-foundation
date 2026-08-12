@echo off
echo === ModMon Foundation Setup ===
echo.

echo [1/6] Installing Composer dependencies...
call composer install
if %ERRORLEVEL% neq 0 goto :error

echo [2/6] Installing npm dependencies...
call npm install
if %ERRORLEVEL% neq 0 goto :error

echo [3/6] Creating .env file...
if not exist .env copy .env.example .env

echo [4/6] Generating application key...
call php artisan key:generate
if %ERRORLEVEL% neq 0 goto :error

echo [5/6] Running migrations...
call php artisan migrate
if %ERRORLEVEL% neq 0 goto :error

echo [6/6] Building frontend assets...
call npm run build
if %ERRORLEVEL% neq 0 goto :error

echo.
echo === Setup complete! ===
echo.
echo Run 'php artisan foundation:doctor' to verify the foundation.
echo Run 'php artisan module:list' to see discovered modules.
echo Run 'php artisan module:install example' to install the Example module.
echo Run 'php artisan test' to run all tests.
echo.
goto :end

:error
echo.
echo Setup failed at a step above. Please check the error and try again.
echo.

:end
