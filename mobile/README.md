# Rasoi mobile

The Android (and later iOS) app is a Capacitor shell around the customer web app. There’s no separate mobile UI code: the phone shows the same Vue screens as the website.

The app opens the server set in the `RASOI_SERVER_URL` repository variable (GitHub → Settings → Secrets and variables → Actions → Variables). If that’s empty, or the server can’t be reached, it shows a **connect screen** that asks for the address and remembers it. An address typed in by hand is used until a build with a different `RASOI_SERVER_URL` is installed.

To host the server, see `deploy/README.md`. The tunnel steps below are for running it on your own computer instead.

## Install the test app on your Android phone

1. On your phone, open the repository’s **Releases** page on GitHub and find **Android test build** (tagged `android-dev`). GitHub Actions publishes a new one every time something under `mobile/` changes.
2. Download the `.apk` file.
3. Open it. Android asks you to allow installs from your browser the first time: allow it, then tap **Install**.
4. New builds install over the old one. You don’t need to uninstall first.

You can also start a build by hand: **Actions → android → Run workflow**.

## Run the server on your computer and open it to the internet

1. In `.env`, set:

   ```
   TRUSTED_PROXIES=*
   ```

   This lets Laravel trust the tunnel’s headers, so pages and assets load over `https`.

2. Start the server. This builds the front end and serves it on port 8000 to your network:

   ```
   composer share
   ```

   Don’t run `composer dev` at the same time: the Vite dev server points the phone at `localhost`, which it can’t reach.

3. In a second terminal, start a tunnel. Cloudflare’s quick tunnel needs no account:

   ```
   # macOS: brew install cloudflared
   # Windows: winget install --id Cloudflare.cloudflared
   # Linux: see https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
   cloudflared tunnel --url http://localhost:8000
   ```

   It prints an address like `https://something-random.trycloudflare.com`. (With ngrok instead: `ngrok http 8000`.)

4. Open the Rasoi app on your phone, enter that address and tap **Open Rasoi**. The app remembers it.

Quick tunnel addresses change every time you restart `cloudflared`. When that happens, reopen the app: it can’t reach the old address, so it shows the connect screen, where you enter the new one.

**Same Wi‑Fi instead of a tunnel:** run `composer share`, find your computer’s local IP address (for example `192.168.1.20`), and enter `http://192.168.1.20:8000` in the app. Your computer’s firewall must allow port 8000.

**Be careful while sharing:** anyone with the tunnel address can reach your local app. Stop the tunnel when you’re done, and set `APP_DEBUG=false` in `.env` if you share the address with anyone else, so error pages don’t show your settings.

The admin panel isn’t reachable through the tunnel: it only answers on `admin.localhost`. Use it on your computer.

## Working on the shell

| What | Command (run in `mobile/`) |
|---|---|
| Install | `npm ci` |
| Copy `www/` into the Android project | `npm run sync` |
| Build a debug APK (needs the Android SDK, for example from Android Studio) | `npm run build:debug` |
| Open in Android Studio | `npx cap open android` |

- App ID: `com.noonlaunch.rasoi`. It can’t change once the app is on Google Play, so confirm it before the first store upload (M9).
- Debug builds are signed with `android/app/debug.keystore`, committed on purpose so every build installs over the last one. SHA-1 `9D:D8:7F:7A:20:0B:78:BF:69:95:F5:68:F3:0B:29:DD:12:05:1A:89`, which is what the Android OAuth client in Google Cloud needs for native Google sign-in (M3b). Release builds will use a separate, secret key.
- Debug builds allow plain `http://` for the same‑Wi‑Fi option; release builds don’t. The connect screen and `allowNavigation: ["*"]` are for testing and get replaced by a fixed server address before release.
