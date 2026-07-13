import { createRouter, createWebHistory } from 'vue-router'
import LobbyView from '../views/LobbyView.vue'
import GameView from '../views/GameView.vue'
import ResultsView from '../views/ResultsView.vue'
import api from '../services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'lobby', component: LobbyView },
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
      next({ name: 'lobby' })
    }
    return
  }
  next()
})

export default router
