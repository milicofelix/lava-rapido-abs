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
        <span class="text-sm font-medium">Perfil</span>
        <select name="role" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $employee->role ?: 'operator') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-medium">Senha {{ $employee->exists ? '(opcional)' : '' }}</span>
        <input name="password" type="password" @required(! $employee->exists) class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @if ($employee->exists)
            <span class="mt-1 block text-xs text-zinc-500">Preencha apenas se quiser alterar a senha.</span>
        @endif
        @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</div>

<div class="mt-5 flex gap-2">
    <button class="rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Salvar</button>
    <a href="{{ route('employees.index') }}" class="rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold">Cancelar</a>
</div>
