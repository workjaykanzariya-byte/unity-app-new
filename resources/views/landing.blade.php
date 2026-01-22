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

        .store-button {
            flex: 1 1 240px;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            border-radius: 16px;
            font-weight: 600;
            text-align: left;
            background: linear-gradient(180deg, #0c0c0c 0%, #000000 100%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .store-button .store-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
        }

        .store-button .store-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: #ffffff;
        }

        .store-button .store-label {
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
        }

        .store-button .store-name {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .store-button:hover,
        .store-button:focus {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.45);
        }

        .phone-wrap {
            display: flex;
            justify-content: center;
        }

        .phone {
            width: min(360px, 100%);
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
            min-height: 460px;
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
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: grid;
            place-items: center;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .app-icon svg {
            width: 62px;
            height: 62px;
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
            align-self: center;
            padding: 12px 28px;
            border-radius: 999px;
            background: rgba(76, 196, 255, 0.2);
            border: 1px solid rgba(76, 196, 255, 0.35);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.9rem;
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

            .store-button {
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
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .store-button {
                width: 100%;
                height: 100%;
                padding: 12px 14px;
            }

            .phone-screen {
                min-height: 380px;
                padding: 16px;
                gap: 12px;
            }

            .feed-card {
                padding: 12px;
                gap: 6px;
            }

            .mobile-only {
                display: block;
            }
        }

        @media (max-width: 420px) {
            .cta-buttons {
                grid-template-columns: 1fr;
            }
        }

        .mobile-only {
            display: none;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.75rem;
            color: rgba(245, 247, 255, 0.85);
        }

        .banner-card {
            border-radius: 16px;
            padding: 12px 14px;
            background: linear-gradient(135deg, rgba(76, 196, 255, 0.2), rgba(76, 196, 255, 0.05));
            border: 1px solid rgba(76, 196, 255, 0.25);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .banner-icon {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            background: rgba(76, 196, 255, 0.25);
            display: grid;
            place-items: center;
            color: #d8f3ff;
            font-size: 0.9rem;
        }

        .banner-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .banner-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .banner-subtitle {
            font-size: 0.75rem;
            color: rgba(198, 201, 229, 0.8);
        }

        .trusted-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: rgba(198, 201, 229, 0.75);
        }

        .avatar-stack {
            display: flex;
            align-items: center;
        }

        .avatar {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #0f1732;
            background: linear-gradient(135deg, rgba(107, 228, 255, 0.6), rgba(76, 196, 255, 0.2));
            margin-left: -6px;
        }

        .avatar:first-child {
            margin-left: 0;
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
                        <a class="store-button primary" href="https://play.google.com/store/apps/details?id=com.peers.peersunity&pcampaignid=web_share" target="_blank" rel="noopener noreferrer">
                            <span class="store-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3.7 2.6C2.9 2 1.8 2.6 1.8 3.6v16.8c0 1 1.1 1.6 1.9 1L13.6 12 3.7 2.6z" fill="#00d2ff"/>
                                    <path d="M13.6 12l3.6-3.4 4.1 2.4c1 .6 1 .9 0 1.5l-4.1 2.4L13.6 12z" fill="#ffe000"/>
                                    <path d="M3.7 21.4L13.6 12l3.6 3.4-5.5 3.2c-.7.4-1.6.4-2.3-.1l-5.7-4.1z" fill="#00f076"/>
                                </svg>
                            </span>
                            <span class="store-text">
                                <span class="store-label">Get it on</span>
                                <span class="store-name">Google Play</span>
                            </span>
                        </a>
                        <a class="store-button secondary" href="https://apps.apple.com/in/app/peers-global-unity/id6739198477" target="_blank" rel="noopener noreferrer">
                            <span class="store-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M15.9 5.7c-1 .8-1.7 1.9-1.6 3.1 1.1.1 2.2-.6 3-1.5.7-.9 1.2-2.1 1.1-3.4-1.2.1-2.4.8-3.5 1.8z" fill="#ffffff"/>
                                    <path d="M19.3 16.9c-.6 1.4-1.2 2.7-2.3 2.7-1 0-1.3-.7-2.5-.7-1.3 0-1.7.7-2.7.7-1.1 0-1.9-1.2-2.5-2.6-1.4-2.6-1.5-5.6-.7-7 .6-1 1.7-1.7 2.9-1.7 1.1 0 1.8.7 2.7.7.8 0 1.8-.8 3.1-.7 1 .1 2 .5 2.7 1.4-2.4 1.4-2 5.1-.7 6.2z" fill="#ffffff"/>
                                </svg>
                            </span>
                            <span class="store-text">
                                <span class="store-label">Download on the</span>
                                <span class="store-name">App Store</span>
                            </span>
                        </a>
                    </div>
                </div>
                <div class="phone-wrap">
                    <div class="phone" aria-hidden="true">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="app-header">
                                <div class="app-icon" aria-hidden="true">
                                    <svg viewBox="0 0 96 96" fill="none" aria-hidden="true">
                                        <rect x="6" y="6" width="84" height="84" rx="20" fill="#ffffff"/>
                                        <path d="M24 44.5L48 28l24 16.5v27L48 88 24 71.5v-27z" stroke="#1f6ddc" stroke-width="3" fill="none"/>
                                        <path d="M32 41l16-10 16 10" stroke="#1f6ddc" stroke-width="2.5" fill="none"/>
                                        <circle cx="48" cy="30" r="4" fill="#e53935"/>
                                        <circle cx="24" cy="44.5" r="4" fill="#e53935"/>
                                        <circle cx="72" cy="44.5" r="4" fill="#e53935"/>
                                        <circle cx="48" cy="58" r="7" stroke="#1f6ddc" stroke-width="2.5"/>
                                        <circle cx="32" cy="69" r="4" fill="#e53935"/>
                                        <circle cx="64" cy="69" r="4" fill="#e53935"/>
                                        <text x="45" y="80" text-anchor="end" font-size="12.5" font-weight="700" fill="#e53935" font-family="Arial, sans-serif">Peers</text>
                                        <text x="48" y="80" text-anchor="start" font-size="12.5" font-weight="700" fill="#1f6ddc" font-family="Arial, sans-serif">Global</text>
                                        <text x="48" y="90" text-anchor="middle" font-size="6.5" font-weight="600" fill="#1f6ddc" font-family="Arial, sans-serif">Community of Collaboration</text>
                                    </svg>
                                </div>
                                <div>
                                    <div class="app-title">Peers Global Unity</div>
                                    <div class="app-subtitle">Vyapaar Jagat</div>
                                </div>
                            </div>
                            <div class="mobile-only chips">
                                <span class="chip">Circles</span>
                                <span class="chip">Referrals</span>
                                <span class="chip">Events</span>
                            </div>
                            <div class="mobile-only banner-card">
                                <div class="banner-icon">★</div>
                                <div class="banner-text">
                                    <div class="banner-title">Welcome to Unity</div>
                                    <div class="banner-subtitle">Connect • Collaborate • Grow</div>
                                </div>
                            </div>
                            <div class="mobile-only trusted-row">
                                <span>Trusted by entrepreneurs</span>
                                <div class="avatar-stack" aria-hidden="true">
                                    <span class="avatar"></span>
                                    <span class="avatar"></span>
                                    <span class="avatar"></span>
                                    <span class="avatar"></span>
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
