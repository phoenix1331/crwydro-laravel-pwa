import { defineConfig, devices } from '@playwright/test';

// Runs in the `e2e` compose service, which shares the app container's network,
// so the app is on localhost: service workers only run on secure origins or localhost.
export default defineConfig({
    testDir: 'tests/e2e',
    timeout: 60_000,
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    outputDir: 'storage/framework/testing/e2e-results',
    use: {
        baseURL: 'http://localhost:8000',
        serviceWorkers: 'allow',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'phone', use: { ...devices['Pixel 7'], permissions: ['geolocation'], geolocation: { latitude: 52.6935, longitude: -3.901, accuracy: 6 } } },
    ],
});
