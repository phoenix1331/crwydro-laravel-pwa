const BASE_MS = 5_000;
const CAP_MS = 15 * 60_000;

/**
 * Exponential backoff with jitter: 5s, 10s, 20s ... capped at 15 minutes.
 * Jitter stops many queued rows retrying in lockstep when signal returns.
 */
export function backoffDelay(attempts, random = Math.random) {
    const exponential = Math.min(CAP_MS, BASE_MS * 2 ** Math.max(0, attempts - 1));

    return Math.round(exponential * (0.8 + random() * 0.4));
}
