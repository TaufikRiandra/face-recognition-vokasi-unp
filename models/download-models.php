<?php
set_time_limit(300); // 5 menit timeout

header('Content-Type: text/plain');

$modelsDir = __DIR__;

// Multiple CDN sources untuk fallback
$cdnSources = [
    'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/',
    'https://unpkg.com/face-api.js@0.22.2/models/',
];

// Model dengan format yang berbeda di setiap CDN
$requiredModels = [
    'ssd_mobilenetv1_model-shard1',
    'ssd_mobilenetv1_model-shard2',
    'ssd_mobilenetv1_model-weights_manifest.json',
    'face_landmark_68_model-shard1',
    'face_landmark_68_model-weights_manifest.json',
    'face_recognition_model-shard1',
    'face_recognition_model-shard2',
    'face_recognition_model-weights_manifest.json'
];

$success = 0;
$failed = 0;
$failedModels = [];

foreach($requiredModels as $model) {
    $fullDestPath = $modelsDir . '/' . $model;
    
    if(file_exists($fullDestPath)) {
        echo "[✓] Sudah ada: $model\n";
        $success++;
        continue;
    }
    
    echo "[⏳] Mengunduh: $model ... ";
    ob_flush();
    flush();
    
    $downloaded = false;
    $lastError = '';
    
    // Coba dari setiap CDN source
    foreach($cdnSources as $idx => $cdnUrl) {
        if($downloaded) break;
        
        $fullUrl = $cdnUrl . $model;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]);
        
        $modelData = @file_get_contents($fullUrl, false, $context);
        
        if($modelData !== false && strlen($modelData) > 0) {
            if(file_put_contents($fullDestPath, $modelData) !== false) {
                echo "OK (" . round(strlen($modelData) / 1024 / 1024, 2) . " MB)\n";
                $success++;
                $downloaded = true;
            } else {
                $lastError = "Gagal simpan file";
            }
        } else {
            $lastError = "CDN-" . ($idx + 1) . " tidak tersedia";
            continue;
        }
    }
    
    if(!$downloaded) {
        echo "GAGAL ($lastError)\n";
        $failed++;
        $failedModels[] = $model;
    }
}

echo "\n=== HASIL ===\n";
echo "✅ Berhasil: $success\n";
echo "❌ Gagal: $failed\n";
echo "\nModel ready di: $modelsDir\n";

if($failed > 0) {
    echo "\n⚠️  MODEL YANG GAGAL:\n";
    foreach($failedModels as $m) {
        echo "  - $m\n";
    }
    echo "\n💡 SOLUSI:\n";
    echo "1. Coba jalankan setup lagi\n";
    echo "2. Jika masih gagal, gunakan Web Browser untuk download manual:\n";
    foreach($cdnSources as $cdnIdx => $cdn) {
        echo "   CDN-" . ($cdnIdx + 1) . ": {$cdn}\n";
        echo "   Simpan file ke: $modelsDir/\n";
    }
    echo "3. Atau hubungi administrator\n";
} else {
    echo "\n✅ SEMUA MODEL BERHASIL DIUNDUH!\n";
}
?>
