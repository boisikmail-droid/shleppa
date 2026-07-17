<template>
  <div class="container waiting">
    <header class="waiting__header">
      <h2 class="waiting__title">{{ roundTitle.text }}</h2>
    </header>

    <div class="card card--highlight">
      <div class="waiting__focus">
        <TeamHat
          class="waiting__hat"
          :hat-id="gameStore.currentTeam?.hat_id || 'tophat'"
          size="lg"
        />
        <p class="waiting__focus-row">
          <span class="waiting__focus-label">Команда</span>
          <span class="waiting__focus-name">{{ gameStore.currentTeam?.name }}</span>
        </p>
        <p class="waiting__focus-row">
          <span class="waiting__focus-label">Игрок</span>
          <span class="waiting__focus-name waiting__focus-name--player">{{ gameStore.currentPlayer?.name }}</span>
        </p>
      </div>

      <div class="stat-row">
        <span class="stat-row__label">В шляпе осталось</span>
        <span class="stat-row__value">{{ gameStore.remainingWords }}</span>
      </div>
      <p class="stat-row__hint">Раунд закончится, когда будут отгаданы все слова</p>

      <div class="stat-row">
        <span class="stat-row__label">Уровень слов</span>
        <DifficultyStars :level="gameStore.teamDifficulty?.currentDifficulty || 1" :title="difficultyHint" />
      </div>
      <p class="stat-row__hint">{{ difficultyHint }}</p>

      <div class="stat-row">
        <span class="stat-row__label">До сброса уровня</span>
        <span class="stat-row__value">{{ cycleText }}</span>
      </div>
      <p class="stat-row__hint">После {{ cycleLength }} угаданных слов цикл уровней начнётся снова</p>
    </div>

    <button class="button-primary" @click="onReady">Готов</button>
    <p class="waiting__sound-hint">При нажатии «Готов» прозвучит сигнал старта. За 5 секунд до конца — предупреждение.</p>

    <div class="card" v-if="gameStore.teams.length">
      <h3>Счёт</h3>
      <ul class="score-list">
        <li v-for="t in gameStore.teams" :key="t.id">
          <span class="score-list__team">
            <TeamHat :hat-id="t.hat_id || 'tophat'" size="sm" />
            <span>{{ t.name }}</span>
          </span>
          <strong>{{ t.score }}</strong>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGameStore } from '../stores/gameStore'
import { getRoundTitle } from '../composables/useRoundTitle'
import { MAX_DIFFICULTY, difficultyLabel } from '../constants/difficulty'
import DifficultyStars from './DifficultyStars.vue'
import TeamHat from './TeamHat.vue'
import { initAudioOnGesture, playTurnStart } from '../services/timerSounds'

const gameStore = useGameStore()

const roundTitle = computed(() =>
  getRoundTitle(gameStore.status, gameStore.round)
)

const difficultyHint = computed(() => {
  const d = gameStore.teamDifficulty?.currentDifficulty || 1
  return `${difficultyLabel(d)} · уровень ${d} из ${MAX_DIFFICULTY}`
})

const cycleLength = computed(
  () => gameStore.teamDifficulty?.nextResetAt ?? 10
)

const cycleText = computed(() => {
  const guessed = gameStore.teamDifficulty?.wordsGuessedInCycle ?? 0
  return `${guessed} из ${cycleLength.value} угадано`
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
  margin-bottom: 16px;
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

.waiting__focus {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  margin-bottom: 4px;
  text-align: center;
}

.waiting__hat {
  margin-bottom: 4px;
}

.waiting__focus-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: 0;
  width: 100%;
}

.waiting__focus-label {
  font-family: var(--font-heading);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--text-dim);
}

.waiting__focus-name {
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 5vw, 2.1rem);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: 0.03em;
  color: var(--gold-bright);
  text-shadow: 0 0 24px var(--title-glow);
}

.waiting__focus-name--player {
  color: var(--text);
  text-shadow: none;
}

.stat-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 14px 0 4px;
  padding-top: 12px;
  border-top: 1px solid var(--hairline);
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
  margin: 12px 0 16px;
  font-size: 0.8rem;
  color: var(--text-dim);
  text-align: center;
  line-height: 1.4;
}

.score-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.score-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid var(--table-line);
  font-size: 0.95rem;
}

.score-list__team {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.score-list li:last-child {
  border-bottom: none;
}

.score-list strong {
  color: var(--gold-bright);
  font-family: var(--font-display);
  font-size: 1.25rem;
}
</style>
