<?php
/**
 * SmartCart - GENEL Yönetim Paneli 
 * Envanter + Vitrin + Tavsiye + Reklam + Loglar + Siber Kalkan
 */
declare(strict_types=1);
session_start();

// --- 1. SİBER GÜVENLİK ---
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/HTTrack|Wget|curl|python|BurpSuite/i", $_SERVER['HTTP_USER_AGENT'])) {
    http_response_code(403); die("Erisim Engellendi.");
}

// --- 2. ADMİN GİRİŞ ---
$admin_pass = "123456"; 
if (isset($_GET['logout'])) { session_destroy(); header("Location: ?"); exit; }
if (!isset($_SESSION['is_admin'])) {
    if (isset($_POST['login_pass']) && $_POST['login_pass'] === $admin_pass) { $_SESSION['is_admin'] = true; } 
    else {
        die('<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-[#0a0e17] flex items-center justify-center h-screen"><form method="POST" class="bg-[#16213e] p-10 rounded-[2.5rem] shadow-2xl border border-[#1e293b] text-center"><img src="https://gizemgunduz.store/logo.png" class="h-8 mx-auto mb-4 text-white"><h2 class="text-white font-black uppercase text-[10px] mb-6">Genel Yönetim Paneli</h2><input type="password" name="login_pass" placeholder="Sifre" autofocus class="w-full bg-[#0f172a] border border-[#334155] p-4 rounded-2xl text-white text-center focus:outline-none mb-4"><button class="w-full bg-cyan-500 text-white font-black py-4 rounded-2xl uppercase text-xs">Sisteme Baglan</button></form></body></html>');
    }
}

// --- 3. DB BAĞLANTISI ---
$db_host = "gizemgunduz.store"; $db_user = "kedi"; $db_pass = "kedis2minis1"; $db_name = "akilli_market";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");

// --- 4. MERKEZİ API VE İŞLEM YÖNETİMİ ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $aid = (int)($_GET['aid'] ?? 1);
    $val = mysqli_real_escape_string($conn, $_GET['val'] ?? '');
    switch($_GET['action']) {
        case 'ekle': 
            mysqli_query($conn, "INSERT INTO sepet (araba_id, barkod) VALUES ($aid, '$val')");
            mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($aid, 'URUN EKLENDI: $val')"); 
            break;
        case 'urun_sil':
            mysqli_query($conn, "DELETE FROM sepet WHERE id=".(int)$val);
            mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($aid, 'URUN SILINDI (ID: $val)')");
            break;
        case 'reset': 
            mysqli_query($conn, "DELETE FROM sepet WHERE araba_id=$aid"); 
            mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=0, durum='beklemede' WHERE araba_id=$aid");
            mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($aid, 'SISTEM SIFIRLANDI')");
            break;
        case 'agirlik_set': mysqli_query($conn, "INSERT INTO weights (weight) VALUES ($val)"); break;
        case 'get_logs': 
            $res = mysqli_query($conn, "SELECT islem, tarih FROM loglar WHERE araba_id=$aid ORDER BY tarih DESC LIMIT 15");
            echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC)); exit;
        case 'vitrin_zamanla':
            $bas = mysqli_real_escape_string($conn, $_POST['bas']); $bit = mysqli_real_escape_string($conn, $_POST['bit']);
            mysqli_query($conn, "UPDATE urunler SET tanitim_baslangic='$bas', tanitim_bitis='$bit' WHERE barkod='$val'");
            break;
    }
    echo json_encode(["status" => "ok"]); exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['env_ekle'])){
        mysqli_query($conn, "INSERT INTO urunler (barkod, urun_adi, fiyat, gramaj) VALUES ('".mysqli_real_escape_string($conn, $_POST['barkod'])."', '".mysqli_real_escape_string($conn, $_POST['ad'])."', ".(float)$_POST['fiyat'].", ".(int)$_POST['gramaj'].")");
    }
    if(isset($_POST['reklam_ekle'])){
        mysqli_query($conn, "INSERT INTO reklamlar (reklam_tipi, icerik_metni, gorsel_url) VALUES ('".$_POST['tip']."', '".mysqli_real_escape_string($conn, $_POST['metin'])."', '".mysqli_real_escape_string($conn, $_POST['url'])."')");
    }
    if(isset($_POST['tav_ekle'])){
        mysqli_query($conn, "INSERT INTO urun_tavsiyeleri (ana_barkod, tavsiye_barkod) VALUES ('".$_POST['ana']."', '".$_POST['tav']."') ON DUPLICATE KEY UPDATE tavsiye_barkod='".$_POST['tav']."'");
    }
}
if(isset($_GET['sil_reklam'])) mysqli_query($conn, "DELETE FROM reklamlar WHERE id=".(int)$_GET['sil_reklam']);
if(isset($_GET['sil_tav'])) mysqli_query($conn, "DELETE FROM urun_tavsiyeleri WHERE id=".(int)$_GET['sil_tav']);

