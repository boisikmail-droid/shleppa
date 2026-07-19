const KEY = 'hat-pending-finish'

/**
 * Черновик итогов хода в sessionStorage — переживает обрыв сети на finishTurn.
 * @typedef {{
 *   sessionId: number,
 *   turnId: number,
 *   actions: Array<{word_id: number, action: string}>,
 *   corrections: Array<{word_id: number, checked: boolean}>,
 *   lastWord: {word_id: number, award_team_id: number|null}|null,
 *   turnLog: Array<{word_id: number, word_text?: string, action?: string, status: string}>,
 * }} PendingFinish
 */

/** @returns {PendingFinish|null} */
export function loadPendingFinish() {
  try {
    const raw = sessionStorage.getItem(KEY)
    if (!raw) return null
    const data = JSON.parse(raw)
    if (!data?.sessionId || !data?.turnId) return null
    return data
  } catch {
    return null
  }
}

/** @param {PendingFinish} payload */
export function savePendingFinish(payload) {
  try {
    sessionStorage.setItem(KEY, JSON.stringify(payload))
  } catch {
    /* quota / private mode */
  }
}

export function clearPendingFinish() {
  try {
    sessionStorage.removeItem(KEY)
  } catch {
    /* ignore */
  }
}
