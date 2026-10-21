<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lihat pilihan iPhone yang tersedia. Tanyakan detail unit dan harga langsung ke toko lewat WhatsApp.">
    <meta name="theme-color" content="#f3f4f6">
    <title>iSafe — Temukan iPhone berikutnya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront">
    @php
        $heroGroup = $products->first();
        $heroProduct = $heroGroup?->first();
        $whatsappUrl = 'https://wa.me/'.$whatsappNumber;
    @endphp

    <header class="store-header">
        <nav class="store-nav" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="store-brand" aria-label="iSafe, beranda">
                <span class="store-brand-mark" aria-hidden="true">i</span>
                <span>iSafe<span class="store-brand-period">.</span></span>
            </a>
            <div class="store-nav-links">
                <a href="#katalog">Katalog</a>
                <a href="#tentang">Tentang toko</a>
            </div>
            <a href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo iSafe, saya ingin tanya soal iPhone yang tersedia.') }}" class="store-nav-cta">
                <span class="store-whatsapp-icon" aria-hidden="true">↗</span>
                Chat toko
            </a>
        </nav>
    </header>

    <main>
        <section class="store-hero">
            <div class="store-hero-copy">
                <p class="store-eyebrow"><span></span> Etalase iPhone iSafe</p>
                <h1>Ketemu iPhone<br>yang <em>pas.</em></h1>
                <p class="store-hero-description">Lihat unit yang tersedia, cek detailnya, lalu tanya harga langsung ke toko. Nggak yakin pilih yang mana? Kita obrolin dulu.</p>
                <div class="store-hero-actions">
                    <a href="#katalog" class="store-primary-button">Lihat katalog <span aria-hidden="true">↓</span></a>
                    <a href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo iSafe, saya sedang mencari iPhone. Boleh minta rekomendasi?') }}" class="store-secondary-link">Tanya dulu <span aria-hidden="true">↗</span></a>
                </div>
                <p class="store-hero-footnote"><span aria-hidden="true">✳</span> Stok katalog mengikuti unit yang tersedia di toko.</p>
            </div>

            <div class="store-hero-art" aria-label="Pilihan iPhone yang tersedia">
                <div class="store-art-topline"><span>iSAFE / PILIHAN HARI INI</span><span>01 — 03</span></div>
                <div class="store-phone-scene" aria-hidden="true">
                    <div class="store-phone-shadow"></div>
                    <div class="store-phone-back">
                        <span class="store-camera store-camera-one"></span>
                        <span class="store-camera store-camera-two"></span>
                        <span class="store-camera store-camera-three"></span>
                        <span class="store-camera-flash"></span>
                        <span class="store-phone-logo">i</span>
                    </div>
                    <div class="store-phone-front">
                        <span class="store-screen-island"></span>
                        <span class="store-screen-glow"></span>
                    </div>
                    <span class="store-art-orbit store-art-orbit-one"></span>
                    <span class="store-art-orbit store-art-orbit-two"></span>
                </div>
                <div class="store-featured-product">
                    <div>
                        @if ($heroProduct)
                            <p>UNIT TERSEDIA</p>
                            <h2>{{ $heroProduct->phoneModel->name }}</h2>
                            <span>{{ $heroProduct->storage }} · {{ $heroProduct->color }} · {{ $heroProduct->condition === 'new' ? 'Baru' : 'Second' }}</span>
                        @else
                            <p>ETALASE iSAFE</p>
                            <h2>Pilihan iPhone</h2>
                            <span>Hubungi kami untuk info unit</span>
                        @endif
                    </div>
                    <span class="store-featured-arrow" aria-hidden="true">↗</span>
                </div>
                <span class="store-art-caption">UNIT DILIHAT LEBIH DEKAT</span>
            </div>
        </section>

        <section id="katalog" class="store-catalog">
            <div class="store-catalog-heading">
                <div>
                    <p class="store-eyebrow"><span></span> Pilihan yang tersedia</p>
                    <h2>Katalog <em>iPhone.</em></h2>
                </div>
                <p>Belum ada harga? Memang lebih enak tanya langsung. Tiap unit punya kondisi dan detail yang berbeda.</p>
            </div>

            @if ($products->isNotEmpty())
                <div class="store-product-grid">
                    @foreach ($products as $units)
                        @php
                            $product = $units->first();
                            $conditionLabel = $product->condition === 'new' ? 'Baru' : 'Second';
                            $variantLabel = match ($product->variant) {
                                'ibox' => 'iBox',
                                'inter' => 'Inter',
                                'other' => 'Lainnya',
                                default => null,
                            };
                            $productMessage = 'Halo iSafe, saya tertarik dengan '.$product->phoneModel->name.' '.$product->storage.' warna '.$product->color.' ('.$conditionLabel.'). Boleh minta info harga dan ketersediaannya?';
                        @endphp
                        <article class="store-product-card">
                            <div class="store-product-visual">
                                <span class="store-product-badge">{{ $conditionLabel }}</span>
                                <span class="store-product-count">{{ $units->count() }} {{ $units->count() === 1 ? 'unit' : 'unit' }}</span>
                                <div class="store-product-device" aria-hidden="true">
                                    <span class="store-product-camera camera-a"></span>
                                    <span class="store-product-camera camera-b"></span>
                                    <span class="store-product-camera camera-c"></span>
                                    <span class="store-product-flash"></span>
                                    <span class="store-product-device-mark">i</span>
                                </div>
                                <span class="store-product-color">{{ $product->color }}</span>
                            </div>
                            <div class="store-product-info">
                                <div class="store-product-name">
                                    <div>
                                        <h3>{{ $product->phoneModel->name }}</h3>
                                        <p>{{ $product->storage }} <span>·</span> {{ $conditionLabel }}</p>
                                    </div>
                                    <span class="store-product-price">Tanya harga</span>
                                </div>
                                <div class="store-product-specs">
                                    @if ($product->battery_health)
                                        <span><b>{{ $product->battery_health }}%</b> battery</span>
                                    @endif
                                    @if ($product->physical_grade)
                                        <span><b>Grade {{ $product->physical_grade }}</b></span>
                                    @endif
                                    @if ($variantLabel)
                                        <span><b>{{ $variantLabel }}</b></span>
                                    @endif
                                </div>
                                <a href="{{ $whatsappUrl }}?text={{ rawurlencode($productMessage) }}" class="store-product-cta">
                                    Tanya unit ini <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="store-empty-catalog">
                    <span class="store-empty-icon" aria-hidden="true">i.</span>
                    <div>
                        <h3>Belum ada unit yang ditampilkan.</h3>
                        <p>Coba tanya langsung ke toko—mungkin ada unit baru yang belum masuk katalog.</p>
                    </div>
                    <a href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo iSafe, ada unit iPhone yang tersedia?') }}" class="store-primary-button">Tanya stok <span aria-hidden="true">↗</span></a>
                </div>
            @endif
        </section>

        <section id="tentang" class="store-contact">
            <div class="store-contact-mark" aria-hidden="true">i.</div>
            <div class="store-contact-copy">
                <p class="store-eyebrow"><span></span> Mau tanya dulu juga boleh</p>
                <h2>Cari yang cocok,<br><em>bukan asal checkout.</em></h2>
                <p>Tanya soal kondisi, warna, atau harga sebelum memutuskan. Kami bantu cek detail unit yang Anda incar.</p>
            </div>
            <a href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo iSafe, saya ingin tanya-tanya soal iPhone.') }}" class="store-contact-button">
                <span class="store-contact-whatsapp" aria-hidden="true">↗</span>
                <span><b>Hubungi lewat WhatsApp</b><small>0851 8366 0490</small></span>
                <span class="store-contact-arrow" aria-hidden="true">↗</span>
            </a>
        </section>
    </main>

    <footer class="store-footer">
        <a href="{{ route('home') }}" class="store-brand" aria-label="iSafe, beranda">
            <span class="store-brand-mark" aria-hidden="true">i</span>
            <span>iSafe<span class="store-brand-period">.</span></span>
        </a>
        <p>Temukan unit. Tanya detailnya. Putuskan dengan tenang.</p>
        <a href="{{ route('login') }}" class="store-admin-link">Masuk admin <span aria-hidden="true">↗</span></a>
    </footer>
</body>
</html>
