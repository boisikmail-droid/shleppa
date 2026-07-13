import { defineStore } from 'pinia'
import { initVk } from '../services/vk'

export const useVkStore = defineStore('vk', {
  state: () => ({
    ready: false,
    inVk: false,
    user: null,
  }),

  getters: {
    displayName: (state) => state.user?.displayName || '',
    photo: (state) => state.user?.photo || '',
  },

  actions: {
    async bootstrap() {
      const result = await initVk()
      this.inVk = Boolean(result.inVk)
      this.user = result.user
      this.ready = true
    },
  },
})
