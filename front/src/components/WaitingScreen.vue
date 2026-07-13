<template>
  <div class="container waiting">
    <header class="waiting__header">
      <span class="waiting__icon">{{ roundTitle.icon }}</span>
      <h2 class="waiting__title">{{ roundTitle.text }}</h2>
    </header>

    <div class="card card--highlight">
      <p><strong>Команда</strong> {{ gameStore.currentTeam?.name }}</p>
      <p><strong>Игрок</strong> {{ gameStore.currentPlayer?.name }}</p>

      <div class="stat-row">
        <span class="stat-row__label">Уровень слов</span>
        <DifficultyStars :level="gameStore.teamDifficulty?.currentDifficulty || 1" :title="difficultyHint" />
      </div>
      <p class="stat-row__hint">{{ difficultyHint }}</p>

      <div class="stat-row">
        <span class="stat-row__label">До сброса уровня</span>
        <span class="stat-row__value">{{ cycleText }}</span>
      </div>
      <p class="stat-row__hint">После {{ CYCLE_LENGTH }} угаданных слов цикл уровней начнётся снова с ★</p>
    </div>

    <div class="card" v-if="gameStore.nextPlayers.length">
      <h3>Следующие ходы</h3>
      <ul class="next-players">
        <li v-for="(p, i) in gameStore.nextPlayers" :key="i">
          {{ p.team_name }} — {{ p.player_name }}
        </li>
      </ul>
    </div>

    <button class="button-primary" @click="onReady">Готов</button>
    <p class="waiting__sound-hint">При нажатии «Готов» прозвучит сигнал старта. За 5 секунд до конца — предупреждение.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGameStore } from '../stores/gameStore'
import { getRoundTitle } from '../composables/useRoundTitle'
import { CYCLE_LENGTH, MAX_DIFFICULTY } from '../constants/difficulty'
import DifficultyStars from './DifficultyStars.vue'
import { initAudioOnGesture, playTurnStart } from '../services/timerSounds'

const gameStore = useGameStore()

const roundTitle = computed(() =>
  getRoundTitle(gameStore.status, gameStore.round)
)

const difficultyHint = computed(() => {
  const d = gameStore.teamDifficulty?.currentDifficulty || 1
  return `Сейчас команда объясняет слова ${d}-го уровня (из ${MAX_DIFFICULTY})`
})

const cycleText = computed(() => {
  const guessed = gameStore.teamDifficulty?.wordsGuessedInCycle ?? 0
  const total = gameStore.teamDifficulty?.nextResetAt ?? CYCLE_LENGTH
  return `${guessed} из ${total} угадано`
})

async function onReady() {
  initAudioOnGesture()
  playTurnStart()
  await gameStore.startTurn()
}
</script>

<style scoped>
.waiting__header {
  text-align: center;
  margin-bottom: 24px;
}

.waiting__icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 8px;
  filter: drop-shadow(0 0 16px rgba(201, 162, 39, 0.35));
}

.waiting__title {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: var(--text);
  margin: 0;
}

.card--highlight {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-gold), var(--shadow);
}

.stat-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 14px 0 4px;
  padding-top: 12px;
  border-top: 1px solid rgba(201, 162, 39, 0.1);
}

.stat-row__label {
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.stat-row__value {
  font-family: var(--font-display);
  font-size: 1.2rem;
  color: var(--gold);
}

.stat-row__hint {
  font-size: 0.8rem;
  color: var(--text-dim);
  margin: 0 0 8px;
  line-height: 1.4;
}

.waiting__sound-hint {
  margin-top: 12px;
  font-size: 0.8rem;
  color: var(--text-dim);
  text-align: center;
  line-height: 1.4;
}
</style>
