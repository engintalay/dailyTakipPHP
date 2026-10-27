#!/bin/bash
# Sistem Kontrol Scripti
# Çıktı: system-check-output.txt

OUTPUT_FILE="system-check-output.txt"

echo "=== SİSTEM KONTROL RAPORU ===" > "$OUTPUT_FILE"
echo "Tarih: $(date)" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 1. PHP Versiyonu
echo "=== PHP Versiyonu ===" >> "$OUTPUT_FILE"
php -v 2>&1 >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 2. PHP Modülleri (önemli olanlar)
echo "=== PHP Modülleri ===" >> "$OUTPUT_FILE"
php -m 2>&1 | grep -E "(pdo|mysql|pgsql|json|mbstring|session|xml|curl|zip)" >> "$OUTPUT_FILE" || echo "Modüller bulunamadı" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 3. MySQL/MariaDB Versiyonu
echo "=== MySQL/MariaDB Versiyonu ===" >> "$OUTPUT_FILE"
mysql --version 2>&1 >> "$OUTPUT_FILE" || echo "MySQL kurulu değil" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 4. PostgreSQL Versiyonu
echo "=== PostgreSQL Versiyonu ===" >> "$OUTPUT_FILE"
psql --version 2>&1 >> "$OUTPUT_FILE" || echo "PostgreSQL kurulu değil" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 5. PostgreSQL Paketleri
echo "=== PostgreSQL Paketleri ===" >> "$OUTPUT_FILE"
rpm -qa | grep -i postgresql 2>&1 >> "$OUTPUT_FILE" || echo "PostgreSQL paketleri bulunamadı" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 6. PDO PostgreSQL uzantısı
echo "=== PDO PostgreSQL Uzantısı ===" >> "$OUTPUT_FILE"
php -r "extension_loaded('pdo_pgsql') ? print 'pdo_pgsql yüklü\n' : print 'pdo_pgsql yüklü değil\n';" 2>&1 >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 7. PDO MySQL uzantısı
echo "=== PDO MySQL Uzantısı ===" >> "$OUTPUT_FILE"
php -r "extension_loaded('pdo_mysql') ? print 'pdo_mysql yüklü\n' : print 'pdo_mysql yüklü değil\n';" 2>&1 >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 8. PostgreSQL Servis Durumu
echo "=== PostgreSQL Servis Durumu ===" >> "$OUTPUT_FILE"
systemctl status postgresql 2>&1 | head -5 >> "$OUTPUT_FILE" || echo "PostgreSQL servis bilgisi alınamadı" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 9. PostgreSQL Kullanıcı Bilgileri (pg_hba.conf)
echo "=== PostgreSQL Kullanıcı Bilgileri (pg_hba.conf) ===" >> "$OUTPUT_FILE"
PG_HBA_PATH="/var/lib/pgsql/data/pg_hba.conf"
if [ -f "$PG_HBA_PATH" ]; then
    echo "Dosya yolu: $PG_HBA_PATH" >> "$OUTPUT_FILE"
    grep -v "^#" "$PG_HBA_PATH" | grep -v "^$" >> "$OUTPUT_FILE"
else
    echo "pg_hba.conf bulunamadı: $PG_HBA_PATH" >> "$OUTPUT_FILE"
    # Alternatif yolları kontrol et
    find /var -name "pg_hba.conf" 2>/dev/null >> "$OUTPUT_FILE" || true
fi
echo "" >> "$OUTPUT_FILE"

# 10. PostgreSQL Veritabanları
echo "=== PostgreSQL Veritabanları ===" >> "$OUTPUT_FILE"
sudo -u postgres psql -l 2>&1 >> "$OUTPUT_FILE" || echo "Veritabanları listelenemedi" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 11. PostgreSQL Bağlantı Testi (dailytakip_user ile)
echo "=== PostgreSQL Bağlantı Testi ===" >> "$OUTPUT_FILE"
PGPASSWORD="tXlsgawUZZW754Os" psql -h 127.0.0.1 -U dailytakip_user -d dailytakip -c "SELECT version(); SELECT current_user;" 2>&1 >> "$OUTPUT_FILE" || echo "Bağlantı başarısız" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 12. Apache/Nginx Versiyonu
echo "=== Apache/Nginx Versiyonu ===" >> "$OUTPUT_FILE"
httpd -v 2>&1 >> "$OUTPUT_FILE" || nginx -v 2>&1 >> "$OUTPUT_FILE" || echo "Web sunucusu bulunamadı" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 13. PostgreSQL Portu
echo "=== PostgreSQL Portu ===" >> "$OUTPUT_FILE"
grep "port" /var/lib/pgsql/data/postgresql.conf 2>/dev/null | head -3 >> "$OUTPUT_FILE" || echo "postgresql.conf okunamadı" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

echo "=== KONTROL TAMAMLANDI ===" >> "$OUTPUT_FILE"
echo "Çıktı dosyası: $OUTPUT_FILE"
