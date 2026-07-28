## Project: dailyTakip (PHP 5.3)

### PHP Version Notes
- **Target**: PHP 5.3 — no `??`, no `[]` short arrays, no `array_column`,
  no `func()[0]` dereference, no `JSON_UNESCAPED_UNICODE`/`JSON_PRETTY_PRINT`,
  no `cal_days_in_month`
- **Server runs PHP 5.3** — `create_function()` mevcut, ancak her ihtimale
  karşı named callback (`_unicodeJsonReplace`) kullanılıyor
- Tüm `create_function` kullanımları temizlendi
- **File permission sorunu**: SSH mount ile oluşturulan JSON dosyaları
  web sunucusu (www-data) tarafından yazılamayabiliyor. `saveJson()`
  otomatik `chmod(0666)` yapar, `initDatabase()` tüm dosyaları düzeltir

### Storage
- JSON files in `data/` (no MySQL/SQLite)
- Functions in `includes/db.php`

### Auth
- Roles: `ADMIN`, `MEMBER`, `VIEWER` (salt okunur)
- `canViewManagement()`: ADMIN ve VIEWER görebilir
- `requireManagementAccess()`: management sayfaları için gate

### Modules
- Daily notes, status, attendance, team calendar, reports, todos
- Nöbet (on-call): `pages/oncall.php` + `api/oncall.php` + `pages/admin/holidays.php`
  - Admin: tam yetki
  - Nöbet ekibi üyesi: bugün ve gelecek günler için kendini atayabilir
  - VIEWER: salt okunur
  - Hafta sonları ve resmi tatiller otomatik hariç
  - Varsayılan görünüm: bu hafta + gelecek hafta (14 gün)

### How to run checks
- PHP syntax: `php -l <file>`
