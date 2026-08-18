@extends('layouts.green_layout')

@section('app-main-content')
<div class="p-4 p-md-5">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                @include('components.admin.breadcrumb', ['group' => 'sysadmin', 'currentPage' => __('sysadmin/user-permissions/index.user_permissions')])
            </nav>
            <h4 class="fw-bold text-primary-green mb-1" style="font-family:'Poppins',sans-serif;">
                {{ __('sysadmin/user-permissions/index.user_permissions') }}
            </h4>
        </div>
    </div>

</div>
@endsection
