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
import { playTenSecondWarning, playCountdownTick, playTimeUp } from '../services/timerSounds'
import { vibrate, VIBRATE } from '../services/vibrate'

const props = defineProps({
  duration: { type: Number, required: true },
  isActive: { type: Boolean, default: false },
  paused: { type: Boolean, default: false },
})

const emit = defineEmits(['timeout', 'tick'])

const remaining = ref(props.duration)
const warnedAt10 = ref(false)
const lastCountdownSecond = ref(null)

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

  if (props.paused) return

  if (r === 10 && !warnedAt10.value) {
    warnedAt10.value = true
    playTenSecondWarning()
    vibrate(VIBRATE.warn10)
  }

  if (r >= 1 && r <= 5 && lastCountdownSecond.value !== r) {
    lastCountdownSecond.value = r
    playCountdownTick(r)
    vibrate(VIBRATE.tick)
  }
}

const timer = createTimer({
  onTick: handleTick,
  onTimeout: () => {
    vibrate(VIBRATE.timeUp)
    playTimeUp()
    emit('timeout')
  },
})

watch(
  () => props.isActive,
  (active) => {
    if (active) {
      remaining.value = props.duration
      warnedAt10.value = false
      lastCountdownSecond.value = null
      timer.start(props.duration)
      if (props.paused) {
        timer.pause()
      }
    } else {
      timer.stop()
      warnedAt10.value = false
      lastCountdownSecond.value = null
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
