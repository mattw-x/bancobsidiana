<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Card;
use App\Models\Merchant;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index() {
        $users = User::withTrashed()->with(['accounts' => fn($q) => $q->withTrashed(),'merchants' => fn($q) => $q->withTrashed()])->latest()->get();
        $cards = Card::withTrashed()->with(['account' => fn($q) => $q->withTrashed()->with(['user' => fn($q2) => $q2->withTrashed()])])->latest()->get();
        $merchants = Merchant::withTrashed()->with(['user' => fn($q) => $q->withTrashed()])->latest()->get();
        $accounts = Account::withTrashed()->with(['user' => fn($q) => $q->withTrashed()])->get();
        return view('admin.dashboard', compact('users', 'cards', 'merchants', 'accounts'));
    }

    // NUEVA FUNCIÓN: Actualizar Saldo
    public function updateBalance(Request $request, Account $account)
    {
        $request->validate(['balance' => 'required|numeric|min:0']);
        $account->update(['balance' => $request->balance]);

        return back()->with('success', "Saldo de la cuenta {$account->account_number} actualizado.");
    }

    // NUEVA FUNCIÓN: Bloquear/Desbloquear Tarjeta
    public function toggleCard(Card $card)
    {
        $newStatus = ($card->status === 'active') ? 'blocked' : 'active';
        $card->update(['status' => $newStatus]);

        return back()->with('success', "Tarjeta {$card->card_number} ahora está: {$newStatus}.");
    }

    public function storeMerchant(Request $request)
{
    $data = $request->validate([
        'user_id' => 'required|exists:users,id|unique:merchants,user_id',
        'merchant_name' => 'required|string|unique:merchants,merchant_name',
        'rif' => 'required|string',
        'card_id' => 'required|exists:cards,id',
        'webhook_url' => 'nullable|url'
    ]);

    // Generamos automáticamente una API Key única para el comercio
    $data['api_key'] = 'sk_' . bin2hex(random_bytes(16));

    Merchant::create($data);

    return back()->with('success', 'Comercio afiliado exitosamente.');
}

public function updateMerchant(Request $request, Merchant $merchant)
{
    // IMPORTANTE: Usamos $merchant->user_id porque definiste esa como primaryKey
    $request->validate([
        'merchant_name' => 'required|string|unique:merchants,merchant_name,' . $merchant->user_id . ',user_id',
        'rif' => 'required|string',
        'card_id' => 'required|exists:cards,id',
        'webhook_url' => 'nullable|url'
    ]);

    $merchant->update($request->only('merchant_name', 'rif', 'webhook_url', 'card_id'));

    return back()->with('success', 'Comercio actualizado correctamente.');
}
    public function resetPassword(Request $request, User $user)
    {
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', "Contraseña de {$user->name} restablecida.");
    }

    // Usuarios
    public function destroyUser(User $user) { $user->delete(); return back()->with('success',
    'Usuario suspendido.'); }
    public function restoreUser(User $user) { $user->restore(); return back()->with('success',
    'Usuario restaurado.'); }
        // Cuentas
        public function destroyAccount(Account $account) { $account->delete(); return
    back()->with('success', 'Cuenta suspendida.'); }
        public function restoreAccount(Account $account) { $account->restore(); return
    back()->with('success', 'Cuenta restaurada.'); }

        // Tarjetas
        public function destroyCard(Card $card) { $card->delete(); return back()->with('success',
    'Tarjeta eliminada (Soft Delete).'); }
        public function restoreCard(Card $card) { $card->restore(); return back()->with('success',
    'Tarjeta restaurada.'); }

        // Comercios
        public function destroyMerchant(Merchant $merchant) { $merchant->delete(); return
    back()->with('success', 'Comercio suspendido.'); }
        public function restoreMerchant(Merchant $merchant) { $merchant->restore(); return
    back()->with('success', 'Comercio restaurado.'); }
}