$urunler = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM urunler ORDER BY urun_adi ASC"), MYSQLI_ASSOC);
$reklamlar = mysqli_query($conn, "SELECT * FROM reklamlar ORDER BY id DESC");
$tavsiyeler = mysqli_query($conn, "SELECT t.*, u1.urun_adi as ana, u2.urun_adi as tav FROM urun_tavsiyeleri t JOIN urunler u1 ON t.ana_barkod=u1.barkod JOIN urunler u2 ON t.tavsiye_barkod=u2.barkod");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><title>Avo-Cart | Genel Yönetim Paneli</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0a0e17; color: #cbd5e1; overflow-x: hidden; }
        .nav-tab.active { color: #22d3ee; border-bottom: 2px solid #22d3ee; }
        .glass { background: #16213e; border: 1px solid #1e293b; }
        .terminal { background: #000; font-family: monospace; font-size: 10px; color: #2ed573; height: 180px; overflow-y: auto; padding: 10px; border: 1px solid #333; }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="p-4 md:p-8">

    <header class="flex flex-wrap justify-between items-center mb-8 glass p-6 rounded-[2rem] gap-4">
        <div class="flex items-center gap-4"><img src="https://gizemgunduz.store/logo.png" class="h-6"><h1 class="font-black uppercase text-white tracking-tighter">Genel Yönetim Paneli</h1></div>
        <div class="flex flex-wrap gap-2 text-[9px] font-black uppercase">
            <button onclick="tabAc('fleet')" class="nav-tab active px-3 py-2">Komuta & Loglar</button>
            <button onclick="tabAc('env')" class="nav-tab px-3 py-2">Envanter & Vitrin</button>
            <button onclick="tabAc('marketing')" class="nav-tab px-3 py-2">Reklam & Tavsiye</button>
            <a href="?logout" class="bg-red-500/20 text-red-500 px-3 py-2 rounded-xl">CIKIS</a>
        </div>
    </header>

    <!-- 1. SEKME: KOMUTA VE LOGLAR -->
    <section id="tab-fleet" class="tab-content grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-4 space-y-4">
            <div id="fleet-list" class="space-y-3"></div>
            <div id="log-content" class="terminal mt-4"></div>
        </div>
        <div class="md:col-span-8">
            <div id="unit-detail" class="glass p-8 rounded-[2.5rem] hidden">
                <div class="flex justify-between items-center mb-8">
                    <h2 id="detay-id" class="text-3xl font-black text-white italic">UNIT #--</h2>
                    <div class="flex gap-2">
                        <button onclick="aksiyon('reset')" class="bg-red-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase shadow-lg">Hard Reset</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div class="bg-[#0f172a] p-6 rounded-3xl border border-[#1e293b]">
                        <span class="text-[10px] font-black text-slate-500 uppercase">Load Cell Sim</span>
                        <div id="detay-agirlik" class="text-4xl font-black text-cyan-400 my-4">0g</div>
                        <input type="range" min="0" max="5000" class="w-full" onchange="agirlikSet(this.value)">
                    </div>
                    <div class="bg-[#0f172a] p-6 rounded-3xl border border-[#1e293b]">
                        <span class="text-[10px] font-black text-slate-500 uppercase">Sanal Okuyucu</span>
                        <div class="flex gap-2 mt-4">
                            <select id="sim-barkod" class="bg-black text-[10px] p-2 rounded-lg flex-1">
                                <?php foreach($urunler as $u): ?><option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?></option><?php endforeach; ?>
                            </select>
                            <button onclick="aksiyon('ekle', $('#sim-barkod').val())" class="bg-cyan-500 text-white p-2 rounded-lg text-[10px] font-black">OKUT</button>
                        </div>
                    </div>
                </div>
                <div id="detay-cart" class="bg-[#0a0e17] p-6 rounded-3xl border border-[#1e293b] text-xs"></div>
            </div>
        </div>
    </section>

    <!-- 2. SEKME: ENVANTER VE VİTRİN -->
    <section id="tab-env" class="tab-content hidden grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-4 glass p-8 rounded-[2.5rem] h-fit">
            <h3 class="font-black text-white uppercase italic mb-6">Urun Kayit</h3>
            <form method="POST" class="space-y-4">
                <input type="text" name="barkod" placeholder="Barkod" class="w-full bg-[#0a0e17] p-3 rounded-xl border border-[#334155]" required>
                <input type="text" name="ad" placeholder="Urun Adi" class="w-full bg-[#0a0e17] p-3 rounded-xl border border-[#334155]" required>
                <div class="flex gap-4">
                    <input type="number" step="0.01" name="fiyat" placeholder="Fiyat" class="w-1/2 bg-[#0a0e17] p-3 rounded-xl border border-[#334155]" required>
                    <input type="number" name="gramaj" placeholder="Gram" class="w-1/2 bg-[#0a0e17] p-3 rounded-xl border border-[#334155]" required>
                </div>
                <button name="env_ekle" class="w-full bg-cyan-500 text-white font-black py-4 rounded-xl uppercase text-xs">Envantere Ekle</button>
            </form>
        </div>
        <div class="md:col-span-8 glass p-6 rounded-[2.5rem] overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="font-black uppercase text-slate-500"><tr><th class="p-4">Urun Detay</th><th>Vitrin Zamanlama</th><th class="text-right p-4">Islem</th></tr></thead>
                <tbody class="divide-y divide-[#1e293b]">
                    <?php foreach($urunler as $u): ?>
                    <tr>
                        <td class="p-4"><b><?= $u['urun_adi'] ?></b><br><small class="text-cyan-400 font-mono"><?= $u['barkod'] ?></small></td>
                        <td><small class="text-[9px]"><?= $u['tanitim_bitis'] ? $u['tanitim_baslangic'].' / '.$u['tanitim_bitis'] : 'Kapalı' ?></small></td>
                        <td class="text-right p-4"><button onclick="vitrinAc('<?= $u['barkod'] ?>')" class="bg-slate-700 text-white px-3 py-1 rounded text-[9px] uppercase font-black">Zamanla</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 3. SEKME: REKLAM VE TAVSİYE -->
    <section id="tab-marketing" class="tab-content hidden grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-4 space-y-6">
            <div class="glass p-8 rounded-[2.5rem]">
                <h3 class="font-black text-white uppercase italic mb-4 text-xs">Reklam Ekle</h3>
                <form method="POST" class="space-y-4">
                    <select name="tip" class="w-full bg-black p-3 rounded-xl"><option value="popup">Pop-Up</option><option value="bant">Kayan Bant</option></select>
                    <input type="text" name="metin" placeholder="Mesaj" class="w-full bg-black p-3 rounded-xl" required>
                    <input type="text" name="url" placeholder="Gorsel URL" class="w-full bg-black p-3 rounded-xl">
                    <button name="reklam_ekle" class="w-full bg-green-600 text-white font-black py-3 rounded-xl uppercase text-xs">Yayinla</button>
                </form>
            </div>
            <div class="glass p-8 rounded-[2.5rem]">
                <h3 class="font-black text-white uppercase italic mb-4 text-xs">Tavsiye Motoru</h3>
                <form method="POST" class="space-y-4">
                    <select name="ana" class="w-full bg-black p-3 rounded-xl"><?php foreach($urunler as $u): ?><option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?></option><?php endforeach; ?></select>
                    <select name="tav" class="w-full bg-black p-3 rounded-xl"><?php foreach($urunler as $u): ?><option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?></option><?php endforeach; ?></select>
                    <button name="tav_ekle" class="w-full bg-purple-600 text-white font-black py-3 rounded-xl uppercase text-xs">Eslestir</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-8 space-y-4">
            <div class="glass p-6 rounded-[2.5rem]">
                <h4 class="text-[9px] font-black uppercase text-slate-500 mb-4">Aktif Reklamlar</h4>
                <div class="grid grid-cols-2 gap-2 text-[10px]">
                    <?php while($r = mysqli_fetch_assoc($reklamlar)): ?>
                    <div class="bg-black/50 p-3 rounded-xl flex justify-between">
                        <span>[<?= $r['reklam_tipi'] ?>] <?= substr($r['icerik_metni'], 0, 20) ?>...</span>
                        <a href="?sil_reklam=<?= $r['id'] ?>" class="text-red-500 font-black">X</a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="glass p-6 rounded-[2.5rem]">
                <h4 class="text-[9px] font-black uppercase text-slate-500 mb-4">Tavsiye Iliskileri</h4>
                <div class="grid grid-cols-2 gap-2 text-[10px]">
                    <?php while($t = mysqli_fetch_assoc($tavsiyeler)): ?>
                    <div class="bg-black/50 p-3 rounded-xl flex justify-between border-l-2 border-purple-500">
                        <span><?= $t['ana'] ?> ➡️ <?= $t['tav'] ?></span>
                        <a href="?sil_tav=<?= $t['id'] ?>" class="text-red-500 font-black">X</a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- MODAL: VITRIN -->
    <div id="vit-modal" class="fixed inset-0 bg-black/90 hidden z-[200] items-center justify-center p-4">
        <div class="glass p-10 rounded-[3rem] w-full max-w-sm">
            <h3 class="font-black text-white uppercase italic mb-6">Vitrin Planla</h3>
            <input type="hidden" id="vit-barkod">
            <div class="space-y-4 text-[10px] font-black uppercase text-slate-400">
                <label>Baslangic</label><input type="datetime-local" id="vit-bas" class="w-full bg-black p-4 rounded-2xl text-white">
                <label>Bitis</label><input type="datetime-local" id="vit-bit" class="w-full bg-black p-4 rounded-2xl text-white">
                <button onclick="vitKaydet()" class="w-full bg-cyan-500 text-white font-black py-5 rounded-2xl mt-4">Yayini Baslat</button>
                <button onclick="$('#vit-modal').hide()" class="w-full text-slate-500 font-black py-2">Iptal</button>
            </div>
        </div>
    </div>

    <script>
        let activeId = null;
        function tabAc(t) { $('.tab-content').addClass('hidden'); $('.nav-tab').removeClass('active'); $('#tab-'+t).removeClass('hidden'); $(`button[onclick="tabAc('${t}')"]`).addClass('active'); }
        
        function fleetGuncelle() {
            $.getJSON('api.php?islem=sepeti_getir&araba_id=1', function(data) {
                let html = `<div onclick="unitSec(1)" class="glass p-6 rounded-3xl border-l-4 ${data.hata_durumu > 0 ? 'border-red-500 animate-pulse' : 'border-cyan-400'} cursor-pointer">
                    <div class="flex justify-between font-black text-white italic"><span>UNIT #1</span><span>${data.terazi_durumu.terazi}g</span></div>
                    <div class="text-[9px] font-bold text-slate-500 mt-1 uppercase">${data.hata_durumu > 0 ? 'HATA' : 'GÜVENLİ'}</div></div>`;
                $('#fleet-list').html(html);
                if(activeId == 1) {
                    $('#detay-agirlik').text(data.terazi_durumu.terazi + "g");
                    let cart = '';
                    data.urunler.forEach(u => { cart += `<div class="flex justify-between p-2 bg-black/30 rounded-lg mt-1"><span>${u.urun_adi}</span><button onclick="aksiyon('urun_sil', ${u.sepet_id})" class="text-red-500">[X]</button></div>`; });
                    $('#detay-cart').html(cart || 'Sepet Bos');
                    logCek();
                }
            });
        }

        function logCek() { $.getJSON(`?action=get_logs&aid=1`, (logs) => { let h = ''; logs.forEach(l => { h += `[${l.tarih.split(' ')[1]}] ${l.islem}<br>`; }); $('#log-content').html(h); }); }
        function unitSec(id) { activeId = id; $('#unit-detail').fadeIn(); fleetGuncelle(); }
        function agirlikSet(v) { $.get(`?action=agirlik_set&aid=1&val=${v}`, fleetGuncelle); }
        function aksiyon(a, v = '') { $.get(`?action=${a}&aid=1&val=${v}`, fleetGuncelle); }
        function vitrinAc(b) { $('#vit-barkod').val(b); $('#vit-modal').css('display', 'flex'); }
        function vitKaydet() {
            let b = $('#vit-barkod').val();
            let fd = new FormData(); fd.append('bas', $('#vit-bas').val()); fd.append('bit', $('#vit-bit').val());
            fetch(`?action=vitrin_zamanla&val=${b}`, { method: 'POST', body: fd }).then(() => { $('#vit-modal').hide(); location.reload(); });
        }

        // Guvenlik
        function kick() { document.body.innerHTML = "<h1>ERISIM REDDEDILDI</h1>"; setTimeout(() => location.replace("about:blank"), 1000); }
        document.addEventListener('contextmenu', e => { e.preventDefault(); kick(); });
        document.onkeydown = e => { if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || (e.ctrlKey && e.keyCode == 85)) { kick(); return false; } };

        setInterval(fleetGuncelle, 3000);
        $(document).ready(fleetGuncelle);
    </script>
</body>
</html>