<template>
  <div class="round-transition">
    <div class="icon">{{ icon }}</div>
    <h1>РАУНД {{ roundNum }}</h1>
    <p>{{ subtitle }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  round: { type: Number, default: 1 },
  status: { type: String, default: '' },
})

const roundNum = computed(() => props.round)

const icon = computed(() => {
  if (props.status === 'round2_gestures') return '🎭'
  if (props.status === 'round3_oneword') return '💬'
  return '🎩'
})

const subtitle = computed(() => {
  if (props.status === 'round2_gestures') return 'Жесты'
  if (props.status === 'round3_oneword') return 'Одно слово'
  return 'Словами'
})
</script>
