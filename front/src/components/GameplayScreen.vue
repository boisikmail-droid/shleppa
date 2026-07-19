<template>
  <div
    class="gameplay"
    :class="{
      'gameplay--paused': paused,
      'gameplay--timeup': timeUp,
    }"
  >
    <p class="gameplay-subtitle">
      {{ timeUp ? 'Общее слово — могут угадывать все' : `Раунд ${gameStore.round} · ${roundTitle.short}` }}
    </p>

    <div class="gameplay-team-row">
      <PlayerAvatar
        :avatar-id="gameStore.currentPlayer?.avatar_id || 'm01'"
        size="md"
      />
      <TeamHat
        :hat-id="gameStore.currentTeam?.hat_id || 'tophat'"
        size="sm"
      />
      <div class="gameplay-team-row__names">
        <p class="gameplay-hat-caption">{{ gameStore.currentTeam?.name }}</p>
        <p class="gameplay-hat-player">{{ gameStore.currentPlayer?.name }}</p>
      </div>
    </div>

    <div class="gameplay-timer-row">
      <p v-if="timeUp" class="time-up-label">Время вышло</p>
      <Timer
        v-else
        :duration="gameStore.timeLimit"
        :is-active="true"
        :paused="paused"
        @tick="onTick"
        @timeout="onTimeout"
      />
      <button
        v-if="!paused && !timeUp"
        type="button"
        class="button-secondary gameplay-pause-btn"
        @click="paused = true"
      >
        Пауза
      </button>
    </div>

    <WordCard
      v-if="gameStore.currentWord"
      :text="gameStore.currentWord.word_text"
      :difficulty="gameStore.currentWord.difficulty"
    />

    <div v-if="!timeUp" class="action-row">
      <button
        class="button-skip"
        :disabled="locked || paused"
        @click="doAction('skip')"
      >
        Пропуск
      </button>
      <button
        class="button-guess"
        :disabled="locked || paused"
        @click="doAction('guess')"
      >
        Верно
      </button>
    </div>

    <div v-else class="action-row action-row--timeup">
      <button
        class="button-skip"
        :disabled="locked || pickingTeam"
        @click="missLastWord"
      >
        Никто не угадал
      </button>
      <button
        class="button-guess"
        :disabled="locked || pickingTeam"
        @click="pickingTeam = true"
      >
        Угадано
      </button>
    </div>

    <div class="gameplay-stats">
      <div class="stat-pill">
        <span class="stat-pill__value">{{ gameStore.remainingWords }}</span>
        <span class="stat-pill__label">неотгадано</span>
      </div>
      <div class="stat-pill stat-pill--gold">
        <span class="stat-pill__value">{{ gameStore.wordsGuessedThisTurn }}</span>
        <span class="stat-pill__label">за ход</span>
      </div>
      <div
        v-for="t in gameStore.teams"
        :key="t.id"
        class="stat-pill"
        :class="{ 'stat-pill--active': t.id === gameStore.currentTeam?.id }"
      >
        <span class="stat-pill__value">{{ t.score }}</span>
        <span class="stat-pill__label">{{ t.name }}</span>
      </div>
    </div>

    <div v-if="paused" class="pause-overlay" role="dialog" aria-modal="true" aria-label="Пауза">
      <p class="pause-overlay__title">Пауза</p>
      <p class="pause-overlay__hint">Таймер остановлен. Можно отвлечься.</p>
      <p class="pause-overlay__time">{{ pauseTimeLabel }}</p>
      <button type="button" class="button-primary pause-overlay__resume" @click="paused = false">
        Продолжить
      </button>
    </div>

    <div
      v-if="pickingTeam"
      class="award-overlay"
      role="dialog"
      aria-modal="true"
      aria-label="Кому присудить очко"
    >
      <p class="award-overlay__title">Кому очко?</p>
      <p class="award-overlay__hint">
        «{{ gameStore.currentWord?.word_text }}» — выберите команду
      </p>
      <div class="award-overlay__teams">
        <button
          v-for="t in gameStore.teams"
          :key="t.id"
          type="button"
          class="button-secondary award-overlay__team"
          :disabled="locked"
          @click="awardLastWord(t.id)"
        >
          <span class="award-overlay__team-name">{{ t.name }}</span>
          <span class="award-overlay__team-score">{{ t.score }}</span>
        </button>
      </div>
      <button
        type="button"
        class="award-overlay__back"
        :disabled="locked"
        @click="pickingTeam = false"
      >
        Назад
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useGameStore } from '../stores/gameStore'
import { getRoundTitle } from '../composables/useRoundTitle'
import Timer from './Timer.vue'
import WordCard from './WordCard.vue'
import TeamHat from './TeamHat.vue'
import PlayerAvatar from './PlayerAvatar.vue'
import { playGuess, playSkip } from '../services/timerSounds'
import {
  requestWakeLock,
  releaseWakeLock,
  bindWakeLockVisibility,
} from '../services/wakeLock'

const emit = defineEmits(['guess', 'skip', 'timeout'])

const gameStore = useGameStore()
const locked = ref(false)
const paused = ref(false)
const timeUp = ref(false)
const pickingTeam = ref(false)

let unbindWake = null

