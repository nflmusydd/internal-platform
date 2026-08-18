<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucwords(__('general.internal_platform')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    {{-- Internal Platform font --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Variabel Warna Utama Berdasarkan Palet */
        :root {
            --primary-green: #043523;       /* Paling gelap: Teks utama & Ikon */
            --primary-green-hover: #478484; /* Medium teal: Untuk efek hover text */
            --bg-body: #eff3f1;             /* Paling terang: Background utama */
            --bg-topbar: #b4c7c0;           /* Hijau muda: Background topbar & dropdown */
            --sidebar-border: #1d362e;      /* Aksen gelap untuk border sidebar toggle */
            --search-focus: #6e9b9c;        /* Muted cyan untuk border pencarian aktif */
            --sidebar-width: 260px;            /* Lebar sidebar */
        }

        body {
            background-color: var(--bg-body); 
            margin: 0;  /* Reset margin */
        }

        /* ==========================================
           LAYOUT WRAPPER (SIDEBAR PUSH EFFECT)
           ========================================== */
        .app-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            overflow-x: hidden; /* Mencegah scrollbar horizontal saat animasi */
            position: relative; /*Sebagai jangkar tombol melayang */
        }

        /* Styling Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-green);
            color: white;
            transition: margin-left 0.3s ease-in-out;
            flex-shrink: 0; /* Mencegah sidebar mengecil */
            z-index: 1000;
        }

        /* Saat sidebar ditutup (digeser ke kiri sejauh lebarnya) */
        .sidebar.collapsed {
            margin-left: calc(-1 * var(--sidebar-width));
        }

        /* Styling Menu Sidebar */
        .sidebar-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
            margin-bottom: 5px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--primary-green-hover);
            color: white;
        }

        /* ==========================================
           RESPONSIVE MOBILE: OVERLAY SIDEBAR
           ========================================== */
        
        /* Set default overlay (tersembunyi) */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Gelap transparan */
            z-index: 990; /* Di bawah sidebar, di atas konten */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            /* Ubah sidebar jadi melayang (fixed) */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1000; /* Paling atas */
                margin-left: 0 !important; /* Matikan efek margin dari desktop */
                transform: translateX(0); /* Posisi normal terbuka */
                transition: transform 0.3s ease-in-out;
            }

            /* Saat ditutup di mobile, geser ke kiri sampai hilang pakai transform */
            .sidebar.collapsed {
                margin-left: 0 !important; 
                transform: translateX(-100%); 
            }

            /* Munculkan layar gelap saat sidebar dibuka (wrapper punya class sidebar-open) */
            .app-wrapper.sidebar-open .sidebar-overlay {
                display: block;
                opacity: 1;
            }

        }

        /* ==========================================
           LAYOUT TOP BAR
           ========================================== */

        /* Konten Utama (Top Bar + Isi Halaman) */
        .main-content {
            flex: 1; 
            min-width: 0;       /* agar tidak overflow */
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
        }
        .topbar {
            background-color: var(--bg-topbar);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.5); /* Border disesuaikan agar lebih halus */
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
            background-color: rgba(180, 199, 192, 0.3); /* Transparan dari warna body */
        }

        /* Styling Search Bar */
        .search-container {
            background-color: white;
            border-radius: 25px;
            padding: 4px 16px; 
            display: flex;
            align-items: center;
            width: 100%; 
            max-width: 280px;
            height: 38px; 
            transition: all 0.3s ease; 
            cursor: text; 
            border: 1px solid transparent;
        }

        /* Efek saat form di-hover ATAU saat sedang mengetik (focus) */
        .search-container:hover,
        .search-container:focus-within {
            transform: scale(1.02); 
            border: 1px solid var(--search-focus); 
        }

        .search-divider {
            width: 2px; 
            height: 18px; 
            background-color: var(--primary-green);
            margin: 0 10px; 
            opacity: 0.4; 
        }

        .search-input {
            border: none;
            outline: none;
            width: 100%;
            background: transparent;
            font-size: 0.9rem; 
            color: var(--primary-green); /* Warna teks saat mengetik */
        }
        
        .search-input::placeholder {
            color: var(--primary-green-hover);
            opacity: 0.7;
        }

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
            display: inline-block; 
        }

        @keyframes spin { 
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); } 
        }

        /* Efek Hover pada Ikon Kanan */
        .hover-effect {
            transition: transform 0.2s ease, opacity 0.2s ease, color 0.3s ease, border-color 0.3s ease;
            display: inline-block;
            color: var(--primary-green); 
        }
        
        .hover-effect:hover {
            transform: scale(1.15); 
            opacity: 0.8;
            color: var(--primary-green-hover) !important;
        }

        /* hilangkan outline biru bawaan Bootstrap saat diklik */
        .dropdown-toggle:focus, 
        .dropdown-toggle:active,
        .user-profile:focus {
            outline: none !important;
            box-shadow: none !important;
            color: var(--primary-green) !important; 
        }
        
        .dropdown-toggle::after {
            display: none; 
        }
        
        /* --- Animasi Dropdown --- */
        .dropdown-menu.show {
            animation: slideDownFadeIn 0.25s ease-out forwards;
            transform-origin: top; 
        }

        @keyframes slideDownFadeIn {
            0% { opacity: 0; transform: translateY(-15px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .dropdown-menu.closing {
            animation: slideUpFadeOut 0.25s ease-in forwards;
            display: block !important; 
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

        .dropdown-item:active {
            background-color: var(--primary-green) !important;
            color: white !important;
        }

        .hover-text {
            color: var(--primary-green); 
            transition: color 0.3s ease;
        }

        .user-profile:hover .hover-text {
            color: var(--primary-green-hover);
        }

        .user-profile:hover .hover-effect {
            transform: scale(1.15);
            opacity: 0.8;
            color: var(--primary-green-hover) !important;
        }

        .btn-sidebar-toggle {
            background-color: white; 
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
            border-left: 13px solid var(--sidebar-border); /* Warna dari CSS Var */
        }
        
        .btn-sidebar-toggle:hover {
            background-color: rgba(180, 199, 192, 0.3);
        }

        /* Animasi panah pada tombol toggle */
        .btn-sidebar-toggle svg {
            transition: transform 0.3s ease;
        }
        .btn-sidebar-toggle.rotated svg {
            transform: rotate(180deg);
        }

        .avatar-sm {
            width: 35px;
            height: 35px;
            object-fit: cover;
            background-color: white;
            border: 1.5px solid var(--primary-green);
            /* transition: border-color 0.3s ease; */
        }

        .avatar-sm.no-border {
            border: none;
        }

        /* Styling Khusus Teks Logo */
        .logo-text {
            font-family: 'Poppins', sans-serif !important;
            letter-spacing: 0.5px;
            vertical-align: middle;
            
            /* Mencegah teks bisa di-select/blok */
            -webkit-user-select: none; /* Safari */
            -ms-user-select: none;     /* IE 10+ and Edge */
            user-select: none;         /* Standard syntax */
        }
    </style>
    @yield('content_headscript')
</head>
<body>

    <div class="app-wrapper">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar collapsed" id="appSidebar">
            <div class="p-4">
                <div class="d-flex align-items-center mb-4 gap-2 border-bottom border-secondary pb-3">
                    <i class="bi bi-box-fill fs-3"></i>
                    <h5 class="mb-0 fw-bold" style="font-family: 'Poppins', sans-serif;">Menu</h5>
                </div>

                <nav>
                    <a href="#" class="sidebar-link active">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-people-fill"></i> Manajemen User
                    </a>
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-file-earmark-text-fill"></i> Laporan
                    </a>
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i> Sistem
                    </a>
                </nav>
            </div>
        </aside>

        <div class="main-content">
            <header class="topbar shadow-sm">
                <div class="d-flex align-items-center gap-4">
                    <button id="sidebarToggleBtn" class="btn-sidebar-toggle text-primary-green gap-2 gap-md-4">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 5L15 12L7 19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>  

                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        {{-- <img src="{{ asset('images/mnm_logo.png') }}" alt="Logo" style="height: 32px;"> --}}
                        @include('layouts.logo.mnm-logo')
                        <h3 class="mb-0 fw-bold text-primary-green logo-text d-none d-md-block text-nowrap" style="font-family: Arial, sans-serif; letter-spacing: 0.5px; vertical-align: middle; margin-right:10px">
                            {{ ucwords(__('general.internal_platform')) }}
                        </h3>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 gap-md-4">

                    <form id="searchForm" action="#" method="GET" class="search-container shadow-sm d-none d-lg-flex">
                        <input type="text" name="search" id="searchInput" class="search-input" placeholder="{{ ucfirst(__('general.search')) }} {{ __('general.menu') }}...">
                        <div class="search-divider"></div>
                        
                        <button type="submit" id="searchButton" class="p-0 border-0 bg-transparent search-icon-btn text-primary-green" style="outline: none;">
                            <i id="searchIcon" class="bi bi-search fs-6 fw-bold"></i>
                        </button>
                    </form>

                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-decoration-none hover-effect text-primary-green" id="bellToggle">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C12.6 2 13 2.4 13 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M5.5 17H18.5M6 17C6 17 7 13 7 9.5C7 6.5 9.2 4 12 4C14.8 4 17 6.5 17 9.5C17 13 18 17 18 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.5 17C9.5 18.4 10.6 19.5 12 19.5C13.4 19.5 14.5 18.4 14.5 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="12" cy="3" r="1.2" fill="currentColor" stroke="currentColor" stroke-width="0.5"/>
                            </svg>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" id="bellDropdownMenu">
                            <li><h6 class="dropdown-header">Notifikasi Baru</h6></li>
                            <li><a class="dropdown-item" href="#">Laporan bulanan telah dibuat</a></li>
                            <li><a class="dropdown-item" href="#">Update sistem berhasil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center text-primary-green fw-bold" href="#">Lihat Semua</a></li>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <div class="user-profile dropdown-toggle" id="userProfileToggle">
                            {{-- <img src="{{ asset('images/user-man.jpg') }}" class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm no-border"> --}}
                            <div class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm fw-bold">
                                {{ \App\Helpers\TextHelper::getInitials('Muhammad Naufal Musyaddad') }} 
                            </div>
                            <span class="hover-text fs-6 fw-semibold d-none d-sm-block">{{ \App\Helpers\TextHelper::limitWordsByChar('Muhammad Naufal Musyaddad', 25) }}</span>
                        </div>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a>
                            <a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a>
                        </div>
                    </div>

                    <a href="/login" class="text-decoration-none ms-2 hover-effect text-primary-green" title="{{  ucfirst(__('general.logout')) }}">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2H5C3.9 2 3 2.9 3 4V20C3 21.1 3.9 22 5 22H12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 2Q14 2 14 4V6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 18V20Q14 22 12 22" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 9L21 12L17 15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                </div>
            </header>
            
            <!-- Main content -->
            <section class="content" height="100%">
                <div class="row" height="100%">
                <div class="col-12" id="contents" height="100%">
                    <div id='flash-msg-container'>
                    {{-- @include('flash::message') --}}
                    </div>
                    @yield('content')
                    <main class="p-4">
                        <h4 class="text-secondary">Dashboard Content Areaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaaaaaaa aaaaaaaaa aaaaaa aaaaaa hadh asdjkah df ashkfashfbashsah sjsjsjs</h4>
                    </main>
                </div>
                </div>
            </section>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function() {
            // ==========================================
            // LOGIKA SIDEBAR PUSH
            // ==========================================
            $('#sidebarToggleBtn').on('click', function() {
                // Tambah/hapus class collapsed pada sidebar
                $('#appSidebar').toggleClass('collapsed');
                
                // Putar ikon panah di dalam tombol 180 derajat
                $(this).toggleClass('rotated');
            });
            
            // ==========================================
            // LOGIKA DROPDOWN (USER & NOTIFIKASI)
            // ==========================================
            let _cursorX = 0;

            const $userToggle = $('#userProfileToggle');
            const $userMenu   = $('#userDropdownMenu');
            const $bellToggle = $('#bellToggle');
            const $bellMenu   = $('#bellDropdownMenu');

            function closeDropdown($menuEl) {
                if ($menuEl.hasClass('show') && !$menuEl.hasClass('closing')) {
                    $menuEl.addClass('closing'); 
                    $menuEl.one('animationend', function() {
                        $menuEl.removeClass('show closing');
                    });
                }
            }

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

            setupCustomDropdown($userToggle, $userMenu, $bellMenu, 15);
            setupCustomDropdown($bellToggle, $bellMenu, $userMenu, 15); 

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#userProfileToggle, #userDropdownMenu').length) {
                    closeDropdown($userMenu);
                }
                if (!$(e.target).closest('#bellToggle, #bellDropdownMenu').length) {
                    closeDropdown($bellMenu);
                }
            });


            // ==========================================
            // LOGIKA AJAX SEARCH
            // ==========================================
            $('#searchForm').on('click', function(e) {
                if (!$(e.target).closest('#searchButton').length) {
                    $('#searchInput').focus();
                }
            });

            $('#searchForm').on('submit', function(e) {
                e.preventDefault(); 

                const keyword = $('#searchInput').val().trim();
                const $icon = $('#searchIcon');

                if (keyword !== '') {
                    $icon.removeClass('bi-search')
                        .addClass('bi-arrow-repeat spinning'); 

                    $.ajax({
                        url: '/api/cari-menu', 
                        type: 'GET',
                        data: { search: keyword },
                        dataType: 'json',
                        
                        success: function(response) {
                            console.log("Hasil pencarian:", response);
                        },
                        error: function(xhr, status, error) {
                            console.error("Pencarian gagal:", error);
                            // Isikan dengan modal/toast
                        },
                        complete: function() {
                            setTimeout(() => { 
                                $icon.removeClass('bi-arrow-repeat spinning')
                                    .addClass('bi-search');
                            }, 500);
                        }
                    });
                } else {
                    $('#searchInput').focus();
                }
            });
        
            // Menutup sidebar ketika area gelap transparan di-klik
            $('#sidebarOverlay').on('click', function() {
                $('#appSidebar').addClass('collapsed');
                $('.app-wrapper').removeClass('sidebar-open');
                $('#sidebarToggleBtn').removeClass('rotated');
            });
        });
    </script>
    @yield('content_tailscript')
</body>
</html>