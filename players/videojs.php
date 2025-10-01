<?php
// Video.js Player for Google Drive Videos
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
    <title><?= $videoTitle ?> - Video.js Player</title>
    
    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.3.0/video-js.css" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            background: #1a1a1a;
            font-family: Arial, sans-serif;
        }
        
        .player-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
        }
        
        .video-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            max-width: 100vw;
            max-height: 100vh;
        }
        
        .video-js {
            width: 100% !important;
            height: 100% !important;
        }
        
        .video-info {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.8);
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .video-info:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translateY(-2px);
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 18px;
            z-index: 999;
            text-align: center;
        }
        
        .loading i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6b6b;
            text-align: center;
            font-size: 16px;
            z-index: 999;
            background: rgba(255, 107, 107, 0.1);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        
        /* Video.js custom styling */
        .video-js .vjs-big-play-button {
            background-color: rgba(43, 108, 176, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            line-height: 76px;
            font-size: 28px;
        }
        
        .video-js .vjs-big-play-button:hover {
            background-color: rgba(43, 108, 176, 1);
            border-color: #fff;
        }
        
        .video-js .vjs-control-bar {
            background: linear-gradient(180deg, transparent, rgba(0,0,0,0.8));
            height: 60px;
        }
        
        .video-js .vjs-progress-control {
            height: 8px;
        }
        
        .video-js .vjs-play-progress {
            background: linear-gradient(90deg, #2b6cb0, #3182ce);
        }
        
        .embedded-video {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        @media (max-width: 768px) {
            .video-info {
                top: 10px;
                left: 10px;
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .video-js .vjs-big-play-button {
                width: 60px;
                height: 60px;
                line-height: 56px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <div class="video-info">
            <strong><?= $videoTitle ?></strong><br>
            <small><i class="fab fa-google-drive"></i> Video.js Player - API Key Free</small>
        </div>
        
        <div class="loading" id="loading">
            <i class="fas fa-spinner"></i>
            <div>Video hazırlanıyor...</div>
        </div>
        
        <div class="error-message" id="error" style="display: none;">
            <h3><i class="fas fa-exclamation-triangle"></i> Video Yüklenemedi</h3>
            <p>Video dosyasına erişilemiyor. Lütfen:</p>
            <ul style="text-align: left; margin-top: 10px;">
                <li>Videonun herkese açık olduğundan emin olun</li>
                <li>Drive linkinin doğru olduğunu kontrol edin</li>
                <li>Sayfayı yenilemeyi deneyin</li>
            </ul>
        </div>
        
        <div class="video-wrapper" id="videoWrapper" style="display: none;">
            <!-- Video.js Player -->
            <video
                id="videoPlayer"
                class="video-js vjs-default-skin"
                controls
                preload="auto"
                poster="<?= htmlspecialchars($posterUrl) ?>"
                data-setup="{}"
            >
                <!-- Google Drive iframe as fallback -->
                <iframe
                    class="embedded-video"
                    src="<?= htmlspecialchars($embedUrl) ?>?autoplay=0&amp;loop=0&amp;controls=1&amp;modestbranding=1"
                    allowfullscreen
                    allowtransparency
                    allow="autoplay; encrypted-media; picture-in-picture"
                ></iframe>
                <p class="vjs-no-js">
                    Videoyu oynatmak için lütfen JavaScript'i etkinleştirin.
                    <a href="https://videojs.com/html5-video-support/" target="_blank">Video.js desteği</a>
                </p>
            </video>
        </div>
    </div>
    
    <!-- Video.js JS -->
    <script src="https://vjs.zencdn.net/8.3.0/video.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const videoWrapper = document.getElementById('videoWrapper');
            
            // Video.js player'ı başlat
            const player = videojs('videoPlayer', {
                controls: true,
                fluid: true,
                responsive: true,
                playbackRates: [0.5, 1, 1.25, 1.5, 2],
                plugins: {},
                html5: {
                    vhs: {
                        overrideNative: true
                    }
                }
            });
            
            // Player hazır olduğunda
            player.ready(function() {
                console.log('Video.js player hazır');
                
                // Birkaç saniye sonra player'ı göster
                setTimeout(() => {
                    loading.style.display = 'none';
                    videoWrapper.style.display = 'block';
                }, 1500);
                
                // Custom kontroller ekle
                this.on('play', function() {
                    console.log('Video oynatılmaya başlandı');
                });
                
                this.on('pause', function() {
                    console.log('Video duraklataldı');
                });
                
                this.on('ended', function() {
                    console.log('Video sona erdi');
                });
                
                this.on('error', function() {
                    console.error('Video.js hatası');
                    loading.style.display = 'none';
                    videoWrapper.style.display = 'none';
                    error.style.display = 'block';
                });
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (!player) return;
                
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        if (player.paused()) {
                            player.play();
                        } else {
                            player.pause();
                        }
                        break;
                    case 'f':
                    case 'F':
                        if (player.isFullscreen()) {
                            player.exitFullscreen();
                        } else {
                            player.requestFullscreen();
                        }
                        break;
                    case 'm':
                    case 'M':
                        player.muted(!player.muted());
                        break;
                    case 'ArrowLeft':
                        player.currentTime(player.currentTime() - 10);
                        break;
                    case 'ArrowRight':
                        player.currentTime(player.currentTime() + 10);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        player.volume(Math.min(player.volume() + 0.1, 1));
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        player.volume(Math.max(player.volume() - 0.1, 0));
                        break;
                }
            });
            
            // Video info hide/show
            const videoInfo = document.querySelector('.video-info');
            let infoTimeout;
            
            function hideInfo() {
                if (videoInfo) {
                    videoInfo.style.opacity = '0';
                }
            }
            
            function showInfo() {
                if (videoInfo) {
                    videoInfo.style.opacity = '1';
                    clearTimeout(infoTimeout);
                    infoTimeout = setTimeout(hideInfo, 4000);
                }
            }
            
            document.addEventListener('mousemove', showInfo);
            document.addEventListener('touchstart', showInfo);
            
            // Başlangıçta bilgiyi göster
            showInfo();
            
            // Debug bilgileri
            console.log('Google Drive Video Player - Video.js Edition');
            console.log('Video ID: <?= $videoId ?>');
            console.log('Video Title: <?= $videoTitle ?>');
            console.log('API Key: Kullanılmıyor (Iframe Embed)');
            
            // Responsive kontrol
            function handleResize() {
                if (player) {
                    player.dimensions(window.innerWidth, window.innerHeight);
                }
            }
            
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>