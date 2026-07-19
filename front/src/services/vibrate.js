const STORAGE_KEY = 'hat-vibrate-enabled'

export function isVibrateEnabled() {
  return localStorage.getItem(STORAGE_KEY) !== 'false'
}

export function setVibrateEnabled(enabled) {
  localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false')
}

export function vibrate(pattern = 40) {
  if (!isVibrateEnabled()) return
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
