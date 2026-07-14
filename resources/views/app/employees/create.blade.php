<x-app.layout heading="Novo usuario" title="Novo usuario · AutoFlow">
    <form method="POST" action="{{ route('employees.store') }}" class="max-w-4xl">
        @csrf
        @include('app.employees._form')
    </form>
</x-app.layout>
