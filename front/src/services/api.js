import axios from 'axios'

const API_BASE = '/api'

const api = axios.create({
  baseURL: API_BASE,
  timeout: 10000,
})

async function withRetry(fn, maxRetries = 3) {
  let lastError
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn()
    } catch (err) {
      lastError = err
      if (err.response?.status === 404 || err.response?.status === 400) {
        throw err
      }
      await new Promise((r) => setTimeout(r, 1000 * (i + 1)))
    }
  }
  throw lastError
}

export default {
  createSession(payload) {
    return withRetry(() => api.post('/session/start', payload))
  },

  getSessionState(sessionId) {
    return withRetry(() => api.get(`/session/${sessionId}/state`), 1)
  },

  getRecap(sessionId) {
    return withRetry(() => api.get(`/session/${sessionId}/recap`), 1)
  },

  startTurn(sessionId, playerId) {
    return api.post('/game/turn/start', { session_id: sessionId, player_id: playerId })
  },

  finishTurn(sessionId, turnId, corrections, actions = [], lastWord = null) {
    const body = {
      session_id: sessionId,
      turn_id: turnId,
      corrections,
      actions,
    }
    if (lastWord) {
      body.last_word = lastWord
    }
    return api.post('/game/turn/finish', body)
  },
}
