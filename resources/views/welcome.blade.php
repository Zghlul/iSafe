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
        $availableUnits = $products->sum(fn ($units) => $units->count());
    @endphp

    <header class="store-header">
        <nav class="store-nav" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="store-brand" aria-label="iSafe, beranda">
                <span class="store-brand-mark" aria-hidden="true">i</span>
                <span>iSafe<span class="store-brand-period">.</span></span>
            </a>
            <div class="store-nav-links">
                <a href="#katalog">Katalog</a>
                <a href="#cara-beli">Cara beli</a>
                <a href="#faq">FAQ</a>
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

        <section class="store-stock-overview" aria-label="Ringkasan stok" data-reveal>
            <div class="store-overview-stat">
                <strong>{{ $availableUnits }}</strong>
                <span>unit tersedia</span>
            </div>
            <div class="store-overview-stat">
                <strong>{{ $products->count() }}</strong>
                <span>pilihan konfigurasi</span>
            </div>
            <p>Stok dapat berubah. Konfirmasi unit yang Anda inginkan sebelum datang atau melakukan pembayaran.</p>
            <a href="#katalog">Jelajahi katalog <span aria-hidden="true">↓</span></a>
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
                        <article class="store-product-card" data-reveal>
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

        <section id="cara-beli" class="store-howto">
            <div class="store-section-heading">
                <div data-reveal>
                    <p class="store-eyebrow"><span></span> Mulai dari yang sederhana</p>
                    <h2>Cara cari unit <em>yang pas.</em></h2>
                </div>
                <p>Belum perlu langsung memutuskan. Kenali pilihannya, tanyakan detail, lalu lanjutkan jika sudah cocok.</p>
            </div>
            <div class="store-howto-grid">
                <article class="store-howto-card" data-reveal>
                    <span>01</span>
                    <h3>Pilih dari katalog</h3>
                    <p>Lihat model, kapasitas, warna, dan keterangan kondisi yang tercantum pada setiap unit.</p>
                </article>
                <article class="store-howto-card" data-reveal>
                    <span>02</span>
                    <h3>Tanya detail unit</h3>
                    <p>Tekan tombol WhatsApp pada unit untuk menanyakan harga, kondisi, dan ketersediaannya.</p>
                </article>
                <article class="store-howto-card" data-reveal>
                    <span>03</span>
                    <h3>Pastikan sebelum deal</h3>
                    <p>Konfirmasi kembali detail unit dan kesepakatan dengan toko sebelum melakukan pembayaran.</p>
                </article>
            </div>
        </section>

        <section class="store-guide">
            <div class="store-guide-copy" data-reveal>
                <p class="store-eyebrow"><span></span> Panduan singkat</p>
                <h2>Perhatikan detailnya,<br><em>pilih dengan nyaman.</em></h2>
                <p>Setiap unit punya kondisi dan kelengkapan yang berbeda. Gunakan informasi di katalog sebagai awal, lalu minta detail yang Anda perlukan.</p>
            </div>
            <div class="store-guide-list" data-reveal>
                <article>
                    <span aria-hidden="true">01</span>
                    <div><h3>Kondisi unit</h3><p>Baru atau second? Pastikan kondisi yang dimaksud dan tanyakan detail unit secara langsung.</p></div>
                </article>
                <article>
                    <span aria-hidden="true">02</span>
                    <div><h3>Baterai dan fisik</h3><p>Untuk unit second, cek kesehatan baterai dan grade fisik yang tercantum bila tersedia.</p></div>
                </article>
                <article>
                    <span aria-hidden="true">03</span>
                    <div><h3>Kapasitas dan kelengkapan</h3><p>Pilih kapasitas yang sesuai kebutuhan, lalu konfirmasi warna dan aksesori yang didapat.</p></div>
                </article>
            </div>
        </section>

        <section id="faq" class="store-faq">
            <div class="store-section-heading">
                <div data-reveal>
                    <p class="store-eyebrow"><span></span> Sebelum menghubungi kami</p>
                    <h2>Pertanyaan <em>umum.</em></h2>
                </div>
                <p>Belum menemukan jawabannya? Kami siap membantu lewat WhatsApp.</p>
            </div>
            <div class="store-faq-list">
                <details data-reveal>
                    <summary>Bagaimana cara mengetahui harga unit?</summary>
                    <p>Harga bisa berbeda menurut model dan kondisi. Gunakan tombol “Tanya unit ini” untuk menanyakan harga terkini.</p>
                </details>
                <details data-reveal>
                    <summary>Apakah stok di katalog selalu tersedia?</summary>
                    <p>Stok dapat berubah setelah katalog diperbarui. Hubungi toko untuk memastikan unit masih tersedia sebelum berkunjung.</p>
                </details>
                <details data-reveal>
                    <summary>Apa yang perlu ditanyakan untuk unit second?</summary>
                    <p>Anda bisa menanyakan kesehatan baterai, kondisi fisik, kelengkapan, serta detail lain yang penting bagi Anda.</p>
                </details>
            </div>
        </section>

        <section id="tentang" class="store-contact" data-reveal>
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
