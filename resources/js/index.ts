/**
 * Either a project `token` (events go straight to Intelliada) or a first-party
 * `endpoint` that adds the token server-side, such as the Laravel package's proxy.
 */
export type IntelliadaConfig = {
    token?: string;
    endpoint?: string;
    domain?: string;
    captureExceptions?: boolean;
    /**
     * Count client-side navigations (Inertia, Livewire `wire:navigate`,
     * Turbo, any `history.pushState` router) as pageviews. Defaults to true.
     */
    trackNavigation?: boolean;
};

export type EventProperties = Record<string, string | number | boolean>;

export type IntelliadaClient = {
    trackPageview: () => void;
    trackEvent: (name: string, properties?: EventProperties) => void;
    captureException: (error: unknown) => void;
    linkCauser: (causerId: string, metadata?: Record<string, unknown>) => void;
};

export type IngestPayload = {
    event_type: string;
    domain: string;
    url?: string;
    pathname?: string;
    referrer?: string | null;
    visitor_hash?: string;
    causer_id?: string;
    metadata?: Record<string, unknown>;
};

const STORAGE_KEY = 'intelliada_visitor_hash';

function readVisitorHash(): string | null {
    try {
        return sessionStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function writeVisitorHash(hash: string): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, hash);
    } catch {
        // sessionStorage unavailable — continue without persistence
    }
}

/**
 * Automated browsers (WebDriver, Playwright, Puppeteer, Cypress and the like)
 * are never counted as visitors.
 */
export function isAutomated(): boolean {
    const automation = window as unknown as Record<string, unknown>;

    return (
        navigator.webdriver === true ||
        'Cypress' in automation ||
        '_phantom' in automation ||
        '__nightmare' in automation
    );
}

function withoutHash(url: string): string {
    const hashIndex = url.indexOf('#');

    return hashIndex === -1 ? url : url.slice(0, hashIndex);
}

/**
 * Deferred a tick so routers that update the title or page after pushing
 * history have finished first.
 */
function watchNavigation(client: IntelliadaClient): void {
    const track = (): void => {
        window.setTimeout(() => client.trackPageview(), 0);
    };

    const pushState = history.pushState;

    history.pushState = function (...args: Parameters<History['pushState']>) {
        pushState.apply(this, args);
        track();
    };

    window.addEventListener('popstate', track);
}

function normalizeError(error: unknown): Error {
    if (error instanceof Error) {
        return error;
    }

    return new Error(typeof error === 'string' ? error : 'Unknown error');
}

export function createClient(config: IntelliadaConfig): IntelliadaClient {
    const endpoint = config.endpoint ?? '/api/v1/ingest';
    const domain = config.domain ?? window.location.hostname;

    async function ingest(payload: IngestPayload): Promise<void> {
        const storedHash = readVisitorHash();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(config.token
                        ? { 'X-Intelliada-Token': config.token }
                        : {}),
                },
                body: JSON.stringify({
                    ...payload,
                    domain,
                    ...(storedHash && !payload.visitor_hash
                        ? { visitor_hash: storedHash }
                        : {}),
                }),
                keepalive: true,
            });

            if (response.status === 429) {
                return;
            }

            if (response.ok) {
                const data = (await response.json()) as {
                    visitor_hash?: string;
                };

                if (data.visitor_hash) {
                    writeVisitorHash(data.visitor_hash);
                }
            }
        } catch {
            // Graceful degradation — never crash the host application
        }
    }

    let lastPageviewUrl: string | null = null;

    /**
     * `document.referrer` only describes how the visitor arrived, so after a
     * client-side navigation the previous page is the referrer. Being on
     * the same host, the server treats it as internal and keeps the
     * visit's original source.
     */
    function trackPageview(): void {
        if (isAutomated()) {
            return;
        }

        const url = withoutHash(window.location.href);

        if (url === lastPageviewUrl) {
            return;
        }

        const referrer = lastPageviewUrl ?? (document.referrer || null);
        lastPageviewUrl = url;

        void ingest({
            event_type: 'pageview',
            url: window.location.href,
            pathname: window.location.pathname,
            referrer,
            domain,
        });
    }

    function trackEvent(name: string, properties: EventProperties = {}): void {
        const eventName = name.trim();

        if (eventName === '' || isAutomated()) {
            return;
        }

        void ingest({
            event_type: 'custom_event',
            url: window.location.href,
            pathname: window.location.pathname,
            domain,
            metadata: {
                name: eventName,
                properties,
            },
        });
    }

    function captureException(error: unknown): void {
        const err = normalizeError(error);

        void ingest({
            event_type: 'exception',
            url: window.location.href,
            pathname: window.location.pathname,
            domain,
            metadata: {
                message: err.message,
                class: err.name,
                stack_trace: err.stack?.split('\n') ?? [],
            },
        });
    }

    function linkCauser(
        causerId: string,
        metadata: Record<string, unknown> = {},
    ): void {
        void ingest({
            event_type: 'audit_trail',
            url: window.location.href,
            pathname: window.location.pathname,
            domain,
            causer_id: causerId,
            metadata: {
                action: 'session_linked',
                ...metadata,
            },
        });
    }

    return {
        trackPageview,
        trackEvent,
        captureException,
        linkCauser,
    };
}

export function init(config: IntelliadaConfig): IntelliadaClient {
    const client = createClient(config);

    if (config.captureExceptions) {
        window.addEventListener('error', (event) => {
            client.captureException(event.error ?? event.message);
        });

        window.addEventListener('unhandledrejection', (event) => {
            client.captureException(event.reason);
        });
    }

    if ((document as Document & { prerendering?: boolean }).prerendering) {
        document.addEventListener(
            'prerenderingchange',
            () => client.trackPageview(),
            { once: true },
        );
    } else {
        client.trackPageview();
    }

    if (config.trackNavigation !== false) {
        watchNavigation(client);
    }

    return client;
}

declare global {
    interface Window {
        Intelliada?: IntelliadaClient;
    }
}
