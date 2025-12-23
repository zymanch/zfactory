@echo off
setlocal

echo ============================================
echo Starting Stable Diffusion WebUI
echo ============================================
echo.

set "SCRIPT_DIR=%~dp0"
set "WEBUI_DIR=%SCRIPT_DIR%stable-diffusion-webui"

if not exist "%WEBUI_DIR%" (
    echo [ERROR] Stable Diffusion WebUI not installed!
    echo.
    echo Please run install.bat first.
    echo.
    pause
    exit /b 1
)

cd "%WEBUI_DIR%"

if not exist "webui-user.bat" (
    echo [ERROR] webui-user.bat not found!
    echo.
    echo Please run install.bat first.
    echo.
    pause
    exit /b 1
)

echo Starting WebUI with API enabled...
echo.
echo API will be available at: http://localhost:7860
echo Swagger docs: http://localhost:7860/docs
echo.
echo Press Ctrl+C to stop the server.
echo.

call webui-user.bat
