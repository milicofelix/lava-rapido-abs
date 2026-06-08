<x-app.layout heading="Novo servico" title="Novo servico · Lava Rapido ABS">
    <form method="POST" action="{{ route('services.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @include('app.services._form')
    </form>
</x-app.layout>
