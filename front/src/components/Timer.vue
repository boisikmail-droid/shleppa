<template>
  <div
    class="timer"
    :class="[
      colorClass,
      {
        'timer-critical': remaining <= 10 && isActive && !paused,
        'timer-paused': paused,
      },
    ]"
  >
    {{ formatted }}
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { createTimer } from '../services/timer'
import { playFiveSecondWarning, playTimeUp } from '../services/timerSounds'

const props = defineProps({
  duration: { type: Number, required: true },
  isActive: { type: Boolean, default: false },
  paused: { type: Boolean, default: false },
})

const emit = defineEmits(['timeout', 'tick'])

const remaining = ref(props.duration)
const warnedAt5 = ref(false)

const formatted = computed(() => {
  const m = Math.floor(remaining.value / 60)
  const s = remaining.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

const colorClass = computed(() => {
  if (remaining.value > 20) return 'timer-green'
  if (remaining.value > 10) return 'timer-yellow'
  return 'timer-red'
})

function handleTick(r) {
  remaining.value = r
  emit('tick', r)

  if (r === 5 && !warnedAt5.value && !props.paused) {
    warnedAt5.value = true
    playFiveSecondWarning()
  }
}

const timer = createTimer({
  onTick: handleTick,
  onTimeout: () => {
    playTimeUp()
    emit('timeout')
  },
})

watch(
  () => props.isActive,
  (active) => {
    if (active) {
      remaining.value = props.duration
      warnedAt5.value = false
      timer.start(props.duration)
      if (props.paused) {
        timer.pause()
      }
    } else {
      timer.stop()
      warnedAt5.value = false
    }
  },
  { immediate: true }
)

watch(
  () => props.paused,
  (paused) => {
    if (!props.isActive) return
    if (paused) {
      timer.pause()
    } else {
      timer.resume()
    }
  }
)

onUnmounted(() => timer.stop())
</script>
