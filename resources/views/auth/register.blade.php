<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucwords(__('auth.register')) }} - {{ ucwords(__('general.internal_platform')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #043523;
            --primary-green-hover: #0f513a;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
        }
        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #043523 0%, #0f513a 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 3rem;
            text-align: center;
        }
        .login-left .brand-logo {
            margin-bottom: 1.5rem;
        }
        .login-left .brand-logo svg {
            max-width: 140px;
            height: auto;
        }
        .login-left h2 {
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }
        .login-left p {
            font-size: 0.9rem;
            opacity: 0.75;
            max-width: 300px;
        }
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        .login-card h3 {
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.25rem;
        }
        .login-card .subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .login-card .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
        }
        .login-card .form-control {
            height: 42px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            transition: all 0.15s ease;
        }
        .login-card .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(4, 53, 35, 0.1);
        }
        .btn-login {
            height: 44px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background-color: var(--primary-green-hover);
            border-color: var(--primary-green-hover);
            color: #fff;
        }
        .btn-login:focus {
            box-shadow: 0 0 0 0.15rem rgba(4, 53, 35, 0.25);
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .invalid-feedback {
            font-size: 0.78rem;
        }
        .alert {
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.65rem 1rem;
        }
        .auth-link {
            font-size: 0.82rem;
            color: #6c757d;
        }
        .auth-link a {
            color: var(--primary-green);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div class="brand-logo">
                @include('layouts.logo.mnm_logo_sagegreen')
            </div>
            <h2>{{ ucwords(__('general.internal_platform')) }}</h2>
            <p>{{ __('auth.register_tagline') }}</p>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>{{ __('auth.register_title') }}</h3>
                <p class="subtitle">{{ __('auth.register_subtitle') }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ ucfirst(__('general.name')) }}</label>
                        <input type="text" class="form-control"
                               id="name" name="name" value="{{ old('name') }}" autofocus
                               placeholder="{{ __('auth.placeholder_name') }}">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="{{ __('auth.placeholder_email') }}">
                        <div class="invalid-feedback" id="emailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">{{ ucfirst(__('auth.password_label')) }}</label>
                        <input type="password" class="form-control"
                               id="password" name="password"
                               placeholder="{{ __('auth.placeholder_password') }}">
                        <div class="invalid-feedback" id="passwordError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">{{ ucfirst(__('auth.password_confirmation')) }}</label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation"
                               placeholder="{{ __('auth.placeholder_password') }}">
                        <div class="invalid-feedback" id="passwordConfirmError"></div>
                    </div>
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="bi bi-person-plus me-1"></i>{{ ucfirst(__('auth.register')) }}
                    </button>
                    <div class="text-center auth-link">
                        {{ __('auth.have_account') }}
                        <a href="{{ route('login') }}">{{ ucfirst(__('general.login')) }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script>
    $(function() {
        $('#registerForm').on('submit', function(e) {
            var valid = true;
            var $name = $('#name');
            var $email = $('#email');
            var $password = $('#password');
            var $passwordConfirm = $('#password_confirmation');

            $name.removeClass('is-invalid');
            $email.removeClass('is-invalid');
            $password.removeClass('is-invalid');
            $passwordConfirm.removeClass('is-invalid');
            $('#nameError, #emailError, #passwordError, #passwordConfirmError').text('');

            if (!$name.val().trim()) {
                $name.addClass('is-invalid');
                $('#nameError').text('{{ __("validation.required", ["attribute" => "name"]) }}');
                valid = false;
            }

            if (!$email.val().trim()) {
                $email.addClass('is-invalid');
                $('#emailError').text('{{ __("validation.required", ["attribute" => "email"]) }}');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.val())) {
                $email.addClass('is-invalid');
                $('#emailError').text('{{ __("validation.email", ["attribute" => "email"]) }}');
                valid = false;
            }

            if (!$password.val()) {
                $password.addClass('is-invalid');
                $('#passwordError').text('{{ __("validation.required", ["attribute" => "password"]) }}');
                valid = false;
            } else if ($password.val().length < 8) {
                $password.addClass('is-invalid');
                $('#passwordError').text('{{ __("validation.min.string", ["attribute" => "password", "min" => 8]) }}');
                valid = false;
            }

            if (!$passwordConfirm.val()) {
                $passwordConfirm.addClass('is-invalid');
                $('#passwordConfirmError').text('{{ __("validation.required", ["attribute" => "password confirmation"]) }}');
                valid = false;
            } else if ($password.val() !== $passwordConfirm.val()) {
                $passwordConfirm.addClass('is-invalid');
                $('#passwordConfirmError').text('{{ __("validation.confirmed", ["attribute" => "password"]) }}');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });

        $('#name').on('input', function() { $(this).removeClass('is-invalid'); $('#nameError').text(''); });
        $('#email').on('input', function() { $(this).removeClass('is-invalid'); $('#emailError').text(''); });
        $('#password').on('input', function() { $(this).removeClass('is-invalid'); $('#passwordError').text(''); });
        $('#password_confirmation').on('input', function() { $(this).removeClass('is-invalid'); $('#passwordConfirmError').text(''); });
    });
    </script>
</body>
</html>
