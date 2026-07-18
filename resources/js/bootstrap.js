import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const privateHostPattern = /^(localhost|127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/;
const configuredReverbHost = import.meta.env.VITE_REVERB_HOST;
const currentHost = window.location.hostname;
const currentScheme = window.location.protocol === 'https:' ? 'https' : 'http';
const shouldUseConfiguredHost = configuredReverbHost
    && ! (privateHostPattern.test(configuredReverbHost) && ! privateHostPattern.test(currentHost));
const reverbScheme = shouldUseConfiguredHost
    ? (import.meta.env.VITE_REVERB_SCHEME ?? currentScheme)
    : currentScheme;
const reverbPort = shouldUseConfiguredHost
    ? (import.meta.env.VITE_REVERB_PORT ?? (reverbScheme === 'https' ? 443 : 80))
    : (reverbScheme === 'https' ? 443 : 80);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: shouldUseConfiguredHost ? configuredReverbHost : currentHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
