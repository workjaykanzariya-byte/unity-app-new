<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peers Global Unity | Download the App</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-start: #0a0f1f;
            --bg-end: #101833;
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-border: rgba(255, 255, 255, 0.14);
            --text-primary: #f5f7ff;
            --text-secondary: #c6c9e5;
            --accent: #6be4ff;
            --accent-strong: #4cc4ff;
            --button-primary: #4cc4ff;
            --button-secondary: rgba(255, 255, 255, 0.12);
            --shadow: 0 24px 60px rgba(4, 8, 26, 0.45);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, #1a2247 0%, var(--bg-end) 45%, var(--bg-start) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            padding: 48px 20px 40px;
        }

        .hero-card {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 28px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 40px;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(107, 228, 255, 0.15);
            color: var(--accent);
            font-weight: 600;
        }

        .headline {
            font-size: clamp(2.4rem, 4vw, 3.4rem);
            font-weight: 700;
            margin: 18px 0 10px;
        }

        .subheadline {
            font-size: clamp(1.1rem, 2vw, 1.35rem);
            color: var(--text-secondary);
            margin-bottom: 18px;
        }

        .description {
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(243, 246, 255, 0.78);
            margin-bottom: 28px;
        }

        .cta-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .cta-button {
            flex: 1 1 180px;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cta-button.primary {
            background: var(--button-primary);
            color: #041226;
            box-shadow: 0 12px 24px rgba(76, 196, 255, 0.35);
        }

        .cta-button.secondary {
            background: var(--button-secondary);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .cta-button:hover,
        .cta-button:focus {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(76, 196, 255, 0.35);
        }

        .phone-wrap {
            display: flex;
            justify-content: center;
        }

        .phone {
            width: min(320px, 100%);
            border-radius: 36px;
            padding: 16px;
            background: linear-gradient(180deg, rgba(21, 31, 58, 0.9) 0%, rgba(8, 12, 26, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px rgba(4, 8, 26, 0.65), inset 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
        }

        .phone::after {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: 30px;
            box-shadow: 0 0 40px rgba(107, 228, 255, 0.18);
            pointer-events: none;
        }

        .phone-notch {
            width: 140px;
            height: 20px;
            background: #0a0f1f;
            border-radius: 0 0 16px 16px;
            margin: 0 auto 14px;
            position: relative;
        }

        .phone-notch::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 6px;
            transform: translateX(-50%);
            width: 54px;
            height: 4px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
        }

        .phone-screen {
            background: linear-gradient(160deg, rgba(19, 27, 54, 0.9) 0%, rgba(10, 14, 29, 0.98) 100%);
            border-radius: 26px;
            padding: 18px;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .app-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .app-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(107, 228, 255, 0.2);
            border: 1px solid rgba(107, 228, 255, 0.3);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 13px;
            color: var(--accent);
        }

        .app-title {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .app-subtitle {
            font-size: 0.85rem;
            color: rgba(198, 201, 229, 0.8);
        }

        .feed-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 14px;
            display: grid;
            gap: 8px;
        }

        .feed-line {
            height: 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
        }

        .feed-line.short {
            width: 60%;
        }

        .install-pill {
            margin-top: auto;
            align-self: flex-start;
            padding: 10px 18px;
            border-radius: 999px;
            background: rgba(76, 196, 255, 0.2);
            border: 1px solid rgba(76, 196, 255, 0.35);
            color: var(--accent);
            font-weight: 600;
            font-size: 0.85rem;
        }

        footer {
            max-width: 1100px;
            margin: 28px auto 0;
            text-align: center;
            color: rgba(198, 201, 229, 0.7);
            font-size: 0.9rem;
            display: grid;
            gap: 6px;
        }

        @media (min-width: 900px) {
            .phone {
                animation: float 6s ease-in-out infinite;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .phone {
                animation: none;
            }

            .cta-button {
                transition: none;
            }
        }

        @media (max-width: 900px) {
            .hero-card {
                padding: 28px 22px;
            }

            .hero-grid {
                grid-template-columns: 1fr;
            }

            .phone-wrap {
                order: 2;
            }

            .page {
                padding: 24px 18px 32px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .cta-button {
                width: 100%;
            }
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero-card">
            <div class="hero-grid">
                <div>
                    <span class="badge">DIRECT ACCESS FROM PEERSGLOBAL</span>
                    <h1 class="headline">I’m officially Live &amp; Ready.</h1>
                    <p class="subheadline">Your trusted entrepreneur network — connect, collaborate, and grow.</p>
                    <p class="description">
                        Hi! I’m Peers Global Unity. I’m now available on the App Store and Google Play. Download me to access your circles, referrals, events, and connections — all in one trusted platform.
                    </p>
                    <div class="cta-buttons">
                        <a class="cta-button primary" href="https://play.google.com/store/apps/details?id=com.peers.peersunity&pcampaignid=web_share" target="_blank" rel="noopener noreferrer">Google Play</a>
                        <a class="cta-button secondary" href="https://apps.apple.com/in/app/peers-global-unity/id6739198477" target="_blank" rel="noopener noreferrer">App Store</a>
                    </div>
                </div>
                <div class="phone-wrap">
                    <div class="phone" aria-hidden="true">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="app-header">
                                <div class="app-icon">PGU</div>
                                <div>
                                    <div class="app-title">Peers Global Unity</div>
                                    <div class="app-subtitle">Vyapaar Jagat</div>
                                </div>
                            </div>
                            <div class="feed-card">
                                <div class="feed-line"></div>
                                <div class="feed-line short"></div>
                            </div>
                            <div class="feed-card">
                                <div class="feed-line"></div>
                                <div class="feed-line short"></div>
                            </div>
                            <div class="feed-card">
                                <div class="feed-line"></div>
                                <div class="feed-line short"></div>
                            </div>
                            <div class="install-pill">Install</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <footer>
            <div>© 2026 Peers Global Unity</div>
            <div>You are viewing the official download page.</div>
        </footer>
    </main>
</body>
</html>
