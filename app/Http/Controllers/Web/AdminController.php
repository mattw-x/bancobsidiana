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
    public function index()
    {
        // Traemos los datos con Eager Loading para evitar el problema N+1
        $users = User::with('account')->latest()->get();
        $merchants = Merchant::with('user')->latest()->get();
        $cards = Card::with('account.user')->latest()->get();

        return view('admin.dashboard', compact('users', 'merchants', 'cards'));
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
