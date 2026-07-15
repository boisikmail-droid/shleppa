<template>
  <div class="game-container container">
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
      @submit="handleFinishTurn"
    />

    <ResultsView v-else-if="gameStore.screen === 'results' || gameStore.status === 'finished'" />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/gameStore'
import WaitingScreen from '../components/WaitingScreen.vue'
import GameplayScreen from '../components/GameplayScreen.vue'
import CorrectionScreen from '../components/CorrectionScreen.vue'
import RoundTransition from '../components/RoundTransition.vue'
import ResultsView from '../views/ResultsView.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })
const gameStore = useGameStore()
const route = useRoute()

let pollInterval = null

onMounted(async () => {
  gameStore.sessionId = Number(props.id)
  await gameStore.fetchGameState(props.id)

  pollInterval = setInterval(async () => {
    if (gameStore.screen === 'waiting' && !gameStore.isTurnActive) {
      try {
        await gameStore.fetchGameState(props.id)
      } catch {
        /* ignore polling errors */
      }
    }
  }, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

function handleGuess() {
  /* handled in GameplayScreen */
}

function handleSkip() {
  /* handled in GameplayScreen */
}

function handleTimeout() {
  // last-word flow already switches to correction via resolveLastWord()
  if (gameStore.screen === 'correction' || gameStore.screen === 'results') {
    return
  }
  gameStore.endTurnLocally()
}

async function handleFinishTurn(corrections) {
  await gameStore.finishTurn(corrections)
}
</script>
