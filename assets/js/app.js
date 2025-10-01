// Google Drive Video Player - JavaScript Functions

class DriveVideoPlayer {
    constructor() {
        this.currentVideoData = null;
        this.baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
        this.initEventListeners();
    }

    initEventListeners() {
        document.getElementById('videoForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.processVideo();
        });
    }

    // Google Drive URL'den video ID'sini çıkar
    extractDriveId(url) {
        const patterns = [
            /\/file\/d\/([a-zA-Z0-9_-]+)/,
            /[?&]id=([a-zA-Z0-9_-]+)/,
            /\/open\?id=([a-zA-Z0-9_-]+)/
        ];

        for (let pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                return match[1];
            }
        }
        return null;
    }

    // Video bilgilerini işle ve player'ları oluştur
    processVideo() {
        const videoUrl = document.getElementById('videoUrl').value.trim();
        const videoTitle = document.getElementById('videoTitle').value.trim() || 'Google Drive Video';
        const posterUrl = document.getElementById('posterUrl').value.trim();

        if (!videoUrl) {
            this.showAlert('Lütfen bir Google Drive video linki girin.', 'danger');
            return;
        }

        const driveId = this.extractDriveId(videoUrl);
        if (!driveId) {
            this.showAlert('Geçerli bir Google Drive video linki girin.', 'danger');
            return;
        }

        // Video data objesi oluştur
        this.currentVideoData = {
            id: driveId,
            title: videoTitle,
            poster: posterUrl || this.getDefaultPoster(driveId),
            originalUrl: videoUrl
        };

        // Drive ID'sini göster
        this.displayDriveId(driveId);

        // Player'ları oluştur
        this.createPlayers();
        
        // Player container'ını göster
        document.getElementById('playersContainer').classList.remove('d-none');
        
        // Smooth scroll to players
        document.getElementById('playersContainer').scrollIntoView({ 
            behavior: 'smooth' 
        });

        this.showAlert('Player'lar başarıyla oluşturuldu!', 'success');
    }

    // Drive ID'sini göster
    displayDriveId(driveId) {
        document.getElementById('driveIdText').textContent = driveId;
        document.getElementById('driveIdDisplay').classList.remove('d-none');
    }

    // Default poster URL oluştur
    getDefaultPoster(driveId) {
        return `https://lh3.googleusercontent.com/d/${driveId}`;
    }

    // Tüm player'ları oluştur
    createPlayers() {
        const players = ['plyr', 'videojs', 'mediaelement', 'html5'];
        
        players.forEach(player => {
            const playerUrl = this.generatePlayerUrl(player);
            document.getElementById(`${player}Frame`).src = playerUrl;
        });
    }

    // Player URL'sini oluştur
    generatePlayerUrl(playerType) {
        const encodedData = btoa(JSON.stringify(this.currentVideoData));
        return `${this.baseUrl}players/${playerType}.php?data=${encodedData}`;
    }

    // Player'ı yeni sekmede aç
    openPlayer(playerType) {
        if (!this.currentVideoData) {
            this.showAlert('Önce bir video seçin.', 'warning');
            return;
        }

        const playerUrl = this.generatePlayerUrl(playerType);
        window.open(playerUrl, '_blank');
    }

    // Embed kodunu kopyala
    copyEmbed(playerType) {
        if (!this.currentVideoData) {
            this.showAlert('Önce bir video seçin.', 'warning');
            return;
        }

        const playerUrl = this.generatePlayerUrl(playerType);
        const embedCode = `<iframe width="560" height="315" src="${playerUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        
        // Clipboard'a kopyala
        if (navigator.clipboard) {
            navigator.clipboard.writeText(embedCode).then(() => {
                this.showAlert(`${this.getPlayerName(playerType)} embed kodu kopyalandı!`, 'success');
            }).catch(() => {
                this.fallbackCopyToClipboard(embedCode);
            });
        } else {
            this.fallbackCopyToClipboard(embedCode);
        }
    }

    // Fallback clipboard function
    fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            this.showAlert('Embed kodu kopyalandı!', 'success');
        } catch (err) {
            prompt('Embed kodunu manuel olarak kopyalayın:', text);
        }
        
        document.body.removeChild(textArea);
    }

    // Player adını döndür
    getPlayerName(playerType) {
        const names = {
            'plyr': 'Plyr',
            'videojs': 'Video.js',
            'mediaelement': 'MediaElement',
            'html5': 'HTML5'
        };
        return names[playerType] || playerType;
    }

    // Alert göster
    showAlert(message, type = 'info') {
        // Mevcut alert'leri temizle
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <strong>${message}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // 5 saniye sonra otomatik kaldır
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Global functions for button clicks
let playerInstance;

function openPlayer(playerType) {
    if (playerInstance) {
        playerInstance.openPlayer(playerType);
    }
}

function copyEmbed(playerType) {
    if (playerInstance) {
        playerInstance.copyEmbed(playerType);
    }
}

// Initialize when DOM loaded
document.addEventListener('DOMContentLoaded', function() {
    playerInstance = new DriveVideoPlayer();
    
    // Example link için click handler
    const exampleLinks = document.querySelectorAll('.example-link');
    exampleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('videoUrl').value = this.getAttribute('data-url');
            document.getElementById('videoTitle').value = this.getAttribute('data-title') || '';
        });
    });
});

// Utility functions
function isValidDriveUrl(url) {
    const drivePatterns = [
        /drive\.google\.com\/file\/d\/[a-zA-Z0-9_-]+/,
        /drive\.google\.com\/open\?id=[a-zA-Z0-9_-]+/
    ];
    
    return drivePatterns.some(pattern => pattern.test(url));
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Console welcome message
console.log('%c🚀 Google Drive Video Player PHP', 'color: #667eea; font-size: 16px; font-weight: bold;');
console.log('%cAPI Key gerektirmeden Google Drive videolarınızı oynatın!', 'color: #764ba2; font-size: 12px;');
console.log('%cGitHub: https://github.com/anbarci/google-drive-video-player-php', 'color: #28a745; font-size: 10px;');