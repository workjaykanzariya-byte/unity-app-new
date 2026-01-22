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
            padding: 32px 28px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 28px;
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
            letter-spacing: 0.3em;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
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
            margin-top: 8px;
        }

        .cta-row {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cta-button {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.35);
        }

        .cta-button:focus-visible {
            outline: 3px solid rgba(34, 211, 238, 0.6);
            outline-offset: 2px;
        }

        .cta-button.primary {
            background: linear-gradient(120deg, var(--accent), #6366f1);
        }

        .cta-button.secondary {
            background: linear-gradient(120deg, var(--success), #22c55e);
        }

        .cta-button.ghost {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.4);
            color: var(--text-main);
        }

        .cta-button:hover {
            transform: translateY(-2px);
        }

        .cta-note {
            margin: 8px 0 0;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .feature-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            padding: 18px;
            display: grid;
            gap: 10px;
            min-height: 140px;
        }

        .feature-icon {
            font-size: 1.5rem;
        }

        .feature-title {
            font-weight: 600;
            margin: 0;
        }

        .feature-text {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
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
                padding: 48px 52px;
            }

            .cta-row {
                flex-direction: row;
            }

            .cta-button {
                flex: 1;
            }

            .features {
                grid-template-columns: repeat(5, minmax(0, 1fr));
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
                <span class="eyebrow">Download the app</span>
                <h1>Peers Global Unity</h1>
                <p class="subtitle">Your trusted entrepreneur network — connect, collaborate, and grow.</p>
                <p class="message">
                    Hi! I’m Peers Global Unity. I’m now available on the App Store and Google Play.
                    Download me to access your circles, referrals, events, and connections — all in one trusted platform.
                </p>
                <div class="cta-group">
                    <div class="cta-row">
                        <a class="cta-button primary" href="{{ $iosUrl }}" rel="noopener">Download on the App Store</a>
                        <a class="cta-button secondary" href="{{ $androidUrl }}" rel="noopener">Get it on Google Play</a>
                    </div>
                    @if ($deepLinkUrl)
                        <p class="cta-note">Already installed? Open the app.</p>
                        <a class="cta-button ghost" href="{{ $deepLinkUrl }}" rel="noopener">Open App</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="features" aria-label="App features">
            <article class="feature-card">
                <div class="feature-icon">🤝</div>
                <h3 class="feature-title">Circles &amp; Community</h3>
                <p class="feature-text">Build trusted circles, collaborate with peers, and grow together.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">💼</div>
                <h3 class="feature-title">Referrals &amp; Deals</h3>
                <p class="feature-text">Unlock warm introductions and curated opportunities.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">📅</div>
                <h3 class="feature-title">Events &amp; Meetups</h3>
                <p class="feature-text">Stay close with exclusive gatherings and meetups.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">💬</div>
                <h3 class="feature-title">1:1 Messaging</h3>
                <p class="feature-text">Connect instantly with members who matter to you.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">✨</div>
                <h3 class="feature-title">Coins &amp; Recognition</h3>
                <p class="feature-text">Celebrate wins, recognize contributions, and earn trust.</p>
            </article>
        </section>

        <footer>
            <div>© {{ date('Y') }} Peers Global Unity</div>
            <div><a href="mailto:support@peersunity.com">support@peersunity.com</a></div>
            <div>You are viewing the official download page.</div>
        </footer>
    </main>
</body>
</html>
