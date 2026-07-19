<template>
  <div id="app-root" :class="{ 'app-root--vk': vkStore.inVk }">
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
import { useVkStore } from './stores/vkStore'

const vkStore = useVkStore()
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

/* В VK сверху своя панель — опускаем переключатели, чтобы не наезжали */
.app-root--vk .app-chrome {
  top: max(52px, calc(env(safe-area-inset-top) + 44px));
}
</style>
