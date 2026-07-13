export const MAX_DIFFICULTY = 7
export const CYCLE_LENGTH = 10

export function formatDifficultyStars(level) {
  const d = Math.min(Math.max(level || 1, 1), MAX_DIFFICULTY)
  return '★'.repeat(d) + '☆'.repeat(MAX_DIFFICULTY - d)
}
