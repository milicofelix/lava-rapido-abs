<?php

namespace App\Console\Commands;

use App\Support\ReadinessReport;
use Illuminate\Console\Command;

class ReadinessCheckCommand extends Command
{
    protected $signature = 'app:readiness-check';

    protected $description = 'Valida dependencias essenciais em tempo de execucao.';

    public function handle(ReadinessReport $readiness): int
    {
        $report = $readiness->check();

        foreach ($report['checks'] as $check) {
            $line = ($check['ok'] ? '[OK] ' : '[FALHA] ').$check['message'];

            $check['ok']
                ? $this->info($line)
                : $this->error($line);
        }

        $this->newLine();

        if (! $report['ready']) {
            $this->error('Aplicacao indisponivel para receber trafego.');

            return self::FAILURE;
        }

        $this->info('Aplicacao pronta para receber trafego.');

        return self::SUCCESS;
    }
}
