<?php

namespace Tests\Feature\App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocaleConfigurationTest extends TestCase
{
    public function test_sistema_usa_locale_timezone_e_faker_brasileiros(): void
    {
        $this->assertSame('pt_BR', config('app.locale'));
        $this->assertSame('pt_BR', config('app.fallback_locale'));
        $this->assertSame('pt_BR', config('app.faker_locale'));
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame('pt_BR', Carbon::getLocale());
    }

    public function test_mensagens_de_validacao_estao_em_portugues(): void
    {
        $validator = Validator::make([], [
            'email' => ['required', 'email'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('O campo e-mail e obrigatorio.', $validator->errors()->first('email'));
    }
}
