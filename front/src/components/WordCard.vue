<template>
  <div class="word-card" :style="cardStyle">
    <div class="word-card__level">Уровень {{ difficulty }}</div>
    <h1>{{ text }}</h1>
    <DifficultyStars class="word-card__stars" :level="difficulty" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import DifficultyStars from './DifficultyStars.vue'

const props = defineProps({
  text: { type: String, required: true },
  difficulty: { type: Number, default: 1 },
})

const colorMap = {
  1: '#7a8f72',
  2: '#a4ac86',
  3: '#c9a227',
  4: '#c4643a',
  5: '#b84a6f',
  6: '#9b1c31',
  7: '#6b1428',
}

const color = computed(() => colorMap[props.difficulty] || colorMap[1])

const cardStyle = computed(() => ({
  color: color.value,
  borderColor: color.value,
  boxShadow: `0 8px 32px rgba(0,0,0,0.5), 0 0 24px ${color.value}22`,
}))
</script>

<style scoped>
.word-card__level {
  font-family: var(--font-heading);
  font-size: 0.65rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  opacity: 0.7;
  margin-bottom: 12px;
  position: relative;
}

.word-card__stars {
  margin-top: 12px;
  justify-content: center;
}
</style>
