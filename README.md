# Intelliada for Laravel

[![Latest Version](https://img.shields.io/github/release/ollico/laravel-intelliada.svg?style=flat-square)](https://github.com/ollico/laravel-intelliada/releases)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Actions Status](https://github.com/ollico/laravel-intelliada/workflows/run-tests/badge.svg)](https://github.com/ollico/laravel-intelliada/actions)

Cookieless, first-party [Intelliada](https://intelliada.com) analytics for Laravel.

The tracking script and its events are served from your own domain, under a path that's random to your app, so your analytics aren't treated as a third-party tracker. Your project token stays on your server.

## Installation

```bash
composer require intelliada/laravel
php artisan intelliada:install --token=your-project-token
```

The install command:

- publishes `config/intelliada.php`;
- saves `INTELLIADA_TOKEN` and a random `INTELLIADA_PATH` to `.env`;
- adds `@intelliada` to your layout's `<head>`, checking `resources/views/app.blade.php`, `layouts/app.blade.php` and `components/layouts/app.blade.php`.

If your layout lives elsewhere, add the directive yourself:

```blade
<head>
    ...
    @intelliada
</head>
```

## What gets tracked

- A pageview for each page load.
- Client-side navigations in Inertia, Livewire `wire:navigate`, Turbo and other `history.pushState` routers, each with the previous page as its referrer so visits keep their original source.
- Custom events from your JavaScript:

```js
window.Intelliada?.trackEvent('Signup', { plan: 'pro' });
```

No cookies are set and nothing is stored beyond the browser session.

## Without Laravel

The browser client is the [`intelliada`](https://www.npmjs.com/package/intelliada) package. Install it in a single-page app or any other JavaScript app and pass your project token:

```bash
npm install intelliada
```

```js
import { init } from 'intelliada'

const intelliada = init({
    token: 'your-project-token',
})

intelliada.trackEvent('Signup', { plan: 'pro' })
```

Events then go to Intelliada from the browser, so the token is visible there. To keep it on your server, send events to an endpoint you control and pass that as `endpoint`, which is what `@intelliada` does.

## How it works

`@intelliada` renders a script tag pointing at `/{path}/main.js` on your domain. The script sends events to `/{path}/e`, and the package forwards them to Intelliada with your token, the visitor's IP address and user agent, and their country if your CDN provides one. The visitor's IP address is used only to compute a daily-rotating visitor hash and is never stored.

Only a visitor's first event waits for Intelliada, because the script needs the visitor hash it returns. Later events are answered straight away and forwarded after the response is sent, so a slow network doesn't hold up your app.

These routes sit outside the `web` middleware group, so they use no session or CSRF token, and they're rate-limited per visitor.

## Privacy and consent

Serving analytics from your own domain doesn't change your privacy obligations. Whether you need consent depends on what's collected and where your visitors are, not on which domain the requests go to.

What the package does:

- Sets no cookies.
- Keeps the visitor hash in `sessionStorage`, which is cleared when the tab closes.
- Uses the visitor's IP address only to compute a hash with a salt that rotates daily, then discards it.
- Skips automated browsers and known bots.

Some regulators, such as those applying the EU ePrivacy Directive, treat any storage on the visitor's device as needing consent unless it's strictly necessary. If your own assessment says you need consent, only render `@intelliada` for visitors who have given it, and list Intelliada in your privacy policy.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `INTELLIADA_TOKEN` | – | Your project token. Tracking is off until it's set. |
| `INTELLIADA_ENABLED` | `true` | Set to `false` to switch tracking off for an environment, such as local. |
| `INTELLIADA_PATH` | `ia` | The first-party path. Set by `intelliada:install`. |
| `INTELLIADA_TIMEOUT` | `2` | Seconds to wait when forwarding an event. |

The route middleware and the CDN country headers (`CF-IPCountry`, `CloudFront-Viewer-Country`, `X-Vercel-IP-Country`, `X-Country-Code`) can be changed in `config/intelliada.php`.

### Behind a load balancer or CDN

Visitors are identified by `$request->ip()`, so make sure Laravel's [trusted proxies](https://laravel.com/docs/requests#configuring-trusted-proxies) are configured. Otherwise every visitor looks like your load balancer.

## Testing

```bash
composer test
```

## Developing the tracking script

`resources/js/script.ts` starts the installed `intelliada` package from the script tag's `data-*` attributes. The build bundles that dependency into `resources/dist/intelliada.min.js`, which is what this package serves. Commit that file:

```bash
npm install
npm run build
```

CI fails if the committed build doesn't match the source. The client itself is the [`intelliada`](https://www.npmjs.com/package/intelliada) package.

## License

MIT. See [LICENSE](LICENSE).
