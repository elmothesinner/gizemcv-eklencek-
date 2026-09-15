<?php
declare(strict_types=1);
$arabaID = (int)($_GET['id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #F9F8F6; overflow: hidden; }
        
        /* Logo için daha yumuşak giriş ve hafif nefes alma efekti */
        @keyframes splashIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes pulseLogo {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
        .animate-splash { animation: splashIn 0.8s ease-out forwards; }
        .animate-pulse-slow { animation: pulseLogo 2s ease-in-out infinite; }

        /* Arka plan akışkanlığı */
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -20px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen w-full flex items-center justify-center relative">

    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-24 -left-24 h-[400px] w-[400px] rounded-full bg-lime-300/15 blur-[100px] animate-float"></div>
        <div class="absolute -bottom-24 -right-24 h-[500px] w-[500px] rounded-full bg-emerald-300/15 blur-[120px] animate-float" style="animation-delay: -3s;"></div>
    </div>

    <div class="relative z-10 animate-splash">
        <div class="animate-pulse-slow">
            <img src="https://gizemgunduz.store/logo.png" alt="SmartCart Logo" class="w-[280px] h-auto drop-shadow-md">
        </div>
    </div>

    <div class="absolute bottom-12 text-slate-400 text-[10px] font-bold tracking-[0.4em] uppercase opacity-50">
        Sistem Yükleniyor...
    </div>

    <script>
        // Sayfa açıldıktan 1500ms (1.5 saniye) sonra otomatik yönlendir
        setTimeout(() => {
            window.location.replace("login.php?id=<?= $arabaID ?>");
        }, 1500);
    </script>
</body>
</html>