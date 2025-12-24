@echo off
chcp 65001 >nul
echo ========================================
echo ComfyUI + FLUX.1 Dev for ZFactory
echo ========================================
echo.
echo Starting ComfyUI with RTX 3060 12GB optimizations...
echo.

REM Check installation type
if exist "ComfyUI\python_embeded\python.exe" (
    REM Portable version with nested structure
    echo [INFO] Detected portable version (nested structure)
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
    cd ComfyUI
    .\python_embeded\python.exe -s ComfyUI\main.py --windows-standalone-build --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto
) else if exist "ComfyUI\main.py" (
    REM Git-cloned version
    echo [INFO] Detected git-cloned version
    echo [INFO] Launch parameters:
    echo   --listen 0.0.0.0 (PHP API access)
    echo   --port 8188 (API port)
    echo   --lowvram (12GB VRAM optimization)
    echo   --preview-method auto (generation preview)
    echo.
    echo [INFO] ComfyUI will be available at: http://localhost:8188
    echo.
    echo Wait for model loading (~2-3 min on first run)...
    echo.
    cd ComfyUI
    python main.py --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto
) else (
    echo [ERROR] ComfyUI not installed!
    echo Please install first - see README_INSTALL.md
    echo.
    pause
    exit /b 1
)

pause
