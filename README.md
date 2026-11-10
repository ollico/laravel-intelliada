# Intelliada for Laravel

[![Latest Version](https://img.shields.io/github/release/ollico/laravel-intelliada.svg?style=flat-square)](https://github.com/ollico/laravel-intelliada/releases)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Actions Status](https://github.com/ollico/laravel-intelliada/workflows/run-tests/badge.svg)](https://github.com/ollico/laravel-intelliada/actions)

Cookieless, first-party [Intelliada](https://intelliada.com) analytics for Laravel.

The tracking script and its events are served from your own domain, under a path that's random to your app, so ad blockers have no third-party host or common pattern to block. Your project token stays on your server.

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

## How it works

`@intelliada` renders a script tag pointing at `/{path}/main.js` on your domain. The script sends events to `/{path}/e`, and the package forwards them to Intelliada with your token, the visitor's IP address and user agent, and their country if your CDN provides one. The visitor's IP address is used only to compute a daily-rotating visitor hash and is never stored.

These routes sit outside the `web` middleware group, so they use no session or CSRF token, and they're rate-limited per visitor.

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

## License

MIT. See [LICENSE](LICENSE).
