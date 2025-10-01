<?php
// MediaElementJS Player for Google Drive Videos
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
    <title><?= $videoTitle ?> - MediaElement Player</title>
    
    <!-- MediaElementJS CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mediaelement/5.1.1/mediaelementplayer.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            background: #0f0f0f;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        
        .player-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        }
        
        .media-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .media-element {
            width: 100%;
            height: 100%;
        }
        
        .video-info {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.85);
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 14px;
            z-index: 1000;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
        }
        
        .video-info:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
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
            background: rgba(0, 0, 0, 0.8);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .loading i {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
            color: #e74c3c;
            animation: pulse 1.5s ease-in-out infinite alternate;
        }
        
        @keyframes pulse {
            from { opacity: 0.5; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1.1); }
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #e74c3c;
            text-align: center;
            font-size: 16px;
            z-index: 999;
            background: rgba(231, 76, 60, 0.15);
            padding: 30px;
            border-radius: 15px;
            border: 2px solid rgba(231, 76, 60, 0.3);
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
        }
        
        .error-message li {
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .error-message li:before {
            content: '•';
            position: absolute;
            left: 0;
            color: #e74c3c;
        }
        
        /* MediaElement custom styling */
        .mejs__container {
            background: #000 !important;
        }
        
        .mejs__controls {
            background: linear-gradient(transparent, rgba(0,0,0,0.8)) !important;
            height: 60px !important;
        }
        
        .mejs__button > button {
            color: #fff !important;
        }
        
        .mejs__button > button:hover {
            color: #e74c3c !important;
        }
        
        .mejs__time-rail .mejs__time-total {
            background: rgba(255, 255, 255, 0.3) !important;
        }
        
        .mejs__time-rail .mejs__time-loaded {
            background: rgba(255, 255, 255, 0.5) !important;
        }
        
        .mejs__time-rail .mejs__time-current {
            background: #e74c3c !important;
        }
        
        .mejs__overlay-play .mejs__overlay-button {
            background-color: rgba(231, 76, 60, 0.8) !important;
            border: 3px solid rgba(255, 255, 255, 0.8) !important;
            border-radius: 50% !important;
            width: 80px !important;
            height: 80px !important;
        }
        
        .mejs__overlay-play .mejs__overlay-button:hover {
            background-color: rgba(231, 76, 60, 1) !important;
        }
        
        .embedded-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        @media (max-width: 768px) {
            .video-info {
                top: 10px;
                left: 10px;
                font-size: 12px;
                padding: 10px 15px;
            }
            
            .loading {
                padding: 20px;
                font-size: 16px;
            }
            
            .loading i {
                font-size: 24px;
            }
            
            .mejs__overlay-play .mejs__overlay-button {
                width: 60px !important;
                height: 60px !important;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <div class="video-info">
            <strong><i class="fas fa-play-circle"></i> <?= $videoTitle ?></strong><br>
            <small><i class="fab fa-google-drive"></i> MediaElement Player - No API Key Required</small>
        </div>
        
        <div class="loading" id="loading">
            <i class="fas fa-film"></i>
            <div><strong>Video Hazırlanıyor</strong></div>
            <small>Lütfen bekleyin...</small>
        </div>
        
        <div class="error-message" id="error" style="display: none;">
            <h3><i class="fas fa-exclamation-circle"></i> Video Yüklenemedi</h3>
            <p>Video dosyasına erişim sağlanamıyor.</p>
            <ul>
                <li>Videonun halka açık olduğundan emin olun</li>
                <li>Google Drive linkini kontrol edin</li>
                <li>Sayfa yenilemeyi deneyin</li>
                <li>Farklı bir player deneyin</li>
            </ul>
        </div>
        
        <div class="media-wrapper" id="mediaWrapper" style="display: none;">
            <!-- MediaElement Player -->
            <video
                id="mediaPlayer"
                class="media-element"
                controls
                preload="auto"
                poster="<?= htmlspecialchars($posterUrl) ?>"
            >
                <!-- Google Drive Embed as source -->
                <iframe
                    class="embedded-iframe"
                    src="<?= htmlspecialchars($embedUrl) ?>?autoplay=0&amp;controls=1&amp;modestbranding=1&amp;rel=0"
                    allowfullscreen
                    allowtransparency
                    allow="autoplay; encrypted-media; picture-in-picture"
                ></iframe>
                
                <p>Bu video tarayıcınızda oynatılamıyor. Lütfen modern bir tarayıcı kullanın.</p>
            </video>
        </div>
    </div>
    
    <!-- MediaElementJS Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mediaelement/5.1.1/mediaelement-and-player.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const mediaWrapper = document.getElementById('mediaWrapper');
            
            try {
                // MediaElement player'i başlat
                const player = new MediaElementPlayer('mediaPlayer', {
                    features: [
                        'playpause',
                        'progress',
                        'current',
                        'duration',
                        'tracks',
                        'volume',
                        'chromecast',
                        'fullscreen'
                    ],
                    enableAutosize: true,
                    stretching: 'responsive',
                    pluginPath: 'https://cdnjs.cloudflare.com/ajax/libs/mediaelement/5.1.1/',
                    shimScriptAccess: 'always',
                    success: function(mediaElement, originalNode, instance) {
                        console.log('MediaElement player başlatıldı');
                        
                        // Player hazır olduğunda göster
                        setTimeout(() => {
                            loading.style.display = 'none';
                            mediaWrapper.style.display = 'block';
                        }, 2000);
                        
                        // Event listeners
                        mediaElement.addEventListener('play', function() {
                            console.log('Video oynatmaya başlandı');
                        });
                        
                        mediaElement.addEventListener('pause', function() {
                            console.log('Video duraklataldı');
                        });
                        
                        mediaElement.addEventListener('ended', function() {
                            console.log('Video sona erdi');
                        });
                        
                        mediaElement.addEventListener('error', function(e) {
                            console.error('MediaElement hatası:', e);
                            loading.style.display = 'none';
                            mediaWrapper.style.display = 'none';
                            error.style.display = 'block';
                        });
                    },
                    error: function(mediaElement, originalNode, instance) {
                        console.error('MediaElement başlatma hatası');
                        loading.style.display = 'none';
                        error.style.display = 'block';
                    }
                });
                
                // Keyboard controls
                document.addEventListener('keydown', function(e) {
                    const video = document.getElementById('mediaPlayer');
                    if (!video) return;
                    
                    switch(e.key) {
                        case ' ':
                            e.preventDefault();
                            if (video.paused) {
                                video.play();
                            } else {
                                video.pause();
                            }
                            break;
                        case 'f':
                        case 'F':
                            if (player && player.enterFullScreen) {
                                player.enterFullScreen();
                            }
                            break;
                        case 'm':
                        case 'M':
                            video.muted = !video.muted;
                            break;
                        case 'ArrowLeft':
                            video.currentTime = Math.max(0, video.currentTime - 10);
                            break;
                        case 'ArrowRight':
                            video.currentTime = Math.min(video.duration, video.currentTime + 10);
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            video.volume = Math.min(1, video.volume + 0.1);
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            video.volume = Math.max(0, video.volume - 0.1);
                            break;
                    }
                });
                
            } catch (err) {
                console.error('Player başlatma hatası:', err);
                loading.style.display = 'none';
                error.style.display = 'block';
            }
            
            // Video info hide/show functionality
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
                    infoTimeout = setTimeout(hideInfo, 5000);
                }
            }
            
            // Mouse/touch events
            document.addEventListener('mousemove', showInfo);
            document.addEventListener('touchstart', showInfo);
            document.addEventListener('click', showInfo);
            
            // Başlangıçta info göster
            showInfo();
            
            // Debug info
            console.log('%cGoogle Drive Video Player - MediaElement Edition', 'color: #e74c3c; font-size: 16px; font-weight: bold;');
            console.log('Video ID: <?= $videoId ?>');
            console.log('Video Title: <?= $videoTitle ?>');
            console.log('Embed URL: <?= $embedUrl ?>');
            console.log('API Key Status: Not Required (Using Iframe Embed)');
            
            // Responsive handling
            function handleResize() {
                const container = document.querySelector('.player-container');
                if (container) {
                    const aspectRatio = 16 / 9;
                    const windowRatio = window.innerWidth / window.innerHeight;
                    
                    if (windowRatio > aspectRatio) {
                        container.style.height = '100vh';
                        container.style.width = (window.innerHeight * aspectRatio) + 'px';
                    } else {
                        container.style.width = '100vw';
                        container.style.height = (window.innerWidth / aspectRatio) + 'px';
                    }
                }
            }
            
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>