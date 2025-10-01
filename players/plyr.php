<?php
// Plyr Player for Google Drive Videos
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
    <title><?= $videoTitle ?> - Plyr Player</title>
    
    <!-- Plyr CSS -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            background: #000;
            font-family: Arial, sans-serif;
        }
        
        .player-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .plyr {
            width: 100%;
            max-width: 100%;
            height: auto;
        }
        
        .plyr__video-embed {
            width: 100%;
            height: 56.25vw; /* 16:9 aspect ratio */
            max-height: 100vh;
        }
        
        .video-info {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 1000;
            transition: opacity 0.3s ease;
        }
        
        .video-info:hover {
            opacity: 0.8;
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 18px;
            z-index: 999;
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
        }
        
        /* Plyr custom styling */
        :root {
            --plyr-color-main: #00b4d8;
            --plyr-video-control-color: white;
            --plyr-video-control-color-hover: #00b4d8;
            --plyr-video-control-background-hover: rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            .plyr__video-embed {
                height: 75vw;
            }
            
            .video-info {
                top: 10px;
                left: 10px;
                font-size: 12px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <div class="video-info">
            <strong><?= $videoTitle ?></strong><br>
            <small>Plyr Player - API Key Free</small>
        </div>
        
        <div class="loading" id="loading">
            <i class="fas fa-spinner fa-spin"></i> Video yüklüyor...
        </div>
        
        <div class="error-message" id="error" style="display: none;">
            <h3>Video yüklenemedi</h3>
            <p>Lütfen videonun herkese açık olduğundan emin olun.</p>
        </div>
        
        <!-- Plyr Player -->
        <div class="plyr__video-embed" id="player" style="display: none;">
            <iframe
                src="<?= htmlspecialchars($embedUrl) ?>?autoplay=0&amp;loop=0&amp;start=0&amp;rel=0&amp;showinfo=0&amp;modestbranding=1&amp;iv_load_policy=3"
                allowfullscreen
                allowtransparency
                allow="autoplay; encrypted-media; picture-in-picture"
            ></iframe>
        </div>
    </div>
    
    <!-- Plyr JS -->
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const playerElement = document.getElementById('player');
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            
            // Plyr'i başlat
            const player = new Plyr('#player', {
                controls: [
                    'play-large',
                    'play',
                    'progress', 
                    'current-time',
                    'duration',
                    'mute',
                    'volume',
                    'settings',
                    'fullscreen'
                ],
                settings: ['quality', 'speed'],
                quality: {
                    default: 'auto',
                    options: ['auto', '1080p', '720p', '480p', '360p']
                },
                speed: {
                    selected: 1,
                    options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
                },
                autoplay: false,
                muted: false,
                clickToPlay: true,
                keyboard: {
                    focused: true,
                    global: true
                },
                tooltips: {
                    controls: true,
                    seek: true
                },
                fullscreen: {
                    enabled: true,
                    fallback: true,
                    iosNative: true
                }
            });
            
            // Player hazır olduğunda
            player.on('ready', function() {
                console.log('Plyr player hazır');
                loading.style.display = 'none';
                playerElement.style.display = 'block';
                
                // Video bilgilerini güncelle
                const iframe = playerElement.querySelector('iframe');
                iframe.onload = function() {
                    console.log('Video iframe yüklendi');
                };
                
                iframe.onerror = function() {
                    console.error('Video iframe hatası');
                    loading.style.display = 'none';
                    error.style.display = 'block';
                };
            });
            
            // Hata durumunda
            player.on('error', function(event) {
                console.error('Plyr hatası:', event);
                loading.style.display = 'none';
                error.style.display = 'block';
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        player.togglePlay();
                        break;
                    case 'f':
                    case 'F':
                        player.fullscreen.toggle();
                        break;
                    case 'm':
                    case 'M':
                        player.muted = !player.muted;
                        break;
                }
            });
            
            // Video info hide/show
            const videoInfo = document.querySelector('.video-info');
            let infoTimeout;
            
            function hideInfo() {
                videoInfo.style.opacity = '0';
            }
            
            function showInfo() {
                videoInfo.style.opacity = '1';
                clearTimeout(infoTimeout);
                infoTimeout = setTimeout(hideInfo, 3000);
            }
            
            document.addEventListener('mousemove', showInfo);
            document.addEventListener('touchstart', showInfo);
            
            // Başlangıçta bilgiyi göster
            showInfo();
            
            console.log('Google Drive Video Player - Plyr Edition');
            console.log('Video ID: <?= $videoId ?>');
            console.log('API Key: Kullanılmıyor (Embed yöntemi)');
        });
    </script>
</body>
</html>