@echo off
setlocal enabledelayedexpansion

echo ============================================
echo Stable Diffusion WebUI Installer
echo ============================================
echo.

:: Get script directory
set "SCRIPT_DIR=%~dp0"
set "MODEL_DIR=%SCRIPT_DIR%model"
set "WEBUI_DIR=%SCRIPT_DIR%stable-diffusion-webui"
set "TARGET_MODEL_DIR=%WEBUI_DIR%\models\Stable-diffusion"

:: Step 1: Check for .safetensors file in ai/model
echo [1/4] Checking for model file...
set "MODEL_FILE="
for %%f in ("%MODEL_DIR%\*.safetensors") do (
    set "MODEL_FILE=%%f"
    goto :found_model
)

:no_model
echo.
echo [ERROR] No .safetensors model file found in ai/model/
echo.
echo Please download a model from:
echo   https://civitai.com/models/4201
echo.
echo Recommended: Realistic Vision v6.0 B1 (4.59 GB)
echo   Direct link: https://civitai.com/api/download/models/245598
echo.
echo After downloading, place the .safetensors file in:
echo   %MODEL_DIR%
echo.
echo Then run this script again.
echo.
pause
exit /b 1

:found_model
echo   Found model: %MODEL_FILE%
echo.

:: Step 2: Check if stable-diffusion-webui directory exists
echo [2/4] Checking for Stable Diffusion WebUI...
if exist "%WEBUI_DIR%" (
    echo   WebUI already installed: %WEBUI_DIR%
    goto :move_model
)

:: Step 3: Clone stable-diffusion-webui repository
echo   WebUI not found. Cloning repository...
echo.

git --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git is not installed!
    echo.
    echo Please install Git from: https://git-scm.com/download/win
    echo.
    pause
    exit /b 1
)

cd "%SCRIPT_DIR%"
git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui

if not exist "%WEBUI_DIR%" (
    echo [ERROR] Failed to clone repository!
    pause
    exit /b 1
)

echo   Repository cloned successfully.
echo.

:move_model
:: Step 4: Move .safetensors file to stable-diffusion-webui/models/Stable-diffusion/
echo [3/4] Moving model file to WebUI directory...

if not exist "%TARGET_MODEL_DIR%" (
    mkdir "%TARGET_MODEL_DIR%"
)

:: Get model filename
for %%f in ("%MODEL_FILE%") do set "MODEL_FILENAME=%%~nxf"

:: Check if model already exists in target directory
if exist "%TARGET_MODEL_DIR%\%MODEL_FILENAME%" (
    echo   Model already exists in target directory.
    echo   Skipping copy.
) else (
    echo   Copying %MODEL_FILENAME% to WebUI models directory...
    copy "%MODEL_FILE%" "%TARGET_MODEL_DIR%\"
    if errorlevel 1 (
        echo   [ERROR] Failed to copy model file!
        pause
        exit /b 1
    )
    echo   Model copied successfully.
)
echo.

:: Step 5: Create webui-user.bat with API enabled
echo [4/4] Creating webui-user.bat with API enabled...
set "WEBUI_USER=%WEBUI_DIR%\webui-user.bat"

if exist "%WEBUI_USER%" (
    echo   webui-user.bat already exists. Backing up...
    copy "%WEBUI_USER%" "%WEBUI_USER%.backup" >nul
)

(
echo @echo off
echo.
echo set PYTHON=
echo set GIT=
echo set VENV_DIR=
echo set COMMANDLINE_ARGS=--api --listen --port 7860 --xformers
echo.
echo call webui.bat
) > "%WEBUI_USER%"

echo   webui-user.bat created with API enabled.
echo.

:: Done
echo ============================================
echo Installation Complete!
echo ============================================
echo.
echo Next steps:
echo   1. Run start.bat to launch Stable Diffusion WebUI
echo   2. Wait for WebUI to load (first run takes 5-10 minutes)
echo   3. Once loaded, API will be available at http://localhost:7860
echo   4. Use: php yii landing/generate-ai all
echo.
echo Press any key to exit...
pause >nul
