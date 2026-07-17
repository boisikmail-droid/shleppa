export function vibrate(pattern = 40) {
  try {
    if (typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function') {
      navigator.vibrate(pattern)
    }
  } catch {
    /* ignore */
  }
}

/** Patterns: short tick, double, long buzz */
export const VIBRATE = {
  tick: 35,
  warn10: [40, 60, 40],
  warn5: [50, 40, 50, 40, 80],
  timeUp: [120, 80, 120, 80, 200],
}
