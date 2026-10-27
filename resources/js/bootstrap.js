import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey    = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost   = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort   = Number(import.meta.env.VITE_REVERB_PORT || 443);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'https';

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        },
    });

    console.info(`[Echo] Reverb → ${reverbScheme}://${reverbHost}:${reverbPort}`);
} else {
    console.warn('[Echo] VITE_REVERB_APP_KEY not set — WebSocket disabled, HTTP polling only.');
}
