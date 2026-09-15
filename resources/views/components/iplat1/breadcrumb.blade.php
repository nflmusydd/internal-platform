@props(['group' => 'sysadmin', 'currentPage' => ''])

@php
    $locale = app()->getLocale();
    $parentMenu = \App\Models\Menu::where('slug', $group)
                    ->whereNull('route_name')
                    ->where('is_active', true)
                    ->first();
    $childMenus = [];
    if ($parentMenu) {
        $childMenus = \App\Models\Menu::where('parent_id', $parentMenu->id)
                        ->where('is_active', true)
                        ->whereNotNull('route_name')
                        ->orderBy('order')
                        ->get();
    }
    $groupName = $parentMenu ? ($locale === 'id' ? $parentMenu->name_id : $parentMenu->name_en) : ucfirst($group);
@endphp

<ol class="breadcrumb mb-0 small">
    <li class="breadcrumb-item">
        <a href="/" class="text-decoration-none text-primary-green">Home</a>
    </li>
    @if($childMenus->count())
        <li class="breadcrumb-item dropdown">
            <a href="#" class="text-decoration-none text-primary-green dropdown-toggle"
               data-bs-toggle="dropdown" role="button">{{ $groupName }}</a>
            <ul class="dropdown-menu">
                @foreach($childMenus as $child)
                    @php $childName = $locale === 'id' ? $child->name_id : $child->name_en; @endphp
                    <li>
                        <a class="dropdown-item {{ request()->routeIs($child->route_name . '*') ? 'active' : '' }}"
                           href="{{ route($child->route_name) }}">
                            {{ $childName }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    @else
        <li class="breadcrumb-item">
            <span class="text-primary-green">{{ $groupName }}</span>
        </li>
    @endif
    @if($currentPage)
        <li class="breadcrumb-item" aria-current="page">{{ $currentPage }}</li>
    @endif
</ol>