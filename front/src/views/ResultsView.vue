<template>
  <div class="container results">
    <header class="results__header">
      <span class="results__icon">🎩</span>
      <h1 class="page-title">Финал</h1>
      <p class="page-subtitle">Игра окончена</p>
    </header>

    <div v-if="loadError" class="error-msg">{{ loadError }}</div>

    <div
      v-for="(team, i) in teams"
      :key="team.id || i"
      class="card score-card"
      :class="{ 'score-card--winner': i === winnerIndex }"
    >
      <h2>{{ team.name }}</h2>
      <div class="score">{{ team.score }}</div>
      <p>баллов</p>
    </div>

    <section v-if="recap" class="recap">
      <h2 class="section-title">Как играли</h2>

      <div v-if="recap.rounds?.length" class="recap__block">
        <h3 class="recap__h">По раундам</h3>
        <ul class="recap__list">
          <li v-for="r in recap.rounds" :key="r.round">
            <strong>Раунд {{ r.round }}</strong>
            — угадано {{ r.guessed }}, пропуск {{ r.skipped }}
          </li>
        </ul>
      </div>

      <div v-if="recap.players?.length" class="recap__block">
        <h3 class="recap__h">Игроки</h3>
        <ul class="recap__list">
          <li v-for="p in recap.players" :key="p.id" class="recap__player">
            <PlayerAvatar :avatar-id="p.avatar_id || 'm01'" size="sm" />
            <span class="recap__player-text">
              <strong>{{ p.name }}</strong>
              <span class="recap__meta">{{ p.team_name }}</span>
              — {{ p.guessed }} угадано / {{ p.skipped }} пропуск
              <span class="recap__net">({{ p.net >= 0 ? '+' : '' }}{{ p.net }})</span>
            </span>
          </li>
        </ul>
      </div>

      <div v-if="recap.highlights?.length" class="recap__block">
        <h3 class="recap__h">Слова с доски</h3>
        <ul class="recap__list recap__words">
          <li v-for="(h, i) in recap.highlights" :key="i">
            «{{ h.word }}» — {{ h.player }} ({{ h.team }}), р.{{ h.round }}
          </li>
        </ul>
      </div>
    </section>

    <p v-else-if="loadingRecap" class="recap__loading">Загружаем историю…</p>

    <div class="results__actions">
      <button class="button-primary" :disabled="sharing" @click="shareResults">
        {{ shareLabel }}
      </button>
      <button class="button-secondary" @click="newGame">Новая игра</button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useGameStore } from '../stores/gameStore'
import { useSessionStore } from '../stores/sessionStore'
import PlayerAvatar from '../components/PlayerAvatar.vue'
import { buildShareText, shareGameResult } from '../services/share'
import { isRunningInVk } from '../services/vk'

const props = defineProps({ id: { type: [String, Number], default: null } })

const router = useRouter()
const gameStore = useGameStore()
const sessionStore = useSessionStore()

const recap = ref(null)
const loadingRecap = ref(false)
const loadError = ref('')
const sharing = ref(false)
const shareHint = ref('')

const teams = computed(() => {
  if (recap.value?.teams?.length) {
    return [...recap.value.teams].sort((a, b) => b.score - a.score)
  }
  const fromStore = gameStore.teams.length ? gameStore.teams : gameStore.finalScores || []
  return [...fromStore].sort((a, b) => b.score - a.score)
})

const winnerIndex = computed(() => {
  if (!teams.value.length) return 0
  return 0
})

const shareLabel = computed(() => {
  if (sharing.value) return 'Открываем…'
  if (shareHint.value) return shareHint.value
  return isRunningInVk() ? 'Поделиться в VK' : 'Поделиться'
})

async function loadRecap(sessionId) {
  if (!sessionId) return
  loadingRecap.value = true
  loadError.value = ''
  try {
    const { data } = await api.getRecap(sessionId)
    recap.value = data
  } catch (e) {
    loadError.value = e.response?.data?.error || 'Не удалось загрузить историю'
  } finally {
    loadingRecap.value = false
  }
}

function newGame() {
  gameStore.resetGame()
  sessionStore.clearSession()
  router.push('/setup')
}

async function shareResults() {
  sharing.value = true
  shareHint.value = ''
  try {
    const text = buildShareText({
      teams: teams.value,
      highlights: recap.value?.highlights || [],
      rounds: recap.value?.rounds || [],
    })
    const result = await shareGameResult(text)
    if (result.ok && result.mode === 'clipboard') {
      shareHint.value = 'Скопировано'
      setTimeout(() => {
        shareHint.value = ''
      }, 2000)
    }
  } finally {
    sharing.value = false
  }
}

onMounted(() => {
  const sid = props.id || sessionStore.sessionId || gameStore.sessionId
  loadRecap(sid)
})

watch(
  () => props.id,
  (id) => {
    if (id) loadRecap(id)
  }
)
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
  filter: drop-shadow(0 0 20px var(--title-glow));
}

.results__actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 8px;
}

.recap {
  margin: 28px 0 16px;
  text-align: left;
}

.recap__block {
  margin-bottom: 1.25rem;
}

.recap__h {
  margin: 0 0 0.5rem;
  font-family: var(--font-heading);
  font-size: 0.85rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
}

.recap__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.recap__list li {
  padding: 8px 0;
  border-bottom: 1px solid var(--table-line);
  font-size: 0.95rem;
  color: var(--text);
}

.recap__player {
  display: flex;
  align-items: center;
  gap: 10px;
}

.recap__player-text {
  min-width: 0;
}

.recap__meta {
  color: var(--text-muted);
  font-size: 0.85rem;
  margin-left: 4px;
}

.recap__net {
  color: var(--text-muted);
  font-size: 0.85rem;
}

.recap__words li {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.recap__loading {
  text-align: center;
  color: var(--text-muted);
  margin: 16px 0;
}
</style>
