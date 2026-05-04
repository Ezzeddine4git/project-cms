@props([
    'columnSpan' => [],
    'columnStart' => [],
    'height' => null,
])

<div
    {{
        ($attributes ?? new \Illuminate\View\ComponentAttributeBag)
            ->gridColumn($columnSpan, $columnStart)
            ->class(['cms-dashboard-loader'])
            ->style(['min-height: ' . ($height ?? '14rem')])
    }}
>
    <div class="cms-dashboard-loader__header">
        <span></span>
        <span></span>
    </div>

    <div class="cms-dashboard-loader__rows">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
</div>
