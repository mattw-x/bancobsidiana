<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BancObsidiana - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-slate-900 p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center text-white">
            <span class="font-bold text-xl tracking-tight">🏦 BancObsidiana Admin</span>
            <span class="text-sm">Módulo de Soporte y Control</span>
        </div>
    </nav>

    <div class="container mx-auto mt-8 px-4">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <span @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer">
                    <strong class="text-xl">&times;</strong>
                </span>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <div class="xl:col-span-2 bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">👥 Gestión de Usuarios</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Nombre</th>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Email</th>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Cuenta</th>
                                <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach($users as $user)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4">{{ $user->name }}</td>
                                <td class="py-3 px-4">{{ $user->email }}</td>
                                <td class="py-3 px-4">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                        {{ $user->account->account_number ?? 'Sin cuenta' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST" onsubmit="return confirm('¿Restablecer contraseña a \'password\'?');">
                                        @csrf
                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold py-1 px-3 rounded shadow">
                                            Reset Password
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">🏪 Comercios Afiliados</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-indigo-600 text-white">
                            <tr>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Comercio (Slug)</th>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Dueño</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach($merchants as $merchant)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold">{{ $merchant->merchant_name }}</td>
                                <td class="py-3 px-4">{{ $merchant->user->name ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="mt-8 bg-white shadow rounded-lg p-6 mb-10">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">💳 Monitoreo de Tarjetas Emitidas (BIN 05)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-emerald-600 text-white">
                        <tr>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Titular</th>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Número de Tarjeta</th>
                            <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Expiración</th>
                            <th class="text-right py-3 px-4 uppercase font-semibold text-sm">Límite</th>
                            <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @foreach($cards as $card)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $card->account->user->name ?? 'Desconocido' }}</td>
                            <td class="py-3 px-4 tracking-widest font-mono text-sm">{{ $card->card_number }}</td>
                            <td class="py-3 px-4 text-center">{{ $card->expiration_date->format('m/y') }}</td>
                            <td class="py-3 px-4 text-right text-green-600 font-bold">${{ number_format($card->credit_limit, 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($card->status == 'active')
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full uppercase">Activa</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full uppercase">{{ $card->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
