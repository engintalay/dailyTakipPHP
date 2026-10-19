<?php
/**
 * Performans Değerlendirme Veri Migrasyon Scripti
 * Eski puanlamayı (1-5) yeni puanlamaya (0-10) dönüştürür
 * Eski kriterleri yeni yapıya uyarlar
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Performans Değerlendirme Migrasyon Scripti ===\n\n";

// 1. Kriter migrasyonu - eski kriterleri yeni yapıya aktar
echo "1. Eski kriterleri yeni yapıya dönüştürme...\n";
$oldCriteria = getEvaluationCriteria();
$newCriteria = array();

// Yeni kriter definisyonları (ağırlıklı)
$weightMapping = array(
    'İş teslimatı ve hedeflere ulaşma' => 20,
    'Teknik kalite ve yetkinlik' => 20,
    'Test, güvenilirlik ve hata yönetimi' => 15,
    'Problem çözme ve iyileştirme' => 10,
    'İş birliği ve iletişim' => 15,
    'Güvenlik ve standartlara uyum' => 10,
    'Öğrenme ve gelişim' => 10,
);

// Mevcut kriterleri oku
$criteriaFile = DB_DIR . 'evaluation_criteria.json';
$criteriaData = loadJson($criteriaFile);

echo "   Mevcut kriter sayısı: " . count($criteriaData) . "\n";

// Yeni kriter dosyası oluştur (zaten mevcutsa güncelle)
$newCriteriaData = array();

// Yeni standart kriterleri ekle
$standardCriteria = array(
    array(
        'id' => 'criterion_1',
        'name' => 'İş teslimatı ve hedeflere ulaşma',
        'description' => 'Proje ve görevlerin zamanında, kaliteli ve hedeflere uygun tamamlanması',
        'weight' => 20,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_2',
        'name' => 'Teknik kalite ve yetkinlik',
        'description' => 'Profesyonel teknik bilgi, beceri ve uzmanlık düzeyi',
        'weight' => 20,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_3',
        'name' => 'Test, güvenilirlik ve hata yönetimi',
        'description' => 'Kod kalitesi, test coverage, hata tespit ve çözüm yaklaşımları',
        'weight' => 15,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_4',
        'name' => 'Problem çözme ve iyileştirme',
        'description' => 'Kritik düşünme, çözüm odaklı yaklaşım ve Süreklilik iyileştirme',
        'weight' => 10,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_5',
        'name' => 'İş birliği ve iletişim',
        'description' => 'Takım içi iş birliği, açık iletişim ve bilgi paylaşımı',
        'weight' => 15,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_6',
        'name' => 'Güvenlik ve standartlara uyum',
        'description' => 'Güvenlik bilinci, standartlara uyum ve best practice\'ler',
        'weight' => 10,
        'created_at' => '2026-10-19 08:50:00'
    ),
    array(
        'id' => 'criterion_7',
        'name' => 'Öğrenme ve gelişim',
        'description' => 'Kendi gelişimi için girişimcilik, öğrenme isteği ve paylaşma',
        'weight' => 10,
        'created_at' => '2026-10-19 08:50:00'
    ),
);

// Eski kriterleri yeni kriterlerle eşleştir
$legacyCriteria = array();
foreach ($criteriaData as $c) {
    $found = false;
    foreach ($standardCriteria as $newC) {
        // Eski kriter adını yeni kriter adıyla eşleştir
        if (strpos($c['name'], 'Takım') !== false && strpos($newC['name'], 'İş birliği') !== false) {
            $legacyCriteria[$c['id']] = $newC['id'];
            $found = true;
            echo "   Eşleştirildi: '{$c['name']}' -> '{$newC['name']}'\n";
            break;
        } elseif (strpos($c['name'], 'İletişim') !== false && strpos($newC['name'], 'İş birliği') !== false) {
            $legacyCriteria[$c['id']] = $newC['id'];
            $found = true;
            echo "   Eşleştirildi: '{$c['name']}' -> '{$newC['name']}'\n";
            break;
        } elseif (strpos($c['name'], 'Öğrenme') !== false && strpos($newC['name'], 'Öğrenme') !== false) {
            $legacyCriteria[$c['id']] = $newC['id'];
            $found = true;
            echo "   Eşleştirildi: '{$c['name']}' -> '{$newC['name']}'\n";
            break;
        } elseif (strpos($c['name'], 'Sorumluluk') !== false && strpos($newC['name'], 'İş teslimatı') !== false) {
            $legacyCriteria[$c['id']] = $newC['id'];
            $found = true;
            echo "   Eşleştirildi: '{$c['name']}' -> '{$newC['name']}'\n";
            break;
        } elseif (strpos($c['name'], 'İş kalitesi') !== false && strpos($newC['name'], 'Teknik') !== false) {
            $legacyCriteria[$c['id']] = $newC['id'];
            $found = true;
            echo "   Eşleştirildi: '{$c['name']}' -> '{$newC['name']}'\n";
            break;
        }
    }
    if (!$found) {
        echo "   Eşleşmeden bırakıldı: '{$c['name']}'\n";
    }
}

// Yeni kriter dosyasını kaydet
saveJson($criteriaFile, $standardCriteria);
echo "   Yeni kriterler kaydedildi.\n\n";

// 2. Değerlendirme puanlarını 0-10 ölçeğine dönüştür
echo "2. Değerlendirme puanlarını dönüştürme (1-5 -> 0-10)...\n";
$evaluationsFile = DB_DIR . 'evaluations.json';
$evaluationsData = loadJson($evaluationsFile);

$migratedCount = 0;
foreach ($evaluationsData as &$e) {
    if (empty($e['scores'])) continue;
    
    $newScores = array();
    foreach ($e['scores'] as $criterionId => $score) {
        // Eski puanlamayı (1-5) yeni puanlamaya (0-10) dönüştür
        // 1 -> 0, 2 -> 2.5, 3 -> 5, 4 -> 7.5, 5 -> 10
        $oldScore = (int)$score;
        $newScore = ($oldScore - 1) * 2.5; // 1->0, 2->2.5, 3->5, 4->7.5, 5->10
        $newScores[$criterionId] = round($newScore, 0);
    }
    
    $e['scores'] = $newScores;
    $migratedCount++;
}

saveJson($evaluationsFile, $evaluationsData);
echo "   $migratedCount değerlendirme puanı dönüştürüldü.\n\n";

// 3. Özet bilgi
echo "=== Migrasyon Tamamlandı ===\n";
echo "Yeni kriter sayısı: 7\n";
echo "Yeni puan aralığı: 0-10\n";
echo "Rating ölçekleri:\n";
echo "  0-2: Çok yetersiz\n";
echo "  3-4: Gelişime açık\n";
echo "  5-6: Beklentileri karşılıyor\n";
echo "  7-8: Beklentilerin üzerinde\n";
echo "  9-10: Üstün performans\n";
echo "\nAğırlıklı puan hesaplama:\n";
echo "  Puan x Ağırlık / 100 toplamı\n";
echo "  Örn: (8x20 + 7x20 + 6x15 + 8x10 + 7x15 + 9x10 + 8x10) / 100 = 7.55\n";
