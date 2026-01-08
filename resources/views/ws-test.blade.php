<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Reverb Private Channel Test</title>

  <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/echo.iife.js"></script>
  <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

  <style>
    body { font-family: Arial, Helvetica, sans-serif; padding: 24px; }
    label { font-weight: 600; }
    input { width: 720px; padding: 8px; margin-top: 6px; }
    button { padding: 10px 14px; margin-top: 12px; cursor: pointer; }
    pre { margin-top: 18px; background:#111; color:#0f0; padding:12px; height:380px; overflow:auto; }
    .hint { color:#555; margin-top:6px; font-size: 13px; }
    .row { margin-top: 12px; }
  </style>
</head>
<body>
  <h2>Reverb Private Channel Test (Debug)</h2>

  <div class="row">
    <label>Receiver Bearer token (ONLY token):</label><br/>
    <input id="token" placeholder="Paste receiver token here" />
  </div>

  <div class="row">
    <label>Chat ID:</label><br/>
    <input id="chatId" placeholder="chat uuid" />
  </div>

  <div class="row">
    <label>Receiver User ID (must match token user):</label><br/>
    <input id="userId" placeholder="receiver user uuid" />
  </div>

  <div class="row">
    <label>API Base URL:</label><br/>
    <input id="apiBase" value="http://127.0.0.1:8000" />
  </div>

  <div class="row">
    <label>Reverb APP KEY:</label><br/>
    <input id="reverbKey" value="{{ env('REVERB_APP_KEY') }}" />
  </div>

  <div class="row">
    <label>Reverb WS Host:</label><br/>
    <input id="wsHost" value="127.0.0.1" />
  </div>

  <div class="row">
    <label>Reverb WS Port:</label><br/>
    <input id="wsPort" value="8080" />
  </div>

  <button id="connect">Connect</button>
  <button id="disconnect" style="margin-left:10px;">Disconnect</button>
  <button id="clear" style="margin-left:10px;">Clear Log</button>

  <pre id="log"></pre>

<script>
  const logEl = document.getElementById('log');
  const log = (msg) => {
    const line = `[${new Date().toLocaleTimeString()}] ${msg}`;
    logEl.textContent += line + "\n";
    logEl.scrollTop = logEl.scrollHeight;
    console.log(line);
  };
  const safeJson = (obj) => { try { return JSON.stringify(obj); } catch { return String(obj); } };

  let echoInstance = null;

  document.getElementById('clear').onclick = () => logEl.textContent = "";
  document.getElementById('disconnect').onclick = () => {
    if (echoInstance) {
      echoInstance.disconnect();
      echoInstance = null;
      log("🛑 Disconnected");
    } else {
      log("ℹ️ Not connected");
    }
  };

  async function testBroadcastAuth(apiBase, token, channelName, socketId) {
    const url = `${apiBase}/broadcasting/auth`;
    const payload = new URLSearchParams();
    payload.set("channel_name", channelName);
    payload.set("socket_id", socketId);

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Accept": "application/json",
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: payload.toString()
      });

      const txt = await res.text();
      log(`🔐 /broadcasting/auth ${res.status} for ${channelName}: ${txt}`);
    } catch (e) {
      log(`❌ /broadcasting/auth FAILED for ${channelName}: ${e.message}`);
    }
  }

  function bindSubscriptionDebug(channelName) {
    try {
      const p = echoInstance.connector.pusher;
      const ch = p.channel(channelName);
      if (!ch) return log(`❌ Cannot access channel object: ${channelName}`);

      ch.bind("pusher:subscription_succeeded", () => log(`✅ SUBSCRIBED OK: ${channelName}`));
      ch.bind("pusher:subscription_error", (status) => log(`❌ SUBSCRIBE ERROR ${channelName}: ${safeJson(status)}`));
    } catch (e) {
      log("❌ bindSubscriptionDebug error: " + e.message);
    }
  }

  document.getElementById('connect').onclick = () => {
    const token = document.getElementById('token').value.trim();
    const chatId = document.getElementById('chatId').value.trim();
    const userId = document.getElementById('userId').value.trim();
    const apiBase = document.getElementById('apiBase').value.trim().replace(/\/+$/, "");
    const key = document.getElementById('reverbKey').value.trim();
    const wsHost = document.getElementById('wsHost').value.trim();
    const wsPort = parseInt(document.getElementById('wsPort').value.trim(), 10);

    if (!token) return alert("Receiver token required");
    if (!chatId) return alert("Chat ID required");
    if (!userId) return alert("Receiver User ID required");

    if (echoInstance) { try { echoInstance.disconnect(); } catch {} echoInstance = null; }

    window.Pusher = Pusher;

    const EchoCtor = window.Echo; // laravel-echo constructor
    echoInstance = new EchoCtor({
      broadcaster: 'reverb',
      key,
      wsHost,
      wsPort,
      wssPort: wsPort,
      forceTLS: false,
      enabledTransports: ['ws'],
      authEndpoint: `${apiBase}/broadcasting/auth`,
      auth: { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } }
    });

    log("✅ Connected to Reverb (attempted)");

    // Connection logs + auth test once socket_id exists
    try {
      const p = echoInstance.connector.pusher;
      p.connection.bind('connected', async () => {
        log("✅ WS connected");
        const socketId = p.connection.socket_id;
        log("ℹ️ socket_id: " + socketId);

        await testBroadcastAuth(apiBase, token, `private-chat.${chatId}`, socketId);
        await testBroadcastAuth(apiBase, token, `private-user.${userId}`, socketId);
      });
      p.connection.bind('error', (err) => log("❌ WS error: " + safeJson(err)));
      p.connection.bind('disconnected', () => log("🛑 WS disconnected"));
    } catch (e) {
      log("ℹ️ Could not bind WS events: " + e.message);
    }

    // Subscribe channels
    const chatChannel = echoInstance.private(`chat.${chatId}`);
    log(`➡️ Subscribing: private-chat.${chatId}`);

    const userChannel = echoInstance.private(`user.${userId}`);
    log(`➡️ Subscribing: private-user.${userId}`);

    // real subscribe debug
    bindSubscriptionDebug(`private-chat.${chatId}`);
    bindSubscriptionDebug(`private-user.${userId}`);

    // listen events
    chatChannel
      .listen('.MessageDelivered', (e) => log("📩 MessageDelivered: " + safeJson(e)))
      .listen('.MessagesSeen', (e) => log("👁️ MessagesSeen: " + safeJson(e)))
      .listen('.UserTyping', (e) => log("⌨️ UserTyping: " + safeJson(e)));

    userChannel
      .listen('.NotificationPushed', (e) => log("🔔 NotificationPushed: " + safeJson(e)))
      .listen('.UserPresenceUpdated', (e) => log("🟢 UserPresenceUpdated: " + safeJson(e)));

    log("ℹ️ Now send a message from Postman using SENDER token. Events should show here.");
  };
</script>
</body>
</html>
