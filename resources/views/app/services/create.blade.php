<x-app.layout heading="Novo serviço" title="Novo serviço · AutoFlow">
    <form method="POST" action="{{ route('services.store') }}" class="max-w-4xl">
        @csrf
        @include('app.services._form')
    </form>
</x-app.layout>
