<x-app.layout heading="Editar veiculo" title="Editar veiculo · AutoFlow">
    <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('app.vehicles._form')
    </form>
</x-app.layout>
