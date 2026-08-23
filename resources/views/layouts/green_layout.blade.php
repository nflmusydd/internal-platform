<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucwords(__('general.internal_platform')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mnm_logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    {{-- Internal Platform font --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    {{-- DataTables custom styles --}}
    <link rel="stylesheet" href="{{ asset('css/admin-datatables.css') }}?v={{ filemtime(public_path('css/admin-datatables.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-toolbar.css') }}?v={{ filemtime(public_path('css/admin-toolbar.css')) }}">
    
    <style>
        /* Variabel Warna Utama Berdasarkan Palet */
        :root {
            --primary-green: #043523;       /* Paling gelap: Teks utama & Ikon */
            --primary-green-hover: #0f513a; /* Medium teal: Untuk efek hover text */
            --bg-body: #f4f7f6;             /* Agak putih: Background utama */
            --bg-topbar: #ffffff;           /* Putih: Background topbar & dropdown */
            --sidebar-border: #1a4233;      /* Aksen gelap untuk border sidebar toggle */
            --search-focus: #0d9488;        /* Muted cyan untuk border pencarian aktif */
            --sidebar-width: 260px;            /* Lebar sidebar */
        }

        html, body {
            height: 100%;
            overflow: hidden;                   /*  Matikan scroll bawaan browser */
            background-color: var(--bg-body); 
            margin: 0;  
            overscroll-behavior-y: none;         /* Mencegah efek pull-to-refresh */
        }

        /* ==========================================
           LAYOUT WRAPPER (SIDEBAR PUSH EFFECT)
           ========================================== */
        .app-wrapper {
            display: flex;         
            width: 100%;           
            height: 100%;     
            overflow: hidden; /* Matikan scroll */
            position: relative; 
        }

        /* Styling Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-green);
            color: white;
            transition: margin-left 0.3s ease-in-out;
            flex-shrink: 0; 
            z-index: 1000;
            height: 100vh;
            overflow: hidden;
        }
        /* Saat sidebar ditutup (digeser ke kiri sejauh lebarnya) */
        .sidebar.collapsed {
            margin-left: calc(-1 * var(--sidebar-width));
        }

        /* Styling Menu Sidebar */
        .sidebar-link {
            cursor: pointer;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
            margin-bottom: 5px;
            min-width: 0; /* agar flex child bisa dipotong teksnya */
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--primary-green-hover);
            color: white;
        }

        /* Mensejajarkan tulisan menu dan toggle sidebar */
        .sidebar .p-4 {
            padding-bottom: 0 !important;
            padding-top: 10px !important; 
            padding-right: 0 !important;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        /*container untuk header Menu */
        .sidebar-header {
            height: 40px; /* Samakan dgn tinggi .btn-sidebar-toggle  */
            display: flex;
            align-items: end;
            padding-right: 1.5rem;
            /* margin-bottom: 1.5rem; */
        }
        .sidebar-header-wrapper {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 15px;
            margin-right: 12px;
        }
        /* Container untuk teks menu agar bisa terpotong (...) */
        .menu-label {
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;    /* maksimal baris */
            -webkit-box-orient: vertical;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            font-size: 0.90rem;
        }
        .submenu-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            font-size: 0.85rem;
        }
        

        /* Container Ikon agar ukuran tetap (Fixed) */
        .sidebar-icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0; /* Mencegah ikon gepeng saat sidebar sempit */
        }
        
        .sidebar-icon svg, .sidebar-icon i {
            width: 100% !important;
            height: auto !important;
            max-height: 100%;
            /* font-size: 1.2rem; Ukuran jika menggunakan Bootstrap Icon */
        }

        /* Styling Sub-menu */
        .sidebar-submenu {
            display: none; /* Sembunyi secara default */
            background-color: rgba(0, 0, 0, 0.15); /* Lebih gelap sedikit dari sidebar */
            list-style: none;
            padding: 0;
            margin: 0 8px 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-bottom: 2px solid rgba(255, 255, 255, 0.1); 
        }
        .sidebar-submenu .sidebar-link {
            padding-left: 15px; /* kananin submenu dikit */
            font-size: 0.88rem;
            /* margin-bottom: 2px; */
        }
        /* Animasi Rotasi Chevron pada Menu yang punya Sub */
        .has-sub::after {
            content: "\f282"; /* Bootstrap Icon Chevron Down */
            font-family: "bootstrap-icons";
            margin-left: 8px;
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }
        .has-sub.sub-open::after {
            transform: rotate(180deg);
        }

        /* ==========================================
            CUSTOM SCROLLBAR (SIDEBAR NAV & CONTENT)
            ========================================== */
        .sidebar nav, .app-main-content {
            flex: 1;
            overflow-y: auto; /* scroll HANYA untuk list menu ini */
            padding-bottom: 20px; 
            -ms-overflow-style: none; 
            scrollbar-gutter: stable; /* mencegah menu bergeser saat scrollbar aktif */
            scrollbar-color: transparent transparent; /* Sembunyikan default di Firefox */
            transition: scrollbar-color 0.3s ease;
            scrollbar-width: thin; 
            scrollbar-color: var(--primary-green-hover) transparent;
        }
        /* Munculkan di Firefox saat nav di-hover */
        .sidebar nav:hover {
            scrollbar-color: var(--primary-green-hover) transparent;
        }

        /* Kode di bawah efek untuk Chrome, Edge, Safari, dan Opera... Firefox hanya pakai kode atasnya (terbatas) */
        .sidebar nav::-webkit-scrollbar, .app-main-content::-webkit-scrollbar {
            width: 6px;
        }

        /* Mengatur "Track" (Jalur tempat scrollbar bergerak) */
        .sidebar nav::-webkit-scrollbar-track {
            /* background: rgba(255, 255, 255, 0.05); 
            border-radius: 10px; */
            background: transparent;
        }
        .app-main-content::-webkit-scrollbar-track {
            /* background: rgba(0, 0, 0, 0.05); 
            border-radius: 10px;  */
             background: transparent;
        }

        /* Hilangkan Panah Atas/Bawah */
        .sidebar nav::-webkit-scrollbar-button, .app-main-content::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }

        /* Mengatur "Thumb" (Batang scrollbar yang bisa ditarik) */
        .sidebar nav::-webkit-scrollbar-thumb, .app-main-content::-webkit-scrollbar-thumb {
            background-color: var(--primary-green-hover); 
            border-radius: 20px;
        }
        /* Efek saat "Thumb" disorot mouse (Hover) */
        .sidebar nav::-webkit-scrollbar-thumb:hover, .app-main-content::-webkit-scrollbar-thumb:hover {
            background-color: var(--primary-green); 
        }

        /* ==========================================
           RESPONSIVE MOBILE: OVERLAY SIDEBAR
           ========================================== */
        
        /* Set default overlay (tersembunyi) */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); 
            z-index: 990; 
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            /* Ubah sidebar jadi melayang (fixed) */
            .sidebar {
                position: fixed;
                top: 0; left: 0; height: 100vh;
                z-index: 1000; 
                margin-left: 0 !important; 
                transform: translateX(0); 
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.collapsed {
                margin-left: 0 !important; 
                transform: translateX(-100%); 
            }

            .app-wrapper.sidebar-open .sidebar-overlay {
                display: block; opacity: 1;
            }
            /* Matikan scroll pada area konten utama ketika sidebar terbuka */
            .app-wrapper.sidebar-open .app-main-content {
                overflow-y: hidden;
            }

        }

        /* ==========================================
           LAYOUT TOP BAR & MAIN CONTENT
           ========================================== */

        /* Container top bar */
        .layout-main-container {
            flex: 1; 
            min-width: 0;       
            height: 100%;
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
            border: 1px solid rgba(255, 255, 255, 0.5); 
        }

        .text-primary-green { color: var(--primary-green) !important; }

        /* ==========================================
           ANIMASI FLOATING TOGGLE BUTTON & SPACER
           ========================================== */

        /* Tombol dibuat melayang (Absolute) */
        .btn-sidebar-toggle {
            background-color: white; 
            border: none;
            border-radius: 12px;
            padding: 8px 12px 8px 6px; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            border-left: 13px solid var(--sidebar-border); /* Garis ikon sidebar */
        }
        
        .btn-sidebar-toggle.floating-toggle {
            position: absolute;
            top: 10px;  
            left: 28px; 
            z-index: 1050; 
            width: 55px; 
            height: 40px;
            transition: transform 0.3s ease-in-out, background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .btn-sidebar-toggle.floating-toggle:hover {
            background-color: rgba(180, 199, 192, 0.3);
        }

        /* Animasi rotasi panah */
        .btn-sidebar-toggle svg { transition: transform 0.3s ease; }
        .btn-sidebar-toggle.rotated svg { transform: rotate(180deg); }

        /* Bayangan Spacer di Top Bar */
        .header-spacer {
            width: 55px; 
            height: 40px;
            margin-right: 1.5rem; /* Mengatur jarak logo tanpa butuh gap-4 Bootstrap */
            transition: all 0.3s ease-in-out;
        }

        /* --- STATE KETIKA SIDEBAR DIBUKA --- */
        .app-wrapper.sidebar-open .header-spacer {
            width: 0;
            margin-right: 0; /* Spacer menyusut, menarik logo ke kiri */
            opacity: 0;
        }

        /* Tombol masuk ke sidebar */
        .app-wrapper.sidebar-open .btn-sidebar-toggle.floating-toggle {
            transform: translateX(175px);    /* posisi tombol di sidebar */
            background-color: transparent;
            border-left-color: transparent; 
            color: #ffffff !important; /* warna panah sidebar */
        }

        .app-wrapper.sidebar-open .btn-sidebar-toggle.floating-toggle:hover {
            background-color: rgba(255, 255, 255, 0.15); 
        }

        /* ==========================================
           KOMPONEN TOPBAR LAINNYA
           ========================================== */
        .search-container {
            background-color: white; 
            border-radius: 25px; 
            padding: 4px 16px; 
            display: flex; 
            
            align-items: center; 
            width: 100%; max-width: 280px; height: 38px; 
            transition: all 0.3s ease; 
            cursor: text; 
            border: 1px solid transparent;
        }
        .search-container:hover, .search-container:focus-within { 
            transform: scale(1.02); 
            border: 1px solid var(--search-focus); 
        }
        .search-divider { 
            width: 2px; height: 18px; 
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
            color: var(--primary-green);  /* Warna teks saat mengetik */
        }
        .search-input::placeholder { 
            color: var(--primary-green-hover); 
            opacity: 0.7; 
        }
        .search-input:focus { 
            box-shadow: none; 
        }
        .search-icon-btn { 
            cursor: pointer; 
            transition: transform 0.2s ease, opacity 0.2s ease; 
        }
        .search-icon-btn:hover {
            transform: scale(1.15); 
            opacity: 0.8; 
        }
        
        .spinning { 
            animation: spin 1s linear infinite !important; 
            display: inline-block; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }

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

        .dropdown-toggle:focus, .dropdown-toggle:active, .user-profile:focus { 
            outline: none !important; 
            box-shadow: none !important; 
            color: var(--primary-green) !important; 
        }
        .dropdown-toggle::after { 
            display: none; 
        }
        
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

        /* --- Konfigurasi Dropdown Notifikasi --- */
        #bellDropdownMenu {
            width: 300px;
            padding: 10px 0;
        }
        #bellDropdownMenu .dropdown-item {
            white-space: nowrap;     
            overflow: hidden;         /* Menyembunyikan teks yang tumpah keluar kotak */
            text-overflow: ellipsis;  /* Menambahkan titik tiga (...) di akhir teks */
            display: block;
            width: 100%;
            padding: 8px 16px;
            color: var(--primary-green);
        }
        /* 'Lihat Semua' tidak terpengaruh ellipsis (teksnya di tengah) */
        #bellDropdownMenu .text-center.dropdown-item {
            white-space: normal; 
            text-overflow: clip;
        }

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

        .avatar-sm { 
            width: 35px; height: 35px; 
            
            object-fit: cover; 
            background-color: white; 
            border: 1.5px solid var(--primary-green);
        }
        .avatar-sm.no-border { 
            border: none; 
        }
        .logo-text { 
            font-family: 'Poppins', sans-serif !important;
            letter-spacing: 0.5px; 
            vertical-align: middle; 
            
            /* Mencegah teks bisa di-select/blok */
            -webkit-user-select: none; /* Safari */
            -ms-user-select: none;     /* IE 10+ and Edge */
            user-select: none;         /* Standard syntax */
        }

        /* ==========================================
           ISI KONTEN DI BAWAH TOPBAR
           ========================================== */
        .app-main-content {
            flex: 1;         
            overflow-y: auto;
        }

        /* ==========================================
           CUSTOM BREADCRUMB & NAV TABS
           ========================================== */
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--primary-green);
        }
        .breadcrumb-item.active {
            color: var(--primary-green);
            font-weight: 600;
        }
        .breadcrumb-item a {
            color: var(--primary-green);
            transition: color 0.2s ease;
        }
        .breadcrumb-item a:hover {
            color: #6c757d !important;
        }
        .breadcrumb .dropdown-menu {
            min-width: 180px;
        }
        .breadcrumb .dropdown-item {
            color: #212529;
        }
        .breadcrumb .dropdown-item:hover {
            background-color: #e9ecef;
            color: #212529;
        }
        .breadcrumb .dropdown-item.active {
            background-color: var(--primary-green);
            color: #fff;
            pointer-events: none;
        }

        .nav-tabs .nav-link {
            color: var(--primary-green);
            border-color: transparent;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-green-hover);
            border-color: var(--primary-green-hover) var(--primary-green-hover) transparent;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-green);
            background-color: #fff;
            border-color: var(--primary-green) var(--primary-green) #fff;
            font-weight: 600;
        }

        .table-responsive table td,
        .table-responsive table th {
            white-space: nowrap;
        }
    </style>
    {{-- @yield('content_headscript') --}}
    @stack('styles')
