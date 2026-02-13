<?php
// Fungsi untuk check apakah semua model sudah tersedia
function checkModelsAvailable() {
    $modelsDir = __DIR__ . '/../models';
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
    
    $missingModels = [];
    foreach($requiredModels as $model) {
        if(!file_exists($modelsDir . '/' . $model)) {
            $missingModels[] = $model;
        }
    }
    
    return [
        'available' => count($missingModels) === 0,
        'missing' => $missingModels,
        'total' => count($requiredModels),
        'found' => count($requiredModels) - count($missingModels)
    ];
}

// Tampilkan banner jika model belum tersedia
$modelStatus = checkModelsAvailable();
if(!$modelStatus['available']) {
    echo '
    <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #92400e; padding: 16px 20px; margin: 10px; border-radius: 8px; border-left: 4px solid #d97706; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 20px; margin-top: 2px; flex-shrink: 0;"></i>
            <div>
                <strong>⚠️ Model AI Belum Di-Setup</strong>
                <p style="margin: 8px 0 0 0; font-size: 13px;">
                    Model face recognition belum tersimpan di server. Jalankan <strong><a href="setup-models.php" style="color: #92400e; text-decoration: underline; font-weight: 600;">setup-models.php</a></strong> 
                    untuk mengunduh model secara permanen.
                </p>
                <p style="margin: 6px 0 0 0; font-size: 12px; opacity: 0.8;">
                    Status: ' . $modelStatus['found'] . '/' . $modelStatus['total'] . ' model tersedia
                </p>
            </div>
        </div>
    </div>
    ';
}
?>
