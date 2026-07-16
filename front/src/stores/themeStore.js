import { defineStore } from 'pinia'
import { DEFAULT_THEME, THEME_STORAGE_KEY, THEMES } from '../constants/themes'

function readStoredTheme() {
  try {
    const value = localStorage.getItem(THEME_STORAGE_KEY)
    if (THEMES.some((t) => t.id === value)) {
      return value
    }
  } catch {
    /* ignore */
  }
  return DEFAULT_THEME
}

function applyTheme(themeId) {
  document.documentElement.setAttribute('data-theme', themeId)
}

export const useThemeStore = defineStore('theme', {
  state: () => ({
    themeId: DEFAULT_THEME,
  }),

  getters: {
    themes: () => THEMES,
    currentTheme(state) {
      return THEMES.find((t) => t.id === state.themeId) || THEMES[0]
    },
  },

  actions: {
    init() {
      this.themeId = readStoredTheme()
      applyTheme(this.themeId)
    },

    setTheme(themeId) {
      if (!THEMES.some((t) => t.id === themeId)) return
      this.themeId = themeId
      applyTheme(themeId)
      try {
        localStorage.setItem(THEME_STORAGE_KEY, themeId)
      } catch {
        /* ignore */
      }
    },

    cycle() {
      const idx = THEMES.findIndex((t) => t.id === this.themeId)
      const next = THEMES[(idx + 1) % THEMES.length]
      this.setTheme(next.id)
    },
  },
})
