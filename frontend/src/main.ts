import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createHead } from '@unhead/vue/client'

// Fonts — self-hosted via @fontsource-variable (variable-fonts, font-display: swap)
import '@fontsource-variable/fraunces'
import '@fontsource-variable/geist'
import '@fontsource-variable/geist-mono'

// Global styles — порядок важен: variables → typography → main → bento
import './styles/variables.css'
import './styles/typography.css'
import './styles/main.css'
import './styles/bento.css'

import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(createHead())
app.use(createPinia())
app.use(router)

app.mount('#app')