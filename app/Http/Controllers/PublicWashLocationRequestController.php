<?php

namespace App\Http\Controllers;

use App\Models\WashLocationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicWashLocationRequestController extends Controller
{
    public function create(): View
    {
        return view('public.location-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'responsible_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', 'min:8', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:150'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:180'],
            'address_number' => ['required', 'string', 'max:30'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'employees_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1200'],
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'Confirme que os dados informados são verdadeiros para enviar a solicitação.',
        ]);

        $validated['email'] = mb_strtolower($validated['email']);
        $validated['owner_password'] = $validated['password'];
        $validated['state'] = mb_strtoupper($validated['state']);
        unset($validated['accept_terms'], $validated['password'], $validated['password_confirmation']);

        if (WashLocationRequest::hasPendingContact($validated['email'], $validated['phone'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Já existe uma solicitação pendente para este e-mail ou WhatsApp. Aguarde a análise antes de enviar novamente.',
                ]);
        }

        $validated['status'] = WashLocationRequest::STATUS_PENDING_REVIEW;

        WashLocationRequest::query()->create($validated);

        return redirect()
            ->route('public.location-requests.thank-you')
            ->with('status', 'Solicitação enviada com sucesso. Em breve entraremos em contato para validar os dados.');
    }

    public function thankYou(): View
    {
        return view('public.location-requests.thank-you');
    }
}
