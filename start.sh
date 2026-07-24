#!/bin/bash

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
PID_FILE="$APP_DIR/server.pid"
LOG_FILE="$APP_DIR/server.log"
PORT=3000

# Çalışıyor mu kontrol et
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if kill -0 "$OLD_PID" 2>/dev/null; then
        echo "✅ dailyTakip zaten çalışıyor (PID: $OLD_PID)"
        echo "   http://localhost:$PORT"
        exit 0
    else
        rm -f "$PID_FILE"
    fi
fi

cd "$APP_DIR"

# Production build varsa start et, yoksa dev
if [ -d ".next" ]; then
    nohup node_modules/.bin/next start -H 0.0.0.0 -p "$PORT" > "$LOG_FILE" 2>&1 &
else
    echo "⚠️  Build bulunamadı, dev modunda başlatılıyor..."
    nohup node_modules/.bin/next dev -H 0.0.0.0 -p "$PORT" > "$LOG_FILE" 2>&1 &
fi

PID=$!
echo $PID > "$PID_FILE"

echo "✅ dailyTakip başlatıldı (PID: $PID)"
echo "   Yerel:    http://localhost:$PORT"
echo "   Ağ:       http://$(hostname -I 2>/dev/null | awk '{print $1}'):$PORT"
echo ""
echo "Durdurmak için: ./stop.sh"
echo "Loglar: $LOG_FILE"
