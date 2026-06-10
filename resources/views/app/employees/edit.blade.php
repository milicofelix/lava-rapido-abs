<x-app.layout heading="Editar funcionario" title="Editar funcionario · AutoFlow">
    <form method="POST" action="{{ route('employees.update', $employee) }}" class="rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('app.employees._form')
    </form>
</x-app.layout>
