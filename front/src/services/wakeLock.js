let wakeLock = null

/** Не даём экрану гаснуть во время пояснения (где поддерживается). */
export async function requestWakeLock() {
  if (typeof navigator === 'undefined' || !navigator.wakeLock?.request) {
    return false
  }
  try {
    wakeLock = await navigator.wakeLock.request('screen')
    wakeLock.addEventListener('release', () => {
      wakeLock = null
    })
    return true
  } catch {
    wakeLock = null
    return false
  }
}

export async function releaseWakeLock() {
  if (!wakeLock) return
  try {
    await wakeLock.release()
  } catch {
    /* ignore */
  }
  wakeLock = null
}

/** После возврата на вкладку браузер снимает lock — запрашиваем снова. */
export function bindWakeLockVisibility(shouldHold) {
  if (typeof document === 'undefined') return () => {}

  const onVisibility = () => {
    if (document.visibilityState === 'visible' && shouldHold()) {
      requestWakeLock()
    }
  }
  document.addEventListener('visibilitychange', onVisibility)
  return () => document.removeEventListener('visibilitychange', onVisibility)
}
