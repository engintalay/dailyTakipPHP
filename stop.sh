#!/bin/bash

PID_FILE="$(cd "$(dirname "$0")" && pwd)/server.pid"

if [ ! -f "$PID_FILE" ]; then
    echo "❌ dailyTakip çalışmıyor (PID dosyası bulunamadı)"
    exit 1
fi

PID=$(cat "$PID_FILE")

if kill "$PID" 2>/dev/null; then
    echo "❌ dailyTakip durduruldu (PID: $PID)"
else
    echo "⚠️  PID: $PID sonlandırılamadı, zaten kapalı olabilir"
fi

rm -f "$PID_FILE"
