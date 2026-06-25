<x-app.layout heading="Editar cliente" title="Editar cliente · AutoFlow">
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('app.customers._form')
    </form>
</x-app.layout>
