const STORAGE_KEY = 'hat-sound-enabled'

let audioCtx = null

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
 * Разблокирует AudioContext — вызывать синхронно из обработчика клика.
 * Браузеры блокируют звук без жеста пользователя (клик / тап).
 */
export function initAudioOnGesture() {
  const ctx = getContext()
  if (ctx.state === 'suspended') {
    ctx.resume()
  }
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

function playSequence(tones) {
  if (!isSoundEnabled()) return

  try {
    const ctx = getContext()
    if (ctx.state === 'suspended') {
      ctx.resume()
    }
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
  playSequence([
    { freq: 523, at: 0, dur: 0.25, vol: 0.65 },
    { freq: 659, at: 0.14, dur: 0.3, vol: 0.6 },
    { freq: 784, at: 0.3, dur: 0.4, vol: 0.55, type: 'triangle' },
  ])
}

/** За 5 секунд до конца — тройной звонок */
export function playFiveSecondWarning() {
  playSequence([
    { freq: 880, at: 0, dur: 0.15, vol: 0.7, type: 'triangle' },
    { freq: 840, at: 0.14, dur: 0.15, vol: 0.7, type: 'triangle' },
    { freq: 800, at: 0.28, dur: 0.15, vol: 0.7, type: 'triangle' },
  ])
}

/** Время вышло — нисходящий гонг */
export function playTimeUp() {
  playSequence([
    { freq: 440, at: 0, dur: 0.4, vol: 0.7, type: 'triangle' },
    { freq: 330, at: 0.12, dur: 0.4, vol: 0.65, type: 'triangle' },
    { freq: 220, at: 0.28, dur: 0.55, vol: 0.7 },
    { freq: 110, at: 0.45, dur: 0.85, vol: 0.6 },
  ])
}

/** Слово угадано — короткий восходящий «динь» */
export function playGuess() {
  playSequence([
    { freq: 587, at: 0, dur: 0.12, vol: 0.55, type: 'sine' },
    { freq: 784, at: 0.06, dur: 0.18, vol: 0.6, type: 'triangle' },
    { freq: 988, at: 0.14, dur: 0.22, vol: 0.5, type: 'sine' },
  ])
}

/** Пропуск — мягкий нисходящий щелчок */
export function playSkip() {
  playSequence([
    { freq: 392, at: 0, dur: 0.1, vol: 0.45, type: 'triangle' },
    { freq: 294, at: 0.05, dur: 0.14, vol: 0.4, type: 'triangle' },
    { freq: 220, at: 0.1, dur: 0.18, vol: 0.35, type: 'sine' },
  ])
}

/** Проверка звука из лобби */
export function testSound() {
  initAudioOnGesture()
  playTurnStart()
}

export function unlockAudio() {
  initAudioOnGesture()
}
