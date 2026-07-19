<template>
  <img
    class="player-avatar"
    :class="[`player-avatar--${size}`]"
    :src="avatar.src"
    :alt="avatar.label"
    decoding="async"
    draggable="false"
  />
</template>

<script setup>
import { computed } from 'vue'
import { getAvatar } from '../constants/avatars'

const props = defineProps({
  avatarId: { type: String, default: 'm01' },
  size: { type: String, default: 'md' }, // sm | md | lg
})

const avatar = computed(() => getAvatar(props.avatarId))
</script>

<style scoped>
.player-avatar {
  display: block;
  object-fit: cover;
  border-radius: var(--radius);
  background: #141418;
  flex-shrink: 0;
}

.player-avatar--sm {
  width: 36px;
  height: 36px;
  border-radius: 6px;
}

.player-avatar--md {
  width: 52px;
  height: 52px;
  border-radius: 8px;
}

.player-avatar--lg {
  width: 88px;
  height: 88px;
  border-radius: 12px;
}
</style>
