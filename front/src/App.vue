<template>
  <div id="app-root">
    <div class="app-chrome">
      <AppPrefs />
      <ThemeSwitcher />
    </div>
    <router-view />
  </div>
</template>

<script setup>
import ThemeSwitcher from './components/ThemeSwitcher.vue'
import AppPrefs from './components/AppPrefs.vue'
</script>

<style scoped>
.app-chrome {
  position: fixed;
  top: max(12px, env(safe-area-inset-top));
  right: max(12px, env(safe-area-inset-right));
  z-index: 50;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
</style>
