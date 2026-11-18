import { init } from 'intelliada';

const script = document.currentScript as HTMLScriptElement | null;

if (script?.dataset.intelliadaToken || script?.dataset.intelliadaEndpoint) {
    const client = init({
        token: script.dataset.intelliadaToken,
        endpoint: script.dataset.intelliadaEndpoint,
        domain: script.dataset.intelliadaDomain,
        captureExceptions: script.dataset.intelliadaExceptions === 'true',
        trackNavigation: script.dataset.intelliadaNavigation !== 'false',
    });

    window.Intelliada = client;
}
