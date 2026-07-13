@echo off
setlocal
set "BIN=%~dp0bin\cloudflared.exe"
if exist "%BIN%" (
  "%BIN%" tunnel --protocol http2 --url http://127.0.0.1:5173
) else (
  echo cloudflared.exe не найден: %BIN%
  echo Скачай: https://github.com/cloudflare/cloudflared/releases/latest
  pause
  exit /b 1
)
