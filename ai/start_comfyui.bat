@echo off
chcp 65001 >nul
echo ========================================
echo ComfyUI + FLUX.1 Dev for ZFactory
echo ========================================
echo.
echo Starting ComfyUI with RTX 3060 12GB optimizations...
echo.

REM Check for portable installation
if not exist "ComfyUI\python_embeded\python.exe" (
    echo [ERROR] Portable version not found!
    echo Expected: ComfyUI\python_embeded\python.exe
    echo Please install portable version - see README_INSTALL.md
    echo.
    pause
    exit /b 1
)

REM Portable version detected
echo [INFO] Using portable version
echo [INFO] Launch parameters:
echo   --windows-standalone-build (portable mode)
echo   --listen 0.0.0.0 (PHP API access)
echo   --port 8188 (API port)
echo   --lowvram (12GB VRAM optimization)
echo   --preview-method auto (generation preview)
echo.
echo [INFO] ComfyUI will be available at: http://localhost:8188
echo.
echo Wait for model loading (~2-3 min on first run)...
echo.

REM Run from portable root, not nested folder
.\ComfyUI\python_embeded\python.exe -s ComfyUI\ComfyUI\main.py --windows-standalone-build --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto

pause
