@echo off
chcp 65001 >nul
echo ========================================
echo ComfyUI Installation for FLUX.1 Dev
echo ========================================
echo.

REM Check if already installed
if exist "ComfyUI\main.py" (
    echo [OK] ComfyUI already installed!
    echo.
    pause
    exit /b 0
)

echo [STEP 1/3] Checking Git...
git --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git not found!
    echo Please install Git: https://git-scm.com/download/win
    echo.
    pause
    exit /b 1
)
echo [OK] Git installed
echo.

echo [STEP 2/3] Cloning ComfyUI repository...
echo This will take 2-5 minutes...
echo.
git clone https://github.com/comfyanonymous/ComfyUI.git

if not exist "ComfyUI\main.py" (
    echo [ERROR] Failed to clone ComfyUI
    pause
    exit /b 1
)
echo [OK] ComfyUI cloned successfully
echo.

echo [STEP 3/3] Installing Python dependencies...
echo This will take 5-10 minutes on first run...
echo.

cd ComfyUI

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python not found!
    echo Please install Python 3.10 or 3.11
    echo Download: https://www.python.org/downloads/
    echo.
    pause
    exit /b 1
)

REM Install PyTorch with CUDA support
echo Installing PyTorch with CUDA...
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu121

REM Install ComfyUI requirements
echo Installing ComfyUI requirements...
pip install -r requirements.txt

cd ..

echo.
echo ========================================
echo [SUCCESS] ComfyUI installed!
echo ========================================
echo.
echo NEXT STEPS:
echo 1. Download FLUX models (~22GB) - see QUICK_START.md
echo 2. Run: start_comfyui.bat
echo.
pause
