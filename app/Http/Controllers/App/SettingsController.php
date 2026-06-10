<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('app.settings.edit', [
            'settings' => AppSetting::allSettings(),
            'themes' => [
                AppSetting::THEME_LIGHT => 'Padrao claro',
                AppSetting::THEME_DARK => 'Dark',
                AppSetting::THEME_SYSTEM => 'Sistema',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_whatsapp' => ['nullable', 'string', 'max:30'],
            'theme' => ['required', Rule::in([
                AppSetting::THEME_LIGHT,
                AppSetting::THEME_DARK,
                AppSetting::THEME_SYSTEM,
            ])],
            'module_cash_register' => ['nullable', 'boolean'],
            'module_credit_receivables' => ['nullable', 'boolean'],
        ]);

        AppSetting::setMany([
            'company_name' => $data['company_name'],
            'company_whatsapp' => $data['company_whatsapp'] ?? '',
            'theme' => $data['theme'],
            'module_cash_register' => $request->boolean('module_cash_register'),
            'module_credit_receivables' => $request->boolean('module_credit_receivables'),
        ]);

        return back()->with('status', 'Configuracoes salvas com sucesso.');
    }
}
