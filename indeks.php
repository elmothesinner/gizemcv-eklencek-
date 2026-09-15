<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SmartCart | Pi-Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-green: #215A31; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #FFFFFF; 
            overflow: hidden; 
            /* 100vh yerine 100dvh kullandık. Mobil/Tablet tarayıcı çubuklarını hesaba katar */
            height: 100dvh; 
            width: 100vw;
            display: flex;
            flex-direction: column;
            margin: 0; padding: 0;
        }

        header {
            height: 40px; 
            background: white;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        footer {
            height: 60px;
            padding: 0.5rem;
            background: white;
            border-top: 1px solid #E2E8F0;
            flex-shrink: 0;
            /* Flex layout'ta her zaman en altta kalmasını garanti altına alır */
            margin-top: auto; 
        }

        .urun-grid {
            display: grid;
            /* Mobilde 2 sütun, otomatik satır */
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: auto;
            gap: 2px;
            flex-grow: 1; 
            /* KRİTİK: Flex child'ın taşıp footer'ı ekran dışına itmesini engeller */
            min-height: 0; 
            background-color: #F1F5F9; 
            /* Eğer ürün çok olursa en azından kaydırılabilir olsun, tamamen kilitlenmesin */
            overflow-y: auto; 
        }

        /* 640px ve üzeri (Tablet / Pi-Panel vs.) için orijinal 3x2 görünüm */
        @media (min-width: 640px) {
            .urun-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                overflow: hidden; /* Geniş ekranda kaydırma çubuğu olmasın, sabit kalsın */
            }
        }

        .urun-kart {
            background: #FFFFFF;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between; 
            padding: 8px 4px;
            box-sizing: border-box;
            height: 100%;
            overflow: hidden;
        }

        .img-box { 
            flex: 1; 
            width: 100%;
            display: flex; 
            align-items: center; 
            justify-content: center;
            min-height: 0; 
        }

        .img-box img {
            max-height: 100%;
            max-width: 85%;
            object-fit: contain;
        }

        .info-box {
            width: 100%;
            text-align: center;
            flex-shrink: 0; 
        }

        .urun-ad {
            font-size: 0.7rem;
            font-weight: 700;
            color: #1E293B;
            line-height: 0.8rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 1.6rem; 
            margin-bottom: 2px;
        }

        .price-tag {
            color: var(--brand-green);
            font-weight: 900;
            font-size: 1rem;
            line-height: 1;
        }

        .pulse-btn {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-green);
            color: white;
            border-radius: 0.75rem;
            font-weight: 900;
            font-size: 1.25rem;
            text-transform: uppercase;
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0px rgba(33, 90, 49, 0.4); }
            100% { box-shadow: 0 0 0 8px rgba(33, 90, 49, 0); }
        }
    </style>
</head>
<body>

    <header>
        <img src="https://gizemgunduz.store/logo.png" class="h-5">
        <h1 class="font-black text-slate-800 uppercase text-[8px] tracking-[0.2em]">Haftalık Fırsatlar</h1>
    </header>

    <main id="urunGrid" class="urun-grid">
    </main>

    <footer>
        <a href="sepet.php?id=1" class="pulse-btn shadow-md">
            BAŞLA
        </a>
    </footer>

    <script>
        async function urunleriYukle() {
            try {
                const response = await fetch('api.php?islem=tanitilan_urunler');
                const data = await response.json();

                if (data.durum === "basarili") {
                    const grid = document.getElementById('urunGrid');
                    grid.innerHTML = '';

                    data.urunler.slice(0, 6).forEach(urun => {
                        grid.innerHTML += `
                            <div class="urun-kart" onclick="urunTikla('${urun.barkod}')">
                                <div class="img-box">
                                    <img src="${urun.gorsel_url}" onerror="this.src='https://gizemgunduz.store/logo.png'">
                                </div>
                                <div class="info-box">
                                    <div class="urun-ad">${urun.urun_adi}</div>
                                    <div class="price-tag">
                                        ${urun.fiyat}<span class="text-[0.6rem] font-bold"> TL</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
            } catch (e) { console.error("Hata!"); }
        }

        async function urunTikla(barkod) {
            const formData = new FormData();
            formData.append('barkod', barkod);
            formData.append('araba_id', 1);
            fetch('api.php?islem=log_kaydet', { method: 'POST', body: formData });
        }

        urunleriYukle();
    </script>
</body>
</html>