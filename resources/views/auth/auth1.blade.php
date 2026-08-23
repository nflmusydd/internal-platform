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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #043523;
            --primary-green-hover: #0f513a;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html, body {
            min-height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            overflow: auto;
            -ms-overflow-style: none;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-green-hover) transparent;
        }

        /* ==========================================
           SCROLLBAR: body (Chrome, Edge, Safari)
           ========================================== */
        body::-webkit-scrollbar {
            width: 6px;
        }
        body::-webkit-scrollbar-track {
            background: transparent;
        }
        body::-webkit-scrollbar-thumb {
            background-color: var(--primary-green-hover);
            border-radius: 20px;
        }
        body::-webkit-scrollbar-thumb:hover {
            background-color: var(--primary-green);
        }

        /* ==========================================
           WRAPPER
           ========================================== */
        .auth-wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* ==========================================
           GREEN BACKGROUND (left & right)
           ========================================== */
        .auth-bg {
            flex: 1;
            background: linear-gradient(135deg, #043523 0%, #0f513a 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 3rem;
            overflow: hidden;
        }
        .auth-bg .brand-logo {
            margin-bottom: 1.5rem;
        }
        .auth-bg .brand-logo svg {
            max-width: 140px;
            height: auto;
        }
        .auth-bg h2 {
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }
        .auth-bg p {
            font-size: 0.9rem;
            opacity: 0.75;
            max-width: 300px;
        }
        .auth-bg .switch-or {
            font-size: 0.8rem;
            opacity: 0.6;
            margin-top: 1rem;
            margin-bottom: 0;
        }
        .auth-bg .btn-switch {
            margin-top: 0.75rem;
            padding: 10px 28px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: transparent;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }
        .auth-bg .btn-switch:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: #fff;
        }

        /* ==========================================
           WHITE PANEL (slides left/right)
           ========================================== */
        .auth-panel {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            background: #fff;
            z-index: 10;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
            padding-top: 3rem;
            padding-bottom: 3rem;
            -ms-overflow-style: none;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-green-hover) transparent;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ==========================================
           SCROLLBAR: panel (Chrome, Edge, Safari)
           ========================================== */
        .auth-panel::-webkit-scrollbar {
            width: 6px;
        }
        .auth-panel::-webkit-scrollbar-track {
            background: transparent;
        }
        .auth-panel::-webkit-scrollbar-thumb {
            background-color: var(--primary-green-hover);
            border-radius: 20px;
        }
        .auth-panel::-webkit-scrollbar-thumb:hover {
            background-color: var(--primary-green);
        }
        .auth-panel.panel-login {
            transform: translateX(100%);
        }
        .auth-panel.panel-register {
            transform: translateX(0%);
        }

        /* ==========================================
           FORM CONTAINER
           ========================================== */
        .auth-form {
            width: 100%;
            max-width: 380px;
            padding: 0 2rem;
            opacity: 1;
            transition: opacity 0.25s ease;
            flex-shrink: 0;
        }
        .auth-form.fade-out {
            opacity: 0;
        }
        .auth-form h3 {
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.25rem;
        }
        .auth-form .subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .auth-form .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
        }
        .auth-form .form-control {
            height: 42px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            transition: all 0.15s ease;
        }
        .auth-form .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(4, 53, 35, 0.1);
        }

        /* ==========================================
           BUTTONS
           ========================================== */
        .btn-auth {
            height: 44px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-auth:hover {
            background-color: var(--primary-green-hover);
            border-color: var(--primary-green-hover);
            color: #fff;
        }
        .btn-auth:focus {
            box-shadow: 0 0 0 0.15rem rgba(4, 53, 35, 0.25);
        }
        .btn-auth:active {
            transform: scale(0.98);
        }

        /* ==========================================
           FORM EXTRAS
           ========================================== */
        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .form-check-label {
            font-size: 0.82rem;
            color: #6c757d;
        }
        .invalid-feedback {
            font-size: 0.78rem;
        }
        .alert {
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.65rem 1rem;
        }

        /* ==========================================
           RESPONSIVE: MOBILE
           ========================================== */
        @media (max-width: 768px) {
            .auth-bg {
                display: none;
            }
            .auth-panel {
                width: 100%;
                transform: translateX(0%) !important;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">

        {{-- ============================
             GREEN BG: LOGIN SIDE (left)
             ============================ --}}
        <div class="auth-bg">
            <div class="brand-logo">
                @include('layouts.logo.mnm_logo_sagegreen')
            </div>
            <h2>{{ ucwords(__('general.internal_platform')) }}</h2>
            <p>{{ __('auth.login_tagline') }}</p>
            <p class="switch-or">{{ __('general.or') ?? 'or' }}</p>
            <button type="button" class="btn-switch" onclick="switchToRegister()">
                <i class="bi bi-person-plus me-1"></i>{{ ucfirst(__('auth.register')) }}
            </button>
        </div>

        {{-- ============================
             GREEN BG: REGISTER SIDE (right)
             ============================ --}}
        <div class="auth-bg">
            <div class="brand-logo">
                @include('layouts.logo.mnm_logo_sagegreen')
            </div>
            <h2>{{ ucwords(__('general.internal_platform')) }}</h2>
            <p>{{ __('auth.register_tagline') }}</p>
            <p class="switch-or">{{ __('general.or') ?? 'or' }}</p>
            <button type="button" class="btn-switch" onclick="switchToLogin()">
                <i class="bi bi-box-arrow-in-right me-1"></i>{{ ucfirst(__('general.login')) }}
            </button>
        </div>

        {{-- ============================
             WHITE PANEL (slides)
             ============================ --}}
        <div class="auth-panel panel-login" id="authPanel">

            {{-- LOGIN PANE --}}
            <div class="auth-form" id="loginPane">
                <h3>{{ __('auth.welcome_back') }}</h3>
                <p class="subtitle">{{ __('auth.login_subtitle') }}</p>

                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any() && !old('name'))
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" class="form-control"
                               id="loginEmail" name="email" value="{{ old('email') }}" autofocus
                               placeholder="{{ __('auth.placeholder_email') }}">
                        <div class="invalid-feedback" id="loginEmailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">{{ ucfirst(__('auth.password_label')) }}</label>
                        <input type="password" class="form-control"
                               id="loginPassword" name="password"
                               placeholder="{{ __('auth.placeholder_password') }}">
                        <div class="invalid-feedback" id="loginPasswordError"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="loginRemember" name="remember">
                            <label class="form-check-label" for="loginRemember">{{ __('auth.remember_me') }}</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-auth w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ ucfirst(__('general.login')) }}
                    </button>
                </form>
            </div>

            {{-- REGISTER PANE --}}
            <div class="auth-form" id="registerPane" style="display:none">
                <h3>{{ __('auth.register_title') }}</h3>
                <p class="subtitle">{{ __('auth.register_subtitle') }}</p>

                @if ($errors->any() && old('name'))
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="registerName" class="form-label">{{ ucfirst(__('general.name')) }}</label>
                        <input type="text" class="form-control"
                               id="registerName" name="name" value="{{ old('name') }}"
                               placeholder="{{ __('auth.placeholder_name') }}">
                        <div class="invalid-feedback" id="registerNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control"
                               id="registerEmail" name="email" value="{{ old('email') }}"
                               placeholder="{{ __('auth.placeholder_email') }}">
                        <div class="invalid-feedback" id="registerEmailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">{{ ucfirst(__('auth.password_label')) }}</label>
                        <input type="password" class="form-control"
                               id="registerPassword" name="password"
                               placeholder="{{ __('auth.placeholder_password') }}">
                        <div class="invalid-feedback" id="registerPasswordError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="registerPasswordConfirm" class="form-label">{{ ucfirst(__('auth.password_confirmation')) }}</label>
                        <input type="password" class="form-control"
                               id="registerPasswordConfirm" name="password_confirmation"
                               placeholder="{{ __('auth.placeholder_password') }}">
                        <div class="invalid-feedback" id="registerPasswordConfirmError"></div>
                    </div>
                    <button type="submit" class="btn btn-auth w-100 mb-3">
                        <i class="bi bi-person-plus me-1"></i>{{ ucfirst(__('auth.register')) }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script>
    var switching = false;

    function switchToRegister(skipPush) {
        if (switching) return;
        switching = true;

        $('#loginPane').addClass('fade-out');

        setTimeout(function() {
            $('#loginPane').hide();
            $('#registerPane').show();
            $('#authPanel').removeClass('panel-login').addClass('panel-register');

            setTimeout(function() {
                $('#registerPane').removeClass('fade-out');
                switching = false;
            }, 50);

            if (!skipPush) {
                history.pushState({ view: 'register' }, '', '{{ route("register") }}');
            }
        }, 250);
    }

    function switchToLogin(skipPush) {
        if (switching) return;
        switching = true;

        $('#registerPane').addClass('fade-out');

        setTimeout(function() {
            $('#registerPane').hide();
            $('#loginPane').show();
            $('#authPanel').removeClass('panel-register').addClass('panel-login');

            setTimeout(function() {
                $('#loginPane').removeClass('fade-out');
                switching = false;
            }, 50);

            if (!skipPush) {
                history.pushState({ view: 'login' }, '', '{{ route("login") }}');
            }
        }, 250);
    }

    $(function() {
        //  ==========================================
        //  INITIAL PANE: based on $authView or old('name')
        //  ==========================================
        @if ($authView === 'register' || old('name'))
            $('#loginPane').hide();
            $('#registerPane').show();
            $('#authPanel').removeClass('panel-login').addClass('panel-register');
            history.replaceState({ view: 'register' }, '', '{{ route("register") }}');
        @else
            history.replaceState({ view: 'login' }, '', '{{ route("login") }}');
        @endif

        // ==========================================
        //  BROWSER BACK/FORWARD: popstate listener
        //  ==========================================
        $(window).on('popstate', function(e) {
            var state = e.originalEvent.state;
            if (state && state.view === 'register') {
                switchToRegister(true);
            } else if (state && state.view === 'login') {
                switchToLogin(true);
            }
        });

        //  ==========================================
        //  LOGIN FORM: frontend validation
        //  ==========================================
        $('#loginForm').on('submit', function(e) {
            var valid = true;
            var $email = $('#loginEmail');
            var $password = $('#loginPassword');

            $email.removeClass('is-invalid');
            $password.removeClass('is-invalid');
            $('#loginEmailError, #loginPasswordError').text('');

            if (!$email.val().trim()) {
                $email.addClass('is-invalid');
                $('#loginEmailError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.email")]) }}');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.val())) {
                $email.addClass('is-invalid');
                $('#loginEmailError').text('{{ __("validation.email", ["attribute" => __("validation.attributes.email")]) }}');
                valid = false;
            }

            if (!$password.val().trim()) {
                $password.addClass('is-invalid');
                $('#loginPasswordError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.password")]) }}');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });

        //  ==========================================
        //  REGISTER FORM: frontend validation
        //  ========================================== 
        $('#registerForm').on('submit', function(e) {
            var valid = true;
            var $name = $('#registerName');
            var $email = $('#registerEmail');
            var $password = $('#registerPassword');
            var $confirm = $('#registerPasswordConfirm');

            $name.removeClass('is-invalid');
            $email.removeClass('is-invalid');
            $password.removeClass('is-invalid');
            $confirm.removeClass('is-invalid');
            $('#registerNameError, #registerEmailError, #registerPasswordError, #registerPasswordConfirmError').text('');

            if (!$name.val().trim()) {
                $name.addClass('is-invalid');
                $('#registerNameError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.name")]) }}');
                valid = false;
            }

            if (!$email.val().trim()) {
                $email.addClass('is-invalid');
                $('#registerEmailError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.email")]) }}');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.val())) {
                $email.addClass('is-invalid');
                $('#registerEmailError').text('{{ __("validation.email", ["attribute" => __("validation.attributes.email")]) }}');
                valid = false;
            }

            if (!$password.val()) {
                $password.addClass('is-invalid');
                $('#registerPasswordError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.password")]) }}');
                valid = false;
            } else if ($password.val().length < 8) {
                $password.addClass('is-invalid');
                $('#registerPasswordError').text('{{ __("validation.min.string", ["attribute" => __("validation.attributes.password"), "min" => 8]) }}');
                valid = false;
            }

            if (!$confirm.val()) {
                $confirm.addClass('is-invalid');
                $('#registerPasswordConfirmError').text('{{ __("validation.required", ["attribute" => __("validation.attributes.password_confirmation")]) }}');
                valid = false;
            } else if ($password.val() !== $confirm.val()) {
                $confirm.addClass('is-invalid');
                $('#registerPasswordConfirmError').text('{{ __("validation.confirmed", ["attribute" => __("validation.attributes.password")]) }}');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });

        //  ==========================================
        // CLEAR ERRORS on input
        // ==========================================
        $('#loginEmail').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#loginEmailError').text('');
        });
        $('#loginPassword').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#loginPasswordError').text('');
        });
        $('#registerName').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#registerNameError').text('');
        });
        $('#registerEmail').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#registerEmailError').text('');
        });
        $('#registerPassword').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#registerPasswordError').text('');
        });
        $('#registerPasswordConfirm').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#registerPasswordConfirmError').text('');
        });
    });
    </script>
</body>
</html>
