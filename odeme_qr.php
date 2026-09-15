<?php
// odeme_qr.php
declare(strict_types=1);

// Siber Güvenlik: Hata detaylarını gizle
ini_set('display_errors', '0');
error_reporting(0);

$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Erişim Engellendi."); }
mysqli_set_charset($conn, "utf8mb4");

$arabaID = (int)($_GET['id'] ?? 1);

// Sepet toplamını hesapla (Veritabanı bazlı tutar güvenliği)
$sql = "SELECT SUM(u.fiyat) as toplam FROM sepet s JOIN urunler u ON s.barkod = u.barkod WHERE s.araba_id = $arabaID";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$toplam = (float)($row['toplam'] ?? 0);

// Rastgele bir Sipariş No oluştur
$orderNo = strtoupper(substr(md5((string)time()), 0, 8));

// QR API URL
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=ORDER_$orderNo";
?>
<!DOCTYPE html>
<html lang="tr" oncontextmenu="return false;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Güvenli Ödeme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Outfit:wght@500&display=swap" rel="stylesheet">
    <style>
        :root { --brand-teal: #0D707D; --brand-green: #215A31; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #F9F8F6; 
            overflow: hidden; 
            user-select: none;
            -webkit-user-select: none;
        }
        .outfit { font-family: 'Outfit', sans-serif; }
        .main-container { height: 100vh; height: 100dvh; }
        
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(33, 90, 49, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(33, 90, 49, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 90, 49, 0); }
        }
        .qr-active { animation: pulse-green 2s infinite; }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="main-container flex flex-col p-6 bg-white fade-in">

    <div class="flex items-center mb-2">
        <button 
            onclick="window.location.href='sepet.php?id=<?= $arabaID ?>'" 
            class="p-2 -ml-2 hover:bg-slate-50 rounded-full transition-all active:scale-90"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
    </div>

    <div class="flex-grow flex flex-col items-center justify-between py-2">
        
        <div class="w-full max-w-[320px] bg-white rounded-[2rem] p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-50 flex flex-col items-center gap-4">
            <div class="relative aspect-square w-full max-w-[240px] rounded-xl overflow-hidden bg-slate-50 flex items-center justify-center qr-active border-4 border-white shadow-sm">
                <img 
                    src="<?= $qrUrl ?>" 
                    alt="Ödeme QR Kodu" 
                    class="w-full h-full object-contain opacity-0 transition-opacity duration-500"
                    onload="this.style.opacity='1'"
                >
            </div>
            <span class="text-[11px] tracking-[2px] uppercase text-slate-400 font-black">
                SİPARİŞ #<?= $orderNo ?>
            </span>
        </div>

        <div class="text-center">
            <p class="outfit font-medium text-slate-500 text-lg mb-1">Ödenecek Tutar</p>
            <h2 class="font-black text-4xl sm:text-5xl tracking-tighter text-[#215A31]">
                ₺<?= number_format($toplam, 2, ',', '.') ?>
            </h2>
        </div>

        <p class="text-center text-slate-500 text-sm leading-relaxed px-6 font-medium">
            Kasada QR kodu okutun ve<br><span class="font-black text-slate-800 uppercase tracking-widest text-xs">ödemenizi gerçekleştirin</span>
        </p>

        <div class="w-full flex flex-col items-center gap-4">
            <span class="text-[10px] text-slate-300 font-bold uppercase tracking-widest animate-pulse">
                (Sistem otomatik kontrol ediliyor)
            </span>

            <button 
                onclick="confirmPayment()"
                id="btn-confirm"
                class="w-full max-w-[320px] py-4 bg-[#215A31] text-white rounded-2xl font-black text-lg hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-green-900/20 flex items-center justify-center gap-3 uppercase tracking-widest"
            >
                Ödemeyi Onayla
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    </div>

    <script>
        const aid = <?= $arabaID ?>;
        const orderNo = '<?= $orderNo ?>';
        const total = '<?= $toplam ?>';

        /* 1. SİSTEMDEN ATMA FONKSİYONU */
        function kickUser(reason) {
            document.body.innerHTML = "<div style='background:#000; color:#ff0000; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:monospace; text-align:center; padding:20px;'><h1>[!] GÜVENLİK İHLALİ <br> OTURUM SONLANDIRILDI.</h1><p>Şüpheli işlem nedeniyle erişiminiz engellendi.</p></div>";
            setTimeout(() => {
                window.location.replace("about:blank");
            }, 1200);
        }

        /* 2. KLAVYE KISAYOLLARI (F12, Ctrl+U vb.) */
        document.onkeydown = function(e) {
            if (
                event.keyCode == 123 || 
                (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) || 
                (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0))
            ) {
                kickUser("Inspector Blocked");
                return false;
            }
        };

        /* 3. DEVTOOLS AÇILDIĞINDA ATMA */
        window.addEventListener('resize', function() {
            if ((window.outerWidth - window.innerWidth) > 160 || (window.outerHeight - window.innerHeight) > 160) {
                kickUser("DevTools Detected");
            }
        });

        /* 4. DEBUGGER KONTROLÜ */
        setInterval(function() {
            var startTime = performance.now();
            debugger; 
            if (performance.now() - startTime > 100) { 
                kickUser("Debugger Detection");
            }
        }, 500);

        /* 5. SAĞ TIK ATMA */
        document.oncontextmenu = function(e) {
            kickUser("Right Click Attempt");
            return false;
        };

        /* 6. KONSOL TEMİZLEYİCİ */
        setInterval(() => console.clear(), 300);

        /* --- ÖDEME MANTIĞI --- */
        function checkPaymentStatus() {
            fetch(`sepet.php?ajax=1&islem=kontrol_et&araba_id=${aid}`)
                .then(r => r.json())
                .then(data => {
                    if(data.durum === 'odendi' || data.durum === 'basarili') {
                        goToSuccess();
                    }
                })
                .catch(err => console.log("Kontrol..."));
        }

        const statusInterval = setInterval(checkPaymentStatus, 3000);

        function confirmPayment() {
            const btn = document.getElementById('btn-confirm');
            btn.disabled = true;
            btn.innerHTML = '<div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>';
            
            fetch(`sepet.php?ajax=1&islem=odeme_yap&araba_id=${aid}`)
                .then(r => r.json())
                .then(data => {
                    if(data.durum === 'basarili') {
                        goToSuccess();
                    } else {
                        alert("Hata: " + (data.mesaj || "İşlem başarısız."));
                        resetButton(btn);
                    }
                })
                .catch(err => {
                    alert("Sunucu hatası!");
                    resetButton(btn);
                });
        }

        function goToSuccess() {
            clearInterval(statusInterval);
            window.location.href = `odeme_sonuc.php?id=${aid}&orderNo=${orderNo}&total=${total}`;
        }

        function resetButton(btn) {
            btn.disabled = false;
            btn.innerHTML = 'Ödemeyi Onayla <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>';
        }
    </script>
</body>
</html>