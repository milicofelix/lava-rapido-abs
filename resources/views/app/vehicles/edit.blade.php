<x-app.layout heading="Editar veiculo" title="Editar veiculo · AutoFlow">
    <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('app.vehicles._form')
    </form>
</x-app.layout>
