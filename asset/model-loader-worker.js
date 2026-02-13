// model-loader-worker.js
// Web Worker untuk load model AI tanpa block UI thread

self.onmessage = async (event) => {
    const { action, modelUrl } = event.data;
    
    if(action === 'loadModels') {
        try {
            console.log('[Worker] Mulai load model dari:', modelUrl);
            
            // Import face-api library di worker
            importScripts('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js');
            
            // Load ketiga model secara parallel
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(modelUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
            ]);
            
            console.log('[Worker] ✅ Semua model berhasil dimuat');
            
            // Kirim pesan sukses ke main thread
            self.postMessage({
                status: 'success',
                message: 'Model berhasil dimuat di background'
            });
        } catch(error) {
            console.error('[Worker] ❌ Error:', error);
            
            // Kirim pesan error ke main thread
            self.postMessage({
                status: 'error',
                message: error.message
            });
        }
    }
};
