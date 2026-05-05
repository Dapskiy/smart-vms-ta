@php
    $appointment = $record;
    $remaining = $appointment->remaining_visitors;

    if (empty($remaining)) {
        $display = '-';
        $isMultiple = false;
    } elseif (count($remaining) === 1) {
        $display = $remaining[0];
        $isMultiple = false;
    } else {
        $firstName = $remaining[0];
        $isMultiple = true;
        $othersCount = count($remaining) - 1;
        $allNamesInline = implode(', ', $remaining);
    }
@endphp

@if (!$isMultiple)
    <span>{{ $display }}</span>
@else
    <span
        x-data="{
            expanded: false,
            allNames: {{ json_encode($allNamesInline) }},
            firstName: {{ json_encode($firstName) }},
            othersCount: {{ $othersCount }}
        }"
        style="display: inline;"
    >
        {{-- Collapsed: "Rexa +1 others" --}}
        <span x-show="!expanded" style="display: inline;">
            <span>{{ $firstName }}</span>
            <button
                @click="expanded = true"
                type="button"
                class="visitor-toggle-btn"
            >+{{ $othersCount }} others</button>
        </span>

        {{-- Expanded: "Rexa, Faza, ... show less" --}}
        <span x-show="expanded" style="display: inline;">
            <span x-text="allNames"></span>
            <button
                @click="expanded = false"
                type="button"
                class="visitor-toggle-btn"
            >show less</button>
        </span>
    </span>
@endif

<style>
    .visitor-toggle-btn {
        background: none;
        border: none;
        padding: 0 0 0 4px;
        color: #2563eb;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline;
        line-height: inherit;
    }
    .visitor-toggle-btn:hover {
        text-decoration: underline;
    }
    .dark .visitor-toggle-btn {
        color: #60a5fa;
    }
</style>
