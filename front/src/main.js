import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useVkStore } from './stores/vkStore'
import { useThemeStore } from './stores/themeStore'
import './assets/themes.css'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

const themeStore = useThemeStore(pinia)
themeStore.init()

const vkStore = useVkStore(pinia)

function mount() {
  app.mount('#app')
}

const boot = vkStore.bootstrap()
const timeout = new Promise((resolve) => setTimeout(resolve, 1500))
Promise.race([boot, timeout]).finally(mount)
