<template>
  <div class="word-card" :style="cardStyle">
    <div class="word-card__level">{{ levelTitle }}</div>
    <h1 class="word-card__text" :style="textStyle">{{ text }}</h1>
    <DifficultyStars class="word-card__stars" :level="difficulty" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import DifficultyStars from './DifficultyStars.vue'
import { difficultyLabel } from '../constants/difficulty'

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
}

const color = computed(() => colorMap[props.difficulty] || colorMap[1])
const levelTitle = computed(() => difficultyLabel(props.difficulty))

/** Самое длинное «слово» без пробелов — от него зависит, влезет ли строка */
const longestToken = computed(() => {
  const parts = props.text.trim().split(/\s+/)
  return parts.reduce((max, p) => Math.max(max, p.length), 0)
})

const textStyle = computed(() => {
  const len = longestToken.value
  const total = props.text.length
  let size = 'clamp(2rem, 9vw, 2.6rem)'
  if (len > 22 || total > 28) size = 'clamp(1rem, 4.2vw, 1.35rem)'
  else if (len > 16 || total > 22) size = 'clamp(1.15rem, 5vw, 1.65rem)'
  else if (len > 12 || total > 16) size = 'clamp(1.35rem, 6.5vw, 2rem)'
  else if (len > 8) size = 'clamp(1.6rem, 7.5vw, 2.3rem)'
  return { fontSize: size }
})

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

.word-card__text {
  max-width: 100%;
  overflow-wrap: anywhere;
  word-break: break-word;
  hyphens: auto;
  line-height: 1.15;
  letter-spacing: 0.02em;
}

.word-card__stars {
  margin-top: 12px;
  justify-content: center;
}
</style>
