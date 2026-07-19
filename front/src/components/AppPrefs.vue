<template>
  <div class="app-prefs" :class="{ 'app-prefs--open': open }">
    <button
      type="button"
      class="app-prefs__toggle"
      :aria-expanded="open"
      aria-controls="app-prefs-menu"
      title="Звук и вибрация"
      @click="open = !open"
    >
      {{ soundOn ? '♪' : '🔇' }}
    </button>

    <div v-if="open" id="app-prefs-menu" class="app-prefs__menu" role="menu">
      <label class="app-prefs__row">
        <input v-model="soundOn" type="checkbox" @change="onSound" />
        <span>Звук</span>
      </label>
      <label class="app-prefs__row">
        <input v-model="vibrateOn" type="checkbox" @change="onVibrate" />
        <span>Вибрация</span>
      </label>
      <button type="button" class="app-prefs__test" @click="onTest">
        Проверить звук
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import {
  isSoundEnabled,
  setSoundEnabled,
  testSound,
  initAudioOnGesture,
} from '../services/timerSounds'
import { isVibrateEnabled, setVibrateEnabled, vibrate, VIBRATE } from '../services/vibrate'

const open = ref(false)
const soundOn = ref(isSoundEnabled())
const vibrateOn = ref(isVibrateEnabled())

function onSound() {
  setSoundEnabled(soundOn.value)
  if (soundOn.value) initAudioOnGesture()
}

function onVibrate() {
  setVibrateEnabled(vibrateOn.value)
  if (vibrateOn.value) vibrate(VIBRATE.tick)
}

function onTest() {
  initAudioOnGesture()
  testSound()
  if (vibrateOn.value) vibrate(VIBRATE.warn10)
}

function onDocClick(e) {
  if (!e.target?.closest?.('.app-prefs')) open.value = false
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>

<style scoped>
.app-prefs {
  position: relative;
  top: auto;
  right: auto;
  z-index: auto;
  margin-right: 0;
}

.app-prefs__toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  padding: 0;
  border-radius: 999px;
  border: 1px solid var(--theme-switcher-border);
  background: var(--theme-switcher-bg);
  color: var(--text);
  box-shadow: var(--shadow);
  cursor: pointer;
  backdrop-filter: blur(10px);
  font-size: 1.1rem;
  line-height: 1;
}

.app-prefs__menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
  padding: 10px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--theme-switcher-border);
  background: var(--theme-switcher-bg);
  box-shadow: var(--shadow);
  backdrop-filter: blur(12px);
}

.app-prefs__row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 6px;
  font-family: var(--font-heading);
  font-size: 0.8rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text);
  cursor: pointer;
}

.app-prefs__test {
  margin-top: 4px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: transparent;
  color: var(--gold);
  font-family: var(--font-heading);
  font-size: 0.72rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
}
</style>
