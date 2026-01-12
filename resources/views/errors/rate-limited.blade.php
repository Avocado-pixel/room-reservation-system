<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Too Many Requests - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            padding: 20px;
        }

        .container {
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }

        .error-code {
            color: #ec4899;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 8px;
        }

        h1 {
            color: #0f172a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .countdown-container {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .countdown-label {
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .countdown-item {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            min-width: 80px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .countdown-value {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .countdown-unit {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .tips {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
            text-align: left;
        }

        .tips-title {
            color: #1e40af;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tips ul {
            color: #3b82f6;
            font-size: 13px;
            margin: 0;
            padding-left: 20px;
        }

        .tips li {
            margin-bottom: 4px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
            opacity: 0.5;
            pointer-events: none;
        }

        .btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .footer {
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        /* Breathing animation for icon */
        @keyframes breathe {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .icon {
            animation: breathe 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">🚦</div>
            <div class="error-code">Error 429</div>
            <h1>Too Many Requests</h1>
            <p>You're browsing a bit too fast! Please wait a moment before continuing.</p>
            
            <div class="countdown-container">
                <div class="countdown-label">You can try again in</div>
                <div class="countdown">
                    <div class="countdown-item">
                        <div class="countdown-value" id="seconds">{{ $retryAfter }}</div>
                        <div class="countdown-unit">Seconds</div>
                    </div>
                </div>
            </div>

            <div class="tips">
                <div class="tips-title">💡 Tips to avoid this</div>
                <ul>
                    <li>Avoid refreshing pages too quickly</li>
                    <li>Disable browser extensions that auto-refresh</li>
                    <li>Use bookmarks instead of repeatedly searching</li>
                </ul>
            </div>

            <a href="javascript:location.reload()" class="btn" id="retryBtn">
                Wait {{ $retryAfter }}s...
            </a>
        </div>

        <div class="footer">
            <p>{{ config('app.name') }} • Rate limit protection</p>
        </div>
    </div>

    <script>
        (function() {
            let remaining = {{ $retryAfter }};
            const secondsEl = document.getElementById('seconds');
            const retryBtn = document.getElementById('retryBtn');

            function update() {
                secondsEl.textContent = remaining;
                
                if (remaining <= 0) {
                    retryBtn.textContent = 'Try Again';
                    retryBtn.classList.add('active');
                    clearInterval(interval);
                } else {
                    retryBtn.textContent = `Wait ${remaining}s...`;
                }
            }

            const interval = setInterval(() => {
                remaining--;
                update();
            }, 1000);

            update();
        })();
    </script>
</body>
</html>
