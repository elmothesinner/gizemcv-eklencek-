<?php
// sepet.php - v9.0 (Reklam Entegrasyonlu + Kendi Kendine Silinen Tavsiye)
declare(strict_types=1);

$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Bağlantı hatası: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

$arabaID = (int)($_GET['id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Akıllı Terminal</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #F3F4F6; height: 100vh; overflow: hidden; }

        #warning-countdown-bar {
            position: fixed; top: 55px; left: 0; width: 100%; height: 40px;
            background: #FFB800; z-index: 60; display: none; align-items: center;
            justify-content: center; font-weight: 900; font-size: 13px; color: #000;
        }

        .reklam-footer-bant { 
            position: fixed; bottom: 85px; left: 0; width: 100%; background: #000; 
            height: 35px; z-index: 45; display: flex; align-items: center; overflow: hidden;
        }
        .bant-scroll { white-space: nowrap; animation: kaydir 40s linear infinite; display: inline-block; }
        .bant-item { color: #FFB800; font-size: 11px; font-weight: 800; text-transform: uppercase; padding-right: 80px; }
        @keyframes kaydir { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }

        #main-container { height: calc(100vh - 225px); margin-top: 55px; overflow-y: auto; padding: 12px; transition: all 0.3s ease; }
        body.has-warning #main-container { margin-top: 95px; }

        .overlay { position: fixed; inset: 0; background: white; z-index: 1000; display: none; flex-direction: column; align-items: center; justify-content: center; padding: 25px; }
        #barcode-receiver { position: absolute; left: -9999px; opacity: 0; }
        
        /* Tavsiye Kutusu Animasyonu */
        #tavsiye-konteynir { transition: all 0.5s ease; }
    </style>
</head>
<body class="pb-26">

    <input type="text" id="barcode-receiver" autofocus autocomplete="off">

    <header class="fixed inset-x-0 top-0 z-40 bg-white border-b flex items-center px-5 h-[55px]">
        <img src="https://gizemgunduz.store/logo.png" class="h-6">
        <div id="status-dot" class="w-3 h-3 bg-green-500 rounded-full ml-4 animate-pulse"></div>
        <span id="urun-sayisi" class="ml-auto text-[11px] font-black text-slate-500 uppercase">0 ÜRÜN</span>
    </header>

    <div id="warning-countdown-bar">
        ⚠️ <span id="warning-msg">AĞIRLIK HATASI!</span> &nbsp; | &nbsp; KİLİTLENMEYE SON: <span id="timer-val" class="bg-black text-white px-2 py-0.5 rounded ml-1">30</span>s
    </div>

    <main id="main-container">
        <div id="sepet-listesi" class="flex flex-col gap-3"></div>
        <div id="tavsiye-konteynir"></div>
    </main>

    <div class="reklam-footer-bant">
        <div class="bant-scroll" id="reklam-listesi">
            <span class="bant-item">SmartCart Akıllı Market • Hoş Geldiniz! • </span>
        </div>
    </div>

    <div id="security-lock" class="overlay">
        <div class="text-center">
            <h2 class="text-4xl font-black text-red-600 uppercase italic">GÜVENLİK KİLİDİ</h2>
            <p class="text-slate-500 font-bold mt-4 uppercase text-xs">Ağırlık uyuşmazlığı nedeniyle kilitlendi.</p>
        </div>
        <button onclick="location.reload()" class="mt-10 px-10 py-5 bg-black text-white rounded-3xl font-black uppercase">Sistemi Aç</button>
    </div>

    <footer class="fixed inset-x-0 bottom-0 z-50 bg-white border-t px-6 flex items-center justify-between h-[85px]">
        <div class="flex flex-col">
            <span class="text-[10px] font-black text-slate-400 uppercase italic">Toplam</span>
            <span id="toplam-tutar" class="text-2xl font-black text-[#0D707D]">0,00 TL</span>
        </div>
        <button onclick="window.location.href='odeme_qr.php?id='+aid" class="h-14 px-10 bg-[#215A31] text-white rounded-2xl font-black text-sm uppercase">ÖDEME</button>
    </footer>

    <script>
        const aid = <?= $arabaID ?>;
        let isLocked = false;
        let countdownValue = 30;
        let countdownTimer = null;
        let sonTavsiyeBarkod = null;

        // 1. REKLAMLARI API'DEN ÇEK VE BANDA BAS
        function reklamlariYukle() {
            $.getJSON(`api.php?islem=reklamlari_getir`, (data) => {
                if (data && data.length > 0) {
                    let reklamHtml = "";
                    data.forEach(r => {
                        // Sadece 'bant' tipindeki reklamları gösteriyoruz
                        if(r.reklam_tipi === 'bant') {
                            reklamHtml += `<span class="bant-item">${r.icerik_metni} • </span>`;
                        }
                    });
                    if(reklamHtml !== "") $("#reklam-listesi").html(reklamHtml + reklamHtml); // Döngü için çift ekledik
                }
            });
        }

        $("#barcode-receiver").on("keypress", function(e) {
            if (e.which === 13) { 
                let barcode = $(this).val().trim();
                if (barcode !== "" && !isLocked) urunEkle(barcode);
                $(this).val("");
            }
        });

        function urunEkle(barcode) {
            $.getJSON(`api.php?islem=sepete_ekle&araba_id=${aid}&barkod=${barcode}`, () => {
                sepetiGetir();
            });
        }

        function urunSil(sid) { 
            $.getJSON(`api.php?islem=urun_sil&araba_id=${aid}&sepet_id=${sid}`, sepetiGetir); 
        }

        function sepetiGetir() {
            $.getJSON(`api.php?islem=sepeti_getir&araba_id=${aid}`, (data) => {
                if(!data) return;

                // Ağırlık ve Kilit Mantığı
                let fark = data.terazi_durumu ? parseFloat(data.terazi_durumu.fark) : 0;
                if (Math.abs(fark) > 100) {
                    if (!isLocked && countdownTimer === null) startCountdown();
                } else {
                    stopCountdown();
                    $("#security-lock").fadeOut(200);
                    isLocked = false;
                }

                if (isLocked || data.hata_durumu === 2) {
                    $("#security-lock").fadeIn(200).css('display', 'flex');
                    isLocked = true;
                    return;
                }

                renderList(data);
            });
        }

        function renderList(data) {
            let urunler = data.urunler || [];
            let tavsiye = data.tavsiye_urun;
            let html = ""; let total = 0;
            
            // Sepet Listesi Oluşturma
            if(urunler.length > 0) {
                $("#urun-sayisi").text(urunler.length + " ÜRÜN");
                urunler.forEach(item => {
                    total += parseFloat(item.fiyat);
                    html += `
                    <div class="p-4 bg-white rounded-3xl flex justify-between items-center border border-slate-100 shadow-sm mb-2">
                        <div class="flex items-center gap-4">
                            <img src="${item.gorsel_url}" class="w-14 h-14 object-contain bg-slate-50 rounded-2xl p-2" onerror="this.src='https://via.placeholder.com/150?text=Urun'">
                            <div class="flex flex-col">
                                <span class="text-[13px] font-black uppercase text-slate-800">${item.urun_adi}</span>
                                <span class="text-sm font-black text-[#0D707D] mt-1">${parseFloat(item.fiyat).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</span>
                            </div>
                        </div>
                        <button onclick="urunSil(${item.sepet_id})" class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center">✕</button>
                    </div>`;
                });
            } else {
                html = "<div class='py-20 text-center opacity-20 font-black uppercase text-xs'>Sepet Boş</div>";
                $("#urun-sayisi").text("0 ÜRÜN");
            }
            
            $("#sepet-listesi").html(html);
            $("#toplam-tutar").text(total.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + " TL");

            // TAVSİYE MANTIĞI: Yeni bir ürün eklendiyse göster ve 3 saniye sonra sil
            if(tavsiye && tavsiye.barkod !== sonTavsiyeBarkod) {
                sonTavsiyeBarkod = tavsiye.barkod;
                showTavsiye(tavsiye);
            }
        }

        function showTavsiye(tavsiye) {
            let tavsiyeHtml = `
            <div id="active-tavsiye" class="mt-4 p-5 bg-gradient-to-br from-[#215A31] to-[#0D707D] rounded-[2rem] shadow-xl relative overflow-hidden text-white animate-bounce">
                <span class="text-[10px] font-black opacity-70 uppercase tracking-widest">Sizin İçin Önerilen</span>
                <div class="flex items-center gap-4 mt-3">
                    <img src="${tavsiye.gorsel_url}" class="w-16 h-16 object-cover bg-white rounded-2xl p-1" onerror="this.src='https://via.placeholder.com/150?text=Oneri'">
                    <div class="flex flex-col flex-1">
                        <span class="text-sm font-bold uppercase leading-tight">${tavsiye.urun_adi}</span>
                        <span class="text-lg font-black text-[#FFB800] mt-0.5">${parseFloat(tavsiye.fiyat).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</span>
                    </div>
                    <button onclick="urunEkle('${tavsiye.barkod}')" class="bg-white text-[#215A31] px-5 py-2.5 rounded-xl font-black text-[11px] uppercase">EKLE</button>
                </div>
            </div>`;
            
            $("#tavsiye-konteynir").html(tavsiyeHtml).fadeIn(300);

            // 3 SANİYE SONRA SİL
            setTimeout(() => {
                $("#active-tavsiye").fadeOut(500, function() {
                    $(this).remove();
                });
            }, 3000);
        }

        function startCountdown() {
            if (countdownTimer !== null) return;
            countdownValue = 30;
            $("#warning-countdown-bar").css('display', 'flex');
            $("body").addClass("has-warning");
            $("#timer-val").text(countdownValue);
            countdownTimer = setInterval(() => {
                countdownValue--;
                $("#timer-val").text(countdownValue);
                if (countdownValue <= 0) {
                    clearInterval(countdownTimer);
                    isLocked = true;
                    $("#warning-countdown-bar").hide();
                    $("#security-lock").fadeIn(200).css('display', 'flex');
                }
            }, 1000);
        }

        function stopCountdown() {
            if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
            $("#warning-countdown-bar").hide();
            $("body").removeClass("has-warning");
        }

        $(document).ready(() => { 
            sepetiGetir(); 
            reklamlariYukle(); // Reklamları panelden çek
            setInterval(sepetiGetir, 3000); 
            setInterval(reklamlariYukle, 30000); // Reklamları her 30sn'de bir tazele
            setInterval(() => { if (!isLocked) $("#barcode-receiver").focus(); }, 500); 
        });
    </script>
</body>
</html>