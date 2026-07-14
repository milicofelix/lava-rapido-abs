<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo {{ $washOrder->code }} · AutoFlow</title>
    @include('components.favicon')
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }
        .page {
            max-width: 760px;
            margin: 32px auto;
            padding: 0 16px;
        }
        .receipt {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }
        .header,
        .row,
        .total-row,
        .actions {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .brand {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #0f766e;
        }
        .muted { color: #71717a; }
        .small { font-size: 12px; }
        .code {
            text-align: right;
            font-weight: 700;
        }
        .section {
            margin-top: 24px;
            border-top: 1px solid #e4e4e7;
            padding-top: 18px;
        }
        h1, h2, p { margin: 0; }
        h2 {
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #52525b;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
        }
        .label {
            display: block;
            color: #71717a;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .value { font-weight: 650; }
        .item {
            padding: 12px 0;
            border-bottom: 1px dashed #e4e4e7;
        }
        .item:last-child { border-bottom: 0; }
        .total-row {
            margin-top: 18px;
            border-radius: 14px;
            background: #ecfeff;
            padding: 16px;
            color: #164e63;
        }
        .total-row strong { font-size: 22px; }
        .actions {
            max-width: 760px;
            margin: 18px auto 32px;
            padding: 0 16px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #d4d4d8;
            background: #ffffff;
            color: #27272a;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
            cursor: pointer;
        }
        .button.primary {
            background: #0e7490;
            border-color: #0e7490;
            color: #ffffff;
        }
        @media print {
            body { background: #ffffff; }
            .page { margin: 0 auto; max-width: none; padding: 0; }
            .receipt { border: 0; box-shadow: none; border-radius: 0; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <main class="page">
        <article class="receipt">
            <header class="header">
                <div>
                    <p class="brand">AutoFlow</p>
                    <p class="muted">Gestao inteligente para lava-rapidos</p>
                </div>
                <div class="code">
                    <p>Recibo de lavagem</p>
                    <p class="muted small">{{ $washOrder->code }}</p>
                    <p class="muted small">{{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </header>

            <section class="section">
                <h2>Cliente e veiculo</h2>
                <div class="grid">
                    <p><span class="label">Cliente</span><span class="value">{{ $washOrder->customer->name }}</span></p>
                    <p><span class="label">Telefone</span><span class="value">{{ $washOrder->customer->phone ?? '-' }}</span></p>
                    <p><span class="label">Veiculo</span><span class="value">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</span></p>
                    <p><span class="label">Placa</span><span class="value">{{ $washOrder->vehicle->plate }}</span></p>
                    <p><span class="label">Status</span><span class="value">{{ $washOrder->statusLabel() }}</span></p>
                    <p><span class="label">Pagamento</span><span class="value">{{ $washOrder->paymentStatusLabel() }}</span></p>
                </div>
            </section>

            <section class="section">
                <h2>Servicos</h2>
                @foreach ($washOrder->services as $service)
                    <div class="item row">
                        <div>
                            <p class="value">{{ $service->pivot->service_name }}</p>
                            <p class="muted small">{{ $service->pivot->estimated_minutes }} min</p>
                        </div>
                        <p class="value">R$ {{ number_format((float) $service->pivot->price, 2, ',', '.') }}</p>
                    </div>
                @endforeach

                <div class="total-row">
                    <span>Total</span>
                    <strong>R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</strong>
                </div>
            </section>

            <section class="section">
                <h2>Pagamentos registrados</h2>
                @forelse ($washOrder->payments->sortBy('paid_at') as $payment)
                    <div class="item row">
                        <div>
                            <p class="value">{{ $payment->methodLabel() }}</p>
                            <p class="muted small">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <p class="value">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="muted">Nenhum pagamento registrado.</p>
                @endforelse
            </section>

            <section class="section">
                <p class="muted small">Este recibo e um comprovante operacional simples emitido pelo AutoFlow.</p>
            </section>
        </article>
    </main>

    <div class="actions">
        <a href="{{ route('wash-orders.show', $washOrder) }}" class="button">Voltar para lavagem</a>
        <button type="button" onclick="window.print()" class="button primary">Imprimir recibo</button>
    </div>
</body>
</html>
