const STORAGE_KEY = 'hat-sound-enabled'

let audioCtx = null
let unlockBound = false

export function isSoundEnabled() {
  return localStorage.getItem(STORAGE_KEY) !== 'false'
}

export function setSoundEnabled(enabled) {
  localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false')
}

function getContext() {
  if (!audioCtx) {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)()
  }
  return audioCtx
}

/**
 * Разблокирует AudioContext (VK WebView / iOS требуют жест + await resume).
 */
export async function initAudioOnGesture() {
  const ctx = getContext()
  try {
    if (ctx.state === 'suspended') {
      await ctx.resume()
    }
    // Тихий буфер — добивает разблокировку в iOS/WebView
    const buffer = ctx.createBuffer(1, 1, 22050)
    const source = ctx.createBufferSource()
    source.buffer = buffer
    source.connect(ctx.destination)
    source.start(0)
  } catch (err) {
    console.warn('[timerSounds] unlock failed:', err)
  }
}

/** Один раз на первый тап/клик по странице (важно для VK Mini App). */
export function bindAudioUnlockOnFirstGesture() {
  if (unlockBound || typeof document === 'undefined') return
  unlockBound = true

  const unlock = () => {
    initAudioOnGesture()
    document.removeEventListener('pointerdown', unlock, true)
    document.removeEventListener('touchstart', unlock, true)
    document.removeEventListener('click', unlock, true)
  }

  document.addEventListener('pointerdown', unlock, true)
  document.addEventListener('touchstart', unlock, true)
  document.addEventListener('click', unlock, true)
}

function playTone(ctx, frequency, startOffset, duration, volume = 0.55, type = 'sine') {
  const t = ctx.currentTime + startOffset
  const osc = ctx.createOscillator()
  const gain = ctx.createGain()

  osc.type = type
  osc.frequency.setValueAtTime(frequency, t)

  gain.gain.setValueAtTime(0.0001, t)
  gain.gain.exponentialRampToValueAtTime(Math.max(volume, 0.0001), t + 0.02)
  gain.gain.exponentialRampToValueAtTime(0.0001, t + duration)

  osc.connect(gain)
  gain.connect(ctx.destination)

  osc.start(t)
  osc.stop(t + duration + 0.05)
}

async function playSequence(tones) {
  if (!isSoundEnabled()) return

  try {
    await initAudioOnGesture()
    const ctx = getContext()
    for (const tone of tones) {
      playTone(
        ctx,
        tone.freq,
        tone.at,
        tone.dur,
        tone.vol ?? 0.55,
        tone.type ?? 'sine'
      )
    }
  } catch (err) {
    console.warn('[timerSounds] воспроизведение не удалось:', err)
  }
}

/** Сигнал старта хода — трёхтоновый перезвон */
export function playTurnStart() {
  return playSequence([
    { freq: 523, at: 0, dur: 0.25, vol: 0.65 },
    { freq: 659, at: 0.14, dur: 0.3, vol: 0.6 },
    { freq: 784, at: 0.3, dur: 0.4, vol: 0.55, type: 'triangle' },
  ])
}

/** За 10 секунд до конца — тройной звонок */
export function playTenSecondWarning() {
  return playSequence([
    { freq: 880, at: 0, dur: 0.15, vol: 0.7, type: 'triangle' },
    { freq: 840, at: 0.14, dur: 0.15, vol: 0.7, type: 'triangle' },
    { freq: 800, at: 0.28, dur: 0.15, vol: 0.7, type: 'triangle' },
  ])
}

/** Пик обратного отсчёта (5…1) — короткий щелчок, выше ближе к нулю */
export function playCountdownTick(secondsLeft) {
  const freq = 660 + (5 - Math.min(Math.max(secondsLeft, 1), 5)) * 80
  return playSequence([{ freq, at: 0, dur: 0.08, vol: 0.65, type: 'square' }])
}

/** Время вышло — нисходящий гонг */
export function playTimeUp() {
  return playSequence([
    { freq: 440, at: 0, dur: 0.4, vol: 0.7, type: 'triangle' },
    { freq: 330, at: 0.12, dur: 0.4, vol: 0.65, type: 'triangle' },
    { freq: 220, at: 0.28, dur: 0.55, vol: 0.7 },
    { freq: 110, at: 0.45, dur: 0.85, vol: 0.6 },
  ])
}

/** Слово угадано — короткий восходящий «динь» */
export function playGuess() {
  return playSequence([
    { freq: 587, at: 0, dur: 0.12, vol: 0.55, type: 'sine' },
    { freq: 784, at: 0.06, dur: 0.18, vol: 0.6, type: 'triangle' },
    { freq: 988, at: 0.14, dur: 0.22, vol: 0.5, type: 'sine' },
  ])
}

/** Пропуск — мягкий нисходящий щелчок */
export function playSkip() {
  return playSequence([
    { freq: 392, at: 0, dur: 0.1, vol: 0.45, type: 'triangle' },
    { freq: 294, at: 0.05, dur: 0.14, vol: 0.4, type: 'triangle' },
    { freq: 220, at: 0.1, dur: 0.18, vol: 0.35, type: 'sine' },
  ])
}

/** Проверка звука из лобби */
export async function testSound() {
  await initAudioOnGesture()
  return playTurnStart()
}

export function unlockAudio() {
  return initAudioOnGesture()
}
