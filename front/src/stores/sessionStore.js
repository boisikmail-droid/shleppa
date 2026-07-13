import { defineStore } from 'pinia'

export const useSessionStore = defineStore('session', {
  state: () => ({
    sessionId: localStorage.getItem('current_session_id'),
  }),

  actions: {
    setSessionId(id) {
      this.sessionId = String(id)
      localStorage.setItem('current_session_id', String(id))
    },

    clearSession() {
      this.sessionId = null
      localStorage.removeItem('current_session_id')
    },
  },
})
