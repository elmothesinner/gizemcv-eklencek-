<?php
// odeme_sonuc.php
declare(strict_types=1);

// Siber Güvenlik: Hata detaylarını gizle
ini_set('display_errors', '0');
error_reporting(0);

$arabaID = (int)($_GET['id'] ?? 1);
$orderNo = htmlspecialchars($_GET['orderNo'] ?? 'SC-' . time());
$total = htmlspecialchars($_GET['total'] ?? '0.00');
?>
<!DOCTYPE html>
<html lang="tr" oncontextmenu="return false;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Ödeme Başarılı</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --brand-teal: #0D707D; --brand-green: #215A31; }
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

        /* Başarı Glow Efekti */
        .success-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(33, 90, 49, 0.15);
            border-radius: 50%;
            filter: blur(20px);
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0% { transform: scale(0.9); opacity: 0.4; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(0.9); opacity: 0.4; }
        }

        .check-anim {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: dash 0.8s ease-in-out forwards 0.3s;
        }

        @keyframes dash {
            to { stroke-dashoffset: 0; }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="main-container flex flex-col items-center justify-between p-6">

    <div class="flex-grow flex flex-col items-center justify-center w-full max-w-[400px]">
        
        <div class="relative flex items-center justify-center mb-6">
            <div class="success-glow"></div>
            <div class="relative z-10 w-20 h-20 bg-[#215A31] rounded-full flex items-center justify-center shadow-xl shadow-green-900/20">
                <svg viewBox="0 0 24 24" class="w-10 h-10" fill="none">
                    <path 
                        class="check-anim"
                        d="M20 6L9 17l-5-5" 
                        stroke="white" 
                        stroke-width="4" 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                    />
                </svg>
            </div>
        </div>

        <div class="text-center fade-in-up">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 mb-2 uppercase tracking-tighter">Ödemeniz alındı.</h1>
            <p class="text-sm sm:text-base text-slate-500 font-medium leading-relaxed px-4">
                Ürünlerinizi alabilirsiniz. <br>
                Bizi tercih ettiğiniz için teşekkürler!
            </p>
        </div>

        <div class="w-full mt-8 bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm fade-in-up" style="animation-delay: 0.2s;">
            <div class="flex justify-between items-center px-1">
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">SİPARİŞ NO</span>
                <span class="text-slate-800 font-black text-sm tracking-tight">#<?= $orderNo ?></span>
            </div>
            <div class="h-[1px] bg-slate-50 w-full my-4"></div>
            <div class="flex justify-between items-center px-1">
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">TOPLAM TUTAR</span>
                <span class="text-[#0D707D] font-black text-xl tracking-tighter">₺<?= number_format((float)$total, 2, ',', '.') ?></span>
            </div>
        </div>

    </div>

    <div class="w-full py-4 flex flex-col items-center fade-in-up" style="animation-delay: 0.4s;">
        <div class="flex items-center justify-center gap-3 bg-white px-6 py-3 rounded-full shadow-sm border border-slate-100">
            <div class="w-4 h-4 border-2 border-[#215A31]/20 border-t-[#215A31] rounded-full animate-spin"></div>
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                <span id="countdown" class="text-[#215A31] font-black">4</span> saniye içinde dönülüyor...
            </span>
        </div>
    </div>

    <script>
        /* 1. Klavye Engelleri */
        document.onkeydown = function(e) {
            if(event.keyCode == 123) return false; // F12
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false; // İncele
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false; // Konsol
            if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false; // Kaynak
        };

        /* 2. Anti-Debugger */
        setInterval(function() {
            var startTime = performance.now();
            debugger; 
            if (performance.now() - startTime > 100) { window.location.reload(); }
        }, 1000);

        /* 3. Konsol Temizleyici */
        setInterval(() => console.clear(), 500);

        /* 4. Yönlendirme Mantığı */
        let seconds = 4;
        const countdownEl = document.getElementById('countdown');

        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.innerText = seconds;
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php?id=<?= $arabaID ?>';
            }
        }, 1000);
    </script>
</body>
</html>