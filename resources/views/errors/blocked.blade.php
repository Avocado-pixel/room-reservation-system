<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporarily Blocked - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }

        h1 {
            color: #1e1b4b;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .subtitle {
            color: #6366f1;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .timer-container {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .timer-label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .timer {
            font-size: 48px;
            font-weight: 700;
            color: #1e1b4b;
            font-variant-numeric: tabular-nums;
        }

        .timer-unit {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .info-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }

        .info-box-title {
            color: #92400e;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .info-box p {
            color: #a16207;
            font-size: 13px;
            margin-bottom: 0;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .footer {
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Animation for the icon */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .icon {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Progress bar */
        .progress-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 16px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 3px;
            transition: width 1s linear;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">⏳</div>
            <div class="subtitle">Access Temporarily Restricted</div>
            <h1>Slow Down</h1>
            <p>We've detected unusual activity from your connection. This is a temporary security measure to protect our service.</p>
            
            <div class="timer-container">
                <div class="timer-label">Access will be restored in</div>
                <div class="timer" id="countdown">
                    <span id="minutes">{{ $remainingMinutes }}</span><span class="timer-unit"> min</span>
                    <span id="seconds">00</span><span class="timer-unit"> sec</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress" style="width: 0%"></div>
                </div>
            </div>

            <div class="info-box">
                <div class="info-box-title">💡 Why did this happen?</div>
                <p>You've made too many requests in a short period. This could be caused by refreshing pages rapidly, automated scripts, or browser extensions.</p>
            </div>

            <a href="/" class="btn" id="retryBtn" style="pointer-events: none; opacity: 0.5;">
                Please Wait...
            </a>
        </div>

        <div class="footer">
            <p>If you believe this is an error, please contact support.</p>
        </div>
    </div>

    <script>
        (function() {
            const totalSeconds = {{ $remainingMinutes * 60 }};
            let remaining = totalSeconds;
            
            const minutesEl = document.getElementById('minutes');
            const secondsEl = document.getElementById('seconds');
            const progressEl = document.getElementById('progress');
            const retryBtn = document.getElementById('retryBtn');

            function updateDisplay() {
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                
                minutesEl.textContent = mins.toString().padStart(2, '0');
                secondsEl.textContent = secs.toString().padStart(2, '0');
                
                const progressPercent = ((totalSeconds - remaining) / totalSeconds) * 100;
                progressEl.style.width = progressPercent + '%';

                if (remaining <= 0) {
                    retryBtn.style.pointerEvents = 'auto';
                    retryBtn.style.opacity = '1';
                    retryBtn.textContent = 'Try Again';
                    clearInterval(interval);
                }
            }

            const interval = setInterval(() => {
                remaining--;
                updateDisplay();
            }, 1000);

            updateDisplay();
        })();
    </script>
</body>
</html>
