<template>
  <div class="container results">
    <header class="results__header">
      <span class="results__icon">🎩</span>
      <h1 class="page-title">Финал</h1>
      <p class="page-subtitle">Игра окончена</p>
    </header>

    <div
      v-for="(team, i) in teams"
      :key="team.id"
      class="card score-card"
      :class="{ 'score-card--winner': i === winnerIndex }"
    >
      <h2>{{ team.name }}</h2>
      <div class="score">{{ team.score }}</div>
      <p>баллов</p>
    </div>

    <button class="button-primary" @click="newGame">Новая игра</button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGameStore } from '../stores/gameStore'
import { useSessionStore } from '../stores/sessionStore'

defineProps({ id: { type: [String, Number], default: null } })

const router = useRouter()
const gameStore = useGameStore()
const sessionStore = useSessionStore()

const teams = computed(() =>
  gameStore.teams.length ? gameStore.teams : gameStore.finalScores || []
)

const winnerIndex = computed(() => {
  if (teams.value.length < 2) return 0
  return teams.value[0].score >= teams.value[1].score ? 0 : 1
})

function newGame() {
  gameStore.resetGame()
  sessionStore.clearSession()
  router.push('/')
}
</script>

<style scoped>
.results__header {
  margin-bottom: 24px;
}

.results__icon {
  display: block;
  text-align: center;
  font-size: 3rem;
  margin-bottom: 8px;
  filter: drop-shadow(0 0 20px rgba(201, 162, 39, 0.4));
}
</style>
