@include('app.components.errors')

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados de acesso</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $employee->exists ? 'Editar usuario' : 'Novo usuario' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Defina os dados de contato, perfil e credenciais de acesso ao sistema.</p>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Nome</span>
            <input name="name" value="{{ old('name', $employee->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-slate-700">E-mail</span>
            <input name="email" type="email" value="{{ old('email', $employee->email) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-slate-700">Telefone</span>
            <input name="phone" value="{{ old('phone', $employee->phone) }}" data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="(11) 99999-9999">
            @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-slate-700">Perfil</span>
            <select name="role" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $employee->role ?: 'operator') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-slate-700">Senha provisoria {{ $employee->exists ? '/ reset opcional' : '' }}</span>
            <input name="password" type="password" @required(! $employee->exists) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @if ($employee->exists)
                <span class="mt-1 block text-xs text-slate-500">Preencha apenas se quiser resetar a senha deste usuario.</span>
            @endif
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        @if ($employee->exists && ! $employee->is(auth()->user()))
            <label class="block">
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

<div class="mt-5 flex flex-wrap justify-end gap-3">
    <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar usuario</button>
</div>
