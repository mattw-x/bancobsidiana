<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Card;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Muestra el Dashboard principal con todas las tablas.
     */
    public function index() {
    $users = \App\Models\User::with(['account', 'merchants'])->latest()->get() ?? collect();
    $cards = \App\Models\Card::with('account.user')->latest()->get() ?? collect();
    $merchants = \App\Models\Merchant::all() ?? collect();
    $accounts = \App\Models\Account::with('user')->get() ?? collect();

    return view('admin.dashboard', compact('users', 'cards', 'merchants', 'accounts'));
}
    public function storeMerchant(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:merchants,user_id',
            'merchant_name' => 'required|string|unique:merchants,merchant_name',
            'rif' => 'required|string',
        ]);

        \App\Models\Merchant::create($request->all());

        return back()->with('success', 'Comercio afiliado exitosamente.');
    }

    /**
     * Resetea la contraseña de un usuario específico a "password".
     */
    public function resetPassword(Request $request, User $user)
    {
        $user->update([
            'password' => Hash::make('password')
        ]);

        return back()->with('success', "Contraseña de {$user->name} restablecida a 'password'.");
    }
}
