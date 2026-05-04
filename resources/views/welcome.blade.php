<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Rental Manager - Sistema de Gestión de Flota Vehicular</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Instrument Sans', system-ui, sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                padding: 1.5rem;
            }
            .container {
                text-align: center;
                padding: 2rem;
                max-width: 600px;
                width: 100%;
            }
            h1 {
                font-size: clamp(2rem, 8vw, 3.5rem);
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(90deg, #22c55e, #3b82f6);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .subtitle {
                font-size: clamp(0.875rem, 3vw, 1.125rem);
                color: #94a3b8;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                background: #22c55e;
                color: #fff;
                padding: 0.875rem 2rem;
                border-radius: 0.75rem;
                text-decoration: none;
                font-weight: 600;
                font-size: 1rem;
                transition: all 0.2s;
                width: 100%;
                max-width: 280px;
            }
            .btn:hover {
                background: #16a34a;
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(34, 197, 94, 0.3);
            }
            .info {
                margin-top: 3rem;
                padding: 1.5rem;
                background: rgba(255,255,255,0.05);
                border-radius: 1rem;
                border: 1px solid rgba(255,255,255,0.1);
            }
            .info p {
                color: #94a3b8;
                font-size: 0.875rem;
                line-height: 1.6;
            }
            .features {
                display: flex;
                gap: 1rem;
                justify-content: center;
                margin-top: 2rem;
                flex-wrap: wrap;
            }
            .feature {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: #cbd5e1;
                font-size: 0.75rem;
            }
            .feature span {
                width: 8px;
                height: 8px;
                background: #22c55e;
                border-radius: 50%;
            }

            @media (min-width: 640px) {
                .btn { width: auto; }
                .feature { font-size: 0.875rem; }
                .features { gap: 2rem; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Rental Manager</h1>
            <p class="subtitle">Sistema de gestión de flota vehicular</p>
            
            <a href="/admin" class="btn">Ir al Panel de Administración</a>
            
            <div class="info">
                <p>Administra tu flota de vehículos, controla ingresos y gastos semanales, manage contratos y más.</p>
                
                <div class="features">
                    <div class="feature"><span></span>Gestión de Flota</div>
                    <div class="feature"><span></span>Control Semanal</div>
                    <div class="feature"><span></span>Contratos</div>
                </div>
            </div>
        </div>
    </body>
</html>