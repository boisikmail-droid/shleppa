<template>
  <div class="container correction">
    <header class="correction__header">
      <span class="correction__label">Итоги хода</span>
      <h2 class="correction__player">{{ gameStore.currentPlayer?.name }}</h2>
    </header>

    <div class="card">
      <table class="correction-table">
        <thead>
          <tr>
            <th>Слово</th>
            <th>Статус</th>
            <th>✓</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.word_id">
            <td>{{ item.word_text }}</td>
            <td>
              <span :class="item.status === 'guessed' ? 'status--ok' : 'status--skip'">
                {{ item.status === 'guessed' ? 'Угадано' : 'Пропуск' }}
              </span>
            </td>
            <td>
              <input v-model="item.checked" type="checkbox" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="hint-box">
      <strong>Как исправлять</strong><br />
      «Верно» по ошибке — снимите галочку (0 баллов)<br />
      «Пропуск», но угадали — поставьте галочку (+1)<br />
      «Пропуск» и не угадали — галочку снять ({{ skipHint }})
    </div>

    <button class="button-primary" :disabled="loading" @click="submit">
      {{ loading ? 'Отправка...' : 'Дальше' }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGameStore } from '../stores/gameStore'

const emit = defineEmits(['submit'])
const gameStore = useGameStore()
const loading = ref(false)

const items = ref([])

const skipHint = computed(() =>
  gameStore.skipPenalty > 0 ? `−${gameStore.skipPenalty}` : 'без штрафа'
)

onMounted(() => {
  items.value = gameStore.currentTurnLog.map((log) => ({
    word_id: log.word_id,
    word_text: log.word_text,
    status: log.status,
    checked: log.status === 'guessed',
  }))
})

async function submit() {
  loading.value = true
  try {
    const corrections = items.value.map((item) => ({
      word_id: item.word_id,
      checked: item.checked,
    }))
    emit('submit', corrections)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.correction__header {
  text-align: center;
  margin-bottom: 24px;
}

.correction__label {
  font-family: var(--font-heading);
  font-size: 0.7rem;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--text-dim);
  display: block;
  margin-bottom: 4px;
}

.correction__player {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  margin: 0;
  color: var(--gold);
}

.status--ok {
  color: var(--success-bright);
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.status--skip {
  color: var(--crimson-bright);
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
</style>
