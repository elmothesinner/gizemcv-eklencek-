<?php
/**
 * SmartCart - Tavsiye ve İlişkili Ürün Yönetimi
 */
$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");

// 1. Yeni Tavsiye Ekleme İşlemi
if (isset($_POST['tavsiye_ekle'])) {
    $ana_barkod = mysqli_real_escape_string($conn, $_POST['ana_barkod']);
    $tavsiye_barkod = mysqli_real_escape_string($conn, $_POST['tavsiye_barkod']);
    
    $sql = "INSERT INTO urun_tavsiyeleri (ana_barkod, tavsiye_barkod) 
            VALUES ('$ana_barkod', '$tavsiye_barkod') 
            ON DUPLICATE KEY UPDATE tavsiye_barkod='$tavsiye_barkod'";
    mysqli_query($conn, $sql);
}

// 2. Tavsiye Silme İşlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    mysqli_query($conn, "DELETE FROM urun_tavsiyeleri WHERE id = $id");
}

// Ürün Listesini Çek (Select boxlar için)
$urunler_sorgu = mysqli_query($conn, "SELECT barkod, urun_adi FROM urunler ORDER BY urun_adi ASC");
$urunler = mysqli_fetch_all($urunler_sorgu, MYSQLI_ASSOC);

// Mevcut Eşleşmeleri Çek
$eslesme_sql = "SELECT t.id, u1.urun_adi as ana_urun, u2.urun_adi as tavsiye_urun 
                FROM urun_tavsiyeleri t 
                JOIN urunler u1 ON t.ana_barkod = u1.barkod 
                JOIN urunler u2 ON t.tavsiye_barkod = u2.barkod";
$eslesmeler = mysqli_query($conn, $eslesme_sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SmartCart | Tavsiye Yönetimi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6">

    <div class="max-w-4xl mx-auto">
        <header class="mb-10 text-center">
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">🛒 Tavsiye Motoru Yönetimi</h1>
            <p class="text-slate-400 font-bold text-xs uppercase mt-2">Hangi ürünün yanında ne satılsın?</p>
        </header>

        <div class="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-100 mb-8">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Bu Ürün Alındığında:</label>
                    <select name="ana_barkod" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-sm focus:ring-2 focus:ring-[#215A31]">
                        <?php foreach($urunler as $u): ?>
                            <option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center justify-center pt-6">
                    <span class="text-2xl">➡️</span>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Bunu Tavsiye Et:</label>
                    <select name="tavsiye_barkod" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-sm focus:ring-2 focus:ring-[#215A31]">
                        <?php foreach($urunler as $u): ?>
                            <option value="<?= $u['barkod'] ?>"><?= $u['urun_adi'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <button type="submit" name="tavsiye_ekle" class="w-full py-4 bg-[#215A31] text-white rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-green-900/20 active:scale-[0.98] transition-all">
                        Eşleşmeyi Kaydet ve Yayına Al
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                <h2 class="font-black text-slate-800 uppercase text-sm tracking-tight">Aktif Tavsiye Listesi</h2>
            </div>
            <table class="w-full text-left">
                <thead class="text-[10px] font-black text-slate-400 uppercase bg-slate-50">
                    <tr>
                        <th class="p-4">Ana Ürün</th>
                        <th class="p-4 text-center">İlişki</th>
                        <th class="p-4">Tavsiye Edilen</th>
                        <th class="p-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = mysqli_fetch_assoc($eslesmeler)): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 font-bold text-slate-700 text-sm"><?= $row['ana_urun'] ?></td>
                        <td class="p-4 text-center text-green-500 font-black">>>></td>
                        <td class="p-4 font-bold text-[#0D707D] text-sm"><?= $row['tavsiye_urun'] ?></td>
                        <td class="p-4 text-right">
                            <a href="?sil=<?= $row['id'] ?>" class="bg-red-50 text-red-500 px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-red-500 hover:text-white transition-all">SİL</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php if(mysqli_num_rows($eslesmeler) == 0): ?>
                <div class="p-10 text-center text-slate-300 font-bold uppercase text-xs">Henüz hiç tavsiye tanımlanmadı.</div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>