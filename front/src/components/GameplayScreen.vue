<template>
  <div
    class="gameplay"
    :class="{
      'gameplay--paused': paused,
      'gameplay--timeup': timeUp,
    }"
  >
    <header class="gameplay-header">
      <div class="gameplay-header__round">
        <span class="gameplay-header__icon">{{ roundTitle.icon }}</span>
        <div class="gameplay-header__round-text">
          <span class="gameplay-header__round-label">Раунд {{ gameStore.round }}</span>
          <span class="gameplay-header__round-mode">{{ roundTitle.short }}</span>
        </div>
      </div>
      <div class="gameplay-header__stats">
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
    </header>

    <p class="gameplay-subtitle">
      {{ timeUp ? 'Общее слово — могут угадывать все' : roundTitle.text }}
    </p>

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
        class="button-guess"
        :disabled="locked || paused"
        @click="doAction('guess')"
      >
        Верно
      </button>
      <button
        class="button-skip"
        :disabled="locked || paused"
        @click="doAction('skip')"
      >
        Пропуск
      </button>
    </div>

    <div v-else class="action-row action-row--timeup">
      <button
        class="button-guess"
        :disabled="locked || pickingTeam"
        @click="pickingTeam = true"
      >
        Угадано
      </button>
      <button
        class="button-skip"
        :disabled="locked || pickingTeam"
        @click="missLastWord"
      >
        Никто не угадал
      </button>
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
import { computed, ref } from 'vue'
import { useGameStore } from '../stores/gameStore'
import { getRoundTitle } from '../composables/useRoundTitle'
import Timer from './Timer.vue'
import WordCard from './WordCard.vue'
import { playGuess, playSkip } from '../services/timerSounds'

const emit = defineEmits(['guess', 'skip', 'timeout'])

const gameStore = useGameStore()
const locked = ref(false)
const paused = ref(false)
const timeUp = ref(false)
const pickingTeam = ref(false)

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
  if (!gameStore.currentWord) {
    emit('timeout')
    return
  }
  timeUp.value = true
  gameStore.turnTimeRemaining = 0
}

async function doAction(action) {
  if (timeUp.value || paused.value || locked.value || !gameStore.currentWord) return

  if (action === 'guess') playGuess()
  else playSkip()

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
  playGuess()
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
  playSkip()
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

.gameplay-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  margin-bottom: 12px;
  background: var(--bg-card);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-gold), var(--shadow);
  position: relative;
  overflow: hidden;
}

.gameplay-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  opacity: 0.5;
}

.gameplay-header__round {
  display: flex;
  align-items: center;
  gap: 12px;
}

.gameplay-header__icon {
  font-size: 2rem;
  line-height: 1;
  filter: drop-shadow(0 0 8px rgba(201, 162, 39, 0.4));
}

.gameplay-header__round-text {
  display: flex;
  flex-direction: column;
}

.gameplay-header__round-label {
  font-family: var(--font-heading);
  font-size: 0.65rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--text-dim);
}

.gameplay-header__round-mode {
  font-family: var(--font-heading);
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
}

.gameplay-header__stats {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
  max-width: 60%;
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
  margin: 0 0 8px;
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
    radial-gradient(ellipse at 50% 40%, rgba(201, 162, 39, 0.12), transparent 55%),
    rgba(7, 6, 8, 0.92);
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
