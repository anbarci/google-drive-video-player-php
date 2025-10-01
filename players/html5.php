<?php
// HTML5 Player for Google Drive Videos
// API Key gerektirmez, iframe embed yöntemini kullanır

// Video verisini al ve decode et
$videoData = null;
if (isset($_GET['data'])) {
    $decodedData = base64_decode($_GET['data']);
    $videoData = json_decode($decodedData, true);
}

if (!$videoData || !isset($videoData['id'])) {
    die('Geçersiz video verisi.');
}

// Güvenlik kontrolleri
$videoId = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoData['id']);
$videoTitle = htmlspecialchars($videoData['title'] ?? 'Google Drive Video', ENT_QUOTES, 'UTF-8');
$posterUrl = filter_var($videoData['poster'] ?? '', FILTER_VALIDATE_URL) ? $videoData['poster'] : "https://lh3.googleusercontent.com/d/{$videoId}";

// Google Drive embed URL'si oluştur (API Key gerektirmez)
$embedUrl = "https://drive.google.com/file/d/{$videoId}/preview";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $videoTitle ?> - HTML5 Player</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            background: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            overflow: hidden;
        }
        
        .player-container {
            width: 100%;
            height: 100vh;
            position: relative;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            background: #000;
        }
        
        .html5-video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
        
        .embedded-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
        }
        
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .play-button {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            pointer-events: all;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .play-button:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        
        .play-button i {
            font-size: 24px;
            color: #333;
            margin-left: 3px;
        }
        
        .video-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 14px;
            z-index: 1000;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .video-info:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: translateY(-2px);
        }
        
        .controls-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        
        .controls-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .controls-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .time-display {
            font-size: 14px;
            color: #ccc;
            min-width: 100px;
            text-align: center;
        }
        
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .volume-slider {
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        
        .volume-progress {
            height: 100%;
            background: #fff;
            border-radius: 2px;
            width: 70%;
            transition: width 0.3s ease;
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 999;
            background: rgba(0, 0, 0, 0.8);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .loading i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
            color: #4CAF50;
            animation: rotate 2s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 999;
            background: rgba(244, 67, 54, 0.15);
            color: #f44336;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid rgba(244, 67, 54, 0.3);
            backdrop-filter: blur(10px);
            max-width: 400px;
        }
        
        .error-message h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .error-message ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }
        
        .error-message li {
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .error-message li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #4CAF50;
        }
        
        .hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .video-info {
                top: 10px;
                left: 10px;
                font-size: 12px;
                padding: 10px 15px;
            }
            
            .controls-bar {
                padding: 15px;
            }
            
            .control-btn {
                font-size: 16px;
                width: 35px;
                height: 35px;
            }
            
            .play-button {
                width: 60px;
                height: 60px;
            }
            
            .play-button i {
                font-size: 20px;
            }
            
            .volume-control {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .controls-left,
            .controls-right {
                gap: 10px;
            }
            
            .time-display {
                font-size: 12px;
                min-width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <div class="video-info">
            <strong><i class="fas fa-video"></i> <?= $videoTitle ?></strong><br>
            <small><i class="fab fa-html5"></i> HTML5 Player - Google Drive Embed</small>
        </div>
        
        <div class="loading" id="loading">
            <i class="fas fa-cog"></i>
            <div><strong>Video Hazırlanıyor</strong></div>
            <small>Google Drive'dan yüklüyor...</small>
        </div>
        
        <div class="error-message" id="error" style="display: none;">
            <h3><i class="fas fa-exclamation-triangle"></i> Video Yüklenemedi</h3>
            <p>Google Drive videosuna erişim sağlanamıyor.</p>
            <ul>
                <li>Video bağlantısını kontrol edin</li>
                <li>Videonun halka açık olduğundan emin olun</li>
                <li>Sayfayı yenilemeyi deneyin</li>
                <li>Farklı bir tarayıcı deneyin</li>
            </ul>
        </div>
        
        <div class="video-wrapper" id="videoWrapper" style="display: none;">
            <!-- HTML5 Video Element -->
            <iframe
                id="videoPlayer"
                class="embedded-iframe"
                src="<?= htmlspecialchars($embedUrl) ?>?autoplay=0&amp;controls=1&amp;modestbranding=1&amp;showinfo=0&amp;rel=0&amp;iv_load_policy=3"
                allowfullscreen
                allowtransparency
                allow="autoplay; encrypted-media; picture-in-picture; web-share"
                loading="lazy"
            ></iframe>
            
            <!-- Custom Controls Overlay -->
            <div class="controls-bar" id="controlsBar">
                <div class="controls-left">
                    <button class="control-btn" id="playPauseBtn" title="Oynat/Duraklat">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="time-display" id="timeDisplay">
                        <span id="currentTime">0:00</span> / <span id="duration">0:00</span>
                    </div>
                </div>
                
                <div class="controls-right">
                    <div class="volume-control">
                        <button class="control-btn" id="muteBtn" title="Sessiz">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <div class="volume-slider" id="volumeSlider">
                            <div class="volume-progress" id="volumeProgress"></div>
                        </div>
                    </div>
                    <button class="control-btn" id="fullscreenBtn" title="Tam Ekran">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const videoWrapper = document.getElementById('videoWrapper');
            const videoPlayer = document.getElementById('videoPlayer');
            const controlsBar = document.getElementById('controlsBar');
            const videoInfo = document.querySelector('.video-info');
            
            let isPlaying = false;
            let isMuted = false;
            let volume = 0.7;
            let controlsTimeout;
            
            // Video yüklenme simülasyonu
            setTimeout(() => {
                loading.style.display = 'none';
                videoWrapper.style.display = 'block';
                console.log('HTML5 Player hazır');
            }, 2000);
            
            // iframe yüklenme kontrolü
            videoPlayer.onload = function() {
                console.log('Google Drive video iframe yüklendi');
            };
            
            videoPlayer.onerror = function() {
                console.error('Video iframe yüklenemedi');
                loading.style.display = 'none';
                videoWrapper.style.display = 'none';
                error.style.display = 'block';
            };
            
            // Kontroller
            const playPauseBtn = document.getElementById('playPauseBtn');
            const muteBtn = document.getElementById('muteBtn');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const volumeSlider = document.getElementById('volumeSlider');
            const volumeProgress = document.getElementById('volumeProgress');
            
            // Play/Pause button (simülasyon)
            playPauseBtn.addEventListener('click', function() {
                isPlaying = !isPlaying;
                const icon = this.querySelector('i');
                if (isPlaying) {
                    icon.className = 'fas fa-pause';
                    this.title = 'Duraklat';
                } else {
                    icon.className = 'fas fa-play';
                    this.title = 'Oynat';
                }
                console.log(isPlaying ? 'Video oynatılıyor' : 'Video duraklataldı');
            });
            
            // Mute button
            muteBtn.addEventListener('click', function() {
                isMuted = !isMuted;
                const icon = this.querySelector('i');
                if (isMuted) {
                    icon.className = 'fas fa-volume-mute';
                    this.title = 'Sesi Aç';
                    volumeProgress.style.width = '0%';
                } else {
                    icon.className = 'fas fa-volume-up';
                    this.title = 'Sessiz';
                    volumeProgress.style.width = (volume * 100) + '%';
                }
                console.log(isMuted ? 'Ses kapatıldı' : 'Ses açıldı');
            });
            
            // Fullscreen button
            fullscreenBtn.addEventListener('click', function() {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                    this.querySelector('i').className = 'fas fa-expand';
                    this.title = 'Tam Ekran';
                } else {
                    document.documentElement.requestFullscreen();
                    this.querySelector('i').className = 'fas fa-compress';
                    this.title = 'Tam Ekrandan Çık';
                }
            });
            
            // Volume slider
            volumeSlider.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const width = rect.width;
                volume = clickX / width;
                
                if (!isMuted) {
                    volumeProgress.style.width = (volume * 100) + '%';
                }
                
                console.log('Ses seviyesi: ' + Math.round(volume * 100) + '%');
            });
            
            // Klavye kısayolları
            document.addEventListener('keydown', function(e) {
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        playPauseBtn.click();
                        break;
                    case 'f':
                    case 'F':
                        fullscreenBtn.click();
                        break;
                    case 'm':
                    case 'M':
                        muteBtn.click();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        volume = Math.min(1, volume + 0.1);
                        if (!isMuted) {
                            volumeProgress.style.width = (volume * 100) + '%';
                        }
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        volume = Math.max(0, volume - 0.1);
                        if (!isMuted) {
                            volumeProgress.style.width = (volume * 100) + '%';
                        }
                        break;
                }
                showControls();
            });
            
            // Kontroller otomatik gizleme
            function hideControls() {
                controlsBar.classList.add('hidden');
                videoInfo.classList.add('hidden');
            }
            
            function showControls() {
                controlsBar.classList.remove('hidden');
                videoInfo.classList.remove('hidden');
                clearTimeout(controlsTimeout);
                controlsTimeout = setTimeout(hideControls, 3000);
            }
            
            // Mouse/touch events
            document.addEventListener('mousemove', showControls);
            document.addEventListener('touchstart', showControls);
            document.addEventListener('click', showControls);
            
            // Başlangıçta kontrolleri göster
            showControls();
            
            // Zaman güncelleme simülasyonu
            let currentTimeSeconds = 0;
            const totalTimeSeconds = 0; // Bilinmiyor, iframe içinde
            
            function updateTime() {
                if (isPlaying) {
                    currentTimeSeconds++;
                }
                
                const currentMin = Math.floor(currentTimeSeconds / 60);
                const currentSec = currentTimeSeconds % 60;
                const currentTimeStr = currentMin + ':' + (currentSec < 10 ? '0' : '') + currentSec;
                
                document.getElementById('currentTime').textContent = currentTimeStr;
                
                if (totalTimeSeconds > 0) {
                    const totalMin = Math.floor(totalTimeSeconds / 60);
                    const totalSec = totalTimeSeconds % 60;
                    const totalTimeStr = totalMin + ':' + (totalSec < 10 ? '0' : '') + totalSec;
                    document.getElementById('duration').textContent = totalTimeStr;
                } else {
                    document.getElementById('duration').textContent = '--:--';
                }
            }
            
            setInterval(updateTime, 1000);
            
            // Debug bilgileri
            console.log('%cGoogle Drive Video Player - HTML5 Edition', 'color: #4CAF50; font-size: 16px; font-weight: bold;');
            console.log('Video ID: <?= $videoId ?>');
            console.log('Video Title: <?= $videoTitle ?>');
            console.log('Embed Method: Google Drive Iframe (No API Key)');
            console.log('Player Type: HTML5 Custom Controls + Iframe');
            
            // Responsive handling
            function handleResize() {
                console.log('Ekran boyutu değişti:', window.innerWidth + 'x' + window.innerHeight);
            }
            
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>