<?php
/**
 * SmartCart - Vitrin & Fırsat Yönetimi v6.2
 */
declare(strict_types=1);

// Veritabanı Bağlantısı
$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");

// --- API TALEPLERİNİ YÖNET (AJAX) ---
if (isset($_GET['islem'])) {
    header('Content-Type: application/json; charset=utf-8');
    $islem = $_GET['islem'];

    switch ($islem) {
        case 'firsatlari_getir':
            // Tüm ürünleri ve tanıtım tarihlerini çek
            $sql = "SELECT barkod, urun_adi, fiyat, gorsel_url, tanitim_baslangic, tanitim_bitis FROM urunler ORDER BY urun_adi ASC";
            $res = mysqli_query($conn, $sql);
            $firsatlar = mysqli_fetch_all($res, MYSQLI_ASSOC);
            echo json_encode(["haftanin_firsatlari" => $firsatlar]);
            break;

        case 'firsat_zamanla':
            // POST verilerini al
            $barkod = mysqli_real_escape_string($conn, $_POST['barkod']);
            $baslangic = mysqli_real_escape_string($conn, $_POST['baslangic']);
            $bitis = mysqli_real_escape_string($conn, $_POST['bitis']);

            // Boş değer kontrolü (Kaldırma işlemi için NULL set edilir)
            $val_bas = empty($baslangic) ? "NULL" : "'$baslangic'";
            $val_bit = empty($bitis) ? "NULL" : "'$bitis'";

            $update = "UPDATE urunler SET tanitim_baslangic = $val_bas, tanitim_bitis = $val_bit WHERE barkod = '$barkod'";
            
            if (mysqli_query($conn, $update)) {
                echo json_encode(["durum" => "basarili"]);
            } else {
                echo json_encode(["durum" => "hata", "mesaj" => mysqli_error($conn)]);
            }
            break;
    }
    exit; // API yanıtından sonra sayfanın geri kalanını yükleme
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCart | Vitrin Kontrol Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sc-primary: #0d6efd; --sc-success: #198754; --sc-bg: #f8f9fa; }
        body { background-color: var(--sc-bg); font-family: 'Inter', sans-serif; }
        .main-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; }
        .img-container { width: 50px; height: 50px; border-radius: 10px; background: #fff; padding: 3px; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; }
        .img-container img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .status-badge { font-size: 0.7rem; padding: 4px 10px; border-radius: 50px; font-weight: 700; text-transform: uppercase; }
        .active-row { background-color: #f0fff4 !important; border-left: 4px solid var(--sc-success); }
        .table thead th { background: #fdfdfd; color: #888; font-size: 0.75rem; border-bottom: 1px solid #eee; }
        .btn-action { border-radius: 8px; transition: 0.2s; }
        .btn-action:hover { transform: scale(1.05); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark mb-1">Günün Fırsatları & Vitrin</h2>
            <p class="text-muted small">Müşteri tabletindeki "Fırsat" sekmesini buradan yönetin.</p>
        </div>
        <div class="col-md-5 text-md-end">
            <button onclick="listeYukle()" class="btn btn-primary shadow-sm rounded-pill px-4">
                <i class="fa-solid fa-sync-alt me-2"></i>Listeyi Yenile
            </button>
        </div>
    </div>

    <div class="card main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ÜRÜN BİLGİSİ</th>
                        <th>BARKOD</th>
                        <th>BİRİM FİYAT</th>
                        <th>YAYIN DURUMU</th>
                        <th class="text-center">YÖNET</th>
                    </tr>
                </thead>
                <tbody id="urunListesi">
                    <!-- JavaScript ile doldurulacak -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Planlama Modalı -->
<div class="modal fade" id="zamanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-clock me-2 text-primary"></i>Yayın Süresi Belirle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modalBarkod">
                <div class="mb-3">
                    <label class="form-label fw-bold small">YAYIN BAŞLANGIÇ</label>
                    <input type="datetime-local" id="tarihBas" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">YAYIN BİTİŞ</label>
                    <input type="datetime-local" id="tarihBit" class="form-control">
                </div>
                <div class="p-3 bg-light rounded-3 small text-muted">
                    <i class="fa-solid fa-info-circle me-1 text-primary"></i> 
                    Bitiş tarihi dolduğunda ürün otomatik olarak vitrinden kaldırılır.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
                <button type="button" onclick="kaydet()" class="btn btn-primary px-4 shadow">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalKontrol = new bootstrap.Modal(document.getElementById('zamanModal'));

    async function listeYukle() {
        const body = document.getElementById('urunListesi');
        body.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border text-primary"></div></td></tr>';

        try {
            // Mevcut dosyanın kendisine islem parametresi ile istek atıyoruz
            const res = await fetch('?islem=firsatlari_getir');
            const data = await res.json();
            body.innerHTML = '';

            data.haftanin_firsatlari.forEach(u => {
                const aktifMi = u.tanitim_bitis !== null;
                const rowStyle = aktifMi ? 'active-row' : '';
                
                body.innerHTML += `
                    <tr class="${rowStyle}">
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="img-container me-3 shadow-sm">
                                    <img src="${u.gorsel_url}" onerror="this.src='https://via.placeholder.com/150'">
                                </div>
                                <span class="fw-bold">${u.urun_adi}</span>
                            </div>
                        </td>
                        <td><code class="text-primary font-monospace">${u.barkod}</code></td>
                        <td class="fw-bold">${u.fiyat} ₺</td>
                        <td>
                            ${aktifMi ? 
                                `<span class="status-badge bg-success-subtle text-success">YAYINDA</span><br>
                                 <small class="text-muted" style="font-size: 10px;">Bitiş: ${u.tanitim_bitis}</small>` : 
                                `<span class="status-badge bg-light text-muted">PASİF</span>`}
                        </td>
                        <td class="text-center">
                            <button onclick="zamanAc('${u.barkod}')" class="btn btn-sm btn-dark btn-action px-3">
                                <i class="fa-solid fa-calendar-alt me-1"></i> Zamanla
                            </button>
                            ${aktifMi ? `
                                <button onclick="temizle('${u.barkod}')" class="btn btn-sm btn-outline-danger border-0 ms-1">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-danger">Veriler yüklenemedi.</td></tr>';
        }
    }

    function zamanAc(barkod) {
        document.getElementById('modalBarkod').value = barkod;
        modalKontrol.show();
    }

    async function kaydet() {
        const barkod = document.getElementById('modalBarkod').value;
        const fd = new FormData();
        fd.append('barkod', barkod);
        fd.append('baslangic', document.getElementById('tarihBas').value);
        fd.append('bitis', document.getElementById('tarihBit').value);

        const res = await fetch('?islem=firsat_zamanla', { method: 'POST', body: fd });
        const r = await res.json();
        if(r.durum === "basarili") {
            modalKontrol.hide();
            listeYukle();
        }
    }

    async function temizle(barkod) {
        if(!confirm('Vitrinden silinsin mi?')) return;
        const fd = new FormData();
        fd.append('barkod', barkod);
        fd.append('baslangic', '');
        fd.append('bitis', '');
        await fetch('?islem=firsat_zamanla', { method: 'POST', body: fd });
        listeYukle();
    }

    window.onload = listeYukle;
</script>
</body>
</html>