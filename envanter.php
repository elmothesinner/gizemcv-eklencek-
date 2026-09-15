<?php
// envanter.php
declare(strict_types=1);

// Siber Güvenlik: Hata detaylarını dış dünyaya tamamen kapat
ini_set('display_errors', '0');
error_reporting(0);

/* =========================
   DB BAĞLANTISI
========================= */
$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Erişim Engellendi."); }
mysqli_set_charset($conn, "utf8mb4");

$mesaj = "";
$mesaj_tipi = ""; 

// FORM İŞLEME (SQL Injection Korumalı)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barkod    = mysqli_real_escape_string($conn, $_POST['barkod']);
    $urun_kodu = mysqli_real_escape_string($conn, $_POST['urun_kodu']);
    $urun_adi  = mysqli_real_escape_string($conn, $_POST['urun_adi']);
    $fiyat     = (float)$_POST['fiyat'];
    $gramaj    = (int)$_POST['gramaj'];
    $gorsel    = mysqli_real_escape_string($conn, $_POST['gorsel_url']);

    $kontrol = mysqli_query($conn, "SELECT barkod FROM urunler WHERE barkod = '$barkod'");
    
    if (mysqli_num_rows($kontrol) > 0) {
        $mesaj = "HATA: Bu barkod ($barkod) zaten kayıtlı!";
        $mesaj_tipi = "danger";
    } else {
        $sql = "INSERT INTO urunler (barkod, urun_kodu, urun_adi, fiyat, gramaj, gorsel_url) 
                VALUES ('$barkod', '$urun_kodu', '$urun_adi', $fiyat, $gramaj, '$gorsel')";
        
        if (mysqli_query($conn, $sql)) {
            $mesaj = "BAŞARILI: $urun_adi envantere eklendi.";
            $mesaj_tipi = "success";
        } else {
            $mesaj = "Veritabanı hatası oluştu.";
            $mesaj_tipi = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr" oncontextmenu="return false;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Envanter Kontrolü</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --brand-teal: #0D707D; --bg-surface: #F9F8F6; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-surface); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            -webkit-user-select: none;
            overflow: hidden;
        }
        .main-card {
            background: white;
            border-radius: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            animation: slideIn 0.5s ease-out;
            max-height: 95vh;
            overflow-y: auto;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        input:focus { border-color: var(--brand-teal) !important; }
        .header-glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid #eee; }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="p-4">

    <header class="fixed inset-x-0 top-0 z-50 h-14 header-glass flex items-center px-6">
        <div class="w-full flex justify-between items-center max-w-[1200px] mx-auto">
            <button onclick="window.history.back()" class="flex items-center gap-2 group">
                <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center border border-slate-100 group-active:scale-90 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
                </div>
                <span class="font-bold text-slate-500 text-[10px] uppercase tracking-widest">Geri Dön</span>
            </button>
            <img src="https://gizemgunduz.store/logo.png" class="h-6" alt="Logo">
        </div>
    </header>

    <div class="main-card mt-10">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#0D707D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tighter leading-none">Envanter Kayıt</h1>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-2">Yeni Ürün Tanımlama Paneli</p>
        </div>

        <?php if ($mesaj): ?>
            <div class="<?= $mesaj_tipi == 'success' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100' ?> p-4 rounded-2xl border mb-6 text-xs font-bold flex items-center gap-3 animate-pulse">
                <span><?= $mesaj_tipi == 'success' ? '✅' : '⚠️' ?></span>
                <?= $mesaj ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="envanter.php" class="space-y-4">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Barkod</label>
                <input type="text" name="barkod" id="barkod" required autofocus autocomplete="off"
                    class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-sm focus:outline-none bg-slate-50 font-black tracking-widest text-[#0D707D]">
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Ürün Adı</label>
                <input type="text" name="urun_adi" required autocomplete="off"
                    class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-sm focus:outline-none bg-slate-50 font-bold uppercase">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Fiyat (₺)</label>
                    <input type="number" step="0.01" name="fiyat" required
                        class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-sm focus:outline-none bg-slate-50 font-black text-[#215A31]">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Gramaj</label>
                    <input type="number" name="gramaj"
                        class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-sm focus:outline-none bg-slate-50 font-semibold">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">SKU (Ürün Kodu)</label>
                <input type="text" name="urun_kodu" autocomplete="off"
                    class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-sm focus:outline-none bg-slate-50 font-mono">
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Görsel URL</label>
                <input type="text" name="gorsel_url" autocomplete="off"
                    class="w-full border border-slate-100 rounded-2xl px-5 py-3 text-[10px] focus:outline-none bg-slate-50 italic">
            </div>

            <button type="submit" 
                class="w-full bg-[#0D707D] text-white py-4 rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-teal-900/20 hover:bg-black transition-all active:scale-[0.97] mt-2">
                Envanteri Güncelle
            </button>
        </form>
    </div>

    <script>
        function kickUser(reason) {
            document.body.innerHTML = "<div style='background:#000; color:#ff0000; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:monospace; text-align:center; padding:20px;'><h1>[!] GÜVENLİK İHLALİ <br> YETKİSİZ ERİŞİM TESPİT EDİLDİ.</h1><p>Sistemden uzaklaştırılıyorsunuz...</p></div>";
            setTimeout(() => {
                window.location.replace("about:blank");
            }, 1000);
        }

        // 1. Klavye Kısayolları (F12, Ctrl+U, Ctrl+Shift+I vb.)
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

        // 2. DevTools Açıldığında (Pencere Boyutu Değişimi)
        window.addEventListener('resize', function() {
            if ((window.outerWidth - window.innerWidth) > 160 || (window.outerHeight - window.innerHeight) > 160) {
                kickUser("DevTools Detected");
            }
        });

        // 3. Debugger Kontrolü
        setInterval(function() {
            var startTime = performance.now();
            debugger; 
            var endTime = performance.now();
            if (endTime - startTime > 100) { 
                kickUser("Debugger Mode");
            }
        }, 500);

        // 4. Sağ Tık Engeli ve Atma
        document.oncontextmenu = function(e) {
            kickUser("Context Menu");
            return false;
        };

        // 5. Konsol Temizleyici (Sızma Testlerine Karşı)
        setInterval(() => console.clear(), 300);

        // Otomatik Barkod Odaklama
        window.onload = () => {
            const barkodInput = document.getElementById('barkod');
            if(barkodInput) barkodInput.focus();
        }
    </script>
</body>
</html>