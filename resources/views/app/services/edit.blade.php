<x-app.layout heading="Editar servico" title="Editar servico · AutoFlow">
    <form method="POST" action="{{ route('services.update', $service) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('app.services._form')
    </form>
</x-app.layout>
