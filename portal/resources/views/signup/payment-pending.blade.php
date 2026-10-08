<x-signup-layout title="Confirmando el pago">
    <meta http-equiv="refresh" content="3">
    <p class="mt-2 text-stone-600">Estamos esperando la confirmación de Stripe. Suele tardar unos segundos; esta página se actualiza sola.</p>
    <p class="mt-4 text-sm text-stone-500">Si pagaste con un método que tarda más (como una domiciliación), te avisaremos por email en cuanto llegue y tu correo se activará solo.</p>
    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Ir a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
