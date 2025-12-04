import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import '../css/app.css'
import 'bootstrap-icons/font/bootstrap-icons.css'

// Configurar Laravel Echo manualmente
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// @ts-ignore
window.Pusher = Pusher

// @ts-ignore
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'app-key',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
})

console.log('✅ Echo configurado:', window.Echo)

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.mount('#app')
