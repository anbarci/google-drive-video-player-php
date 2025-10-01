<?php
/**
 * Google Drive Video Player - PHP Configuration
 * 
 * Bu dosya projenin temel ayarlarını içerir.
 * Güvenlik ve performans ayarları burada yapılır.
 * 
 * @author Your Name
 * @version 1.0.0
 */

// Hata raporlama (production'da kapatın)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// Güvenlik ayarları
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// HTTPS yönlendirmesi (isteğe bağlı)
if (!isset($_SERVER['HTTPS']) && $_ENV['FORCE_HTTPS'] ?? false) {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL");
    exit();
}

// Proje ayarları
define('PROJECT_NAME', 'Google Drive Video Player');
define('PROJECT_VERSION', '1.0.0');
define('PROJECT_AUTHOR', 'Your Name');
define('PROJECT_URL', 'https://github.com/anbarci/google-drive-video-player-php');

// Desteklenen video player'ları
define('SUPPORTED_PLAYERS', [
    'plyr' => [
        'name' => 'Plyr Player',
        'description' => 'Modern ve şık HTML5 video player',
        'icon' => 'fas fa-play-circle',
        'color' => 'primary'
    ],
    'videojs' => [
        'name' => 'Video.js Player',
        'description' => 'Güçlü ve özelleştirilebilir video player',
        'icon' => 'fas fa-video',
        'color' => 'success'
    ],
    'mediaelement' => [
        'name' => 'MediaElement Player',
        'description' => 'HTML5 media framework ile gelişmiş player',
        'icon' => 'fas fa-play',
        'color' => 'warning'
    ],
    'html5' => [
        'name' => 'HTML5 Player',
        'description' => 'Özel kontroller ile basit HTML5 player',
        'icon' => 'fas fa-film',
        'color' => 'info'
    ]
]);

// Google Drive ayarları
define('DRIVE_SETTINGS', [
    'embed_base_url' => 'https://drive.google.com/file/d/',
    'preview_suffix' => '/preview',
    'thumbnail_base_url' => 'https://lh3.googleusercontent.com/d/',
    'allowed_parameters' => [
        'autoplay' => 0,
        'controls' => 1,
        'modestbranding' => 1,
        'showinfo' => 0,
        'rel' => 0,
        'iv_load_policy' => 3
    ]
]);

// Cache ayarları
define('CACHE_SETTINGS', [
    'enabled' => true,
    'duration' => 3600, // 1 saat
    'directory' => __DIR__ . '/cache/'
]);

// Güvenlik ayarları
define('SECURITY_SETTINGS', [
    'max_video_id_length' => 50,
    'allowed_video_id_pattern' => '/^[a-zA-Z0-9_-]+$/',
    'max_title_length' => 200,
    'max_poster_url_length' => 500,
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 60, // Dakikada maksimum istek
        'window' => 60 // Saniye
    ]
]);

// Log ayarları
define('LOG_SETTINGS', [
    'enabled' => true,
    'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'file' => __DIR__ . '/logs/app.log',
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'rotate' => true
]);

// API ayarları (gelecekteki geliştirmeler için)
define('API_SETTINGS', [
    'version' => 'v1',
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100,
        'window' => 3600
    ],
    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST'],
        'allowed_headers' => ['Content-Type', 'Authorization']
    ]
]);

// Veritabanı ayarları (isteğe bağlı - gelecekteki geliştirmeler için)
define('DATABASE_SETTINGS', [
    'enabled' => false,
    'type' => 'sqlite', // mysql, postgresql, sqlite
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'drive_player',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
]);

// Utility functions

/**
 * Güvenli video ID kontrolü
 */
function validateVideoId($videoId) {
    if (empty($videoId)) {
        return false;
    }
    
    if (strlen($videoId) > SECURITY_SETTINGS['max_video_id_length']) {
        return false;
    }
    
    return preg_match(SECURITY_SETTINGS['allowed_video_id_pattern'], $videoId);
}

/**
 * Video URL'den ID çıkarma
 */
function extractVideoId($url) {
    $patterns = [
        '/\/file\/d\/([a-zA-Z0-9_-]+)/',
        '/[?&]id=([a-zA-Z0-9_-]+)/',
        '/\/open\?id=([a-zA-Z0-9_-]+)/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Güvenli HTML çıktısı
 */
function safeOutput($text, $allowTags = false) {
    if ($allowTags) {
        return strip_tags($text, '<b><i><u><strong><em>');
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Log yazma fonksiyonu
 */
function writeLog($level, $message, $context = []) {
    if (!LOG_SETTINGS['enabled']) {
        return;
    }
    
    $logLevels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $logLevels[LOG_SETTINGS['level']] ?? 1;
    $messageLevel = $logLevels[$level] ?? 1;
    
    if ($messageLevel < $currentLevel) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    
    // Log dizinini oluştur
    $logDir = dirname(LOG_SETTINGS['file']);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Log dosyasını döndür (boyut kontrolü)
    if (file_exists(LOG_SETTINGS['file']) && filesize(LOG_SETTINGS['file']) > LOG_SETTINGS['max_file_size']) {
        if (LOG_SETTINGS['rotate']) {
            rename(LOG_SETTINGS['file'], LOG_SETTINGS['file'] . '.' . date('Y-m-d-H-i-s'));
        } else {
            file_put_contents(LOG_SETTINGS['file'], '');
        }
    }
    
    file_put_contents(LOG_SETTINGS['file'], $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Rate limiting kontrolü
 */
function checkRateLimit($identifier = null) {
    if (!SECURITY_SETTINGS['rate_limit']['enabled']) {
        return true;
    }
    
    $identifier = $identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $cacheKey = 'rate_limit_' . md5($identifier);
    $cacheFile = (CACHE_SETTINGS['directory'] ?? '/tmp/') . $cacheKey;
    
    $now = time();
    $window = SECURITY_SETTINGS['rate_limit']['window'];
    $maxRequests = SECURITY_SETTINGS['rate_limit']['max_requests'];
    
    // Cache dosyası varsa oku
    $requests = [];
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?: [];
    }
    
    // Eski istekleri temizle
    $requests = array_filter($requests, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Yeni isteği ekle
    $requests[] = $now;
    
    // Limit kontrolü
    if (count($requests) > $maxRequests) {
        writeLog('WARNING', 'Rate limit exceeded', ['ip' => $identifier, 'requests' => count($requests)]);
        return false;
    }
    
    // Cache'i güncelle
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    file_put_contents($cacheFile, json_encode($requests));
    
    return true;
}

/**
 * URL validasyonu
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * CSRF token oluşturma
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token kontrolü
 */
function validateCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Session başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate limiting kontrolü
if (!checkRateLimit()) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Too Many Requests',
        'message' => 'Rate limit exceeded. Please try again later.',
        'retry_after' => SECURITY_SETTINGS['rate_limit']['window']
    ]);
    exit;
}

// İlk kurulum kontrolü
if (!file_exists(__DIR__ . '/.installed')) {
    writeLog('INFO', 'First installation detected');
    
    // Gerekli dizinleri oluştur
    $directories = [
        dirname(LOG_SETTINGS['file']),
        CACHE_SETTINGS['directory']
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            writeLog('INFO', 'Created directory: ' . $dir);
        }
    }
    
    // Kurulum tamamlandı işaretini koy
    file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
    writeLog('INFO', 'Installation completed');
}

// Proje başlatıldı logu
writeLog('INFO', 'Google Drive Video Player initialized', [
    'version' => PROJECT_VERSION,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);
?>