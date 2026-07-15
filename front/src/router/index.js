import { createRouter, createWebHistory } from 'vue-router'
import WelcomeView from '../views/WelcomeView.vue'
import SetupWizardView from '../views/SetupWizardView.vue'
import GameView from '../views/GameView.vue'
import ResultsView from '../views/ResultsView.vue'
import api from '../services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'welcome', component: WelcomeView },
    { path: '/setup', name: 'setup', component: SetupWizardView },
    { path: '/lobby', redirect: '/setup' },
    { path: '/game/:id', name: 'game', component: GameView, props: true },
    { path: '/results/:id', name: 'results', component: ResultsView, props: true },
  ],
})

router.beforeEach(async (to, from, next) => {
  if (to.name === 'game' || to.name === 'results') {
    const sessionId = to.params.id
    try {
      const { data } = await api.getSessionState(sessionId)
      if (to.name === 'game' && data.status === 'finished') {
        next({ name: 'results', params: { id: sessionId } })
        return
      }
      next()
    } catch {
      next({ name: 'welcome' })
    }
    return
  }
  next()
})

export default router
