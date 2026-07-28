## Project: dailyTakip (PHP 5.3)

### PHP Version Notes
- **Target**: PHP 5.3 — no `??`, no `[]` short arrays, no `array_column`,
  no `func()[0]` dereference, no `JSON_UNESCAPED_UNICODE`/`JSON_PRETTY_PRINT`,
  no `cal_days_in_month`
- **Server runs PHP 8.0+** — `create_function()` kaldırıldığı için
  `unicodeJsonEncode`'da named callback (`_unicodeJsonReplace`) kullanıldı
- Tüm `create_function` kullanımları temizlendi

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
