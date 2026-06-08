<x-app.layout heading="Novo cliente" title="Novo cliente · Lava Rapido ABS">
    <form method="POST" action="{{ route('customers.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @include('app.customers._form')
    </form>
</x-app.layout>
