@props(['icon' => 'bi-inbox', 'message' => 'Tidak ada data', 'description' => ''])

<tr>
    <td colspan="{{ $attributes->get('colspan', 5) }}" class="text-center py-5">
        <div class="d-flex flex-column align-items-center">
            <i class="bi {{ $icon }} fs-1 text-muted mb-3"></i>
            <p class="text-muted mb-1 fw-semibold">{{ $message }}</p>
            @if($description)
                <p class="text-muted small mb-0">{{ $description }}</p>
            @endif
        </div>
    </td>
</tr>