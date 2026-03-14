<x-layouts.app.header>
    <div class="p-6 bg-zinc-50 dark:bg-zinc-900 min-h-screen" x-data="{ openMerchantModal: false }">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">🏦 BancObsidiana Core</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Panel de Administración Central.</p>
            </div>
            <button @click="openMerchantModal = true" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                <flux:icon.plus class="size-5 mr-2" />
                Nuevo Comercio
            </button>
        </div>

        {{-- ESTADO GLOBAL --}}
        <div class="mb-10">
            <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center">
                <span class="w-2 h-6 bg-indigo-500 rounded-full mr-2"></span> Estado Global
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($accounts ?? [] as $acc)
                <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold uppercase text-zinc-400">Balance</span>
                        <span class="text-emerald-600 font-bold font-mono">${{ number_format($acc->balance, 2) }}</span>
                    </div>
                    <div class="text-zinc-900 dark:text-white font-bold truncate mt-2">{{ $acc->user->name ?? 'N/A' }}</div>
                    <div class="text-[9px] text-zinc-500 font-mono">{{ $acc->account_number }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-8">
            {{-- SECCIÓN USUARIOS: Cuentas desplegables --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm w-full">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <h2 class="font-bold">Usuarios y Cuentas</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-4">Usuario / Email</th>
                                <th class="px-6 py-4">Gestión de Cuentas</th>
                                <th class="px-6 py-4 text-center">Seguridad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($users as $user)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50" x-data="{ showAccounts: false }">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-bold text-base">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-500 mb-2">{{ $user->email }}</div>
                                    <button @click="showAccounts = !showAccounts" class="text-[10px] bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded font-bold uppercase hover:bg-indigo-500 hover:text-white transition-colors">
                                        <span x-show="!showAccounts">Mostrar Cuentas ({{ $user->accounts->count() }})</span>
                                        <span x-show="showAccounts">Ocultar Cuentas</span>
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <div x-show="showAccounts" x-collapse class="space-y-3">
                                        @foreach($user->accounts as $account)
                                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-100 dark:border-zinc-700">
                                            <div>
                                                <div class="text-[10px] font-mono text-zinc-400">{{ $account->account_number }}</div>
                                                <div class="text-xs font-bold text-emerald-600">${{ number_format($account->balance, 2) }}</div>
                                            </div>
                                            <form action="{{ route('admin.accounts.update-balance', $account->id) }}" method="POST" class="flex items-center gap-2">
                                                @csrf
                                                <input type="number" name="balance" step="0.01" value="{{ $account->balance }}"
                                                       class="w-20 p-1 text-[10px] border rounded bg-white dark:bg-zinc-900 dark:text-white">
                                                <button type="submit" class="p-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-600 hover:text-white transition-colors">
                                                    <flux:icon.check class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div x-show="!showAccounts" class="text-zinc-400 italic text-xs">Cuentas ocultas...</div>
                                </td>
                                <td class="px-6 py-4 text-center align-top">
                                    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:text-amber-700 font-bold text-xs flex items-center gap-1 mx-auto">
                                            <flux:icon.key class="size-3" /> Reset Clave
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECCIÓN TARJETAS: Ancho completo y datos recuperados --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm w-full">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50">
                    <h2 class="font-bold">Tarjetas de la Red</h2>
                    <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-1 rounded font-black italic uppercase">BancObsidiana Network</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left font-mono">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 text-xs uppercase font-sans">
                            <tr>
                                <th class="px-4 py-4 text-center">Ver</th>
                                <th class="px-4 py-4">PAN</th>
                                <th class="px-4 py-4">CVV</th>
                                <th class="px-4 py-4">Vence</th>
                                <th class="px-4 py-4">Estado</th>
                                <th class="px-4 py-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cards as $card)
                            <tr x-data="{ reveal: false }" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-4 py-4 text-center font-sans">
                                    <button @click="reveal = !reveal" class="text-zinc-400 hover:text-indigo-500">
                                        <flux:icon.eye class="size-4" x-show="!reveal" />
                                        <flux:icon.eye-slash class="size-4 text-indigo-500" x-show="reveal" style="display:none;" />
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-xs tracking-widest">
                                    <span x-show="!reveal">**** **** **** {{ substr($card->card_number, -4) }}</span>
                                    <span x-show="reveal" class="text-indigo-600 font-bold" style="display:none;">{{ $card->card_number }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span x-show="!reveal" class="text-zinc-300">***</span>
                                    <span x-show="reveal" class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded text-zinc-900 dark:text-white" style="display:none;">{{ $card->cvv }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span x-show="!reveal" class="text-zinc-300">**/**</span>
                                    <span x-show="reveal" class="text-zinc-900 dark:text-white" style="display:none;">{{ $card->expiration_date->format('m/y') }}</span>
                                </td>
                                <td class="px-4 py-4 font-sans">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $card->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $card->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-sans">
                                    <form action="{{ route('admin.cards.toggle', $card->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[10px] font-bold uppercase {{ $card->status === 'active' ? 'text-red-500' : 'text-emerald-500' }}">
                                            {{ $card->status === 'active' ? 'Bloquear' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- COMERCIOS: Ahora en la parte inferior ocupando el ancho necesario --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="font-bold text-indigo-600">Comercios Afiliados</h2>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($merchants ?? [] as $merchant)
                    <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-transparent hover:border-indigo-500/30 transition-all group">
                        <div class="w-10 h-10 bg-indigo-600 text-white rounded-lg flex items-center justify-center font-bold">
                            {{ substr($merchant->merchant_name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-sm dark:text-white truncate">{{ $merchant->merchant_name }}</div>
                            <div class="text-[9px] text-zinc-500 font-mono">{{ $merchant->rif }}</div>
                            <div class="text-[9px] text-indigo-400 truncate">{{ $merchant->url ?? 'No URL' }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- MODAL REGISTRO COMERCIO (Sin cambios) --}}
        <div x-show="openMerchantModal" ... (mismo código del modal que tenías) ...>
            ...
        </div>
    </div>
</x-layouts.app.header>
