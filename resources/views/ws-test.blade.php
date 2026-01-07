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
    pre { margin-top: 18px; background:#111; color:#0f0; padding:12px; height:360px; overflow:auto; }
    .hint { color:#555; margin-top:6px; font-size: 13px; }
    .row { margin-top: 12px; }
  </style>
</head>
<body>
  <h2>Reverb Private Channel Test (Chat + User Channels)</h2>

  <div class="row">
    <label>Bearer token (ONLY token, no "Bearer"):</label><br/>
    <input id="token" />
  </div>

  <div class="row">
    <label>Chat ID:</label><br/>
    <input id="chatId" />
  </div>

  <div class="row">
    <label>User ID (receiver):</label><br/>
    <input id="userId" />
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
  <button id="clear">Clear Log</button>

  <pre id="log"></pre>

<script>
const logEl = document.getElementById('log');
const log = msg => {
  const line = `[${new Date().toLocaleTimeString()}] ${msg}`;
  logEl.textContent += line + "\n";
  logEl.scrollTop = logEl.scrollHeight;
};

document.getElementById('clear').onclick = () => logEl.textContent = "";

document.getElementById('connect').onclick = () => {
  const token = document.getElementById('token').value.trim();
  const chatId = document.getElementById('chatId').value.trim();
  const userId = document.getElementById('userId').value.trim();
  const apiBase = document.getElementById('apiBase').value.trim();
  const key = document.getElementById('reverbKey').value.trim();
  const wsHost = document.getElementById('wsHost').value.trim();
  const wsPort = parseInt(document.getElementById('wsPort').value.trim());

  window.Pusher = Pusher;
  window.Echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost,
    wsPort,
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: `${apiBase}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      }
    }
  });

  log("Connected to Reverb");

  Echo.private(`chat.${chatId}`)
    .listen('.MessageDelivered', e => log("📩 MessageDelivered " + JSON.stringify(e)))
    .listen('.MessagesSeen', e => log("👁 MessagesSeen " + JSON.stringify(e)))
    .listen('.UserTyping', e => log("⌨ UserTyping " + JSON.stringify(e)));

  Echo.private(`user.${userId}`)
    .listen('.NotificationPushed', e => log("🔔 NotificationPushed " + JSON.stringify(e)))
    .listen('.UserPresenceUpdated', e => log("🟢 Presence " + JSON.stringify(e)));
};
</script>
</body>
</html>
