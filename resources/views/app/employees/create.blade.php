<x-app.layout heading="Novo funcionario" title="Novo funcionario · AutoFlow">
    <form method="POST" action="{{ route('employees.store') }}" class="rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @include('app.employees._form')
    </form>
</x-app.layout>
