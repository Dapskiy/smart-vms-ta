@props(['appointment'])

@php
    $remaining = $appointment->remaining_visitors;

    if (empty($remaining)) {
        $display = '-';
        $isMultiple = false;
    } elseif (count($remaining) === 1) {
        $display = $remaining[0];
        $isMultiple = false;
    } else {
        $display = $remaining[0];
        $isMultiple = true;
        $othersCount = count($remaining) - 1;
        $allNames = $remaining;
    }
@endphp

<div class="visitor-list-wrapper">
    <span class="visitor-main">{{ $display }}</span>

    @if ($isMultiple)
        <button x-data="{
            expanded: false,
            allNames: {{ json_encode($allNames) }},
            othersCount: {{ $othersCount }},
            toggle() {
                this.expanded = !this.expanded;
            }
        }" @click="toggle()" type="button" class="visitor-btn"
            style="background: none; border: none; padding: 0 4px; color: #2563eb; cursor: pointer; font-size: 0.875rem; font-weight: 500; text-decoration: none; display: inline;">
            <span x-show="!expanded">+ {{ $othersCount }} others</span>
            <span x-show="expanded">show less</span>
        </button>

        <div class="visitor-expanded" x-show="expanded"
            style="display: none; margin-top: 8px; padding: 8px; background: #f9fafb; border-radius: 4px; border-left: 3px solid #2563eb; margin-left: 0;">
            @foreach ($allNames as $name)
                <div style="font-size: 0.875rem; margin-bottom: 4px;">{{ $name }}</div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .dark .visitor-expanded {
        background: #1f2937 !important;
        border-left-color: #3b82f6 !important;
    }

    .visitor-btn:hover {
        text-decoration: underline;
    }
</style>
