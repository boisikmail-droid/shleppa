<template>
  <div class="container lobby">
    <header class="lobby__header">
      <span class="page-title__icon">🎩</span>
      <h1 class="page-title">Шляпа</h1>
      <p class="page-subtitle">Настройте команды и начните игру</p>
      <div v-if="vkStore.inVk && vkStore.user" class="vk-user">
        <img
          v-if="vkStore.photo"
          :src="vkStore.photo"
          :alt="vkStore.displayName"
          class="vk-user__photo"
        />
        <span class="vk-user__name">{{ vkStore.displayName }}</span>
        <span class="vk-user__hint">party на одном телефоне</span>
      </div>
    </header>

    <div v-if="error" class="error-msg">{{ error }}</div>

    <div class="card">
      <h3 class="section-title">Команда I</h3>
      <label>Название</label>
      <input v-model="team1Name" type="text" placeholder="Красные" />

      <label>Игроки</label>
      <div v-for="(player, i) in team1Players" :key="'t1-' + i" class="player-row">
        <input v-model="team1Players[i]" type="text" placeholder="Имя игрока" />
        <button v-if="team1Players.length > 1" @click="removePlayer(1, i)">✕</button>
      </div>
      <button
        v-if="team1Players.length < 10"
        class="button-secondary"
        @click="addPlayer(1)"
      >
        + Игрок
      </button>
    </div>

    <div class="card">
      <h3 class="section-title">Команда II</h3>
      <label>Название</label>
      <input v-model="team2Name" type="text" placeholder="Синие" />

      <label>Игроки</label>
      <div v-for="(player, i) in team2Players" :key="'t2-' + i" class="player-row">
        <input v-model="team2Players[i]" type="text" placeholder="Имя игрока" />
        <button v-if="team2Players.length > 1" @click="removePlayer(2, i)">✕</button>
      </div>
      <button
        v-if="team2Players.length < 10"
        class="button-secondary"
        @click="addPlayer(2)"
      >
        + Игрок
      </button>
    </div>

    <div class="card slider-group">
      <label>Слов в игре — {{ totalWords }}</label>
      <input v-model.number="totalWords" type="range" min="30" max="150" step="10" />
      <div class="slider-labels">
        <span>30</span><span>60</span><span>90</span><span>120</span><span>150</span>
      </div>
    </div>

    <div class="card slider-group">
      <label>Время на ход — {{ timeLimit }} сек</label>
      <input v-model.number="timeLimit" type="range" min="45" max="80" step="1" />
      <div class="slider-labels">
        <span>45</span><span>60</span><span>75</span><span>80</span>
      </div>
    </div>

    <button class="button-primary" :disabled="loading" @click="startGame">
      {{ loading ? 'Создание...' : 'Начать игру' }}
    </button>

    <div class="sound-row">
      <button type="button" class="button-secondary sound-row__test" @click="onTestSound">
        Проверить звук
      </button>
      <label class="sound-row__toggle">
        <input v-model="soundOn" type="checkbox" @change="onSoundToggle" />
        Звук таймера
      </label>
    </div>
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
  initAudioOnGesture,
} from '../services/timerSounds'

const router = useRouter()
const sessionStore = useSessionStore()
const vkStore = useVkStore()

const team1Name = ref('Красные')
const team1Players = ref(['Алексей', 'Дмитрий'])
const team2Name = ref('Синие')
const team2Players = ref(['Мария', 'Олег'])
const totalWords = ref(60)
const timeLimit = ref(60)
const loading = ref(false)
const error = ref(null)
const soundOn = ref(true)

onMounted(() => {
  soundOn.value = isSoundEnabled()
  // Prefill first player with VK name; others stay manual for party mode
  if (vkStore.displayName) {
    team1Players.value = [vkStore.displayName, '']
  }
})

function onTestSound() {
  initAudioOnGesture()
  testSound()
}

function onSoundToggle() {
  setSoundEnabled(soundOn.value)
}

function addPlayer(team) {
  const list = team === 1 ? team1Players : team2Players
  if (list.value.length < 10) list.value.push('')
}

function removePlayer(team, index) {
  const list = team === 1 ? team1Players : team2Players
  list.value.splice(index, 1)
}

function validate() {
  if (!team1Name.value.trim() || !team2Name.value.trim()) {
    return 'Укажите названия команд'
  }
  const p1 = team1Players.value.map((p) => p.trim()).filter(Boolean)
  const p2 = team2Players.value.map((p) => p.trim()).filter(Boolean)
  if (p1.length < 1 || p2.length < 1) return 'Минимум 1 игрок в каждой команде'
  if (p1.length > 10 || p2.length > 10) return 'Максимум 10 игроков в команде'
  if (totalWords.value % 10 !== 0) return 'Количество слов должно быть кратно 10'
  return null
}

async function startGame() {
  initAudioOnGesture()
  error.value = validate()
  if (error.value) return

  loading.value = true
  try {
    const { data } = await api.createSession({
      team1_name: team1Name.value.trim(),
      team1_players: team1Players.value.map((p) => p.trim()).filter(Boolean),
      team2_name: team2Name.value.trim(),
      team2_players: team2Players.value.map((p) => p.trim()).filter(Boolean),
      total_words: totalWords.value,
      time_limit: timeLimit.value,
    })
    sessionStore.setSessionId(data.session_id)
    router.push(`/game/${data.session_id}`)
  } catch (err) {
    error.value = err.response?.data?.errors?.join(', ') || 'Ошибка создания игры'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.lobby__header {
  margin-bottom: 8px;
}

.vk-user {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
  padding: 8px 12px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.vk-user__photo {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}

.vk-user__name {
  font-size: 0.9rem;
  font-weight: 500;
}

.vk-user__hint {
  margin-left: auto;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.sound-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 12px;
}

.sound-row__test {
  flex: 1;
}

.sound-row__toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  color: var(--text-muted);
  white-space: nowrap;
  cursor: pointer;
}
</style>
