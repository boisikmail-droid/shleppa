<template>
  <div class="gameplay">
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
          <span class="stat-pill__label">отгадано</span>
        </div>
      </div>
    </header>

    <p class="gameplay-subtitle">{{ roundTitle.text }}</p>

    <Timer
      :duration="gameStore.timeLimit"
      :is-active="true"
      @tick="onTick"
      @timeout="onTimeout"
    />

    <WordCard
      v-if="gameStore.currentWord"
      :text="gameStore.currentWord.word_text"
      :difficulty="gameStore.currentWord.difficulty"
    />

    <div class="action-row">
      <button
        class="button-guess"
        :disabled="locked"
        @click="doAction('guess')"
      >
        Верно
      </button>
      <button
        class="button-skip"
        :disabled="locked"
        @click="doAction('skip')"
      >
        Пропуск
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

const roundTitle = computed(() =>
  getRoundTitle(gameStore.status, gameStore.round)
)

function onTick(remaining) {
  gameStore.turnTimeRemaining = remaining
}

function onTimeout() {
  emit('timeout')
}

async function doAction(action) {
  if (locked.value || !gameStore.currentWord) return

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
</script>

<style scoped>
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
  gap: 8px;
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
</style>
