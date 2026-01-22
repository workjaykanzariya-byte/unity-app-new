<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peers Global Unity | Download the App</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-start: #0b1220;
            --bg-end: #141a2a;
            --card-bg: rgba(15, 23, 42, 0.9);
            --card-border: rgba(148, 163, 184, 0.2);
            --text-main: #f8fafc;
            --text-muted: #cbd5f5;
            --accent: #4f46e5;
            --accent-2: #22d3ee;
            --success: #10b981;
            --shadow: 0 25px 60px rgba(15, 23, 42, 0.6);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, rgba(79, 70, 229, 0.25), transparent 45%),
                linear-gradient(135deg, var(--bg-start), var(--bg-end));
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px 48px;
        }

        .page {
            width: 100%;
            max-width: 1100px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .hero-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 36px 28px;
            box-shadow: var(--shadow);
            display: grid;
            gap: 32px;
            position: relative;
            overflow: hidden;
        }

        .hero-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(140deg, rgba(34, 211, 238, 0.08), transparent 60%);
            pointer-events: none;
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.25em;
            font-size: 0.7rem;
            color: #93c5fd;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(147, 197, 253, 0.35);
            padding: 8px 14px;
            border-radius: 999px;
            width: fit-content;
        }

        h1 {
            margin: 0;
            font-size: clamp(2.2rem, 5vw, 3.6rem);
            line-height: 1.08;
        }

        .hero-highlight {
            color: #7dd3fc;
        }

        .subtitle {
            font-size: clamp(1.05rem, 2vw, 1.4rem);
            color: var(--text-muted);
            margin: 0;
        }

        .message {
            font-size: 1rem;
            line-height: 1.7;
            margin: 0;
            color: #e2e8f0;
        }

        .cta-group {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }

        .cta-row {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .store-button {
            appearance: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            padding: 14px 18px;
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(15, 23, 42, 0.85);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .store-button:focus-visible {
            outline: 3px solid rgba(34, 211, 238, 0.6);
            outline-offset: 2px;
        }

        .store-button:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .store-badge {
            display: grid;
            gap: 2px;
        }

        .store-eyebrow {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.7);
        }

        .store-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .store-icon {
            width: 30px;
            height: 30px;
        }

        .cta-button {
            appearance: none;
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-main);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: transparent;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            border-color: rgba(148, 163, 184, 0.8);
        }

        .cta-note {
            margin: 8px 0 0;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        .phone-preview {
            width: min(420px, 92vw);
            aspect-ratio: 9 / 19.5;
            border-radius: 28px;
            overflow: hidden;
            margin: 0 auto;
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.45);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.35), rgba(15, 23, 42, 0.6));
            transform: translate3d(0, 0, 0);
            transition: transform 0.2s ease-out;
        }

        .phone-preview__img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            user-select: none;
            pointer-events: none;
        }

        @keyframes floatY {
            0% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-12px) scale(1.01);
            }
            100% {
                transform: translateY(0) scale(1);
            }
        }

        footer {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        footer a {
            color: inherit;
        }

        @media (min-width: 768px) {
            body {
                padding: 48px 40px 64px;
            }

            .hero-card {
                grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
                align-items: center;
                padding: 48px 52px;
            }

            .cta-row {
                flex-direction: row;
            }

            .store-button {
                flex: 1;
            }

        }

        .phone-preview {
            animation: floatY 6s ease-in-out infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .phone-preview {
                animation: none;
                transition: none;
            }
        }

        @media (max-width: 768px) {
            .phone-preview {
                width: 86vw;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
    @php
        $iosUrl = config('app.store_ios_url', '#');
        $androidUrl = config('app.store_android_url', '#');
        $deepLinkUrl = config('app.deep_link_url');
    @endphp

    <main class="page">
        <section class="hero-card">
            <div class="hero-content">
                <span class="eyebrow">Direct access from PeersGlobal</span>
                <h1>I’m officially <span class="hero-highlight">Live &amp; Ready.</span></h1>
                <p class="subtitle">Your trusted entrepreneur network — connect, collaborate, and grow.</p>
                <p class="message">
                    Hi! I’m Peers Global Unity. I’m now available on the App Store and Google Play.
                    Download me to access your circles, referrals, events, and connections — all in one trusted platform.
                </p>
                <div class="cta-group">
                    <div class="cta-row">
                        <a class="store-button" href="{{ $androidUrl }}" rel="noopener">
                            <svg class="store-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#34A853" d="M3.8 2.6l10.2 9.4-10.2 9.4c-.5-.3-.8-.9-.8-1.5V4.1c0-.6.3-1.1.8-1.5z"/>
                                <path fill="#4285F4" d="M14 12l3.2 2.9-4 2.2-4.5-4.1 4.5-4.1 4 2.2L14 12z"/>
                                <path fill="#FBBC04" d="M3.8 2.6l12.2 7.1-3.3 3-8.9-8.2z"/>
                                <path fill="#EA4335" d="M3.8 21.4l8.9-8.2 3.3 3-12.2 7.1z"/>
                            </svg>
                            <span class="store-badge">
                                <span class="store-eyebrow">Get it on</span>
                                <span class="store-title">Google Play</span>
                            </span>
                        </a>
                        <a class="store-button" href="{{ $iosUrl }}" rel="noopener">
                            <svg class="store-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M16.6 13.2c0 2.3 2.1 3.1 2.2 3.1-.1.2-.3.6-.6 1.1-.4.6-.9 1.3-1.6 1.3-.7 0-1-.4-1.8-.4-.8 0-1.1.4-1.9.4-.7 0-1.2-.7-1.6-1.3-.9-1.3-1.6-3.7-.7-5.3.5-.8 1.4-1.4 2.4-1.4.7 0 1.4.4 1.8.4.4 0 1.2-.5 2.1-.4.4 0 1.6.1 2.4 1.2-.1.1-1.5.9-1.5 2.6z"/>
                                <path fill="currentColor" d="M14.8 7.2c.3-.4.5-1.1.4-1.7-.5 0-1.1.3-1.5.7-.3.3-.6 1-.5 1.6.5 0 1.1-.3 1.6-.7z"/>
                            </svg>
                            <span class="store-badge">
                                <span class="store-eyebrow">Download on the</span>
                                <span class="store-title">App Store</span>
                            </span>
                        </a>
                    </div>
                    @if ($deepLinkUrl)
                        <p class="cta-note">Already installed? Open the app.</p>
                        <a class="cta-button" href="{{ $deepLinkUrl }}" rel="noopener">Open App</a>
                    @endif
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="phone-preview">
                    <!-- Primary image URL uses /api/v1/files/{id}; fallback uses /storage/... URL. Motion is disabled for prefers-reduced-motion. -->
                    <img
                        class="phone-preview__img"
                        src="{{ url('/api/v1/files/019be49e-72b7-729a-8e9a-7e267656f7ee') }}"
                        alt="Peers Global Unity app preview"
                        loading="lazy"
                        decoding="async"
                        width="360"
                        height="640"
                        onerror="this.onerror=null;this.src='https://peersunity.com/storage/uploads/2026/01/22/f8384db7-7c38-4621-a890-3e4e87ed4fb0.webp';"
                    >
                </div>
            </div>
        </section>

        <footer>
            <div>© {{ date('Y') }} Peers Global Unity</div>
            <div>You are viewing the official download page.</div>
        </footer>
    </main>
    <script>
        (() => {
            const preview = document.querySelector('.phone-preview');
            if (!preview) return;

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) return;

            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            if (!isMobile) return;
            const maxTilt = 6;
            let targetX = 0;
            let targetY = 0;
            let currentX = 0;
            let currentY = 0;

            const applyTransform = () => {
                currentX += (targetX - currentX) * 0.08;
                currentY += (targetY - currentY) * 0.08;
                preview.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) rotateX(${-(currentY / 6)}deg) rotateY(${currentX / 6}deg)`;
                requestAnimationFrame(applyTransform);
            };

            const setFromScroll = () => {
                const rect = preview.getBoundingClientRect();
                const viewportCenter = window.innerHeight / 2;
                const offset = (rect.top + rect.height / 2 - viewportCenter) / viewportCenter;
                targetY = Math.max(-12, Math.min(12, offset * 12));
                targetX = 0;
            };

            const setFromOrientation = (event) => {
                const gamma = event.gamma ?? 0;
                const beta = event.beta ?? 0;
                targetX = Math.max(-maxTilt, Math.min(maxTilt, gamma)) * 0.6;
                targetY = Math.max(-maxTilt, Math.min(maxTilt, beta)) * 0.6;
            };

            const startOrientation = () => {
                window.addEventListener('deviceorientation', setFromOrientation, true);
            };

            if (isMobile && 'DeviceOrientationEvent' in window && typeof DeviceOrientationEvent.requestPermission === 'function') {
                DeviceOrientationEvent.requestPermission()
                    .then((state) => {
                        if (state === 'granted') {
                            startOrientation();
                        } else {
                            window.addEventListener('scroll', setFromScroll, { passive: true });
                            setFromScroll();
                        }
                    })
                    .catch(() => {
                        window.addEventListener('scroll', setFromScroll, { passive: true });
                        setFromScroll();
                    });
            } else if (isMobile && 'DeviceOrientationEvent' in window) {
                startOrientation();
            } else {
                window.addEventListener('scroll', setFromScroll, { passive: true });
                setFromScroll();
            }

            requestAnimationFrame(applyTransform);
        })();
    </script>
</body>
</html>
