<?php
// uye_ol.php
declare(strict_types=1);

// Siber Güvenlik: Hata detaylarını dış dünyaya kapat
ini_set('display_errors', '0');
error_reporting(0);

/* =========================
   DB AYARLARI VE BAĞLANTI
========================= */
$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Erişim Reddedildi."); }
mysqli_set_charset($conn, "utf8mb4");

$arabaID = (int)($_GET['id'] ?? 1);

/* =========================
   KAYIT İŞLEMİ (POST)
========================= */
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $ad_soyad = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $telefon = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $sifre = $_POST['password'] ?? '';

    if (empty($ad_soyad) || empty($telefon)) {
        $error = "Lütfen ad soyad ve telefon bilgilerini doldurunuz.";
    } else {
        // Simüle edilen kayıt işlemi
        $success = true;
        header("Refresh: 2; url=sepet.php?id=$arabaID");
    }
}
?>
<!DOCTYPE html>
<html lang="tr" oncontextmenu="return false;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Güvenli Üye Ol</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-teal: #0D707D; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #F9F8F6; 
            overflow: hidden; 
            /* Metin seçmeyi engelle */
            user-select: none;
            -webkit-user-select: none;
        }
        .main-container { 
            height: 100vh; 
            height: 100dvh; 
        }
        .fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        
        input:focus { border-color: var(--brand-teal) !important; }
        
        /* Mobil klavye açıldığında formun kaydırılabilir olması için */
        .glass-card {
            max-height: 90vh;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .glass-card::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="main-container flex items-center justify-center p-4">

    <?php if ($success): ?>
        <div class="fixed top-6 left-1/2 -translate-x-1/2 bg-[#10b981] text-white px-6 py-3 rounded-xl shadow-2xl z-50 fade-in text-center text-sm font-bold">
            Üyeliğiniz başarıyla gerçekleşti ✅ <br>
            <span class="text-[10px] opacity-80 font-normal uppercase tracking-widest">Yönlendiriliyorsunuz...</span>
        </div>
    <?php endif; ?>

    <div class="w-full max-w-[400px] bg-white border border-slate-200 rounded-[2.5rem] p-6 sm:p-8 shadow-xl fade-in flex flex-col glass-card">
        
        <div class="text-center mb-6">
            <h1 class="text-xl font-black text-[#0D707D] leading-tight mb-1 uppercase tracking-tighter">
                Kullanıcı Bulunamadı
            </h1>
            <p class="text-[12px] text-slate-400 font-medium">
                Alışverişe başlamak için lütfen üye olun.
            </p>
        </div>

        <form method="POST" class="flex flex-col gap-3">
            <input type="hidden" name="signup" value="1">
            
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Ad Soyad</label>
                <input 
                    required
                    name="name" 
                    type="text" 
                    placeholder="Ahmet Yılmaz"
                    autocomplete="off"
                    class="w-full border border-slate-200 rounded-2xl px-5 py-3.5 text-sm focus:outline-none bg-slate-50 transition-all font-semibold"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Telefon</label>
                <input 
                    required
                    name="phone" 
                    type="tel" 
                    placeholder="05XX XXX XX XX"
                    autocomplete="off"
                    class="w-full border border-slate-200 rounded-2xl px-5 py-3.5 text-sm focus:outline-none bg-slate-50 transition-all font-semibold"
                >
            </div>

            <div class="py-1">
                <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer group">
                    <input 
                        type="checkbox" 
                        onchange="document.getElementById('passArea').classList.toggle('hidden')"
                        class="w-4 h-4 rounded-md accent-[#0D707D] cursor-pointer"
                    >
                    <span class="group-hover:text-black transition-colors font-bold">Şifre oluşturmak istiyorum</span>
                </label>
            </div>

            <div id="passArea" class="hidden space-y-1 fade-in">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Şifre (Opsiyonel)</label>
                <input 
                    name="password" 
                    type="password" 
                    placeholder="••••••••"
                    class="w-full border border-slate-200 rounded-2xl px-5 py-3.5 text-sm focus:outline-none bg-slate-50 font-semibold"
                >
            </div>

            <?php if ($error): ?>
                <div class="text-red-500 text-[11px] font-bold bg-red-50 p-2 rounded-lg border border-red-100 italic">
                    ⚠️ <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col gap-2 pt-3">
                <button 
                    type="submit" 
                    class="w-full bg-[#0D707D] text-white py-4 rounded-2xl text-xs uppercase tracking-widest font-black shadow-lg hover:bg-black transition-all active:scale-[0.96]"
                >
                    Üye Ol ve Başla
                </button>

                <div class="flex items-center gap-3 my-1">
                    <div class="h-[1px] bg-slate-100 flex-1"></div>
                    <span class="text-[9px] text-slate-300 font-bold uppercase">veya</span>
                    <div class="h-[1px] bg-slate-100 flex-1"></div>
                </div>

                <a 
                    href="sepet.php?id=<?= $arabaID ?>" 
                    class="w-full bg-white text-[#0D707D] border-2 border-[#0D707D] py-3.5 rounded-2xl text-xs uppercase tracking-widest font-black text-center hover:bg-[#0D707D] hover:text-white transition-all active:scale-[0.96]"
                >
                    Üye Olmadan Devam Et
                </a>
            </div>
        </form>

        <p class="text-center mt-6 text-[11px] text-slate-400 font-bold">
            Zaten hesabınız var mı? <a href="login.php?id=<?= $arabaID ?>" class="text-[#0D707D] underline">Giriş Yap</a>
        </p>
    </div>

    <script>
        /* 1. Klavye Engelleri */
        document.onkeydown = function(e) {
            if(event.keyCode == 123) return false; // F12
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false; // İncele
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false; // Konsol
            if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false; // Kaynak
        };

        /* 2. Anti-Debugger (Geliştirici Araçları Açılırsa Yenile) */
        setInterval(function() {
            var startTime = performance.now();
            debugger; 
            if (performance.now() - startTime > 100) { window.location.reload(); }
        }, 1000);

        /* 3. Konsol Temizleyici */
        setInterval(() => {
            console.clear();
            console.log("%cBU ALAN GÜVENLİK NEDENİYLE KAPATILMIŞTIR.", "color: red; font-size: 20px; font-weight: bold;");
        }, 500);
    </script>
</body>
</html>