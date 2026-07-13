<template>
  <span class="difficulty-stars" :title="title">
    <span class="difficulty-stars__icons" aria-hidden="true">
      <span
        v-for="n in MAX_DIFFICULTY"
        :key="n"
        class="difficulty-stars__icon"
        :class="{ 'difficulty-stars__icon--active': n <= level }"
      >
        {{ n <= level ? '★' : '☆' }}
      </span>
    </span>
    <span class="difficulty-stars__label">{{ level }} / {{ MAX_DIFFICULTY }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { MAX_DIFFICULTY } from '../constants/difficulty'

const props = defineProps({
  level: { type: Number, default: 1 },
  title: { type: String, default: '' },
})

const level = computed(() =>
  Math.min(Math.max(props.level || 1, 1), MAX_DIFFICULTY)
)
</script>

<style scoped>
.difficulty-stars {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.difficulty-stars__icons {
  display: inline-flex;
  gap: 2px;
  letter-spacing: 0;
  font-size: 1.05rem;
  line-height: 1;
  color: var(--gold);
}

.difficulty-stars__icon {
  opacity: 0.35;
}

.difficulty-stars__icon--active {
  opacity: 1;
}

.difficulty-stars__label {
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  color: var(--text-muted);
  white-space: nowrap;
}
</style>
