<template>
  <div class="container setup">
    <header class="setup__header">
      <router-link to="/" class="setup__back">← На главную</router-link>
      <h1 class="page-title setup__title">Настройка</h1>
      <nav class="setup__steps" aria-label="Этапы">
        <span
          v-for="n in 3"
          :key="n"
          class="setup__step"
          :class="{
            'setup__step--active': step === n,
            'setup__step--done': step > n,
          }"
        >
          {{ n }}
        </span>
      </nav>
      <p class="page-subtitle">{{ stepTitles[step - 1] }}</p>
    </header>

    <div v-if="error" class="error-msg">{{ error }}</div>

    <!-- Step 1: teams -->
    <section v-if="step === 1" class="setup__panel">
      <label class="setup__label">Количество команд</label>
      <div class="setup__team-count">
        <button
          v-for="n in [2, 3, 4]"
          :key="n"
          type="button"
          class="setup__chip"
          :class="{ 'setup__chip--on': teamCount === n }"
          @click="setTeamCount(n)"
        >
          {{ n }}
        </button>
      </div>

      <div v-for="(team, ti) in teams" :key="ti" class="setup__team">
        <h3 class="section-title">Команда {{ ti + 1 }}</h3>
        <label>Название</label>
        <input v-model="team.name" type="text" :placeholder="defaultTeamName(ti)" maxlength="100" />

        <label>Игроки</label>
        <div v-for="(player, pi) in team.players" :key="pi" class="player-row">
          <input v-model="team.players[pi]" type="text" placeholder="Имя игрока" maxlength="100" />
          <button
            v-if="team.players.length > 1"
            type="button"
            @click="removePlayer(ti, pi)"
          >
            ✕
          </button>
        </div>
        <button
          v-if="team.players.length < 10"
          type="button"
          class="button-secondary"
          @click="addPlayer(ti)"
        >
          + Игрок
        </button>
      </div>

      <button type="button" class="button-primary" @click="confirmTeams">
        Подтвердить состав команд
      </button>
    </section>

    <!-- Step 2: words / time -->
    <section v-else-if="step === 2" class="setup__panel">
      <div class="slider-group">
        <label>Слов в игре — {{ totalWords }}</label>
        <input v-model.number="totalWords" type="range" min="40" max="100" step="5" />
        <div class="slider-labels">
          <span>40</span><span>60</span><span>80</span><span>100</span>
        </div>
      </div>

      <div class="slider-group">
        <label>Время на ход — {{ timeLimit }} сек</label>
        <input v-model.number="timeLimit" type="range" min="20" max="90" step="5" />
        <div class="slider-labels">
          <span>20</span><span>45</span><span>70</span><span>90</span>
        </div>
      </div>

      <div class="setup__nav">
        <button type="button" class="button-secondary" @click="step = 1">Назад</button>
        <button type="button" class="button-primary" @click="step = 3">Далее</button>
      </div>
    </section>

    <!-- Step 3: filters -->
    <section v-else class="setup__panel">
      <h3 class="section-title">Сложность слов</h3>
      <p class="setup__hint">По умолчанию выбрано всё. Снимите лишнее.</p>
      <label
        v-for="level in DIFFICULTY_LEVELS"
        :key="level.id"
        class="setup__check"
      >
        <input v-model="selectedDifficulties" type="checkbox" :value="level.id" />
        <span>
          <strong>{{ level.label }}</strong>
          <em>({{ level.examples }})</em>
        </span>
      </label>

      <h3 class="section-title setup__cat-title">Категории по смыслу</h3>
      <label
        v-for="cat in CATEGORIES"
        :key="cat.id"
        class="setup__check"
      >
        <input v-model="selectedCategories" type="checkbox" :value="cat.id" />
        <span>{{ cat.label }}</span>
      </label>

      <div class="sound-row">
        <button type="button" class="button-secondary sound-row__test" @click="onTestSound">
          Проверить звук
        </button>
        <label class="sound-row__toggle">
          <input v-model="soundOn" type="checkbox" @change="onSoundToggle" />
          Звук таймера
        </label>
      </div>

      <div class="setup__nav">
        <button type="button" class="button-secondary" @click="step = 2">Назад</button>
        <button type="button" class="button-primary" :disabled="loading" @click="startGame">
          {{ loading ? 'Создание...' : 'Начать игру' }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useSessionStore } from '../stores/sessionStore'
import { useVkStore } from '../stores/vkStore'
import {
  testSound,
  isSoundEnabled,
  setSoundEnabled,
} from '../services/timerSounds'
import { DIFFICULTY_LEVELS, CATEGORIES } from '../constants/difficulty'

const router = useRouter()
const sessionStore = useSessionStore()
const vkStore = useVkStore()

const step = ref(1)
const stepTitles = [
  'Состав команд',
  'Слова и время',
  'Сложность и категории',
]

