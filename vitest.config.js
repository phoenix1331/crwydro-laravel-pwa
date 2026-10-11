import { defineConfig } from 'vitest/config';

// Kept separate from vite.config.js so tests skip the Laravel and PWA build plugins
export default defineConfig({
    test: {
        environment: 'node',
        include: ['tests/js/**/*.test.js'],
        setupFiles: ['tests/js/setup.js'],
    },
});
