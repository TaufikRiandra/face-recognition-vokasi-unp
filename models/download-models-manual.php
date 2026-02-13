<?php
// models/download-models-manual.php
// File ini menyediakan cara manual untuk download model jika CDN gagal

set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Model Manual</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.6;
        }
        .model-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .model-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background: #fafafa;
        }
        .model-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 13px;
            word-break: break-all;
        }
        .model-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .link-btn {
            background: #2196F3;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .link-btn:hover { background: #1976D2; }
        .link-btn.copy-btn {
            background: #4CAF50;
        }
        .link-btn.copy-btn:hover {
            background: #45a049;
        }
        .instruction {
            background: #fff9c4;
            border-left: 4px solid #FBC02D;
            padding: 15px;
            margin-top: 30px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.6;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Download Model Manual</h1>
        <p class="subtitle">Jika setup otomatis gagal, gunakan link di bawah untuk download manual</p>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Catatan:</strong> Jika CDN di bawah juga tidak bisa diakses, mungkin ada masalah koneksi internet atau firewall. 
            Hubungi server administrator untuk bantuan lebih lanjut.
        </div>

        <h2 style="margin-bottom: 15px; font-size: 18px;">📥 Link Download</h2>

        <div class="model-grid">
            <?php
            $models = [
                [
                    'name' => 'ssd_mobilenetv1_model-shard1',
                    'urls' => [
                        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/ssd_mobilenetv1_model-shard1',
                        'unpkg' => 'https://unpkg.com/face-api.js@0.22.2/models/ssd_mobilenetv1_model-shard1'
                    ]
                ],
                [
                    'name' => 'ssd_mobilenetv1_model-shard2',
                    'urls' => [
                        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/ssd_mobilenetv1_model-shard2',
                        'unpkg' => 'https://unpkg.com/face-api.js@0.22.2/models/ssd_mobilenetv1_model-shard2'
                    ]
                ],
                [
                    'name' => 'ssd_mobilenetv1_model-weights_manifest.json',
                    'urls' => [
                        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/ssd_mobilenetv1_model-weights_manifest.json',
                        'unpkg' => 'https://unpkg.com/face-api.js@0.22.2/models/ssd_mobilenetv1_model-weights_manifest.json'
                    ]
                ],
                [
                    'name' => 'face_landmark_68_model-shard1',
                    'urls' => [
                        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/face_landmark_68_model-shard1',
                        'unpkg' => 'https://unpkg.com/face-api.js@0.22.2/models/face_landmark_68_model-shard1'
                    ]
                ],
                [
                    'name' => 'face_landmark_68_model-weights_manifest.json',
                    'urls' => [
                        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/face_landmark_68_model-weights_manifest.json',
                        'unpkg' => 'https://unpkg.com/face-api.js@0.22.2/models/face_landmark_68_model-weights_manifest.json'
                    ]
                ]
            ];

            foreach($models as $model):
            ?>
            <div class="model-card">
                <div class="model-name"><?= $model['name'] ?></div>
                <div class="model-links">
                    <?php foreach($model['urls'] as $cdn => $url): ?>
                    <a href="<?= $url ?>" target="_blank" class="link-btn">
                        <i class="fas fa-download"></i>
                        Download (<?= $cdn ?>)
                    </a>
                    <?php endforeach; ?>
                    <button class="link-btn copy-btn" onclick="copyFilename('<?= $model['name'] ?>')">
                        <i class="fas fa-copy"></i>
                        Copy nama file
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="instruction">
            <strong>📋 Cara Menggunakan:</strong>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Klik "Download" untuk setiap file di atas</li>
                <li>Simpan file ke folder: <code><?= __DIR__ ?>/</code></li>
                <li>Pastikan nama file PERSIS sama seperti yang ditampilkan</li>
                <li>Refresh halaman <code>setup-models.php</code> untuk verifikasi</li>
                <li>Atau jalankan <code>download-models.php</code> lagi</li>
            </ol>
        </div>
    </div>

    <script>
    function copyFilename(name) {
        navigator.clipboard.writeText(name).then(() => {
            alert('Nama file disalin: ' + name);
        });
    }
    </script>
</body>
</html>
