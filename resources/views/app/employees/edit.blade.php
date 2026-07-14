<x-app.layout heading="Editar usuario" title="Editar usuario · AutoFlow">
    <form method="POST" action="{{ route('employees.update', $employee) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('app.employees._form')
    </form>
</x-app.layout>
