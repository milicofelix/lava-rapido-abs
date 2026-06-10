<x-app.layout heading="Editar servico" title="Editar servico · AutoFlow">
    <form method="POST" action="{{ route('services.update', $service) }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('app.services._form')
    </form>
</x-app.layout>
