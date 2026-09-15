<?php
// reklam_yonetici.php
$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Reklam Ekle
if(isset($_POST['ekle'])){
    $tip = $_POST['reklam_tipi'];
    $metin = $_POST['icerik_metni'];
    $url = $_POST['gorsel_url'];
    mysqli_query($conn, "INSERT INTO reklamlar (reklam_tipi, icerik_metni, gorsel_url) VALUES ('$tip', '$metin', '$url')");
}

// Reklam Sil
if(isset($_GET['sil'])){
    $id = (int)$_GET['sil'];
    mysqli_query($conn, "DELETE FROM reklamlar WHERE id = $id");
}

$reklamlar = mysqli_query($conn, "SELECT * FROM reklamlar ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SmartCart | Reklam Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-8 font-sans">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-black mb-8 text-slate-800 uppercase italic">Reklam Yönetim Merkezi</h1>
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <form method="POST" class="grid grid-cols-2 gap-4">
                <select name="reklam_tipi" class="p-4 bg-slate-50 rounded-xl font-bold">
                    <option value="popup">Sağ Popup (Görsel Gerektirir)</option>
                    <option value="bant">Alt Kayan Bant (Sadece Metin)</option>
                </select>
                <input type="text" name="icerik_metni" placeholder="Reklam Metni" class="p-4 bg-slate-50 rounded-xl" required>
                <input type="text" name="gorsel_url" placeholder="Görsel URL (Popup için)" class="p-4 bg-slate-50 rounded-xl col-span-2">
                <button name="ekle" class="bg-[#215A31] text-white p-4 rounded-xl font-bold uppercase">Reklamı Yayına Al</button>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400">
                    <tr>
                        <th class="p-4">TİP</th>
                        <th class="p-4">İÇERİK</th>
                        <th class="p-4 text-right">İŞLEM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($r = mysqli_fetch_assoc($reklamlar)): ?>
                    <tr class="text-sm">
                        <td class="p-4 font-bold text-[#0D707D] uppercase"><?= $r['reklam_tipi'] ?></td>
                        <td class="p-4 font-medium text-slate-600"><?= $r['icerik_metni'] ?></td>
                        <td class="p-4 text-right">
                            <a href="?sil=<?= $r['id'] ?>" class="text-red-500 font-bold text-xs uppercase">SİL</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>