@echo off
:: Фиксируем настройки для видеокарты AMD RX 9060 XT
set OLLAMA_VULKAN=1
set OLLAMA_NEW_ENGINE=true
set HSA_OVERRIDE_GFX_VERSION=

:: Очистка зависших процессов
taskkill /f /im ollama.exe >nul 2>&1

:: Запуск сервера Ollama в фоне
echo Starting Ollama Server with Vulkan (RX 9060 XT)...
start /b ollama serve

:: Ожидание инициализации GPU
timeout /t 5 /nobreak >nul

echo.
echo Select Application to launch:
echo 1. Claude Code
echo 2. Codex
echo 3. OpenCode
echo 4. OpenClaw
echo.

set /p choice="Enter number (1-4): "

if "%choice%"=="1" ollama launch claude 
if "%choice%"=="2" ollama launch codex 
if "%choice%"=="3" ollama launch opencode 
if "%choice%"=="4" ollama launch openclaw 

pause