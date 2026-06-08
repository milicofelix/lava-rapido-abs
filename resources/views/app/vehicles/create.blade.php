<x-app.layout heading="Novo veiculo" title="Novo veiculo · Lava Rapido ABS">
    <form method="POST" action="{{ route('vehicles.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @include('app.vehicles._form')
    </form>
</x-app.layout>
