export function createTimer(callbacks) {
  let intervalId = null
  let remaining = 0

  return {
    start(duration) {
      this.stop()
      remaining = duration
      callbacks.onTick?.(remaining)

      intervalId = setInterval(() => {
        remaining -= 1
        callbacks.onTick?.(remaining)

        if (remaining <= 0) {
          this.stop()
          callbacks.onTimeout?.()
        }
      }, 1000)
    },

    stop() {
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }
    },

    getRemaining() {
      return remaining
    },
  }
}
