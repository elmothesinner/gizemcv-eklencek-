<?php
/**
 * SmartCart - Command Center v6.0 (Ultimate IoT Console)
 */
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(0);

$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Erişim Reddedildi."); }
mysqli_set_charset($conn, "utf8mb4");

// --- API MANTIĞI ---
if (isset($_GET['action'])) {
    $aid = isset($_GET['araba_id']) ? (int)$_GET['araba_id'] : 1;
    $val = mysqli_real_escape_string($conn, $_GET['val'] ?? '');
    header('Content-Type: application/json');

    switch($_GET['action']) {
        case 'ekle':
            mysqli_query($conn, "INSERT INTO sepet (araba_id, barkod) VALUES ($aid, '$val')");
            exit(json_encode(["status" => "ok"]));

        case 'agirlik_set':
            mysqli_query($conn, "INSERT INTO weights (weight) VALUES ($val)");
            exit(json_encode(["status" => "ok"]));

        case 'lock_force':
            // Arabayı zorla kilitle (Güvenlik Uyarısı Çıkar)
            mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=1 WHERE araba_id=$aid");
            exit(json_encode(["status" => "ok", "msg" => "Ünite Uzaktan Kilitlendi!"]));

        case 'reset':
            mysqli_query($conn, "DELETE FROM sepet WHERE araba_id=$aid");
            mysqli_query($conn, "DELETE FROM loglar WHERE araba_id=$aid");
            mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=0, durum='beklemede' WHERE araba_id=$aid");
            exit(json_encode(["status" => "ok", "msg" => "Sistem Fabrika Ayarlarına Döndü."]));

        case 'bypass':
            mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=0 WHERE araba_id=$aid");
            exit(json_encode(["status" => "ok"]));
        
        case 'get_fleet_status':
            $res = mysqli_query($conn, "SELECT araba_id, pil_yuzdesi, guvenlik_uyarisi, durum FROM arabalar");
            echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
            exit;

        case 'get_logs':
            $res = mysqli_query($conn, "SELECT islem, tarih FROM loglar WHERE araba_id = $aid ORDER BY tarih DESC LIMIT 20");
            echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
            exit;
    }
}
$urunler = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM urunler"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SmartCart | Ultimate IoT Console</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --neon-blue: #00d2ff; --neon-red: #ff4757; --neon-green: #2ed573; --bg-dark: #0a0e17; --panel-bg: rgba(22, 33, 62, 0.95); }
        body { background: var(--bg-dark); color: white; font-family: 'Inter', sans-serif; display: flex; margin: 0; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 300px; background: var(--panel-bg); border-right: 1px solid #1e293b; padding: 20px; overflow-y: auto; }
        .unit-card { background: #16213e; padding: 15px; border-radius: 12px; margin-bottom: 10px; cursor: pointer; border: 2px solid transparent; transition: 0.3s; }
        .unit-card.active { border-color: var(--neon-blue); box-shadow: 0 0 15px rgba(0, 210, 255, 0.2); }
        .unit-card.locked { border-left: 5px solid var(--neon-red); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        /* Layout */
        .main { flex: 1; display: grid; grid-template-columns: 1fr 400px; gap: 0; }
        .center-content { padding: 25px; overflow-y: auto; border-right: 1px solid #1e293b; }
        .right-panel { padding: 20px; background: #0a0e17; display: flex; flex-direction: column; gap: 20px; }

        /* Widgets */
        .glass-card { background: #16213e; border-radius: 20px; padding: 20px; border: 1px solid #1e293b; margin-bottom: 20px; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .stat-box { background: #0f172a; padding: 15px; border-radius: 15px; text-align: center; }
        .stat-val { font-size: 1.5rem; font-weight: 900; display: block; font-family: monospace; }
        .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: bold; }

        /* Controls */
        .btn { padding: 12px; border: none; border-radius: 10px; color: white; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 12px; text-transform: uppercase; }
        .btn-blue { background: var(--neon-blue); }
        .btn-red { background: var(--neon-red); }
        .btn-orange { background: #f39c12; }
        .btn:hover { filter: brightness(1.2); }

        /* Terminal & Logs */
        .terminal { background: #000; border-radius: 10px; padding: 15px; font-family: 'Courier New', monospace; font-size: 11px; color: var(--neon-green); height: 250px; overflow-y: auto; border: 1px solid #333; }
        .log-line { margin-bottom: 5px; border-bottom: 1px solid #111; padding-bottom: 2px; }
        .log-time { color: #555; margin-right: 8px; }

        select, input[type="range"] { width: 100%; background: #0f172a; color: white; border: 1px solid #333; padding: 8px; border-radius: 8px; margin-top: 5px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 style="letter-spacing: 2px; margin-bottom: 20px;"><i class="fa-solid fa-microchip"></i> FLEET CONTROL</h3>
    <div id="unit-list"></div>
</div>

<div class="main">
    <div class="center-content">
        <div id="no-select" style="text-align:center; margin-top:200px; opacity:0.3;">
            <i class="fa-solid fa-satellite-dish fa-4x mb-4"></i>
            <h2>Ünite Bağlantısı Bekleniyor...</h2>
        </div>

        <div id="ui" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h1 id="unit-title" style="margin:0; color:var(--neon-blue);">Unit #--</h1>
                <div style="display:flex; gap:10px;">
                    <button onclick="aksiyon('bypass')" class="btn btn-orange"><i class="fa-solid fa-unlock"></i> Bypass</button>
                    <button onclick="aksiyon('lock_force')" class="btn btn-red"><i class="fa-solid fa-lock"></i> Force Lock</button>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-box">
                    <span class="stat-label">Sensör Ağırlığı</span>
                    <span id="live-weight" class="stat-val">0g</span>
                    <input type="range" id="weight-slider" min="0" max="5000" step="5" onchange="agirlikSet(this.value)">
                </div>
                <div class="stat-box">
                    <span class="stat-label">Sapma Analizi</span>
                    <span id="weight-diff" class="stat-val">0g</span>
                    <small id="status-text">SECURE</small>
                </div>
            </div>

            <div class="glass-card" style="margin-top:20px;">
                <h4 class="stat-label"><i class="fa-solid fa-barcode"></i> Sanal Barkod Okuyucu</h4>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <select id="urun-select">
                        <?php foreach($urunler as $u): ?>
                            <option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?> (<?= $u['gramaj'] ?>g) - <?= $u['fiyat'] ?>₺</option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="aksiyon('ekle')" class="btn btn-blue" style="white-space:nowrap;">Scan Item</button>
                </div>
            </div>

            <div class="glass-card">
                <h4 class="stat-label"><i class="fa-solid fa-cart-flatbed"></i> Aktif Sepet</h4>
                <div id="cart-list" style="max-height: 200px; overflow-y:auto; font-size: 13px;"></div>
                <div style="text-align:right; border-top:1px solid #333; margin-top:10px; padding-top:10px;">
                    <span class="stat-label">Toplam Tutar:</span>
                    <span id="total-val" style="font-size:1.5rem; font-weight:900; color:var(--neon-green);">0.00 ₺</span>
                </div>
            </div>

            <button onclick="aksiyon('reset')" class="btn" style="background:#444; width:100%; border:1px solid #666; opacity:0.6;"><i class="fa-solid fa-power-off"></i> Hard Reset (Clean All Data)</button>
        </div>
    </div>

    <div class="right-panel">
        <h4 class="stat-label"><i class="fa-solid fa-terminal"></i> Security Logs & Terminal</h4>
        <div id="log-content" class="terminal"></div>
        
        <div class="glass-card" style="margin-top:auto;">
            <h4 class="stat-label">System Health</h4>
            <div style="display:flex; justify-content:space-between; margin-top:10px;">
                <small>Cloud Sync</small>
                <small style="color:var(--neon-green)">ACTIVE</small>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:5px;">
                <small>Database Latency</small>
                <small>14ms</small>
            </div>
        </div>
    </div>
</div>

<script>
    let activeId = null;

    function fleetGuncelle() {
        $.getJSON('simulator.php?action=get_fleet_status', function(data) {
            let html = '';
            data.forEach(c => {
                const isActive = activeId == c.araba_id ? 'active' : '';
                const isLocked = c.guvenlik_uyarisi == 1 ? 'locked' : '';
                html += `<div class="unit-card ${isActive} ${isLocked}" onclick="unitSec(${c.araba_id})">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:bold;">UNIT #${c.araba_id}</span>
                        <span style="font-size:10px; padding:2px 6px; background:rgba(255,255,255,0.1); border-radius:4px;">${c.durum}</span>
                    </div>
                    <div style="height:4px; background:#000; margin:10px 0; border-radius:2px; overflow:hidden;">
                        <div style="width:${c.pil_yuzdesi}%; height:100%; background:linear-gradient(90deg, #ff4757, #2ed573);"></div>
                    </div>
                    <small style="font-size:10px; opacity:0.6;">Battery: %${c.pil_yuzdesi}</small>
                </div>`;
            });
            $('#unit-list').html(html);
        });
    }

    function unitSec(id) {
        activeId = id;
        $('#no-select').hide(); $('#ui').show();
        $('#unit-title').text("Unit #" + id);
        verileriCek();
    }

    function verileriCek() {
        if(!activeId) return;
        $.getJSON(`api.php?islem=sepeti_getir&araba_id=${activeId}`, function(data) {
            const terazi = parseFloat(data.terazi_durumu.terazi || 0);
            const fark = parseFloat(data.terazi_durumu.fark || 0);
            
            $('#live-weight').html(Math.round(terazi) + "g");
            $('#weight-diff').html((fark > 0 ? "+" : "") + Math.round(fark) + "g");

            if(Math.abs(fark) > 100) {
                $('#weight-diff').css('color', 'var(--neon-red)');
                $('#status-text').text("VULNERABLE").css('color', 'var(--neon-red)');
            } else {
                $('#weight-diff').css('color', 'var(--neon-green)');
                $('#status-text').text("SECURE").css('color', 'var(--neon-green)');
            }

            let html = ''; let total = 0;
            if(data.urunler) {
                data.urunler.forEach(u => {
                    total += parseFloat(u.fiyat);
                    html += `<div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #1e293b;">
                        <span>${u.urun_adi}</span>
                        <span style="color:var(--neon-blue); font-family:monospace;">${u.fiyat}₺</span>
                    </div>`;
                });
            }
            $('#cart-list').html(html || '<div style="opacity:0.2; padding:20px; text-align:center;">Empty Cart</div>');
            $('#total-val').text(total.toFixed(2) + " ₺");
        });

        $.getJSON(`simulator.php?action=get_logs&araba_id=${activeId}`, function(logs) {
            let html = '';
            logs.forEach(l => {
                html += `<div class="log-line"><span class="log-time">${l.tarih.split(' ')[1]}</span> ${l.islem}</div>`;
            });
            $('#log-content').html(html);
        });
    }

    function agirlikSet(v) {
        if(!activeId) return;
        $.get(`simulator.php?action=agirlik_set&araba_id=${activeId}&val=${v}`, verileriCek);
    }

    function aksiyon(a) {
        if(!activeId) return;
        let v = (a === 'ekle') ? $('#urun-select').val() : '';
        $.get(`simulator.php?action=${a}&araba_id=${activeId}&val=${v}`, function(res) {
            if(res.msg) console.log(res.msg);
            verileriCek();
            fleetGuncelle();
        });
    }

    setInterval(fleetGuncelle, 4000);
    setInterval(verileriCek, 2000);
    $(document).ready(fleetGuncelle);
</script>
</body>
</html>