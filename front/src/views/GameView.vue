<template>
  <div class="game-container container">
    <SyncOverlay :phase="gameStore.syncPhase" />

    <RoundTransition v-if="gameStore.showRoundTransition" :round="gameStore.round" :status="gameStore.status" />

    <WaitingScreen
      v-else-if="gameStore.screen === 'waiting' && gameStore.status !== 'finished'"
    />

    <GameplayScreen
      v-else-if="gameStore.screen === 'gameplay'"
      @guess="handleGuess"
      @skip="handleSkip"
      @timeout="handleTimeout"
    />

    <CorrectionScreen
      v-else-if="gameStore.screen === 'correction'"
    />

    <ResultsView v-else-if="gameStore.screen === 'results' || gameStore.status === 'finished'" />
  </div>
</template>

<script setup>
import { useGameStore } from '../stores/gameStore'
import WaitingScreen from '../components/WaitingScreen.vue'
import GameplayScreen from '../components/GameplayScreen.vue'
import CorrectionScreen from '../components/CorrectionScreen.vue'
import RoundTransition from '../components/RoundTransition.vue'
import ResultsView from '../views/ResultsView.vue'
import SyncOverlay from '../components/SyncOverlay.vue'

defineProps({ id: { type: [String, Number], required: true } })
const gameStore = useGameStore()

function handleGuess() {
  /* handled in GameplayScreen */
}

function handleSkip() {
  /* handled in GameplayScreen */
}

async function handleTimeout() {
  // last-word flow already switches to correction via resolveLastWord()
  if (gameStore.screen === 'correction' || gameStore.screen === 'results' || gameStore.screen === 'waiting') {
    return
  }
  await gameStore.endTurnOrCorrect()
}
</script>
