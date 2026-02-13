<?php
session_start();
include 'backend/koneksi.php';

// Check login
if(!isset($_SESSION['admin_id'])){
  header("Location: admin/login.php");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Model Face Recognition</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .header-icon {
            font-size: 40px;
            color: #667eea;
        }

        h1 {
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .description {
            background: #f0f9ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            font-size: 14px;
            color: #0c4a6e;
            line-height: 1.6;
        }

        .model-list {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            max-height: 300px;
            overflow-y: auto;
        }

        .model-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .model-item:last-child {
            border-bottom: none;
        }

        .model-icon {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            font-size: 12px;
            flex-shrink: 0;
        }

        .model-icon.pending {
            background: #94a3b8;
        }

        .model-icon.success {
            background: #10b981;
        }

        .model-icon.error {
            background: #ef4444;
        }

        .model-name {
            flex: 1;
            font-family: monospace;
            font-size: 12px;
            color: #475569;
        }

        .model-size {
            color: #94a3b8;
            font-size: 11px;
        }

        button {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .status {
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }

        .status.show {
            display: block;
        }

        .status.loading {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .status.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .log-output {
            background: #1e293b;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
            line-height: 1.5;
        }

        .log-output p {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon"><i class="fas fa-cog"></i></div>
            <div>
                <h1>Setup Model</h1>
                <p class="subtitle">Face Recognition System</p>
            </div>
        </div>

        <div class="description">
            <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
            Halaman ini akan mengunduh dan menyimpan model ML ke folder server secara <strong>permanent</strong>. 
            Model tidak akan hilang meskipun user clear browser cache. Ini hanya perlu dilakukan sekali saat setup awal.
        </div>

        <div class="model-list" id="modelList">
            <!-- Model list akan di-generate via JavaScript -->
        </div>

        <button id="downloadBtn" onclick="startDownload()">
            <i class="fas fa-download"></i>
            🚀 Mulai Download Model
        </button>

        <div class="status loading" id="status">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-spinner fa-spin"></i>
                <div id="statusText">Mempersiapkan...</div>
            </div>
        </div>

        <div class="log-output" id="logOutput"></div>

        <div class="stats" id="stats" style="display: none;">
            <div class="stat-box">
                <div class="stat-number" id="successCount">0</div>
                <div class="stat-label">Berhasil</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" id="failedCount">0</div>
                <div class="stat-label">Gagal</div>
            </div>
        </div>
    </div>

    <script>
    const models = [
        { name: 'ssd_mobilenetv1_model-shard1', size: '15 MB' },
        { name: 'ssd_mobilenetv1_model-shard2', size: '8 MB' },
        { name: 'ssd_mobilenetv1_model-weights_manifest.json', size: '30 KB' },
        { name: 'face_landmark_68_model-shard1', size: '25 MB' },
        { name: 'face_landmark_68_model-weights_manifest.json', size: '20 KB' },
        { name: 'face_recognition_model-shard1', size: '32 MB' },
        { name: 'face_recognition_model-shard2', size: '18 MB' },
        { name: 'face_recognition_model-weights_manifest.json', size: '25 KB' }
    ];

    // Generate model list
    function generateModelList() {
        const html = models.map(m => `
            <div class="model-item">
                <div class="model-icon pending"><i class="fas fa-clock"></i></div>
                <div class="model-name">${m.name}</div>
                <div class="model-size">${m.size}</div>
            </div>
        `).join('');
        document.getElementById('modelList').innerHTML = html;
    }

    generateModelList();

    async function startDownload() {
        const btn = document.getElementById('downloadBtn');
        const statusDiv = document.getElementById('status');
        const logDiv = document.getElementById('logOutput');

        btn.disabled = true;
        statusDiv.classList.add('show');
        statusDiv.classList.remove('error', 'success');
        statusDiv.classList.add('loading');
        document.getElementById('statusText').textContent = '⏳ Mengunduh model... (ini bisa memakan waktu 2-5 menit)';
        logDiv.innerHTML = '';

        try {
            // Setup EventSource untuk real-time updates
            const response = await fetch('models/download-models.php');
            const text = await response.text();

            // Parse output dan update UI
            const lines = text.split('\n');
            let successCount = 0;
            let failedCount = 0;

            lines.forEach(line => {
                if(line.trim()) {
                    // Add log output
                    const p = document.createElement('p');
                    p.textContent = line;
                    logDiv.appendChild(p);

                    // Count results
                    if(line.includes('✓')) successCount++;
                    if(line.includes('GAGAL')) failedCount++;

                    logDiv.scrollTop = logDiv.scrollHeight;
                }
            });

            if(response.ok && failedCount === 0) {
                statusDiv.classList.remove('loading');
                statusDiv.classList.add('success');
                document.getElementById('statusText').innerHTML = '<i class="fas fa-check-circle"></i> ✅ Semua model berhasil diunduh dan disimpan!';
                
                // Show stats
                document.getElementById('successCount').textContent = successCount;
                document.getElementById('failedCount').textContent = failedCount;
                document.getElementById('stats').style.display = 'grid';
            } else if(failedCount > 0) {
                statusDiv.classList.remove('loading');
                statusDiv.classList.add('error');
                document.getElementById('statusText').innerHTML = `<i class="fas fa-exclamation-circle"></i> ⚠️ ${failedCount} model gagal diunduh.<br><small>Opsi: <a href="models/download-models-manual.php" target="_blank" style="color: #fff; text-decoration: underline; font-weight: 600;">Download manual</a> | Coba lagi</small>`;
                
                // Show stats
                document.getElementById('successCount').textContent = successCount;
                document.getElementById('failedCount').textContent = failedCount;
                document.getElementById('stats').style.display = 'grid';
            }
        } catch(err) {
            statusDiv.classList.remove('loading');
            statusDiv.classList.add('error');
            document.getElementById('statusText').innerHTML = `<i class="fas fa-times-circle"></i> ❌ Error: ${err.message}`;
        } finally {
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
