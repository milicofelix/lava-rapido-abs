<x-app.layout heading="Editar cliente" title="Editar cliente · Lava Rapido ABS">
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('app.customers._form')
    </form>
</x-app.layout>
