<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucwords(__('general.internal_platform')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        /* Variabel Warna Utama */
        :root {
            --primary-green: #1B5E20; /* Warna dasar hijau gelap */
            --bg-gray: #F0F0F0;     /* Warna background abu-abu */
        }

        body {
            background-color: #D9D9D9; /* Background body umum */
        }

        .topbar {
            background-color: var(--bg-gray);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ccc;
        }

        /* Styling Teks Hijau */
        .text-primary-green {
            color: var(--primary-green) !important;
        }

        /* Styling Tombol Toggle Kiri dgn svg */
        .btn-toggle {
            background-color: white;
            border: none;
            border-radius: 12px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn-toggle:hover {
            background-color: #f0f0f0;
        }

        /* Styling Search Bar */
        .search-container {
            background-color: white;
            border-radius: 25px;
            padding: 4px 16px; /* Dikecilkan dari 5px 20px */
            display: flex;
            align-items: center;
            min-width: 220px; /* Dikecilkan dari 300px agar lebih proporsional */
            height: 38px; /* Menyamakan tinggi dengan icon di sebelahnya */
            transition: all 0.3s ease; /* Animasi transisi mulus */
            cursor: text; /* Mengubah kursor menjadi teks saat mengarah ke form */
            border: 1px solid transparent;
        }

        /* Efek saat form di-hover ATAU saat sedang mengetik (focus) */
        .search-container:hover,
        .search-container:focus-within {
            transform: scale(1.02); /* Animasi membesar sedikit */
            /* box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; */
            border: 1px solid #c8e6c9; /* Border hijau tipis menyala */
        }

        .search-divider {
            width: 2px; /* Dikecilkan dari 3px */
            height: 18px; /* Dikecilkan dari 24px */
            background-color: var(--primary-green);
            margin: 0 10px; /* Jarak dirapatkan */
            opacity: 0.6; /* Dibuat sedikit transparan agar tidak terlalu mencolok */
        }

        .search-input {
            border: none;
            outline: none;
            width: 100%;
            background: transparent;
            font-size: 0.9rem; /* Ukuran font disesuaikan */
        }
        
        /* Menghilangkan border biru saat input di-klik */
        .search-input:focus {
            box-shadow: none;
        }

        /* Ikon search */
        .search-icon-btn {
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .search-icon-btn:hover {
            transform: scale(1.15);
            opacity: 0.8;
        }

        /* Efek Putar untuk Ikon Loading */
        .spinning {
            animation: spin 1s linear infinite !important;
            display: inline-block; /* Wajib agar ikon bisa berputar */
        }

        @keyframes spin { 
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); } 
        }

        /* Efek Hover pada Ikon Kanan */
        .hover-effect {
            transition: transform 0.2s ease, opacity 0.2s ease;
            display: inline-block;
        }
        .hover-effect:hover {
            transform: scale(1.15); /* Membesar sedikit */
            opacity: 0.8;
        }

        /* hilangkan outline BIRU bawaan Bootstrap saat diklik */
        .dropdown-toggle:focus, 
        .dropdown-toggle:active,
        .user-profile:focus {
            outline: none !important;
            box-shadow: none !important;
            color: var(--primary-green) !important; /* Tetap hijau saat diklik */
        }
        
        
        /* Hilangkan panah dropdown default bawaan bootstrap */
        .dropdown-toggle::after {
            display: none; 
        }
        
        /* --- Animasi Dropdown --- */
        .dropdown-menu.show {
            animation: slideDownFadeIn 0.25s ease-out forwards;
            transform-origin: top; /* Animasi berpusat dari atas */
        }

        @keyframes slideDownFadeIn {
            0% {
                opacity: 0;
                transform: translateY(-15px); /* Posisi awal sedikit ke atas */
            }
            100% {
                opacity: 1;
                transform: translateY(0); /* Posisi akhir kembali normal */
            }
        }

        .dropdown-menu.closing {
            animation: slideUpFadeOut 0.25s ease-in forwards;
            display: block !important; /* Tahan display tetap block selama animasi berjalan */
            transform-origin: top;
        }

        @keyframes slideUpFadeOut {
            0% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-15px); }
        }

        /* Styling area user */
        .user-profile {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile:hover .hover-text {
            color: #1a202c;
        }

        .user-profile:hover .hover-effect {
            transform: scale(1.15);
            opacity: 0.8;
        }

        /* Mengubah highlight BIRU menjadi HIJAU saat item menu dropdown diklik */
        .dropdown-item:active {
            background-color: var(--primary-green) !important;
            color: white !important;
        }

        .hover-text {
            color: #2f855a; /* warna awal */
            transition: color 0.3s ease;
        }
        .hover-text:hover {
            color: #5dab63; /* warna saat hover */
        }

        .user-profile:hover .hover-text {
            color: #5dab63;
        }

        .btn-sidebar-toggle {
            background-color: white; /* Background body umum */
            border: none;
            border-radius: 12px;
            padding: 8px 12px 8px 6px; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
            overflow: hidden;
            position: relative;
            border-left: 13px solid #1f6431;
        }
        /* .btn-sidebar-toggle::before {
            content: '';
            position: absolute;
            left: 0px;
            top: 0;
            bottom: 0;
            width: 13px;
            background-color: #1f6431;
            border-radius: 2px 2px 2px 2px; 
        } */
        .btn-sidebar-toggle:hover {
            background-color: #f0f0f0;
        }

        .avatar-sm {
            width: 35px;
            height: 35px;
            object-fit: cover;
            background-color: white;
            border: 1px solid var(--primary-green);
        }

        .avatar-sm.no-border {
            border: none;
        }

    </style>
    @yield('content_headscript')
</head>
<body>

    <header class="topbar shadow-sm">
        
        <div class="d-flex align-items-center gap-4">
            <button class="btn-sidebar-toggle ">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- <rect x="7" y="-4" width="3.5" height="32" fill="#1f6431"/> --}}
                    {{-- <path d="M7 5L15 12L7 19" stroke="#1f6431" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/> --}}
                </svg>
            </button>   

            <div class="d-flex align-items-center gap-3">
                {{-- <img src="{{ asset('images/mnm_logo.png') }}" alt="Logo" style="height: 32px;"> --}}
                @include('layouts.logo.mnm-logo')
                <h3 class="mb-0 fw-bold text-primary-green" style="font-family: Arial, sans-serif; letter-spacing: 0.5px; vertical-align: middle;">
                    {{ ucwords(__('general.internal_platform')) }}
                </h3>
            </div>
        </div>

        <div class="d-flex align-items-center gap-4">

            <form id="searchForm" action="#" method="GET" class="search-container shadow-sm">
                <input type="text" name="search" id="searchInput" class="search-input" placeholder="{{ ucfirst(__('general.search')) }} {{ __('general.menu') }}...">
                <div class="search-divider"></div>
                
                <button type="submit" id="searchButton" class="p-0 border-0 bg-transparent search-icon-btn" style="outline: none;">
                    <i id="searchIcon" class="bi bi-search text-primary-green fs-6 fw-bold"></i>
                </button>
            </form>

            <div class="dropdown">
                <a href="#" class="dropdown-toggle text-decoration-none hover-effect" id="bellToggle">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Tangkai atas -->
                        <path d="M12 2C12.6 2 13 2.4 13 3" stroke="#1f6431" stroke-width="1.5" stroke-linecap="round"/>
                        <!-- Badan lonceng -->
                        <path d="M5.5 17H18.5M6 17C6 17 7 13 7 9.5C7 6.5 9.2 4 12 4C14.8 4 17 6.5 17 9.5C17 13 18 17 18 17" stroke="#1f6431" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <!-- Pemukul bawah -->
                        <path d="M9.5 17C9.5 18.4 10.6 19.5 12 19.5C13.4 19.5 14.5 18.4 14.5 17" stroke="#1f6431" stroke-width="1.5" stroke-linecap="round"/>
                        <!-- Titik tangkai -->
                        <circle cx="12" cy="3" r="1.2" fill="#1f6431" stroke="#1f6431" stroke-width="0.5"/>
                    </svg>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" id="bellDropdownMenu">
                    <li><h6 class="dropdown-header">Notifikasi Baru</h6></li>
                    <li><a class="dropdown-item" href="#">Laporan bulanan telah dibuat</a></li>
                    <li><a class="dropdown-item" href="#">Update sistem berhasil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center text-primary-green" href="#">Lihat Semua</a></li>
                </ul>
            </div>

            <div class="dropdown">
                <div class="user-profile dropdown-toggle" id="userProfileToggle">
                    {{-- <i class="bi bi-person-circle text-primary-green hover-effect" style="font-size: 2rem;"></i> --}}
                    {{-- <img src="{{ asset('images/user-man.jpg') }}" class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm no-border"> --}}
                    <div class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm">
                        {{ \App\Helpers\TextHelper::getInitials('Muhammad Naufal Musyaddad') }} 
                    </div>
                    <span class="hover-text fs-6">{{ \App\Helpers\TextHelper::limitWordsByChar('Muhammad Naufal Musyaddad', 25) }}</span>
                </div>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a>
                    <a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a>
                </div>
            </div>

            <a href="/login" class="text-decoration-none ms-2 hover-effect" title="Logout">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Persegi dengan semua sudut rounded sama, lalu potong sisi kanan tengah -->
                    <path d="M12 2H5C3.9 2 3 2.9 3 4V20C3 21.1 3.9 22 5 22H12" 
                        stroke="#1f6431" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- Sudut kanan atas (dengan lengkungan sama rx=2) -->
                    <path d="M12 2Q14 2 14 4V6" 
                        stroke="#1f6431" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- Sudut kanan bawah (dengan lengkungan sama rx=2) -->
                    <path d="M14 18V20Q14 22 12 22" 
                        stroke="#1f6431" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- Chevron > -->
                    <path d="M17 9L21 12L17 15" stroke="#1f6431" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

        </div>
    </header>

    <!-- REQUIRED SCRIPTS -->
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function() {
            // ==========================================
            // 1. LOGIKA DROPDOWN (USER & NOTIFIKASI)
            // ==========================================
            let _cursorX = 0;

            const $userToggle = $('#userProfileToggle');
            const $userMenu   = $('#userDropdownMenu');
            const $bellToggle = $('#bellToggle');
            const $bellMenu   = $('#bellDropdownMenu');

            // Fungsi khusus menutup menu
            function closeDropdown($menuEl) {
                if ($menuEl.hasClass('show') && !$menuEl.hasClass('closing')) {
                    $menuEl.addClass('closing'); 
                    
                    // .one() di jQuery sama dengan { once: true } di vanilla JS
                    $menuEl.one('animationend', function() {
                        $menuEl.removeClass('show closing');
                    });
                }
            }

            // Fungsi serbaguna Setup Dropdown
            function setupCustomDropdown($toggleEl, $menuEl, $otherMenuEl, yOffset = 15) {
                $toggleEl.on('mousedown', function(e) {
                    _cursorX = e.clientX;
                });

                $toggleEl.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); 

                    closeDropdown($otherMenuEl);

                    const isOpen = $menuEl.hasClass('show') && !$menuEl.hasClass('closing');

                    if (isOpen) {
                        closeDropdown($menuEl);
                    } else {
                        $menuEl.removeClass('closing').addClass('show'); 
                        
                        let x = _cursorX;
                        // Gunakan native .getBoundingClientRect() untuk akurasi fixed position
                        const toggleRect = this.getBoundingClientRect(); 
                        let y = toggleRect.bottom + yOffset; 

                        const menuWidth = $menuEl.outerWidth();
                        const safeRightBoundary = $(window).width() - 20; 

                        if (x + menuWidth > safeRightBoundary) {
                            x = safeRightBoundary - menuWidth;
                        }
                        if (x < 10) x = 10;
                        
                        $menuEl.css({
                            position: 'fixed',
                            left: x + 'px',
                            top: y + 'px'
                        });
                    }
                });
            }

            // Terapkan ke tombol
            setupCustomDropdown($userToggle, $userMenu, $bellMenu, 15);
            setupCustomDropdown($bellToggle, $bellMenu, $userMenu, 15); 

            // Klik di luar untuk menutup (Lebih ringkas dengan .closest())
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#userProfileToggle, #userDropdownMenu').length) {
                    closeDropdown($userMenu);
                }
                if (!$(e.target).closest('#bellToggle, #bellDropdownMenu').length) {
                    closeDropdown($bellMenu);
                }
            });


            // ==========================================
            // 2. LOGIKA AJAX PENCARIAN
            // ==========================================
            
            // Agar bisa klik dimana saja di area form untuk fokus, kecuali tombol submit
            $('#searchForm').on('click', function(e) {
                // Jika yang diklik bukan elemen di dalam #searchButton
                if (!$(e.target).closest('#searchButton').length) {
                    $('#searchInput').focus();
                }
            });

            $('#searchForm').on('submit', function(e) {
                e.preventDefault(); // Mencegah form reload halaman

                const keyword = $('#searchInput').val().trim();
                const $icon = $('#searchIcon');

                // Cek jika input tidak kosong
                if (keyword !== '') {
                    
                    // Ubah ikon search menjadi ikon loading (spinner) saat mencari
                    $icon.removeClass('bi-search')
                        .addClass('bi-arrow-repeat spinning')
                        .css('animation', 'spin 1s linear infinite'); 

                    $.ajax({
                        url: '/api/cari-menu', // GANTI dengan endpoint/URL pencarian kamu
                        type: 'GET',
                        data: { search: keyword },
                        dataType: 'json',
                        
                        success: function(response) {
                            // Apa yang terjadi jika sukses?
                            console.log("Hasil pencarian:", response);
                            
                            // Contoh aksi:
                            // 1. Munculkan dropdown list hasil pencarian di bawah form
                            // 2. Atau render data HTML dari response.data ke dalam tabel
                            
                            // Kosongkan form setelah selesai (opsional)
                            // $('#searchInput').val(''); 
                        },
                        error: function(xhr, status, error) {
                            // Apa yang terjadi jika gagal (misal server error)?
                            console.error("Pencarian gagal:", error);
                            alert('Terjadi kesalahan saat mencari data.');      // Ganti dengan pop up aplikasi
                        },
                        complete: function() {
                            // Terjadi baik saat sukses maupun gagal (Kembalikan ikon seperti semula)
                            setTimeout(() => { // Timeout hanya agar efek loading terlihat sebentar
                                $icon.removeClass('bi-arrow-repeat spinning')
                                    .addClass('bi-search')
                                    .css('animation', 'none');
                            }, 500);
                        }
                    });
                } else {
                    // Jika klik ikon cari & input masih kosong, arahkan fokus ke input
                    $('#searchInput').focus();
                }
            });
        });
    </script>
    @yield('content_tailscript')
</body>
</html>