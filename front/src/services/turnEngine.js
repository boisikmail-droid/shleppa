/**
 * Клиентский выбор слова и цикл сложности (зеркало WordSelector / TeamDifficultyState).
 */

export function difficultyFallbackOrder(allowed, start) {
  const levels = [...new Set((allowed || []).map((d) => Number(d)))].sort((a, b) => a - b)
  if (!levels.length) return [Number(start) || 1]

  let idx = levels.indexOf(Number(start))
  if (idx === -1) {
    idx = 0
    for (let i = 0; i < levels.length; i++) {
      if (levels[i] >= Number(start)) {
        idx = i
        break
      }
    }
  }

  return [...levels.slice(idx), ...levels.slice(0, idx)]
}

export function difficultyForCycleIndex(cycle, wordsGuessedInCycle) {
  if (!cycle?.length) return 1
  return cycle[wordsGuessedInCycle % cycle.length]
}

export function applyGuessDifficultyUpdate(state, cycle) {
  const len = Math.max(1, cycle?.length || 1)
  let wordsGuessedInCycle = (state.wordsGuessedInCycle || 0) + 1
  if (wordsGuessedInCycle >= len) {
    wordsGuessedInCycle = 0
  }
  return {
    currentDifficulty: difficultyForCycleIndex(cycle || [], wordsGuessedInCycle),
    wordsGuessedInCycle,
    nextResetAt: len,
  }
}

/**
 * @param {Array<{id:number, text:string, difficulty:number, category?:string}>} pool
 * @param {number} currentDifficulty
 * @param {number[]} allowedDifficulties
 * @param {number[]} excludeIds
 */
export function pickNextWord(pool, currentDifficulty, allowedDifficulties, excludeIds = []) {
  const exclude = new Set(excludeIds)
  const ordered = difficultyFallbackOrder(allowedDifficulties, currentDifficulty)

  for (const difficulty of ordered) {
    const candidates = pool.filter(
      (w) => w.difficulty === difficulty && !exclude.has(w.id)
    )
    if (candidates.length) {
      return candidates[Math.floor(Math.random() * candidates.length)]
    }
  }

  return null
}
