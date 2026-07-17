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

    <!-- Step 1: pool + shuffle into teams -->
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

      <h3 class="section-title">Список игроков</h3>
      <p class="setup__hint">Сначала всех в общий список — потом перемешаем по командам.</p>
      <div v-for="(_, pi) in playerPool" :key="'p' + pi" class="player-row">
        <input
          v-model="playerPool[pi]"
          type="text"
          placeholder="Имя игрока"
          maxlength="100"
        />
        <button
          v-if="playerPool.length > 1"
          type="button"
          @click="removePoolPlayer(pi)"
        >
          ✕
        </button>
      </div>
      <button
        v-if="playerPool.length < 40"
        type="button"
        class="button-secondary"
        @click="addPoolPlayer"
      >
        + Игрок
      </button>

      <button type="button" class="button-primary setup__shuffle" @click="shuffleIntoTeams">
        Перемешать по командам
      </button>
      <p v-if="!shuffledOnce" class="setup__hint">Можно кликать сколько угодно раз — состав команд будет случайным.</p>

      <template v-if="shuffledOnce">
        <div v-for="(team, ti) in teams" :key="'t' + ti" class="setup__team">
          <h3 class="section-title">Команда {{ ti + 1 }}</h3>
          <label>Название</label>
          <div class="player-row setup__name-row">
            <input v-model="team.name" type="text" placeholder="Название команды" maxlength="100" />
            <button type="button" title="Случайное название" @click="rerollName(ti)">🎲</button>
          </div>

          <label>Шляпа команды</label>
          <div class="setup__hat-current">
            <img :src="getHat(team.hatId).src" :alt="getHat(team.hatId).label" class="setup__hat-preview" />
            <div class="setup__hat-meta">
              <span class="setup__hat-label">{{ getHat(team.hatId).label }}</span>
              <div class="setup__hat-actions">
                <button type="button" class="button-secondary setup__hat-btn" @click="rerollHat(ti)">
                  Случайная
                </button>
                <button type="button" class="button-secondary setup__hat-btn" @click="openHatPicker(ti)">
                  Выбрать…
                </button>
              </div>
            </div>
          </div>

          <label>Игроки после перемешки</label>
          <ul class="setup__roster">
            <li v-for="(name, ni) in team.players" :key="ni">{{ name }}</li>
            <li v-if="!team.players.length" class="setup__roster-empty">пока пусто</li>
          </ul>
        </div>
      </template>

      <button type="button" class="button-primary" :disabled="!shuffledOnce" @click="confirmTeams">
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

      <label class="setup__check setup__check--opt">
        <input v-model="skipPenaltyOn" type="checkbox" />
        <span>
          <strong>Штраф за пропуск слова</strong>
          <em>без галочки пропуск ничего не стоит</em>
        </span>
      </label>

      <div v-if="skipPenaltyOn" class="slider-group setup__penalty">
        <label>Штраф — {{ skipPenalty }} {{ penaltyWord }}</label>
        <input v-model.number="skipPenalty" type="range" min="1" max="5" step="1" />
        <div class="slider-labels">
          <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
        </div>
      </div>

      <label class="setup__check setup__check--opt">
        <input v-model="lastWordCommon" type="checkbox" />
        <span>
          <strong>Последнее слово общее</strong>
          <em>после таймера слово могут угадывать все команды</em>
        </span>
      </label>

      <div class="setup__nav">
        <button type="button" class="button-secondary" @click="step = 1">Назад</button>
        <button type="button" class="button-primary" @click="step = 3">Далее</button>
      </div>
    </section>

    <!-- Step 3: difficulty + categories -->
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

    <div
      v-if="hatPickerTeam !== null"
      class="hat-modal"
      role="dialog"
      aria-modal="true"
      aria-label="Выбор шляпы"
      @click.self="closeHatPicker"
    >
      <div class="hat-modal__sheet">
        <div class="hat-modal__head">
          <h3 class="hat-modal__title">Выберите шляпу</h3>
          <button type="button" class="hat-modal__close" @click="closeHatPicker">✕</button>
        </div>
        <div class="hat-modal__grid">
          <button
            v-for="hat in HATS"
            :key="hat.id"
            type="button"
            class="hat-modal__opt"
            :class="{ 'hat-modal__opt--on': teams[hatPickerTeam]?.hatId === hat.id }"
            @click="chooseHatFromPicker(hat.id)"
          >
            <img :src="hat.src" :alt="hat.label" />
            <span>{{ hat.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
import { pickTeamNames, pickOneTeamName } from '../constants/teamNames'
import { HATS, pickHatIds, pickOneHatId, getHat } from '../constants/hats'

const router = useRouter()
const sessionStore = useSessionStore()
const vkStore = useVkStore()

const step = ref(1)
const stepTitles = [
  'Игроки и команды',
  'Слова и время',
  'Сложность и категории',
]

const teamCount = ref(2)
const teams = ref(makeTeams(2))
const playerPool = ref(['', '', '', ''])
const shuffledOnce = ref(false)
const totalWords = ref(60)
const timeLimit = ref(60)
const skipPenaltyOn = ref(true)
const skipPenalty = ref(2)
const lastWordCommon = ref(true)
const selectedDifficulties = ref(DIFFICULTY_LEVELS.map((l) => l.id))
const selectedCategories = ref(CATEGORIES.map((c) => c.id))
const soundOn = ref(isSoundEnabled())
const loading = ref(false)
const error = ref('')
const hatPickerTeam = ref(null)

const penaltyWord = computed(() => {
  const n = skipPenalty.value
  if (n === 1) return 'балл'
  if (n >= 2 && n <= 4) return 'балла'
  return 'баллов'
})

function makeTeams(n, excludeNames = [], excludeHats = []) {
  const names = pickTeamNames(n, excludeNames)
  const hats = pickHatIds(n, excludeHats)
  return Array.from({ length: n }, (_, i) => ({
    name: names[i],
    hatId: hats[i],
    players: [],
  }))
}

function namedPool() {
  return playerPool.value.map((p) => p.trim()).filter(Boolean)
}

function shuffleArray(arr) {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

function shuffleIntoTeams() {
  error.value = ''
  const names = namedPool()
  if (names.length < teamCount.value) {
    error.value = `Нужно минимум ${teamCount.value} игрока с именами (по одному на команду)`
    return
  }

  const shuffled = shuffleArray(names)
  const buckets = Array.from({ length: teamCount.value }, () => [])
  shuffled.forEach((name, i) => {
    buckets[i % teamCount.value].push(name)
  })

  teams.value = teams.value.map((t, i) => ({
    ...t,
    players: buckets[i] || [],
  }))
  shuffledOnce.value = true
}

function addPoolPlayer() {
  if (playerPool.value.length < 40) {
    playerPool.value.push('')
  }
}

function removePoolPlayer(pi) {
  if (playerPool.value.length > 1) {
    playerPool.value.splice(pi, 1)
  }
}

function rerollName(ti) {
  const exclude = teams.value.map((t, i) => (i === ti ? '' : t.name))
  teams.value[ti].name = pickOneTeamName(exclude)
}

function rerollHat(ti) {
  const exclude = teams.value.map((t, i) => (i === ti ? '' : t.hatId))
  teams.value[ti].hatId = pickOneHatId(exclude)
}

function openHatPicker(ti) {
  hatPickerTeam.value = ti
}

function closeHatPicker() {
  hatPickerTeam.value = null
}

function chooseHatFromPicker(hatId) {
  if (hatPickerTeam.value === null) return
  setTeamHat(hatPickerTeam.value, hatId)
  closeHatPicker()
}

function setTeamHat(ti, hatId) {
  const taken = teams.value.some((t, i) => i !== ti && t.hatId === hatId)
  if (taken) {
    const other = teams.value.findIndex((t, i) => i !== ti && t.hatId === hatId)
    if (other >= 0) {
      teams.value[other].hatId = teams.value[ti].hatId
    }
  }
  teams.value[ti].hatId = hatId
}

function setTeamCount(n) {
  const prev = teams.value
  const kept = []
  for (let i = 0; i < Math.min(prev.length, n); i++) {
    kept.push({
      name: prev[i].name,
      hatId: prev[i].hatId || pickOneHatId(kept.map((t) => t.hatId)),
      players: [],
    })
  }
  if (kept.length < n) {
    const extra = makeTeams(
      n - kept.length,
      kept.map((t) => t.name),
      kept.map((t) => t.hatId)
    )
    kept.push(...extra)
  }
  teamCount.value = n
  teams.value = kept
  shuffledOnce.value = false
}

function confirmTeams() {
  error.value = ''
  if (!shuffledOnce.value) {
    error.value = 'Сначала перемешайте игроков по командам'
    return
  }
  for (const team of teams.value) {
    if (!team.name.trim()) {
      error.value = 'У каждой команды должно быть название'
      return
    }
    if (!team.players.length) {
      error.value = 'В каждой команде нужен хотя бы один игрок'
      return
    }
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
        hat_id: t.hatId || 'tophat',
      })),
      total_words: totalWords.value,
      time_limit: timeLimit.value,
      difficulties: [...selectedDifficulties.value].sort((a, b) => a - b),
      categories: [...selectedCategories.value],
      skip_penalty: skipPenaltyOn.value ? skipPenalty.value : 0,
      last_word_common: lastWordCommon.value,
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
  if (vkStore.displayName && !playerPool.value[0]) {
    playerPool.value[0] = vkStore.displayName
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
  background: var(--hint-bg);
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
  background: var(--hint-bg);
}

.setup__shuffle {
  margin-top: 0.5rem;
}

.setup__team {
  padding: 12px 0 4px;
  border-top: 1px solid var(--border);
}

.setup__roster {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.setup__roster li {
  padding: 6px 10px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 0.9rem;
}

.setup__roster-empty {
  color: var(--text-muted);
  border-style: dashed !important;
}

.setup__hat-current {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
  padding: 10px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.setup__hat-preview {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: var(--radius);
  flex-shrink: 0;
}

.setup__hat-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.setup__hat-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.setup__hat-btn {
  width: auto;
  margin-top: 0;
  padding: 8px 12px;
  font-size: 0.72rem;
}

.setup__hat-label {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 0.8rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
}

.hat-modal {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  flex-direction: column;
  background: var(--bg-deep);
}

.hat-modal__sheet {
  flex: 1;
  min-height: 0;
  width: 100%;
  max-width: 560px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  padding:
    max(12px, env(safe-area-inset-top))
    14px
    max(14px, env(safe-area-inset-bottom));
  background: var(--bg-main);
}

.hat-modal__head {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
  margin-bottom: 4px;
  border-bottom: 1px solid var(--border);
}

.hat-modal__title {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 1rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold);
}

.hat-modal__close {
  border: 1px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  border-radius: var(--radius);
  width: 40px;
  height: 40px;
  cursor: pointer;
  font-size: 1.1rem;
}

.hat-modal__grid {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 12px 2px 8px;
  -webkit-overflow-scrolling: touch;
}

.hat-modal__opt {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 6px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--bg-card);
  color: var(--text-muted);
  cursor: pointer;
}

.hat-modal__opt img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: calc(var(--radius) - 2px);
  display: block;
}

.hat-modal__opt span {
  font-family: var(--font-heading);
  font-size: 0.62rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  text-align: center;
  line-height: 1.2;
}

.hat-modal__opt--on {
  border-color: var(--gold);
  box-shadow: var(--shadow-gold);
  color: var(--gold-bright);
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
  border-bottom: 1px solid var(--table-line);
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

.setup__check--opt {
  border-bottom: none;
  padding: 10px 0 2px;
}

.setup__penalty {
  margin-left: 26px;
  padding-top: 0;
}
</style>
