/**
 * One-shot position for an entry. Never `watchPosition`: a single fix on
 * save keeps the GPS radio off the rest of the time and saves battery.
 * GPS works with no mobile signal, it just takes longer for a cold fix.
 */
export function getPosition({ timeout = 15000, maximumAge = 30000 } = {}) {
    // The browser's own timeout only starts once permission is granted, so an
    // unanswered permission prompt would otherwise leave the save hanging
    const deadline = new Promise((resolve) => setTimeout(() => resolve({ location: null, reason: 'unavailable' }), timeout + 5000));

    return Promise.race([deadline, request(timeout, maximumAge)]);
}

function request(timeout, maximumAge) {
    return new Promise((resolve) => {
        if (!('geolocation' in navigator)) {
            resolve({ location: null, reason: 'unsupported' });

            return;
        }

        navigator.geolocation.getCurrentPosition(
            ({ coords }) =>
                resolve({
                    location: {
                        lat: round(coords.latitude, 7),
                        lng: round(coords.longitude, 7),
                        accuracy: round(coords.accuracy, 2),
                        altitude: coords.altitude === null ? null : round(coords.altitude, 2),
                    },
                    reason: null,
                }),
            (error) =>
                resolve({
                    location: null,
                    reason: error.code === error.PERMISSION_DENIED ? 'denied' : 'unavailable',
                }),
            { enableHighAccuracy: true, timeout, maximumAge },
        );
    });
}

function round(value, places) {
    const factor = 10 ** places;

    return Math.round(value * factor) / factor;
}
