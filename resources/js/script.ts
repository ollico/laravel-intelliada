import { init } from './index';

const script = document.currentScript as HTMLScriptElement | null;

if (script?.dataset.token || script?.dataset.endpoint) {
    const client = init({
        token: script.dataset.token,
        endpoint: script.dataset.endpoint,
        domain: script.dataset.domain,
        captureExceptions: script.dataset.exceptions === 'true',
        trackNavigation: script.dataset.navigation !== 'false',
    });

    window.Intelliada = client;
}
