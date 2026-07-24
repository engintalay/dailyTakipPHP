#!/bin/bash
set -e

echo "=== dailyTakip Kurulum ==="
echo ""

# Node.js kontrol
if ! command -v node &> /dev/null; then
    echo "❌ Node.js bulunamadı. Lütfen https://nodejs.org adresinden Node.js 18+ yükleyin."
    exit 1
fi

echo "✅ Node.js $(node --version)"
echo ""

# Bağımlılıkları yükle
echo "📦 Bağımlılıklar yükleniyor..."
npm install

# Prisma generate
echo "🗄️  Veritabanı oluşturuluyor..."
npx prisma generate
npx prisma db push

# Seed
echo "🌱 Seed verileri ekleniyor..."
npx tsx src/prisma/seed.ts

# Build
echo "🔨 Build alınıyor..."
npm run build

echo ""
echo "=== ✅ Kurulum tamam! ==="
echo ""
echo "Uygulamayı başlatmak için: ./start.sh"
echo "Veya: npm run dev"
echo ""
echo "Varsayılan giriş bilgileri:"
echo "  Admin: admin@dailytakip.com / admin123"
echo "  Ali:   ali@dailytakip.com   / user123"
echo "  Ayşe:  ayse@dailytakip.com  / user123"
