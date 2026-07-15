export function createTimer(callbacks) {
  let intervalId = null
  let remaining = 0
  let paused = false

  function tick() {
    remaining -= 1
    callbacks.onTick?.(remaining)

    if (remaining <= 0) {
      stop()
      callbacks.onTimeout?.()
    }
  }

  function startInterval() {
    stopInterval()
    intervalId = setInterval(tick, 1000)
  }

  function stopInterval() {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  function stop() {
    stopInterval()
    paused = false
  }

  return {
    start(duration) {
      stop()
      remaining = duration
      paused = false
      callbacks.onTick?.(remaining)
      startInterval()
    },

    pause() {
      if (!intervalId || paused) return
      stopInterval()
      paused = true
    },

    resume() {
      if (!paused || remaining <= 0) return
      paused = false
      startInterval()
    },

    stop,

    getRemaining() {
      return remaining
    },

    isPaused() {
      return paused
    },
  }
}
