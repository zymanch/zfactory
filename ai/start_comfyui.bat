@echo off
chcp 65001 >nul
echo ========================================
echo ComfyUI + FLUX.1 Dev for ZFactory
echo ========================================
echo.
echo Starting ComfyUI with RTX 3060 12GB optimizations...
echo.

REM Check for nested structure (portable version)
if exist "ComfyUI\ComfyUI\main.py" (
    cd ComfyUI\ComfyUI
) else if exist "ComfyUI\main.py" (
    cd ComfyUI
) else (
    echo [ERROR] ComfyUI not installed!
    echo Please install first - see README_INSTALL.md
    pause
    exit /b 1
)

echo [INFO] Launch parameters:
echo   --listen 0.0.0.0 (PHP access)
echo   --port 8188 (API port)
echo   --lowvram (12GB optimization)
echo   --preview-method auto (generation preview)
echo.
echo [INFO] ComfyUI will be available at: http://localhost:8188
echo.
echo Wait for model loading (~2-3 min on first run)...
echo.

REM Use embedded Python if available (portable version)
if exist "..\python_embeded\python.exe" (
    echo [INFO] Using embedded Python from portable version
    ..\python_embeded\python.exe main.py --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto
) else (
    echo [INFO] Using system Python
    python main.py --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto
)

pause
