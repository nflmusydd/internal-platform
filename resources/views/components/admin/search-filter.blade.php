@props(['id', 'filters' => [], 'maxCols' => 2])

@php
    $rows = [];
    $currentRow = [];
    foreach ($filters as $filter) {
        if (!empty($filter['newRow']) && !empty($currentRow)) {
            $rows[] = $currentRow;
            $currentRow = [];
        }
        $currentRow[] = $filter;
    }
    if (!empty($currentRow)) {
        $rows[] = $currentRow;
    }
@endphp

<div id="filterBar-{{ $id }}" class="filter-bar mb-3" style="--filter-cols: {{ $maxCols }};display:none;">
    @foreach($rows as $rowFilters)
        <div class="filter-row">
            @foreach($rowFilters as $filter)
                <div class="filter-group">
                    <label class="filter-label">{{ $filter['label'] ?? '' }}</label>

                    @if(($filter['type'] ?? 'text') === 'select')
                        <div class="dropdown custom-filter-dropdown">
                            <button class="filter-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-chevron-down filter-dropdown-icon"></i>
                                <span class="filter-dropdown-text">{{ $filter['placeholder'] ?? ucfirst(__('general.all')) }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm">
                                @if(!empty($filter['placeholder']))
                                    <li><a class="dropdown-item active" href="javascript:void(0)" data-value="">{{ $filter['placeholder'] }}</a></li>
                                @endif
                                @if(!empty($filter['options']))
                                    @foreach($filter['options'] as $val => $label)
                                        <li><a class="dropdown-item" href="javascript:void(0)" data-value="{{ $val }}">{{ $label }}</a></li>
                                    @endforeach
                                @endif
                            </ul>
                            <input type="hidden" data-filter="{{ $filter['key'] }}" value="">
                        </div>
                    @elseif(($filter['type'] ?? 'text') === 'date')
                        <div class="filter-input-wrap">
                            <i class="bi bi-calendar3 filter-input-icon"></i>
                            <input type="date" class="filter-input" data-filter="{{ $filter['key'] }}">
                        </div>
                    @else
                        <div class="filter-input-wrap">
                            <i class="bi bi-search filter-input-icon"></i>
                            <input type="text" class="filter-input" data-filter="{{ $filter['key'] }}"
                                   placeholder="{{ $filter['placeholder'] ?? '' }}">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="filter-group filter-reset-group">
        <button class="btn btn-sm btn-outline-secondary filter-reset" type="button"
                data-target="{{ $id }}" title="{{ ucfirst(__('general.reset')) }}">
            <i class="bi bi-arrow-counterclockwise me-1 fs-5"></i>{{ ucfirst(__('general.reset')) }}
        </button>
    </div>
</div>
