<?php
/**
 * SmartCart - Tümleşik API Sistemi (v5.0)
 * Pi-Panel, Sepet, Vitrin ve Yönetim Paneli Tam Entegrasyon
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$db_host = "gizemgunduz.store";
$db_user = "kedi";
$db_pass = "kedis2minis1";
$db_name = "akilli_market";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    http_response_code(500);
    die(json_encode(["durum" => "hata", "mesaj" => "Baglanti hatasi"]));
}

// Parametreleri temizle
$islem    = mysqli_real_escape_string($conn, trim($_REQUEST['islem'] ?? ''));
$araba_id = (int)($_REQUEST['araba_id'] ?? 1);
$uid      = mysqli_real_escape_string($conn, trim($_REQUEST['uid'] ?? $_REQUEST['barkod'] ?? ''));

if (ob_get_length()) ob_clean();

switch($islem) {

    case 'sepeti_getir':
        // 1. SON İŞLEM ZAMANI
        $son_islem_res = mysqli_query($conn, "SELECT (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(s.ekleme_tarihi)) as fark 
                            FROM sepet s WHERE s.araba_id = $araba_id ORDER BY s.id DESC LIMIT 1");
        $son_islem = mysqli_fetch_assoc($son_islem_res);
        $fark_saniye = (int)($son_islem['fark'] ?? 0);

        // 2. AĞIRLIK KONTROLÜ
        $kontrol = agirlikKontrolEt($conn, $araba_id);
        $hata_seviyesi = 0;

        if ($kontrol['durum'] == "hata") {
            $hata_seviyesi = 1; 
            if ($fark_saniye >= 30) {
                $hata_seviyesi = 2; 
                mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=1 WHERE araba_id=$araba_id");
            }
        } else {
            mysqli_query($conn, "UPDATE arabalar SET guvenlik_uyarisi=0 WHERE araba_id=$araba_id");
        }

        // 3. SEPET LİSTESİ
        $res = mysqli_query($conn, "SELECT s.id as sepet_id, u.urun_adi, u.fiyat, u.gorsel_url, u.barkod 
                                    FROM sepet s JOIN urunler u ON s.barkod = u.barkod 
                                    WHERE s.araba_id = $araba_id ORDER BY s.id DESC");
        $urunler = mysqli_fetch_all($res, MYSQLI_ASSOC);

        // 4. TAVSİYE MOTORU
        $tavsiye_urun = null;
        if (!empty($urunler)) {
            $en_son = $urunler[0]['barkod'];
            $t_res = mysqli_query($conn, "SELECT u.barkod, u.urun_adi, u.fiyat, u.gorsel_url 
                                          FROM urun_tavsiyeleri t JOIN urunler u ON t.tavsiye_barkod = u.barkod 
                                          WHERE t.ana_barkod = '$en_son' LIMIT 1");
            $tavsiye_urun = mysqli_fetch_assoc($t_res);
        }

        echo json_encode([
            "hata_durumu" => $hata_seviyesi,
            "terazi_durumu" => $kontrol,
            "urunler" => $urunler,
            "tavsiye_urun" => $tavsiye_urun
        ]);
        break;

    case 'tanitilan_urunler':
        // Pi-Panel (Haftalık Fırsatlar) için aktif zamanlı ürünler
        $sql = "SELECT barkod, urun_adi, fiyat, gorsel_url 
                FROM urunler 
                WHERE tanitim_baslangic <= NOW() AND tanitim_bitis >= NOW() 
                ORDER BY tanitim_baslangic DESC LIMIT 6";
        $res = mysqli_query($conn, $sql);
        $urunler = mysqli_fetch_all($res, MYSQLI_ASSOC);
        echo json_encode(["durum" => "basarili", "urunler" => $urunler]);
        break;

    case 'firsatlari_getir':
        // Vitrin yönetim paneli için tüm liste
        $sql = "SELECT barkod, urun_adi, fiyat, gorsel_url, tanitim_baslangic, tanitim_bitis FROM urunler ORDER BY urun_adi ASC";
        $res = mysqli_query($conn, $sql);
        echo json_encode(["haftanin_firsatlari" => mysqli_fetch_all($res, MYSQLI_ASSOC)]);
        break;

    case 'firsat_zamanla':
        // Vitrin yönetim panelinden gelen POST isteği
        $barkod = mysqli_real_escape_string($conn, $_POST['barkod'] ?? $uid);
        $baslangic = mysqli_real_escape_string($conn, $_POST['baslangic'] ?? '');
        $bitis = mysqli_real_escape_string($conn, $_POST['bitis'] ?? '');

        $val_bas = empty($baslangic) ? "NULL" : "'$baslangic'";
        $val_bit = empty($bitis) ? "NULL" : "'$bitis'";

        $update = "UPDATE urunler SET tanitim_baslangic = $val_bas, tanitim_bitis = $val_bit WHERE barkod = '$barkod'";
        if (mysqli_query($conn, $update)) {
            echo json_encode(["durum" => "basarili"]);
        } else {
            echo json_encode(["durum" => "hata", "mesaj" => mysqli_error($conn)]);
        }
        break;

    case 'reklamlari_getir':
        // Sepet altındaki kayan bant için reklamlar
        $res = mysqli_query($conn, "SELECT reklam_tipi, icerik_metni, gorsel_url FROM reklamlar WHERE aktif_mi = 1 ORDER BY sira ASC");
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
        break;

    case 'sepete_ekle':
        $urun_sorgu = mysqli_query($conn, "SELECT barkod FROM urunler WHERE barkod = '$uid'");
        if (mysqli_num_rows($urun_sorgu) > 0) {
            mysqli_query($conn, "INSERT INTO sepet (araba_id, barkod) VALUES ($araba_id, '$uid')");
            mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($araba_id, 'URUN EKLENDI: $uid')");
            echo json_encode(["durum" => "basarili"]);
        }
        break;

    case 'urun_sil':
        $sepet_id = (int)$_REQUEST['sepet_id'];
        mysqli_query($conn, "DELETE FROM sepet WHERE id = $sepet_id AND araba_id = $araba_id");
        mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($araba_id, 'URUN SILINDI')");
        echo json_encode(["durum" => "basarili"]);
        break;

    case 'log_kaydet':
        // Pi-Panel tıklama logları
        $barkod = mysqli_real_escape_string($conn, $_POST['barkod'] ?? '');
        mysqli_query($conn, "INSERT INTO loglar (araba_id, islem) VALUES ($araba_id, 'VITRIN TIKLANDI: $barkod')");
        echo json_encode(["durum" => "ok"]);
        break;
}

function agirlikKontrolEt($conn, $araba_id) {
    $res_b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(u.gramaj) as toplam FROM sepet s JOIN urunler u ON s.barkod = u.barkod WHERE s.araba_id = $araba_id"));
    $beklenen = (float)($res_b['toplam'] ?? 0);
    $res_t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT weight FROM weights ORDER BY id DESC LIMIT 1"));
    $gercek = (float)($res_t['weight'] ?? 0);
    $fark = abs($gercek - $beklenen);
    return [
        "durum" => ($fark > 100) ? "hata" : "ok",
        "fark" => $fark,
        "terazi" => $gercek,
        "beklenen" => $beklenen
    ];
}

mysqli_close($conn);
ob_end_flush();