export function nowIso() {
    return new Date().toISOString();
}

/**
 * A timestamp strictly later than `previous`, so two edits in the same
 * millisecond still order correctly under last-write-wins.
 */
export function nextTimestamp(previous) {
    const now = Date.now();
    const floor = previous ? Date.parse(previous) + 1 : 0;

    return new Date(Math.max(now, floor)).toISOString();
}
