<x-app.layout heading="Novo cliente" title="Novo cliente · AutoFlow">
    <form method="POST" action="{{ route('customers.store') }}" class="max-w-4xl">
        @csrf
        @include('app.customers._form')
    </form>
</x-app.layout>
