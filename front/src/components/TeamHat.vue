<template>
  <img
    class="team-hat"
    :class="sizeClass"
    :src="hat.src"
    :alt="hat.label"
    :title="hat.label"
    draggable="false"
  />
</template>

<script setup>
import { computed } from 'vue'
import { getHat } from '../constants/hats'

const props = defineProps({
  hatId: { type: String, default: 'tophat' },
  size: { type: String, default: 'md' }, // sm | md | lg | hero
})

const hat = computed(() => getHat(props.hatId))
const sizeClass = computed(() => `team-hat--${props.size}`)
</script>

<style scoped>
.team-hat {
  display: block;
  object-fit: contain;
  user-select: none;
  border-radius: 18%;
  background: color-mix(in srgb, var(--bg-elevated) 70%, transparent);
  box-shadow: var(--shadow);
}

.team-hat--sm {
  width: 44px;
  height: 44px;
}

.team-hat--md {
  width: 72px;
  height: 72px;
}

.team-hat--lg {
  width: 110px;
  height: 110px;
}

.team-hat--hero {
  width: min(42vw, 180px);
  height: min(42vw, 180px);
  filter: drop-shadow(0 12px 28px color-mix(in srgb, var(--bg-deep) 45%, transparent));
}
</style>