</head>
<body>

    <div class="app-wrapper">
        
        <button id="sidebarToggleBtn" class="btn-sidebar-toggle floating-toggle text-primary-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7 5L15 12L7 19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>  

        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar collapsed" id="appSidebar">
            <div class="p-4">
                <div class="sidebar-header-wrapper mb-2">
                    <div class="sidebar-header">
                        {{-- <i class="bi bi-box-fill fs-3 me-2"></i> --}}
                        <h5 class="mb-0 fw-bold" style="font-family: 'Poppins', sans-serif;">{{ ucfirst(__('general.menu')) }}</h5>
                    </div>
                </div>

                <nav>
                    @foreach($menus as $menu)
                        @php
                            $name = app()->getLocale() == 'id' ? $menu->name_id : $menu->name_en;
                            $hasSub = $menu->children->isNotEmpty();
                            $activeParent = $hasSub && $menu->children->contains(fn($c) => request()->routeIs($c->route_name . '*'));
                            $activeSingle = !$hasSub && request()->routeIs($menu->route_name . '*');
                            $menuIconView = \App\Helpers\LayoutHelper::convertToViewPath($menu->icon);
                        @endphp

                        @if($hasSub)
                            <div class="sidebar-item">
                                <a class="sidebar-link has-sub {{ $activeParent ? 'sub-open active' : '' }}" title="{{ $name }}">
                                    <div class="sidebar-icon">
                                        @includeIf($menuIconView)
                                        @unless($menuIconView && View::exists($menuIconView)) <i class="bi bi-square-fill"></i> @endunless
                                    </div>
                                    <span class="menu-label">{{ $name }}</span>
                                </a>
                                <div class="sidebar-submenu" data-slug="{{ $menu->slug }}" style="{{ $activeParent ? 'display: block;' : '' }}">
                                    @foreach($menu->children as $child)
                                        @php 
                                            $childName = app()->getLocale() == 'id' ? $child->name_id : $child->name_en; 
                                            $childIconView =\App\Helpers\LayoutHelper::convertToViewPath($child->icon);;
                                        @endphp
                                        <a href="{{ $child->route_name ? route($child->route_name) : '#' }}" 
                                           class="sidebar-link {{ request()->routeIs($child->route_name . '*') ? 'active' : '' }}" 
                                           title="{{ $childName }}">
                                            <div class="sidebar-icon">
                                                @includeIf($childIconView)
                                                @unless($childIconView && View::exists($childIconView)) <i class="bi bi-dash-lg"></i> @endunless
                                            </div>
                                            <span class="submenu-label">{{ $childName }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $menu->route_name ? route($menu->route_name) : '#' }}" 
                               class="sidebar-link {{ $activeSingle ? 'active' : '' }}" title="{{ $name }}">
                                <div class="sidebar-icon">
                                    @includeIf($menuIconView)
                                    @unless($menuIconView && View::exists($menuIconView)) <i class="bi bi-square-fill"></i> @endunless
                                </div>
                                <span class="menu-label">{{ $name }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="layout-main-container">
            <header class="topbar shadow-sm">
                
                <div class="d-flex align-items-center">
                    
                    <div id="topbarSpacer" class="header-spacer"></div>

                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        {{-- <img src="{{ asset('images/mnm_logo.png') }}" alt="Logo" style="height: 32px;"> --}}
                        @include('layouts.logo.mnm_logo')
                        <h3 class="mb-0 fw-bold text-primary-green logo-text d-none d-md-block text-nowrap" style="font-family: Arial, sans-serif; letter-spacing: 0.5px; vertical-align: middle; margin-right:12px">
                            {{ ucwords(__('general.internal_platform')) }}
                        </h3>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 gap-md-4">
                    <form id="searchForm" action="#" method="GET" class="search-container shadow-sm d-none d-lg-flex">
                        <input type="text" name="search" id="navSearchInput" class="search-input" placeholder="{{ ucfirst(__('general.search')) }} {{ __('general.menu') }}...">
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
                            <li><h6 class="dropdown-header">{{  ucfirst(__('general.new_notification')) }}</h6></li>
                            <li><a class="dropdown-item" href="#">Notifikasi dummy satu </a></li>
                            <li><a class="dropdown-item" href="#">Notifkasi dummy dua</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center text-primary-green fw-bold" href="#">{{ ucfirst(__('general.show')) }} {{ ucfirst(__('general.all')) }}</a></li>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <div class="user-profile dropdown-toggle" id="userProfileToggle">
                            {{-- <img src="{{ asset('images/user-man.jpg') }}" class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm no-border"> --}}
                            <div class="rounded-circle text-primary-green d-flex align-items-center justify-content-center hover-effect avatar-sm fw-bold">
                                {{ \App\Helpers\TextHelper::getInitials(Auth::user()->name ?? '') }} 
                            </div>
                            <span class="hover-text fs-6 fw-semibold d-none d-sm-block">{{ \App\Helpers\TextHelper::limitWordsByChar(Auth::user()->name ?? '', 25) }}</span>
                        </div>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> {{  ucfirst(__('general.profile')) }}</a>
                            <a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> {{  ucfirst(__('general.settings')) }} </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="text-decoration-none ms-2 hover-effect text-primary-green bg-transparent border-0 p-0" title="{{ ucfirst(__('general.logout')) }}">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2H5C3.9 2 3 2.9 3 4V20C3 21.1 3.9 22 5 22H12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 2Q14 2 14 4V6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 18V20Q14 22 12 22" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17 9L21 12L17 15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </form>

                </div>
            </header>
            
            <section class="app-main-content" height="100%">
                <div class="row" height="100%">
                <div class="col-12" id="app-main-body" height="100%">
                    <div id='flash-msg-container'>
                    {{-- @include('flash::message') --}}
                    </div>
                    @yield('app-main-content')
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
            // SIDEBAR STATE (localStorage)
            // ==========================================
            var LS_KEY_SIDEBAR = 'app_sidebar_open';
            var LS_KEY_SUBMENUS = 'app_sidebar_submenus';
            var savedSidebar = localStorage.getItem(LS_KEY_SIDEBAR);
            var savedSubmenus = JSON.parse(localStorage.getItem(LS_KEY_SUBMENUS) || '[]');
            var restoringSidebar = false;

            // Restore sidebar state on load (no animation)
            if (savedSidebar === 'true') {
                restoringSidebar = true;
                $('#appSidebar').css('transition', 'none');
                $('.layout-main-container').css('transition', 'none');
                $('#topbarSpacer').css('transition', 'none');

                $('#appSidebar').removeClass('collapsed');
                $('.app-wrapper').addClass('sidebar-open');
                $('#sidebarToggleBtn').addClass('rotated');
            }

            // Restore open submenus (no animation)
            if (savedSubmenus.length) {
                $.each(savedSubmenus, function(i, slug) {
                    var $item = $('.sidebar-submenu[data-slug="' + slug + '"]');
                    if ($item.length) {
                        $item.show();
                        $item.prev('.has-sub').addClass('sub-open');
                    }
                });
            }

            // Re-enable transitions after paint
            if (restoringSidebar) {
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        $('#appSidebar').css('transition', '');
                        $('.layout-main-container').css('transition', '');
                        $('#topbarSpacer').css('transition', '');
                        restoringSidebar = false;
                        setTimeout(function() {
                            $.fn.dataTable.tables().forEach(function(table) {
                                var dt = $(table).DataTable();
                                if (dt) dt.columns.adjust();
                            });
                        }, 350);
                    });
                });
            }

            // ==========================================
            // LOGIKA SIDEBAR PUSH & ANIMATION
            // ==========================================
            $('#sidebarToggleBtn').on('click', function() {
                $('#appSidebar').toggleClass('collapsed');
                $('.app-wrapper').toggleClass('sidebar-open');
                $(this).toggleClass('rotated');
                var isOpen = $('#appSidebar').hasClass('collapsed') ? 'false' : 'true';
                localStorage.setItem(LS_KEY_SIDEBAR, isOpen);
                setTimeout(function() {
                    $.fn.dataTable.tables().forEach(function(table) {
                        var dt = $(table).DataTable();
                        if (dt) dt.columns.adjust();
                    });
                }, 350);
            });
            
            // ==========================================
            // LOGIKA DROPDOWN (USER & NOTIFIKASI)
            // ==========================================
            let layoutCursorX = 0;

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
                    layoutCursorX = e.clientX; 
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
                        
                        let x = layoutCursorX;
                        const toggleRect = this.getBoundingClientRect(); 
                        let y = toggleRect.bottom + yOffset; 

                        const menuWidth = $menuEl.outerWidth();
                        const safeRightBoundary = $(window).width() - 20; 

                        if (x + menuWidth > safeRightBoundary) 
                            x = safeRightBoundary - menuWidth;
                        if (x < 10) 
                            x = 10;
                        
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
                if (!$(e.target).closest('#userProfileToggle, #userDropdownMenu').length) 
                    closeDropdown($userMenu);
                if (!$(e.target).closest('#bellToggle, #bellDropdownMenu').length) 
                    closeDropdown($bellMenu);
            });

            // ==========================================
            // LOGIKA AJAX SEARCH
            // ==========================================
            $('#searchForm').on('click', function(e) {
                if (!$(e.target).closest('#searchButton').length) 
                    $('#navSearchInput').focus();
            });

            $('#searchForm').on('submit', function(e) {
                e.preventDefault(); 
                const keyword = $('#navSearchInput').val().trim();
                const $icon = $('#searchIcon');

                if (keyword !== '') {
                    $icon.removeClass('bi-search').addClass('bi-arrow-repeat spinning'); 

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
                                $icon.removeClass('bi-arrow-repeat spinning').addClass('bi-search');
                        }, 500);
                        }
                    });
                } else {
                    $('#navSearchInput').focus();
                }
            });
        
            // Menutup sidebar ketika area gelap transparan di-klik
            $('#sidebarOverlay').on('click', function() {
                $('#appSidebar').addClass('collapsed');
                $('.app-wrapper').removeClass('sidebar-open');
                $('#sidebarToggleBtn').removeClass('rotated');
                setTimeout(function() {
                    $.fn.dataTable.tables().forEach(function(table) {
                        var dt = $(table).DataTable();
                        if (dt) dt.columns.adjust();
                    });
                }, 350);
            });

            // ==========================================
            // LOGIKA SUB-MENU SIDEBAR
            // ========================================== 
            function saveSubmenuState() {
                var open = [];
                $('.sidebar-submenu').each(function() {
                    if ($(this).is(':visible')) {
                        open.push($(this).data('slug'));
                    }
                });
                localStorage.setItem(LS_KEY_SUBMENUS, JSON.stringify(open));
            }

            $('.has-sub').on('click', function(e) {
                e.preventDefault();
                
                const $parent = $(this).parent();
                const $submenu = $parent.find('.sidebar-submenu');

                $(this).toggleClass('sub-open');        //  rotasi panah

                $submenu.slideToggle(300);               // Animasi buka/tutup

                saveSubmenuState();
            });
        });
    </script>

    {{-- @yield('content_tailscript') --}}
    <script>
        window.adminTranslations = {
            errorOccurred: @json(__('general.error_occurred')),
            saving: @json(__('general.saving')),
            deleting: @json(__('general.deleting')),
        };
    </script>
    @stack('scripts')
</body>
</html>