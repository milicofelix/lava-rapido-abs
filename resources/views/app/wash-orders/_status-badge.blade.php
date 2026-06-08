@php
    $statusClasses = [
        'aguardando' => 'bg-zinc-100 text-zinc-700',
        'em_preparacao' => 'bg-amber-100 text-amber-800',
        'lavando' => 'bg-cyan-100 text-cyan-800',
        'aspirando' => 'bg-sky-100 text-sky-800',
        'aplicando_cera' => 'bg-fuchsia-100 text-fuchsia-800',
        'finalizando' => 'bg-indigo-100 text-indigo-800',
        'pronto_para_retirada' => 'bg-emerald-100 text-emerald-800',
        'entregue' => 'bg-zinc-950 text-white',
        'cancelado' => 'bg-red-100 text-red-800',
    ];
@endphp

<span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-zinc-100 text-zinc-700' }}">
    {{ $label }}
</span>
