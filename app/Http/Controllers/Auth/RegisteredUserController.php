<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $invitation = Invitation::where('token', $request->token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            abort(403, 'La invitación no es válida o ya venció.');
        }

        return view('auth.register', [
            'token' => $request->token,
            'invitedEmail' => $invitation->email,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $invitation = Invitation::where('token', $request->token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return back()->withErrors([
                'email' => 'La invitación no es válida o venció.',
            ])->withInput();
        }

        if (strcasecmp($invitation->email, $request->email) !== 0) {
            return back()->withErrors([
                'email' => 'El email no coincide con el de la invitación.',
            ])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $invitation->used_at = now();
        $invitation->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}