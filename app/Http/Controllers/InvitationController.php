<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function create()
    {
        $invitations = Invitation::orderByDesc('id')->paginate(10);

        return view('invitations.create', compact('invitations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'send_email' => 'nullable|boolean',
        ]);

        $existingInvitation = Invitation::where('email', $request->email)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existingInvitation) {
            return back()
                ->withErrors([
                    'email' => 'Ya existe una invitación pendiente para este correo.',
                ])
                ->withInput();
        }

        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'created_by' => Auth::id(),
        ]);

        $link = route('register', ['token' => $invitation->token]);

        if ($request->boolean('send_email')) {
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $link));

            return back()
                ->with('success', 'Invitación creada y enviada por correo.')
                ->with('invitation_link', $link);
        }

        return back()
            ->with('success', 'Invitación creada correctamente.')
            ->with('invitation_link', $link);
    }

    public function resend(Invitation $invitation)
    {
        if (! $invitation->isPending()) {
            return back()->withErrors([
                'email' => 'Solo se pueden reenviar invitaciones pendientes.',
            ]);
        }

        $link = route('register', ['token' => $invitation->token]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $link));

        return back()->with('success', 'Invitación reenviada por correo.');
    }

    public function revoke(Invitation $invitation)
    {
        if (! $invitation->isPending()) {
            return back()->withErrors([
                'email' => 'Solo se pueden revocar invitaciones pendientes.',
            ]);
        }

        $invitation->revoked_at = now();
        $invitation->save();

        return back()->with('success', 'Invitación revocada correctamente.');
    }
}