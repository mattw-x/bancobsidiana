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
        // Obtenemos los datos necesarios
        $users = User::with(['account', 'merchants'])->latest()->get();
        $cards = Card::with('account.user')->latest()->get();
        $merchants = Merchant::all();
        $accounts = Account::with('user')->get();

        // Pasamos 'cards' (no allCards) para que coincida con tu blade
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
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:merchants,user_id',
            'merchant_name' => 'required|string|unique:merchants,merchant_name',
            'rif' => 'required|string',
        ]);

        Merchant::create($request->all());
        return back()->with('success', 'Comercio afiliado exitosamente.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', "Contraseña de {$user->name} restablecida.");
    }
}