const teamCount = ref(2)
const teams = ref(makeTeams(2))
const totalWords = ref(60)
const timeLimit = ref(60)
const selectedDifficulties = ref(DIFFICULTY_LEVELS.map((l) => l.id))
const selectedCategories = ref(CATEGORIES.map((c) => c.id))
const soundOn = ref(isSoundEnabled())
const loading = ref(false)
const error = ref('')

function defaultTeamName(i) {
  return ['Красные', 'Синие', 'Зелёные', 'Жёлтые'][i] || `Команда ${i + 1}`
}

function makeTeams(n) {
  return Array.from({ length: n }, (_, i) => ({
    name: defaultTeamName(i),
    players: [''],
  }))
}

function setTeamCount(n) {
  const prev = teams.value
  const next = makeTeams(n)
  for (let i = 0; i < Math.min(prev.length, n); i++) {
    next[i] = {
      name: prev[i].name || defaultTeamName(i),
      players: [...prev[i].players],
    }
  }
  teamCount.value = n
  teams.value = next
}

function addPlayer(ti) {
  if (teams.value[ti].players.length < 10) {
    teams.value[ti].players.push('')
  }
}

function removePlayer(ti, pi) {
  if (teams.value[ti].players.length > 1) {
    teams.value[ti].players.splice(pi, 1)
  }
}

function confirmTeams() {
  error.value = ''
  for (const team of teams.value) {
    if (!team.name.trim()) {
      error.value = 'У каждой команды должно быть название'
      return
    }
    const named = team.players.map((p) => p.trim()).filter(Boolean)
    if (named.length < 1) {
      error.value = 'В каждой команде нужен хотя бы один игрок с именем'
      return
    }
    team.players = named
  }
  step.value = 2
}

function onTestSound() {
  testSound()
}

function onSoundToggle() {
  setSoundEnabled(soundOn.value)
}

async function startGame() {
  error.value = ''
  if (!selectedDifficulties.value.length) {
    error.value = 'Выберите хотя бы одну сложность'
    return
  }
  if (!selectedCategories.value.length) {
    error.value = 'Выберите хотя бы одну категорию'
    return
  }

  loading.value = true
  try {
    const { data } = await api.createSession({
      teams: teams.value.map((t) => ({
        name: t.name.trim(),
        players: t.players.map((p) => p.trim()).filter(Boolean),
      })),
      total_words: totalWords.value,
      time_limit: timeLimit.value,
      difficulties: [...selectedDifficulties.value].sort((a, b) => a - b),
      categories: [...selectedCategories.value],
    })
    sessionStore.setSessionId(data.session_id)
    router.push(`/game/${data.session_id}`)
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = Array.isArray(msgs) ? msgs.join('; ') : (e.response?.data?.error || 'Не удалось создать игру')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await vkStore.bootstrap()
  if (vkStore.displayName && teams.value[0]) {
    if (!teams.value[0].players[0]) {
      teams.value[0].players[0] = vkStore.displayName
    }
  }
})
</script>

<style scoped>
.setup__header {
  margin-bottom: 1rem;
}

.setup__back {
  display: inline-block;
  margin-bottom: 0.75rem;
  color: var(--text-muted);
  text-decoration: none;
  font-size: 0.9rem;
}

.setup__back:hover {
  color: var(--gold);
}

.setup__title {
  font-size: 2.2rem;
  margin-bottom: 0.75rem;
}

.setup__steps {
  display: flex;
  gap: 8px;
  margin-bottom: 0.5rem;
}

.setup__step {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: 1px solid var(--border);
  font-family: var(--font-heading);
  font-size: 0.8rem;
  color: var(--text-dim);
}

.setup__step--active {
  border-color: var(--gold);
  color: var(--gold-bright);
  box-shadow: var(--shadow-gold);
}

.setup__step--done {
  background: rgba(201, 162, 39, 0.2);
  border-color: var(--gold-dim);
  color: var(--gold);
}

.setup__panel {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.setup__label {
  margin-bottom: 0;
}

.setup__team-count {
  display: flex;
  gap: 8px;
  margin-bottom: 0.5rem;
}

.setup__chip {
  min-width: 48px;
  padding: 10px 14px;
  font-family: var(--font-heading);
  letter-spacing: 0.06em;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: var(--radius);
  cursor: pointer;
}

.setup__chip--on {
  border-color: var(--gold);
  color: var(--gold-bright);
  background: rgba(201, 162, 39, 0.12);
}

.setup__team {
  padding: 12px 0 4px;
  border-top: 1px solid var(--border);
}

.setup__hint {
  margin: -0.25rem 0 0.5rem;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.setup__check {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  cursor: pointer;
  font-size: 0.95rem;
}

.setup__check input {
  margin-top: 3px;
  accent-color: var(--gold);
}

.setup__check strong {
  font-weight: 600;
}

.setup__check em {
  display: block;
  font-style: normal;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 2px;
}

.setup__cat-title {
  margin-top: 1rem;
}

.setup__nav {
  display: flex;
  gap: 10px;
  margin-top: 0.75rem;
}

.setup__nav .button-primary,
.setup__nav .button-secondary {
  flex: 1;
}

.slider-group {
  padding: 8px 0;
}
</style>
