<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TripInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PendingTripInvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invitation = TripInvitation::query()
            ->with('trip:id,destination')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        abort_unless(
            hash_equals($invitation->email, Str::lower(trim($request->user()->email))),
            404,
        );

        return view('trip-invitations.show', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }
}
