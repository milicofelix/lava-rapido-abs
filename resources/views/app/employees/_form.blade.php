@include('app.components.errors')

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="text-sm font-medium">Nome</span>
        <input name="name" value="{{ old('name', $employee->name) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-medium">E-mail</span>
        <input name="email" type="email" value="{{ old('email', $employee->email) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-medium">Telefone</span>
        <input name="phone" value="{{ old('phone', $employee->phone) }}" data-mask="phone" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2" placeholder="(11) 99999-9999">
        @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-medium">Perfil</span>
        <select name="role" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $employee->role ?: 'operator') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-medium">Senha provisoria {{ $employee->exists ? '/ reset de senha opcional' : '' }}</span>
        <input name="password" type="password" @required(! $employee->exists) class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @if ($employee->exists)
            <span class="mt-1 block text-xs text-zinc-500">Preencha apenas se quiser resetar a senha deste usuario.</span>
        @endif
        @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    @if ($employee->exists && ! $employee->is(auth()->user()))
        <label class="block">
            <span class="text-sm font-medium">Status</span>
            <select name="is_active" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                <option value="1" @selected((string) old('is_active', (int) $employee->is_active) === '1')>Ativo</option>
                <option value="0" @selected((string) old('is_active', (int) $employee->is_active) === '0')>Inativo</option>
            </select>
            @error('is_active') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    @endif
</div>

<div class="mt-5 flex gap-2">
    <button class="rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Salvar</button>
    <a href="{{ route('employees.index') }}" class="rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold">Cancelar</a>
</div>
