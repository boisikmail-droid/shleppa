<template>
  <div class="theme-switcher" :class="{ 'theme-switcher--open': open }">
    <button
      type="button"
      class="theme-switcher__toggle"
      :aria-expanded="open"
      aria-controls="theme-switcher-menu"
      @click="open = !open"
    >
      <span class="theme-switcher__swatch" :data-theme-preview="themeStore.themeId" />
      <span class="theme-switcher__label">{{ themeStore.currentTheme.label }}</span>
    </button>

    <div
      v-if="open"
      id="theme-switcher-menu"
      class="theme-switcher__menu"
      role="menu"
    >
      <button
        v-for="theme in themeStore.themes"
        :key="theme.id"
        type="button"
        class="theme-switcher__option"
        :class="{ 'theme-switcher__option--active': theme.id === themeStore.themeId }"
        role="menuitemradio"
        :aria-checked="theme.id === themeStore.themeId"
        @click="select(theme.id)"
      >
        <span class="theme-switcher__swatch" :data-theme-preview="theme.id" />
        <span class="theme-switcher__option-text">
          <span class="theme-switcher__option-name">{{ theme.label }}</span>
          <span class="theme-switcher__option-hint">{{ theme.hint }}</span>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useThemeStore } from '../stores/themeStore'

const themeStore = useThemeStore()
const open = ref(false)

function select(id) {
  themeStore.setTheme(id)
  open.value = false
}

function onDocClick(e) {
  const root = e.target?.closest?.('.theme-switcher')
  if (!root) open.value = false
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>

<style scoped>
.theme-switcher {
  position: fixed;
  top: max(12px, env(safe-area-inset-top));
  right: max(12px, env(safe-area-inset-right));
  z-index: 50;
}

.theme-switcher__toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid var(--theme-switcher-border);
  background: var(--theme-switcher-bg);
  color: var(--text);
  box-shadow: var(--shadow);
  cursor: pointer;
  backdrop-filter: blur(10px);
  font-family: var(--font-heading);
  font-size: 0.72rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.theme-switcher__label {
  max-width: 9ch;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.theme-switcher__swatch {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 1px solid color-mix(in srgb, var(--text) 20%, transparent);
  background: linear-gradient(135deg, #c9a227 0%, #8f1d2c 100%);
}

.theme-switcher__swatch[data-theme-preview='noir'] {
  background: linear-gradient(135deg, #e8c547 0%, #8f1d2c 100%);
}

.theme-switcher__swatch[data-theme-preview='neon'] {
  background: linear-gradient(135deg, #2de2e6 0%, #ff2e90 100%);
}

.theme-switcher__swatch[data-theme-preview='daylight'] {
  background: linear-gradient(135deg, #4a6f74 0%, #c8c4bb 100%);
}

.theme-switcher__swatch[data-theme-preview='fog'] {
  background: linear-gradient(135deg, #2f4558 0%, #a7b0b9 100%);
}

.theme-switcher__swatch[data-theme-preview='grove'] {
  background: linear-gradient(135deg, #8ea882 0%, #a66b3b 100%);
}

.theme-switcher__menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 200px;
  padding: 8px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--theme-switcher-border);
  background: var(--theme-switcher-bg);
  box-shadow: var(--shadow);
  backdrop-filter: blur(12px);
}

.theme-switcher__option {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  border: 1px solid transparent;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text);
  cursor: pointer;
  text-align: left;
}

.theme-switcher__option:hover,
.theme-switcher__option--active {
  border-color: var(--border-strong);
  background: var(--btn-secondary-hover);
}

.theme-switcher__option-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.theme-switcher__option-name {
  font-family: var(--font-heading);
  font-size: 0.8rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.theme-switcher__option-hint {
  font-family: var(--font-body);
  font-size: 0.75rem;
  color: var(--text-muted);
  letter-spacing: 0;
  text-transform: none;
}
</style>
