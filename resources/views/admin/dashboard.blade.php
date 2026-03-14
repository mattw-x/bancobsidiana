

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
                </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-8">

                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                        <h2 class="font-bold">Usuarios Registrados</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 text-xs uppercase">
                                <tr>
                                    <th class="px-6 py-4">Usuario / Email</th>
                                    <th class="px-6 py-4">Saldo</th>
                                    <th class="px-6 py-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($users ?? [] as $user)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold">{{ $user->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-emerald-600">
                                        ${{ number_format($user->account->balance ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        {{-- BOTÓN RESET PASSWORD --}}
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

                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50">
                        <h2 class="font-bold">Tarjetas de la Red</h2>
                        <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-1 rounded font-black italic uppercase">BancObsidiana Network</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left font-mono">
                            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 text-xs uppercase font-sans">
                                <tr>
                                    <th class="px-4 py-4 text-center">Ver</th>
                                    <th class="px-4 py-4">Número PAN</th>
                                    <th class="px-4 py-4">CVV</th>
                                    <th class="px-4 py-4">Vence</th>
                                    <th class="px-4 py-4 text-right font-sans">Límite</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cards ?? [] as $card)
                                <tr x-data="{ reveal: false }" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-4 py-4 text-center">
                                        <button @click="reveal = !reveal" class="text-zinc-400 hover:text-indigo-500">
                                            <flux:icon.eye class="size-4" x-show="!reveal" />
                                            <flux:icon.eye-slash class="size-4 text-indigo-500" x-show="reveal" style="display:none;" />
                                        </button>
                                    </td>
                                    <td class="px-4 py-4 text-xs tracking-widest">
                                        <span x-show="!reveal">**** **** **** {{ substr($card->card_number, -4) }}</span>
                                        <span x-show="reveal" class="text-indigo-600 font-bold" style="display:none;">{{ $card->card_number }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span x-show="!reveal" class="text-zinc-300">***</span>
                                        <span x-show="reveal" class="bg-zinc-200 dark:bg-zinc-700 px-1 rounded" style="display:none;">{{ $card->cvv }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span x-show="!reveal" class="text-zinc-300">**/**</span>
                                        <span x-show="reveal" style="display:none;">{{ $card->expiration_date->format('m/y') }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-sans font-bold">
                                        ${{ number_format($card->credit_limit, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm self-start">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="font-bold text-indigo-600">Comercios Afiliados</h2>
                </div>
                <div class="p-4 space-y-4">
                    @foreach($merchants ?? [] as $merchant)
                    <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-transparent hover:border-indigo-500/30 transition-all group">
                        <div class="w-10 h-10 bg-indigo-600 text-white rounded-lg flex items-center justify-center font-bold">
                            {{ substr($merchant->merchant_name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-sm dark:text-white">{{ $merchant->merchant_name }}</div>
                            <div class="text-[9px] text-zinc-500 font-mono">{{ $merchant->rif }}</div>
                            {{-- URL Visible --}}
                            <div class="text-[9px] text-indigo-400 truncate max-w-[150px]">{{ $merchant->url ?? 'No URL' }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-show="openMerchantModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/60 backdrop-blur-md p-4"
             style="display: none;">

            <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-2xl p-6 border border-zinc-200 dark:border-zinc-700 shadow-2xl">
                 <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold dark:text-white italic">REGISTRAR COMERCIO</h3>
                    <button @click="openMerchantModal = false" class="text-zinc-400 hover:text-zinc-600">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                 </div>

                 <form action="{{ route('admin.merchants.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Nombre Comercial</label>
                        <input type="text" name="merchant_name" required placeholder="Eje: Tienda Tech" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">RIF</label>
                            <input type="text" name="rif" required placeholder="J-12345678" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Dueño (Usuario)</label>
                            <select name="user_id" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none">
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- CAMPO NUEVO: URL --}}
                    <div>
                        <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">URL de la Tienda (Webhook)</label>
                        <input type="url" name="url" placeholder="https://mi-tienda.com/api/callback" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="flex gap-4 pt-6">
                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl font-bold shadow-lg shadow-indigo-500/30 transition-all">
                            Vincular Comercio
                        </button>
                    </div>
                 </form>
            </div>
        </div>
    </div>
</x-layouts.app.header>

