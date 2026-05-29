<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cartaz - {{ $event->name }}</title>
        <style>
            @page {
                size: A4;
                margin: 0;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #e5e7eb;
                color: #020617;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .toolbar {
                display: flex;
                justify-content: center;
                gap: 12px;
                padding: 18px;
            }

            .toolbar a,
            .toolbar button {
                border: 0;
                border-radius: 18px;
                padding: 12px 18px;
                color: #fff;
                background: #020617;
                cursor: pointer;
                font: inherit;
                font-size: 14px;
                font-weight: 800;
                text-decoration: none;
            }

            .sheet {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto 32px;
                padding: 24mm 20mm;
                background:
                    radial-gradient(circle at 80% 8%, rgba(16, 185, 129, 0.18), transparent 32%),
                    linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                box-shadow: 0 24px 70px rgba(2, 6, 23, 0.18);
            }

            .brand {
                height: 52px;
                width: auto;
            }

            .eyebrow {
                margin: 34mm 0 0;
                color: #047857;
                font-size: 13px;
                font-weight: 900;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            h1 {
                max-width: 150mm;
                margin: 10px 0 0;
                font-size: 54px;
                line-height: 1;
                letter-spacing: 0;
            }

            .description {
                max-width: 138mm;
                margin: 18px 0 0;
                color: #475569;
                font-size: 22px;
                line-height: 1.45;
            }

            .qr-area {
                display: grid;
                grid-template-columns: 74mm 1fr;
                gap: 16mm;
                align-items: center;
                margin-top: 28mm;
                padding: 12mm;
                border: 1px solid #e2e8f0;
                border-radius: 10mm;
                background: #fff;
            }

            .qr img {
                display: block;
                width: 74mm;
                height: 74mm;
            }

            .steps {
                display: grid;
                gap: 10px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .steps li {
                display: flex;
                gap: 10px;
                align-items: flex-start;
                color: #334155;
                font-size: 18px;
                font-weight: 750;
                line-height: 1.35;
            }

            .steps span {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                flex: 0 0 auto;
                border-radius: 999px;
                color: #022c22;
                background: #a7f3d0;
                font-size: 13px;
                font-weight: 900;
            }

            .url {
                margin-top: 12mm;
                color: #64748b;
                font-size: 14px;
                line-height: 1.5;
                word-break: break-all;
            }

            .footer {
                color: #64748b;
                font-size: 13px;
                font-weight: 700;
            }

            @media print {
                body {
                    background: #fff;
                }

                .toolbar {
                    display: none;
                }

                .sheet {
                    margin: 0;
                    box-shadow: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <button type="button" onclick="window.print()">Imprimir ou salvar PDF</button>
            <a href="{{ route('events.qr-code', [$event, 'download' => 1]) }}">Baixar QR Code</a>
        </div>

        <main class="sheet">
            <section>
                <img src="{{ asset('brand/fotx-logo-black.png') }}" class="brand" alt="Fotx">

                <p class="eyebrow">{{ $event->location ?: 'Evento Fotx' }}</p>
                <h1>Encontre suas fotos deste evento.</h1>
                <p class="description">
                    Escaneie o QR Code, envie uma selfie com consentimento e veja as fotos em que você aparece.
                </p>

                <div class="qr-area">
                    <div class="qr">
                        <img src="{{ route('events.qr-code', $event) }}" alt="QR Code do evento {{ $event->name }}">
                    </div>
                    <ol class="steps">
                        <li><span>1</span> Aponte a camera para o QR Code.</li>
                        <li><span>2</span> Abra a galeria do evento.</li>
                        <li><span>3</span> Envie uma selfie para buscar suas fotos.</li>
                        <li><span>4</span> Escolha, pague e baixe online.</li>
                    </ol>
                </div>

                <p class="url">{{ $event->public_qr_url() }}</p>
            </section>

            <footer class="footer">
                Fotos: {{ $event->name }} @if ($event->event_date) - {{ $event->event_date->format('d/m/Y') }} @endif
            </footer>
        </main>
    </body>
</html>
