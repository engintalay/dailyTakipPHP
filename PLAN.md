# dailyTakip v2 — Çoklu Proje & MySQL Geçiş Planı

> Bu belge yalnızca planlama amaçlıdır. Kodlama, proje yeni makineye kopyalanıp
> PHP/MySQL bilgileri paylaşıldıktan sonra başlayacaktır.

## 1. Amaç

Mevcut JSON dosya tabanlı tek ekipli `dailyTakip` uygulamasını; çoklu proje
desteği, hiyerarşik roller ve MySQL veritabanı ile çalışan geliştirilmiş bir
sürüme (v2) taşımak.

**Kritik kısıt:** Mevcut canlı uygulama çalışmaya devam etmeli. v2, eski
projenin geliştirilmiş hali olacak ve ayrı bir makinede geliştirilecek.

## 2. Netleşen Gereksinimler

### 2.1 Çoklu Proje (Ekip) Desteği
- Her "ekip" = "proje" olarak modellenir.
- Projeler birbirini **göremez** (tam veri izolasyonu).
- Tüm veri modülleri (daily notlar, durumlar, katılım, todo'lar,
  değerlendirmeler, nöbet, tatiller, jira işleri) proje bazında ayrılır.

### 2.2 Rol Hiyerarşisi
```
Süper Admin  (idari, en üst)
    │
    └── Proje Yöneticisi
            │
            └── Koordinatör
                    │
                    └── Kullanıcı (Member)
```

- **Süper Admin**
  - En üst seviye idari rol.
  - Proje açar, kullanıcı oluşturur, kullanıcıları projelere atar.
  - Rol/yetki ataması yapar (kimin proje yöneticisi, koordinatör, viewer
    olacağını belirler).
  - Viewer'ın hangi seviyede görüntüleme yapacağını belirler.
  - **Proje içi verilere (daily, değerlendirme vb.) erişemez.** Sadece idari
    işlemler yapar.

- **Proje Yöneticisi**
  - Mevcut ADMIN rolünün bir üst seviyesi.
  - Kendisine bağlı koordinatörlerin yönettiği tüm projeleri görür/yönetir.
  - Ek olarak kendisine atanan başka projeleri de görür/yönetir.
  - Birden fazla projede proje yöneticisi olabilir.

- **Koordinatör**
  - Proje Yöneticisi'nin bir alt seviyesi.
  - Birden fazla kullanıcıyı ve birden fazla projeyi yönetir.
  - Bir proje yöneticisine bağlıdır.
  - Mevcut ADMIN kullanıcılar bu seviyeye gelecek.

- **Kullanıcı (Member)**
  - Temel kullanıcı. Kendi işlemlerini yapar.

- **Viewer (Salt Okunur)**
  - Read-only kullanıcı.
  - Süper admin tarafından belirlenen seviyede görüntüleme yapar:
    - Proje yöneticisi seviyesinde (geniş görünüm), veya
    - Kullanıcı seviyesinde (dar görünüm).
  - Viewer yetki seviyesini **Süper Admin** belirler.

### 2.3 Çoklu Üyelik Kuralları
- Teknik olarak bir kullanıcı birden fazla projede yer alabilecek.
- Bir proje yöneticisi genel olarak birden fazla projede proje yöneticisi
  olabilir (beklenen senaryo).
- Bir kullanıcının karma rollerde olması (bir projede yönetici, diğerinde
  temel kullanıcı) **şimdilik engellenecek.** Altyapı destekleyecek şekilde
  tasarlanacak ama kullanım kısıtlanacak; ihtiyaç olursa açılacak.

### 2.4 Veri Taşıma (Migration)
- Mevcut tüm JSON verileri yeni bir **"Kovuşturma"** projesine aktarılacak.
- Mevcut kullanıcılar "Kovuşturma" projesine atanacak.
- Mevcut ADMIN kullanıcılar → **Koordinatör** rolüne map'lenecek.
- Mevcut MEMBER → Kullanıcı; mevcut VIEWER → Viewer.

## 3. Teknik Kararlar

### 3.1 Veritabanı
- JSON dosya tabanından **MySQL**'e geçiş.
- Gerekçe: ilişkisel yapı (proje-kullanıcı-rol ilişkileri), veri bütünlüğü
  (foreign key), eş zamanlı yazma güvenliği, sorgulama performansı.

### 3.2 Veri Erişim Katmanı
- **PDO wrapper** yaklaşımı (önerilen): mevcut fonksiyon imzaları korunur
  (`getDailyNotes()`, `createUser()`, `getEvaluations()` vb.), içleri MySQL/PDO
  ile yeniden yazılır. Böylece sayfa/katman değişikliği minimumda kalır.
- Fonksiyonlara proje bağlamı (`project_id`) parametresi / filtresi eklenir.

### 3.3 PHP Sürümü
- Yeni makinede güncel PHP sürümü kullanılacak (7.x / 8.x bekleniyor).
- Sürüm bilgisi paylaşılınca modern syntax (PDO, null coalescing, kısa dizi
  vb.) kullanım kararı netleşecek.
