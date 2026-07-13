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

  getNextWord(sessionId, teamId, round, excludeWordIds = []) {
    const params = { session_id: sessionId, team_id: teamId, round }
    if (excludeWordIds.length) {
      params.exclude_word_ids = excludeWordIds.join(',')
    }
    return api.get('/game/next-word', { params })
  },

  startTurn(sessionId, playerId) {
    return api.post('/game/turn/start', { session_id: sessionId, player_id: playerId })
  },

  submitAction(sessionId, playerId, wordId, action, turnId) {
    return api.post('/game/action', {
      session_id: sessionId,
      player_id: playerId,
      word_id: wordId,
      action,
      turn_id: turnId,
    })
  },

  finishTurn(sessionId, turnId, corrections) {
    return api.post('/game/turn/finish', {
      session_id: sessionId,
      turn_id: turnId,
      corrections,
    })
  },

  nextRound(sessionId) {
    return api.post('/round/next', { session_id: sessionId })
  },
}