onMounted(() => {
  requestWakeLock()
  unbindWake = bindWakeLockVisibility(() => !paused.value && !timeUp.value)
})

onUnmounted(() => {
  if (unbindWake) unbindWake()
  releaseWakeLock()
})

const roundTitle = computed(() =>
  getRoundTitle(gameStore.status, gameStore.round)
)

const pauseTimeLabel = computed(() => {
  const r = gameStore.turnTimeRemaining ?? gameStore.timeLimit
  const m = Math.floor(r / 60)
  const s = r % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

function onTick(remaining) {
  gameStore.turnTimeRemaining = remaining
}

function onTimeout() {
  paused.value = false
  gameStore.turnTimeRemaining = 0
  // Общее последнее слово выключено — ход просто заканчивается,
  // текущее слово остаётся в шляпе
  if (!gameStore.currentWord || !gameStore.lastWordCommon) {
    emit('timeout')
    return
  }
  timeUp.value = true
}

async function doAction(action) {
  if (timeUp.value || paused.value || locked.value || !gameStore.currentWord) return

  if (action === 'guess') await playGuess()
  else await playSkip()

  locked.value = true

  try {
    const result = await gameStore.submitAction(gameStore.currentWord.word_id, action)
    emit(action)
    if (result?.finished) {
      emit('timeout')
    }
  } finally {
    setTimeout(() => {
      locked.value = false
    }, 500)
  }
}

async function awardLastWord(teamId) {
  if (locked.value) return
  locked.value = true
  await playGuess()
  try {
    await gameStore.resolveLastWord(teamId)
    pickingTeam.value = false
    emit('timeout')
  } finally {
    locked.value = false
  }
}

async function missLastWord() {
  if (locked.value) return
  locked.value = true
  await playSkip()
  try {
    await gameStore.resolveLastWord(null)
    pickingTeam.value = false
    emit('timeout')
  } finally {
    locked.value = false
  }
}
</script>

<style scoped>
.gameplay {
  position: relative;
}

.gameplay-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid var(--hairline);
}

.stat-pill {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 58px;
  padding: 8px 10px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: var(--radius);
}

.stat-pill--gold {
  border-color: rgba(201, 162, 39, 0.25);
  background: rgba(201, 162, 39, 0.06);
}

.stat-pill--active {
  border-color: rgba(201, 162, 39, 0.45);
  box-shadow: 0 0 12px rgba(201, 162, 39, 0.15);
}

.stat-pill__value {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.1;
  color: var(--text);
}

.stat-pill--gold .stat-pill__value {
  color: var(--gold);
}

.stat-pill__label {
  font-family: var(--font-heading);
  font-size: 0.55rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text-dim);
  margin-top: 2px;
}

.gameplay-subtitle {
  text-align: center;
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--text-dim);
  margin: 4px 0 8px;
}

.gameplay-team-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin: 0 0 8px;
}

.gameplay-team-row__names {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  text-align: left;
}

.gameplay-hat-caption {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--gold);
}

.gameplay-hat-player {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(1.1rem, 4vw, 1.4rem);
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: 0.02em;
  color: var(--text);
}

.gameplay--timeup .gameplay-subtitle {
  color: var(--crimson-bright);
}

.gameplay-timer-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
  min-height: 5.5rem;
  justify-content: center;
}

.time-up-label {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(2.2rem, 8vw, 3.4rem);
  font-weight: 700;
  line-height: 1;
  letter-spacing: 0.03em;
  color: var(--crimson-bright);
  text-align: center;
  text-shadow: 0 0 28px rgba(196, 30, 58, 0.35);
}

.gameplay-pause-btn {
  min-width: 140px;
  padding: 10px 18px;
  font-size: 0.85rem;
}

.action-row--timeup {
  margin-top: 24px;
}

.pause-overlay,
.award-overlay {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
  background:
    radial-gradient(ellipse at 50% 40%, var(--round-glow), transparent 55%),
    var(--overlay-scrim);
  backdrop-filter: blur(6px);
}

.pause-overlay__title,
.award-overlay__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(2.4rem, 11vw, 4rem);
  font-weight: 700;
  line-height: 0.95;
  color: var(--gold-bright);
  letter-spacing: 0.04em;
  text-align: center;
}

.pause-overlay__hint,
.award-overlay__hint {
  margin: 0;
  font-size: 1.05rem;
  color: var(--text-muted);
  text-align: center;
  max-width: 28ch;
}

.pause-overlay__time {
  margin: 4px 0 8px;
  font-family: var(--font-display);
  font-size: 2.4rem;
  font-weight: 700;
  color: var(--text);
}

.pause-overlay__resume {
  width: min(100%, 320px);
  margin-top: 4px;
}

.award-overlay__teams {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: min(100%, 360px);
  margin-top: 8px;
}

.award-overlay__team {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 16px 18px;
  text-align: left;
}

.award-overlay__team-name {
  font-family: var(--font-heading);
  font-size: 1rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.award-overlay__team-score {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gold);
}

.award-overlay__back {
  margin-top: 8px;
  padding: 10px 16px;
  background: transparent;
  border: none;
  color: var(--text-dim);
  font-family: var(--font-heading);
  font-size: 0.85rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  cursor: pointer;
}

.award-overlay__back:disabled {
  opacity: 0.5;
  cursor: default;
}
</style>
