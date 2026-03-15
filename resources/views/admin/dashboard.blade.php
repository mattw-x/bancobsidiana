<x-layouts.app.header>
    <div class="p-6 bg-zinc-50 dark:bg-zinc-900 min-h-screen" x-data="{ openMerchantModal: false }">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight"> 🏦 BancObsidiana Core</h1>
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
            {{-- SECCIÓN USUARIOS --}}
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
                                <th class="px-6 py-4 text-center">Acciones / Seguridad</th>
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
                                            {{-- Suspender / Restaurar Cuenta --}}
                                            @if($account->trashed())
                                                <form action="{{ route('admin.accounts.restore', $account->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" title="Restaurar Cuenta" class="p-1 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-600 hover:text-white transition-colors">
                                                        <flux:icon.arrow-path class="size-3" />
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('¿Suspender cuenta?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Suspender Cuenta" class="p-1 bg-red-50 text-red-600 rounded hover:bg-red-600 hover:text-white transition-colors">
                                                        <flux:icon.trash class="size-3" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                    <div x-show="!showAccounts" class="text-zinc-400 italic text-xs">Cuentas ocultas...</div>
                                </td>
                                <td class="px-6 py-4 text-center align-top">
                                    <div class="flex flex-col gap-2 items-center">
                                        {{-- Reset Password (igual) --}}
                                        <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-amber-600 hover:text-amber-700 font-bold text-xs flex items-center gap-1">
                                                <flux:icon.key class="size-3" /> Clave
                                            </button>
                                        </form>

                                        {{-- Suspender / Restaurar Usuario --}}
                                        @if($user->trashed())
                                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-700 font-bold text-xs flex items-center gap-1">
                                                    <flux:icon.arrow-path class="size-3" /> Restaurar
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Suspender usuario?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-700 font-bold text-xs flex items-center gap-1">
                                                    <flux:icon.no-symbol class="size-3" /> Suspender
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECCIÓN TARJETAS (Sin cambios estructurales) --}}
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
                                <th class="px-4 py-4">CUENTA</th>
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
                                <td class="px-4 py-4">
                                    <span x-show="!reveal" class="text-zinc-300">XXX-XXXXXXXXXXXX</span>
                                    <span x-show="reveal" class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded text-zinc-900 dark:text-white" style="display:none;">{{ $card->account->account_number }}</span>
                                </td>
                                <td class="px-4 py-4 text-xs tracking-widest">
                                    <span x-show="!reveal">**** **** **** {{ substr($card->card_number, -4) }}</span>
                                    <span x-show="reveal" class="text-indigo-600 font-bold" style="display:none;">{{ substr($card->card_number, 0, 4) }} {{ substr($card->card_number, 4, 4) }} {{ substr($card->card_number, 8, 4) }} {{ substr($card->card_number, -4) }}</span>
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
                                    <div class="flex flex-col gap-1 items-end mt-2 border-t border-zinc-200 dark:border-zinc-700 pt-2">
                                        @if($card->trashed())
                                            <form action="{{ route('admin.cards.restore', $card->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-[9px] font-bold uppercase text-indigo-500  hover:text-indigo-700 flex items-center gap-1">
                                                    <flux:icon.arrow-path class="size-3" /> Restaurar
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.cards.destroy', $card->id) }}" method="POST"  onsubmit="return confirm('¿Eliminar tarjeta?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-[9px] font-bold uppercase text-zinc-400 hover:text-red-500 flex items-center gap-1">
                                                    <flux:icon.trash class="size-3" /> Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        {{-- COMERCIOS --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                <h2 class="font-bold text-indigo-600">Comercios Afiliados</h2>
            </div>
            <div class="p-4 grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach($merchants ?? [] as $merchant)
                <div x-data="{ openEditModal: false }" class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-transparent hover:border-indigo-500/30 transition-all group">

                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-indigo-600 text-white rounded-lg flex items-center justify-center font-bold shrink-0">
                            {{ substr($merchant->merchant_name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-sm dark:text-white truncate">{{ $merchant->merchant_name }}</div>
                            <div class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Dueño: <span class="text-indigo-500">{{ $merchant->user->name ?? 'N/A' }}</span></div>
                            <div class="text-[10px] text-zinc-500 font-mono">RIF: {{ $merchant->rif }}</div>
                            <div class="text-[10px] text-indigo-400 truncate">Webhook: {{ $merchant->webhook_url ?? 'Ninguna' }}</div>
                        </div>
                    </div>

                    {{-- Botones Editar / Eliminar Comercio --}}
                    <div class="flex items-center gap-2">
                        <button @click="openEditModal = true" class="p-1.5 text-zinc-400 hover:text-indigo-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors">
                            <flux:icon.pencil class="size-4" />
                        </button>

                        @if($merchant->trashed())
                            <form action="{{ route('admin.merchants.restore', $merchant->user_id) }}" method="POST">
                                @csrf
                                <button type="submit" title="Restaurar Comercio" class="p-1.5 text-emerald-500 hover:text-emerald-700 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors">
                                    <flux:icon.arrow-path class="size-4" />
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.merchants.destroy', $merchant->user_id) }}" method="POST" onsubmit="return confirm('¿Suspender comercio?');">
                                @csrf @method('DELETE')
                                <button type="submit" title="Suspender Comercio" class="p-1.5 text-zinc-400 hover:text-red-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors">
                                    <flux:icon.trash class="size-4" />
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- MODAL EDICIÓN COMERCIO (Interno) --}}
                    <div x-show="openEditModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/60 backdrop-blur-md p-4">
                        <div @click.away="openEditModal = false" class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-2xl p-6 border border-zinc-200 dark:border-zinc-700 shadow-2xl">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-xl font-bold dark:text-white italic">EDITAR COMERCIO</h3>
                                <button @click="openEditModal = false" type="button" class="text-zinc-400 hover:text-zinc-600"><flux:icon.x-mark class="size-6" /></button>
                            </div>
                            <form action="{{ route('admin.merchants.update', $merchant->user_id) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Nombre Comercial</label>
                                    <input type="text" name="merchant_name" value="{{ $merchant->merchant_name }}" required class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">RIF</label>
                                    <input type="text" name="rif" value="{{ $merchant->rif }}" required class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                {{-- SELECCIÓN DE TARJETA (Solo las de este dueño) --}}
                                <div>
                                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Tarjeta Receptora (Comercio Credit)</label>
                                    <select name="card_id" required class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                                        @foreach($cards->where('brand', 'Comercio Credit')->where('account.user_id', $merchant->user_id) as $card)
                                            <option value="{{ $card->id }}" {{ $merchant->card_id == $card->id ? 'selected' : '' }}>
                                                **** {{ substr($card->card_number, -4) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">URL Webhook</label>
                                    <input type="url" name="webhook_url" value="{{ $merchant->webhook_url }}" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="pt-4"><button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl font-bold shadow-lg transition-all">Guardar Cambios</button></div>
                            </form>
                        </div>
                    </div>
                    {{-- FIN MODAL EDICIÓN --}}

                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BLOQUE PHP PARA PREPROCESAR TARJETAS PARA EL MODAL DE CREACIÓN --}}
    @php
        $comercioCardsGrouped = [];
        foreach($cards as $c) {
            if($c->brand === 'Comercio Credit' && $c->account && $c->account->user) {
                $uid = $c->account->user->id;
                $comercioCardsGrouped[$uid][] = [
                    'id' => $c->id,
                    'label' => '**** ' . substr($c->card_number, -4)
                ];
            }
        }
    @endphp

    {{-- MODAL CREACIÓN NUEVO COMERCIO (Dinámico con Alpine.js) --}}
    <div x-show="openMerchantModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/60 backdrop-blur-md p-4"
         style="display: none;">

        <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-2xl p-6 border border-zinc-200 dark:border-zinc-700 shadow-2xl" @click.away="openMerchantModal = false">
             <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold dark:text-white italic">REGISTRAR COMERCIO</h3>
                <button @click="openMerchantModal = false" class="text-zinc-400 hover:text-zinc-600">
                    <flux:icon.x-mark class="size-6" />
                </button>
             </div>

             <form action="{{ route('admin.merchants.store') }}" method="POST" class="space-y-4"
                   x-data="{
                       selectedUser: '',
                       cardsData: {{ json_encode($comercioCardsGrouped) }},
                       get availableCards() { return this.cardsData[this.selectedUser] || []; }
                   }">
                @csrf
                <div>
                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Nombre Comercial</label>
                    <input type="text" name="merchant_name" required placeholder="Eje: Tienda FinTech" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">RIF</label>
                        <input type="text" name="rif" required placeholder="J-12345678" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Dueño (Usuario)</label>
                        <select name="user_id" x-model="selectedUser" required class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Seleccione...</option>
                            @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- SELECCIÓN DE TARJETA REACTIVA --}}
                <div>
                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">Tarjeta Receptora (Comercio Credit)</label>
                    <select name="card_id" required class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none disabled:opacity-50 focus:ring-2 focus:ring-indigo-500" :disabled="availableCards.length === 0">
                        <option value="">Seleccione una tarjeta...</option>
                        <template x-for="card in availableCards" :key="card.id">
                            <option :value="card.id" x-text="card.label"></option>
                        </template>
                    </select>
                    <p x-show="selectedUser && availableCards.length === 0" class="text-[9px] text-red-500 mt-1 ml-1" style="display: none;">
                        * El usuario no posee tarjetas "Comercio Credit".
                    </p>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-zinc-500 uppercase ml-1">URL de la Tienda (Webhook)</label>
                    <input type="url" name="webhook_url" placeholder="https://mi-tienda.com/api/callback" class="w-full p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl dark:text-white border-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex gap-4 pt-6">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl font-bold shadow-lg shadow-indigo-500/30 transition-all disabled:opacity-50" :disabled="availableCards.length === 0">
                        Vincular Comercio
                    </button>
                </div>
             </form>
        </div>
    </div>
</x-layouts.app.header>
