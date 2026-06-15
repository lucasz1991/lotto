<div class="relative h-screen select-none overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-8">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.35),transparent_32%),radial-gradient(circle_at_78%_28%,rgba(250,204,21,0.22),transparent_26%),radial-gradient(circle_at_55%_88%,rgba(14,165,233,0.24),transparent_34%)]"></div>
    <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.08)_1px,transparent_1px)] [background-size:42px_42px]"></div>

    <ul class="lotto-balls pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <li style="--left: 8%; --size: 58px; --delay: 0s; --duration: 15s; --color-a: #2563eb; --color-b: #0f172a; --number: '6';"></li>
        <li style="--left: 19%; --size: 92px; --delay: 2s; --duration: 18s; --color-a: #facc15; --color-b: #f59e0b; --number: '12';"></li>
        <li style="--left: 31%; --size: 64px; --delay: 5s; --duration: 16s; --color-a: #0ea5e9; --color-b: #1d4ed8; --number: '27';"></li>
        <li style="--left: 43%; --size: 112px; --delay: 1s; --duration: 20s; --color-a: #f97316; --color-b: #dc2626; --number: '35';"></li>
        <li style="--left: 59%; --size: 76px; --delay: 6s; --duration: 17s; --color-a: #22c55e; --color-b: #15803d; --number: '44';"></li>
        <li style="--left: 72%; --size: 104px; --delay: 3s; --duration: 19s; --color-a: #60a5fa; --color-b: #1e3a8a; --number: '7';"></li>
        <li style="--left: 86%; --size: 70px; --delay: 8s; --duration: 16s; --color-a: #fde047; --color-b: #eab308; --number: '49';"></li>
        <li style="--left: 94%; --size: 118px; --delay: 4s; --duration: 22s; --color-a: #38bdf8; --color-b: #0369a1; --number: '18';"></li>
    </ul>

    <div class="relative z-10 flex h-full items-center justify-center">

    </div>

    <style>
        .lotto-balls li {
            position: absolute;
            bottom: -150px;
            left: var(--left);
            width: var(--size);
            height: var(--size);
            border-radius: 9999px;
            background:
                radial-gradient(circle at 30% 24%, rgba(255, 255, 255, .88) 0 9%, transparent 10%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, .95) 0 30%, transparent 31%),
                linear-gradient(135deg, var(--color-a), var(--color-b));
            box-shadow: 0 24px 60px rgba(15, 23, 42, .34);
            animation: lotto-float var(--duration) linear infinite;
            animation-delay: var(--delay);
            opacity: .58;
        }

        .lotto-balls li::after {
            content: var(--number);
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -50%);
            color: #0f172a;
            font-size: calc(var(--size) * .27);
            font-weight: 900;
        }

        .lotto-orbit {
            animation: lotto-spin 18s linear infinite;
        }

        .lotto-mini-ball {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid rgba(255, 255, 255, .86);
            box-shadow: 0 26px 60px rgba(15, 23, 42, .35);
            font-size: 1.5rem;
            font-weight: 900;
            animation: lotto-bounce 4.8s ease-in-out infinite;
            animation-delay: var(--move-delay);
        }

        @keyframes lotto-float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            100% {
                transform: translateY(-120vh) rotate(420deg);
            }
        }

        @keyframes lotto-spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes lotto-bounce {
            0%, 100% {
                transform: translateY(0) rotate(-3deg);
            }
            50% {
                transform: translateY(-16px) rotate(4deg);
            }
        }
    </style>
</div>
