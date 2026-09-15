<?php
/**
 * SmartCart - Güvenlik Kilit Ekranı (v2.1 API Uyumlu)
 */
declare(strict_types=1);

// API Dosyanızın adını burada tanımlayın (Örn: api.php veya sepet.php içindeki ajax kısmı)
$API_PATH = "api.php"; 
$arabaID = (int)($_GET['id'] ?? 1);
$ADMIN_BARCODE = "ADMIN123"; // Burayı ACTIONS içindeki ADMIN_BARCODE ile aynı yapın

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Sistem Kilitli</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #1F3D2B; overflow: hidden; }
        .pulse-warning { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }
        
        /* Glassmorphism dokunuşu */
        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="min-h-screen w-full flex items-center justify-center px-6">

    <div class="max-w-md w-full flex flex-col items-center text-center">
        
        <div class="mb-10 relative">
            <div class="absolute inset-0 bg-white/10 blur-3xl rounded-full"></div>
            <div class="relative w-32 h-32 flex items-center justify-center bg-white/5 rounded-full border border-white/10">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                    <path d="M8 10V7.5C8 5.01472 10.0147 3 12.5 3C14.9853 3 17 5.01472 17 7.5V10" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M7 10H18C18.5523 10 19 10.4477 19 11V19C19 19.5523 18.5523 20 18 20H7C6.44772 20 6 19.5523 6 19V11C6 10.4477 6.44772 10 7 10Z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                    <circle cx="12" cy="15" r="1.5" fill="white"/>
                </svg>
            </div>
        </div>

        <h1 class="text-white text-[36px] font-bold mb-6 leading-[45px]">
            Sepet kontrolü gerekli
        </h1>

        <div class="flex items-center gap-3 mb-10">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 3L22 20H2L12 3Z" fill="#C9A227" fill-opacity="0.15" />
                <path d="M12 4.8L20.4 19.2H3.6L12 4.8Z" stroke="#C9A227" stroke-width="2" stroke-linejoin="round"/>
                <path d="M12 9V13" stroke="#C9A227" stroke-width="2" stroke-linecap="round"/>
                <path d="M12 16.5H12.01" stroke="#C9A227" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <span class="text-[#C9A227] font-semibold text-[20px] leading-7">
                Eksik veya fazla ürün tespit edildi
            </span>
        </div>

        <div class="space-y-2 mb-12">
            <p class="text-white text-[18px] font-medium leading-7">Sistem geçici olarak durduruldu.</p>
            <p class="text-[#D1D5DB] text-[18px] font-normal leading-7">Görevli barkodu okutun.</p>
        </div>

        <div class="flex items-center justify-center gap-3">
            <span class="w-2 h-2 rounded-full bg-[#C9A227]"></span>
            <span class="text-[#C9A227] font-medium text-[14px] uppercase tracking-widest">
                Barkod Okuyucu Aktif
            </span>
            <span class="w-2 h-2 rounded-full bg-[#C9A227] pulse-warning"></span>
        </div>

        <div class="mt-12 text-white/20 text-[10px] font-mono tracking-tighter uppercase">
            Device ID: SC-<?= str_pad((string)$arabaID, 3, '0', STR_PAD_LEFT) ?> | Auth Required
        </div>
    </div>

    <script>
        let keyBuffer = "";
        const aid = <?= $arabaID ?>;
        const ADMIN_CODE = "<?= $ADMIN_BARCODE ?>";
        const API_URL = "<?= $API_PATH ?>";

        // 1. Fiziksel Barkod Okuyucu Dinleyicisi
        $(document).on('keydown', function(e) {
            if (e.which === 13) { // Enter (Barkod okuyucular enter basar)
                if (keyBuffer.length > 0) {
                    processUnlock(keyBuffer);
                }
                keyBuffer = "";
            } else if (e.key.length === 1) {
                keyBuffer += e.key;
            }
        });

        // 2. Kilit Açma İşlemi (API v2.1: hata_temizle)
        function processUnlock(code) {
            if (code === ADMIN_CODE) {
                // API üzerinden güvenliği temizle
                $.getJSON(`${API_URL}?islem=hata_temizle&araba_id=${aid}`, function(data) {
                    if (data.durum === "basarili") {
                        window.location.href = `sepet.php?id=${aid}`;
                    }
                });
            } else {
                // Yanlış Barkod: Görsel geri bildirim
                $('body').addClass('shake');
                setTimeout(() => $('body').removeClass('shake'), 500);
            }
        }

        // 3. Otomatik Takip (Polling)
        // Eğer görevli başka bir panelden veya veritabanından kilidi açarsa sayfa kendiliğinden döner
        setInterval(() => {
            $.getJSON(`${API_URL}?islem=sepeti_getir&araba_id=${aid}`, function(data) {
                if (data.hata_durumu === 0) {
                    window.location.href = `sepet.php?id=${aid}`;
                }
            });
        }, 2000);
    </script>
</body>
</html>