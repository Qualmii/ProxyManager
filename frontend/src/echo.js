import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY || 'proxy-manager-key',
  wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
  wsPort: import.meta.env.VITE_REVERB_PORT || 6001,
  wssPort: import.meta.env.VITE_REVERB_PORT || 6001,
  forceTLS: false,
  enabledTransports: ['ws'],
  disableStats: true,
})

export default echo