- NOT: Mevcut sürüm PHP 5.3 kısıtlarıyla yazılmış; yeni makinede bu kısıtlar
  kalkabilir.

### 3.4 Geliştirme Ortamı
- Geliştirme **ayrı bir makinede** yapılacak (canlı sistem korunur).
- Canlı `dailyTakip` hiç durmadan çalışmaya devam edecek.

## 4. MySQL Şema Taslağı (ön tasarım)

> Kesin şema, geliştirme başında netleştirilecek. Aşağıdaki taslaktır.

- **projects** — `id, name, slug, is_active, created_at`
- **users** — `id, name, email, password_hash, global_role (SUPER_ADMIN / NULL),
  is_active, created_at`
- **project_members** — kullanıcı-proje-rol ilişkisi:
  `id, project_id, user_id, role (PROJECT_MANAGER / COORDINATOR / MEMBER / VIEWER),
  viewer_scope (MANAGER / USER — sadece viewer için), created_at`
- **coordinator_manager** — koordinatör → proje yöneticisi bağı (hiyerarşi):
  `id, coordinator_user_id, manager_user_id`
  (veya project_members üzerinden türetme — tasarımda değerlendirilecek)
- **daily_notes** — mevcut alanlar + `project_id`
- **daily_statuses** — mevcut alanlar + `project_id`
- **attendances** — mevcut alanlar + `project_id`
- **todos** — mevcut alanlar + `project_id`
- **evaluation_periods** — mevcut alanlar + `project_id`
- **evaluation_criteria** — mevcut alanlar + `project_id` (+ weight alanı mevcut)
- **evaluations** — mevcut alanlar + `project_id`
- **on_call** (team + assignments) — proje bazlı
- **holidays** — proje bazlı veya global (karar verilecek)
- **jira_tasks** — mevcut alanlar + `project_id`

Her veri tablosuna `project_id` foreign key eklenerek izolasyon sağlanır.

## 5. Yetkilendirme Mantığı (ön tasarım)

- Oturum açan kullanıcının **aktif projesi** seçili olur (proje seçici UI).
- Tüm veri sorguları aktif `project_id` ile filtrelenir.
- Erişilebilir projeler kullanıcının rolüne göre belirlenir:
  - Süper Admin → idari paneller (veri yok)
  - Proje Yöneticisi → kendi projeleri + bağlı koordinatörlerin projeleri
  - Koordinatör → yönettiği projeler
  - Member → üyesi olduğu proje(ler)
  - Viewer → scope'una göre (manager/user seviyesi) read-only
- `auth.php` genişletilecek: `requireProjectAccess()`, `canManageProject()`,
  `getAccessibleProjects()` gibi yeni kontroller.

## 6. Geçiş Stratejisi (önerilen: kademeli)

1. **Aşama 0 — Hazırlık**
   - Proje yeni makineye kopyalanır.
   - PHP sürümü + MySQL erişim bilgileri paylaşılır.
   - Yeni dizin yolu belirlenir.

2. **Aşama 1 — MySQL'e geçiş (aynı özellikler)**
   - MySQL şeması oluşturulur (tek proje varsayımıyla).
   - PDO veri erişim katmanı yazılır (fonksiyon imzaları korunur).
   - JSON → MySQL migration scripti ("Kovuşturma" projesine aktarım).
   - Mevcut tüm özelliklerin MySQL üzerinde aynı çalıştığı doğrulanır.

3. **Aşama 2 — Çoklu proje & roller**
   - `projects`, `project_members`, hiyerarşi tabloları devreye alınır.
   - Veri sorgularına `project_id` filtresi eklenir.
   - Rol/yetki mantığı (`auth.php`) genişletilir.
   - Proje seçici UI eklenir.

4. **Aşama 3 — Süper Admin paneli**
   - Proje açma/kapatma.
   - Kullanıcı oluşturma + projelere atama.
   - Rol atama (proje yöneticisi / koordinatör / viewer + viewer scope).
   - Koordinatör → proje yöneticisi bağlama.

5. **Aşama 4 — Test & devreye alma**
   - Her rol için erişim senaryoları test edilir.
   - Proje izolasyonu doğrulanır.
   - Canlıya alım planı yapılır.

## 7. Yeni Makinede İlk Adımda Paylaşılacaklar

- [ ] PHP sürümü (örn: 8.1)
- [ ] MySQL erişim bilgileri (host, db adı, kullanıcı, şifre) veya "oluştur" talebi
- [ ] Projenin kopyalandığı yeni dizin yolu

## 8. Açık Konular / Sonra Karar Verilecekler

- `holidays` (tatiller) proje bazlı mı yoksa global mi olacak?
- Koordinatör-proje yöneticisi hiyerarşisi ayrı tablo mı yoksa
  `project_members` üzerinden mi türetilecek?
- Viewer scope'unun (manager/user) ekranlara tam yansıması.
- Çoklu üyelik kısıtının (karma rol engeli) uygulama katmanında nasıl
  zorlanacağı.
- Mevcut `impersonate` (kullanıcı taklidi) özelliğinin yeni rol yapısıyla
  ilişkisi.

---
*Son güncelleme: planlama aşaması. Kodlama henüz başlamadı.*
