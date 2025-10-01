<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Drive Video Player - PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .player-card {
            transition: all 0.3s ease;
            border: none;
            background: rgba(255, 255, 255, 0.95);
        }
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 20px;
        }
        .btn-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .player-preview {
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
        }
        .drive-id-display {
            font-family: monospace;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            font-size: 0.9em;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <div class="glass p-4 text-center text-white">
                    <div class="logo mx-auto">
                        <i class="fas fa-play"></i>
                    </div>
                    <h1 class="mb-3">Google Drive Video Player</h1>
                    <p class="lead mb-4">API key gerektirmeden Google Drive videolarınızı farklı player'larla oynatın</p>
                    
                    <form id="videoForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="url" id="videoUrl" class="form-control form-control-lg" 
                                       placeholder="Google Drive video linkini yapıştırın" required>
                                <small class="text-light mt-2 d-block">
                                    Örnek: https://drive.google.com/file/d/1ABC123.../view
                                </small>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-gradient btn-lg w-100">
                                    <i class="fas fa-magic me-2"></i>Oynatıcıları Oluştur
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <input type="text" id="videoTitle" class="form-control" 
                                       placeholder="Video başlığı (opsiyonel)">
                            </div>
                            <div class="col-md-6">
                                <input type="url" id="posterUrl" class="form-control" 
                                       placeholder="Poster URL (opsiyonel)">
                            </div>
                        </div>
                    </form>
                    
                    <div id="driveIdDisplay" class="d-none">
                        <h5>Tespit Edilen Drive ID:</h5>
                        <div id="driveIdText" class="drive-id-display"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="playersContainer" class="d-none">
            <h2 class="text-center text-white mb-4">Video Oynatıcılar</h2>
            <div class="row g-4">
                <!-- Plyr Player -->
                <div class="col-lg-6">
                    <div class="card player-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-play-circle me-2"></i>Plyr Player</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="player-preview">
                                <iframe id="plyrFrame" src="" frameborder="0" class="w-100 h-100" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="openPlayer('plyr')">
                                <i class="fas fa-external-link-alt"></i> Tam Ekran Aç
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyEmbed('plyr')">
                                <i class="fas fa-code"></i> Embed Kodu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Video.js Player -->
                <div class="col-lg-6">
                    <div class="card player-card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-video me-2"></i>Video.js Player</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="player-preview">
                                <iframe id="videojsFrame" src="" frameborder="0" class="w-100 h-100" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-success btn-sm me-2" onclick="openPlayer('videojs')">
                                <i class="fas fa-external-link-alt"></i> Tam Ekran Aç
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyEmbed('videojs')">
                                <i class="fas fa-code"></i> Embed Kodu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MediaElement Player -->
                <div class="col-lg-6">
                    <div class="card player-card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-play me-2"></i>MediaElement Player</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="player-preview">
                                <iframe id="mediaelementFrame" src="" frameborder="0" class="w-100 h-100" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-warning btn-sm me-2" onclick="openPlayer('mediaelement')">
                                <i class="fas fa-external-link-alt"></i> Tam Ekran Aç
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyEmbed('mediaelement')">
                                <i class="fas fa-code"></i> Embed Kodu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HTML5 Player -->
                <div class="col-lg-6">
                    <div class="card player-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-film me-2"></i>HTML5 Player</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="player-preview">
                                <iframe id="html5Frame" src="" frameborder="0" class="w-100 h-100" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-info btn-sm me-2" onclick="openPlayer('html5')">
                                <i class="fas fa-external-link-alt"></i> Tam Ekran Aç
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyEmbed('html5')">
                                <i class="fas fa-code"></i> Embed Kodu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 mt-5">
        <div class="container">
            <p class="text-white mb-0">
                <i class="fas fa-heart text-danger"></i> 
                Google Drive Video Player PHP - API Key Gerektirmez
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>