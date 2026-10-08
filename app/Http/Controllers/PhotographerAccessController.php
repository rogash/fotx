<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhotographerAccessController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'portfolio' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user->has_pending_photographer_request()) {
            $user->request_photographer_access($validated['portfolio'] ?? null);
        }

        return redirect()->route('dashboard')->with('status', 'Pedido enviado. Avisaremos por aqui quando o acesso de fotógrafo for liberado.');
    }
}
