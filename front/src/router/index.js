import { createRouter, createWebHistory } from 'vue-router'
import api from '../services/api'
import { useGameStore } from '../stores/gameStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'welcome',
      component: () => import('../views/WelcomeView.vue'),
    },
    {
      path: '/setup',
      name: 'setup',
      component: () => import('../views/SetupWizardView.vue'),
    },
    { path: '/lobby', redirect: '/setup' },
    {
      path: '/game/:id',
      name: 'game',
      component: () => import('../views/GameView.vue'),
      props: true,
    },
    {
      path: '/results/:id',
      name: 'results',
      component: () => import('../views/ResultsView.vue'),
      props: true,
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  if (to.name === 'game' || to.name === 'results') {
    const sessionId = to.params.id
    try {
      const { data } = await api.getSessionState(sessionId)
      const gameStore = useGameStore()
      gameStore.applyState(data)

      if (to.name === 'game' && data.status === 'finished') {
        next({ name: 'results', params: { id: sessionId } })
        return
      }

      if (to.name === 'game') {
        if (!gameStore.tryRestorePendingForSession(sessionId)) {
          gameStore.screen = 'waiting'
          gameStore.isTurnActive = false
        }
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
