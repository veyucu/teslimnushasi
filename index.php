<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teslim Nüshası - İmzalı Belgelerinizi Dijital Ortamda Saklayın</title>
    <meta name="description"
        content="İmzalı fatura ve irsaliye nüshalarınızı güvenle saklayın, kolayca bulun. Teslim Nüshası ile evrak kaybına son!">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Teslim Nüshası
            </a>

            <button class="mobile-toggle" onclick="toggleMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <ul class="navbar-nav" id="navMenu">
                <li><a href="#features">Özellikler</a></li>
                <li><a href="#how-it-works">Nasıl Çalışır</a></li>
                <li><a href="#contact">İletişim</a></li>
                <li><a href="login" class="btn btn-outline">Giriş Yap</a></li>
                <li><a href="register" class="btn btn-primary">Kayıt Ol</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>İmzalı Belgelerinizi<br><span>Dijital Ortamda</span> Saklayın</h1>
                <p>Fatura ve irsaliye teslim nüshalarınızı güvenle arşivleyin, saniyeler içinde bulun. Artık evrak
                    aramakla vakit kaybetmeyin!</p>
                <div class="hero-buttons">
                    <a href="register" class="btn btn-primary btn-lg">
                        Ücretsiz Başlayın
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="#features" class="btn btn-secondary btn-lg">Daha Fazla Bilgi</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/images/hero.png" alt="Belge Yönetim Sistemi">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Neden Teslim Nüshası?</h2>
                <p>Modern belge yönetimi ile işletmenizi dijital çağa taşıyın</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <h3>Kolay Yükleme</h3>
                    <p>Belgelerinizi sürükle-bırak yöntemiyle veya kamera ile anında sisteme yükleyin.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3>Akıllı Arama</h3>
                    <p>Belge numarası, müşteri adı veya tarih ile saniyeler içinde aradığınız belgeye ulaşın.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3>Güvenli Depolama</h3>
                    <p>Belgeleriniz şifrelenmiş sunucularda güvenle saklanır. Asla kaybolmaz.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3>Zaman Tasarrufu</h3>
                    <p>Arşivde saatler geçirmek yerine belgelerinize anında erişin.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3>Yapay Zeka Destekli OCR</h3>
                    <p>Yüklediğiniz belgeler otomatik analiz edilir; belge numarası, tarih ve müşteri bilgileri sizin
                        için ayrıştırılır.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3>Mobil Uyumlu</h3>
                    <p>Telefonunuzdan veya tabletinizden belgelerinize her yerden erişin ve yönetin.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>Nasıl Çalışır?</h2>
                <p>Sadece 3 adımda belgelerinizi dijital arşivinize ekleyin</p>
            </div>

            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Kayıt Olun</h3>
                    <p>Ücretsiz hesabınızı oluşturun ve hemen kullanmaya başlayın.</p>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Belge Yükleyin</h3>
                    <p>İmzalı teslim nüshalarınızı fotoğraflayın veya tarayın ve yükleyin.</p>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Kolayca Bulun</h3>
                    <p>İstediğiniz zaman, istediğiniz yerden belgelerinize ulaşın.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>10.000+</h3>
                    <p>Arşivlenen Belge</p>
                </div>
                <div class="stat-item">
                    <h3>100+</h3>
                    <p>Aktif Kullanıcı</p>
                </div>
                <div class="stat-item">
                    <h3>%99.9</h3>
                    <p>Uptime Garantisi</p>
                </div>
                <div class="stat-item">
                    <h3>7/24</h3>
                    <p>Erişim İmkanı</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <h2>Bizimle İletişime Geçin</h2>
                    <p>Sorularınız mı var? Yardımcı olmaktan mutluluk duyarız.</p>

                    <ul class="contact-details">
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>info@teslimnushasi.com</span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Levent Mahallesi, Büyükdere Cad. No:185<br>Kanyon AVM, Kat:8, 34394
                                Şişli/İstanbul</span>
                        </li>
                    </ul>
                </div>

                <form class="contact-form" id="contactForm" onsubmit="submitContact(event)">
                    <div class="form-group">
                        <label for="name">Adınız Soyadınız</label>
                        <input type="text" id="contactName" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-posta Adresiniz</label>
                        <input type="email" id="contactEmail" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Mesajınız</label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" id="contactBtn">Mesaj Gönder</button>
                </form>
                <div id="contactToast" class="contact-toast"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Teslim Nüshası
                    </div>
                    <p class="footer-desc">İmzalı fatura ve irsaliye nüshalarınızı güvenle saklayın, kolayca bulun.</p>
                </div>

                <div>
                    <h4>Hızlı Linkler</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Özellikler</a></li>
                        <li><a href="#how-it-works">Nasıl Çalışır</a></li>
                        <li><a href="#contact">İletişim</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Hesap</h4>
                    <ul class="footer-links">
                        <li><a href="login">Giriş Yap</a></li>
                        <li><a href="register">Kayıt Ol</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Teslim Nüshası. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            document.getElementById('navMenu').classList.toggle('active');
        }

        // Scroll olduğunda navbar stilini değiştir
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.boxShadow = 'none';
            }
        });

        // İletişim formu AJAX
        async function submitContact(e) {
            e.preventDefault();
            const btn = document.getElementById('contactBtn');
            const toast = document.getElementById('contactToast');

            btn.disabled = true;
            btn.textContent = 'Gönderiliyor...';

            try {
                const res = await fetch('/api/contact.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: document.getElementById('contactName').value,
                        email: document.getElementById('contactEmail').value,
                        message: document.getElementById('contactMessage').value
                    })
                });
                const data = await res.json();

                toast.textContent = data.message;
                toast.className = 'contact-toast ' + (data.success ? 'success' : 'error');
                toast.style.display = 'block';

                if (data.success) {
                    document.getElementById('contactForm').reset();
                }

                setTimeout(() => { toast.style.display = 'none'; }, 4000);
            } catch (err) {
                toast.textContent = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                toast.className = 'contact-toast error';
                toast.style.display = 'block';
                setTimeout(() => { toast.style.display = 'none'; }, 4000);
            }

            btn.disabled = false;
            btn.textContent = 'Mesaj Gönder';
        }
    </script>
</body>

</html>