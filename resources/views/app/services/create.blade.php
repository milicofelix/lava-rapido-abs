<x-app.layout heading="Novo servico" title="Novo servico · AutoFlow">
    <form method="POST" action="{{ route('services.store') }}" class="max-w-4xl">
        @csrf
        @include('app.services._form')
    </form>
</x-app.layout>
