@echo off
setlocal
cd /d "%~dp0"

where py >nul 2>nul
if %errorlevel%==0 (
    py -3 orchestrator.py
    goto :end
)

where python >nul 2>nul
if %errorlevel%==0 (
    python orchestrator.py
    goto :end
)

echo [ERROR] Python 3 was not found. Install Python and enable "Add Python to PATH".
pause

:end
endlocal
