<x-app.layout heading="Novo veiculo" title="Novo veiculo · AutoFlow">
    <form method="POST" action="{{ route('vehicles.store') }}" class="max-w-4xl">
        @csrf
        @include('app.vehicles._form')
    </form>
</x-app.layout>
