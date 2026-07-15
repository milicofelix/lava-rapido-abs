@include('app.components.errors')

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="employee-form-card">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados de acesso</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $employee->exists ? 'Editar usuario' : 'Novo usuario' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Defina os dados de contato, perfil e credenciais de acesso ao sistema.</p>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <label class="block" data-tour="employee-form-name">
            <span class="text-sm font-bold text-slate-700">Nome</span>
            <input name="name" value="{{ old('name', $employee->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block" data-tour="employee-form-email">
            <span class="text-sm font-bold text-slate-700">E-mail</span>
            <input name="email" type="email" value="{{ old('email', $employee->email) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block" data-tour="employee-form-phone">
            <span class="text-sm font-bold text-slate-700">Telefone</span>
            <input name="phone" value="{{ old('phone', $employee->phone) }}" data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="(11) 99999-9999">
            @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block" data-tour="employee-form-role">
            <span class="text-sm font-bold text-slate-700">Perfil</span>
            <select name="role" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $employee->role ?: 'operator') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block" data-tour="employee-form-password">
            <span class="text-sm font-bold text-slate-700">Senha provisoria {{ $employee->exists ? '/ reset opcional' : '' }}</span>
            <input name="password" type="password" @required(! $employee->exists) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @if ($employee->exists)
                <span class="mt-1 block text-xs text-slate-500">Preencha apenas se quiser resetar a senha deste usuario.</span>
            @endif
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        @if ($employee->exists && ! $employee->is(auth()->user()))
            <label class="block" data-tour="employee-form-status">
                <span class="text-sm font-bold text-slate-700">Status</span>
                <select name="is_active" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="1" @selected((string) old('is_active', (int) $employee->is_active) === '1')>Ativo</option>
                    <option value="0" @selected((string) old('is_active', (int) $employee->is_active) === '0')>Inativo</option>
                </select>
                @error('is_active') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        @endif
    </div>
</section>

<div class="mt-5 flex flex-wrap justify-end gap-3" data-tour="employee-form-actions">
    <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar usuario</button>
</div>

@php
    $employeeFormTour = [
        'key' => $employee->exists ? 'employees.edit.v1' : 'employees.create.v1',
        'title' => $employee->exists ? 'Edição de usuário' : 'Cadastro de usuário',
        'steps' => [
            [
                'target' => '[data-tour="employee-form-card"]',
                'title' => 'Dados de acesso',
                'body' => 'Cadastre quem fará parte da operação e poderá acessar o sistema da unidade.',
            ],
            [
                'target' => '[data-tour="employee-form-name"]',
                'title' => 'Nome',
                'body' => 'Use o nome que a equipe reconhece, pois ele aparece em responsáveis, histórico e auditoria.',
            ],
            [
                'target' => '[data-tour="employee-form-email"]',
                'title' => 'E-mail de login',
                'body' => 'Este e-mail será usado para entrar no sistema e precisa ser único.',
            ],
            [
                'target' => '[data-tour="employee-form-role"]',
                'title' => 'Perfil',
                'body' => 'Escolha o perfil com cuidado. Operador fica focado na execução, enquanto atendente e administrador têm mais acessos.',
            ],
            [
                'target' => '[data-tour="employee-form-password"]',
                'title' => 'Senha provisória',
                'body' => $employee->exists
                    ? 'Na edição, preencha somente se quiser redefinir a senha desse usuário.'
                    : 'No cadastro, informe uma senha inicial para o primeiro acesso do usuário.',
            ],
            [
                'target' => '[data-tour="employee-form-status"]',
                'title' => 'Status de acesso',
                'body' => 'Em edição, você pode desativar o usuário sem apagar seu histórico operacional.',
            ],
            [
                'target' => '[data-tour="employee-form-actions"]',
                'title' => 'Salvar usuário',
                'body' => 'Revise os dados e salve para aplicar o acesso na unidade.',
            ],
        ],
    ];
@endphp
<script type="application/json" data-onboarding-tour>
    {!! json_encode($employeeFormTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
