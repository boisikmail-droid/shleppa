<template>
  <div class="welcome">
    <div class="welcome__stage" aria-hidden="true">
      <div class="welcome__vignette" />
      <div class="welcome__spot" />
      <div class="welcome__beams" />
      <div class="welcome__smoke welcome__smoke--a" />
      <div class="welcome__smoke welcome__smoke--b" />
      <div class="welcome__embers">
        <i v-for="n in 14" :key="n" class="welcome__ember" :style="emberStyle(n)" />
      </div>

      <div class="welcome__words">
        <span
          v-for="(w, i) in fallingWords"
          :key="i"
          class="welcome__word"
          :class="{ 'welcome__word--long': w.text.length > 18 }"
          :style="w.style"
        >{{ w.text }}</span>
      </div>

      <div class="welcome__hat-wrap">
        <div class="welcome__hat-glow" />
        <svg class="welcome__hat" viewBox="0 0 360 280" fill="none" xmlns="http://www.w3.org/2000/svg">
          <ellipse class="welcome__hat-shadow" cx="180" cy="248" rx="148" ry="20" fill="currentColor" opacity="0.55" />
          <path
            d="M36 204c22-32 74-48 144-48s122 16 144 48c-30 24-84 36-144 36S66 228 36 204Z"
            fill="var(--hat-body)"
            stroke="var(--hat-stroke)"
            stroke-width="1.6"
          />
          <path
            d="M48 198c18-20 62-32 132-32s114 12 132 32"
            stroke="var(--hat-stroke-bright)"
            stroke-width="1"
            opacity="0.22"
          />
          <path
            d="M112 200V88c0-20 30-36 68-36s68 16 68 36v112"
            fill="var(--hat-crown)"
            stroke="var(--hat-stroke)"
            stroke-width="1.6"
          />
          <path
            d="M122 96c8-10 28-18 58-18s50 8 58 18"
            stroke="var(--hat-stroke-bright)"
            stroke-width="1"
            opacity="0.18"
          />
          <path d="M112 148h136" stroke="var(--hat-band)" stroke-width="16" />
          <path d="M112 140h136M112 156h136" stroke="var(--hat-stroke)" stroke-width="1.1" opacity="0.65" />
          <ellipse cx="180" cy="54" rx="70" ry="15" fill="var(--hat-top)" stroke="var(--hat-stroke)" stroke-width="1.3" />
          <ellipse cx="180" cy="50" rx="52" ry="8" fill="var(--hat-inner)" opacity="0.7" />
          <path
            d="M158 148c6 5 14 8 22 8s16-3 22-8"
            stroke="var(--hat-stroke-bright)"
            stroke-width="1.4"
            opacity="0.4"
          />
        </svg>
      </div>
    </div>

    <div class="welcome__copy">
      <p class="welcome__mark">Шляпа</p>
      <h1 class="welcome__title">Слова вытаскивают — компания раскрывается</h1>
      <p class="welcome__lead">
        Эрудиция, память, смех и общая волна — party на одном телефоне.
      </p>
      <div class="welcome__actions">
        <router-link
          v-if="resumePath"
          class="welcome__cta welcome__cta--resume"
          :to="resumePath"
        >
          <span class="welcome__cta-label">{{ resumeLabel }}</span>
          <span class="welcome__cta-arrow" aria-hidden="true">→</span>
        </router-link>
        <router-link class="welcome__cta" :class="{ 'welcome__cta--secondary': !!resumePath }" to="/setup">
          <span class="welcome__cta-label">{{ resumePath ? 'Новая игра' : 'К настройкам игры' }}</span>
          <span class="welcome__cta-arrow" aria-hidden="true">→</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
import { useSessionStore } from '../stores/sessionStore'
import { loadPendingFinish } from '../services/turnSync'

const sessionStore = useSessionStore()
const resumePath = ref(null)
const resumeLabel = ref('Продолжить партию')

const phrases = [
  'Белшок',
  'Анкгор-Ват',
  'замок Нойшванштайн',
  'Афина или Паллада все таки?',
  'убийцы в белых халатах',
  'кот Шрёдингера',
  'Тутанхамон',
  'машина времени',
  'Эверест с телескопом',
  'чебурашка на пантеоне',
  'тайная вечеря',
  'чёрный квадрат Малевича',
  'пингвин в смокинге',
  'седьмая печать',
]

function wordStyle(i) {
  const x = 6 + ((i * 17) % 78)
  const delay = (i * 0.85) % 9
  const dur = 9 + (i % 5) * 0.8
  const rot = -14 + (i * 7) % 28
  const sway = 10 + (i % 4) * 6
  return {
    '--x': `${x}%`,
    '--delay': `${delay}s`,
    '--dur': `${dur}s`,
    '--rot': `${rot - 14}deg`,
    '--sway': `${sway}px`,
    '--scale': i % 3 === 0 ? '1.05' : '0.92',
  }
}

const fallingWords = phrases.map((text, i) => ({
  text,
  style: wordStyle(i),
}))

function emberStyle(n) {
  return {
    '--x': `${12 + (n * 5.7) % 76}%`,
    '--delay': `${(n * 0.45) % 6}s`,
    '--dur': `${5 + (n % 4)}s`,
  }
}

onMounted(async () => {
  const pending = loadPendingFinish()
  const id = pending?.sessionId || sessionStore.sessionId
  if (!id) return

  try {
    const { data } = await api.getSessionState(id)
    if (data.status === 'finished') {
      resumePath.value = `/results/${id}`
      resumeLabel.value = 'Смотреть итоги'
      return
    }
    resumePath.value = `/game/${id}`
    resumeLabel.value = pending ? 'Доотправить ход' : 'Продолжить партию'
  } catch {
    sessionStore.clearSession()
  }
})
</script>

<style scoped>
.welcome {
  position: relative;
  min-height: 100vh;
  min-height: 100dvh;
  overflow: hidden;
  display: grid;
  place-items: stretch;
  background: var(--welcome-bg);
}

.welcome__stage {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.welcome__vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 65% 50% at 50% 40%, transparent 25%, rgba(0, 0, 0, 0.62) 100%);
}

.welcome__spot {
  position: absolute;
  left: 50%;
  top: 12%;
  width: min(95vw, 560px);
  height: 48vh;
  transform: translateX(-50%);
  background: radial-gradient(ellipse at 50% 35%, var(--welcome-spot), transparent 68%);
  animation: spotBreathe 5.5s ease-in-out infinite alternate;
}

.welcome__beams {
  position: absolute;
  left: 50%;
  bottom: 18%;
  width: min(110vw, 640px);
  height: 55vh;
  transform: translateX(-50%);
  background:
    conic-gradient(
      from 210deg at 50% 100%,
      transparent 0deg,
      color-mix(in srgb, var(--gold-bright) 12%, transparent) 18deg,
      transparent 36deg,
      color-mix(in srgb, var(--gold-bright) 16%, transparent) 54deg,
      transparent 72deg,
      color-mix(in srgb, var(--gold) 10%, transparent) 95deg,
      transparent 120deg
    );
  mask-image: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent 85%);
  opacity: 0.85;
  animation: beamsPulse 7s ease-in-out infinite alternate;
}

.welcome__smoke {
  position: absolute;
  left: 50%;
  bottom: 16%;
  width: 300px;
  height: 200px;
  transform: translateX(-50%);
  background: radial-gradient(ellipse at center, rgba(236, 232, 223, 0.08), transparent 70%);
  filter: blur(20px);
}

.welcome__smoke--a {
  animation: smokeDrift 10s ease-in-out infinite alternate;
}

.welcome__smoke--b {
  width: 390px;
  bottom: 12%;
  opacity: 0.4;
  animation: smokeDrift 13s ease-in-out infinite alternate-reverse;
}

.welcome__embers {
  position: absolute;
  inset: 0 0 30% 0;
}

.welcome__ember {
  position: absolute;
  top: 70%;
  left: var(--x);
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: var(--welcome-ember);
  box-shadow: 0 0 8px color-mix(in srgb, var(--welcome-ember) 80%, transparent);
  animation: emberRise var(--dur) ease-in var(--delay) infinite;
  opacity: 0;
}

.welcome__words {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.welcome__word {
  position: absolute;
  top: -12%;
  left: var(--x);
  max-width: min(72vw, 280px);
  padding: 6px 12px;
  font-family: var(--font-display);
  font-size: clamp(0.9rem, 2.6vw, 1.2rem);
  font-style: italic;
  line-height: 1.2;
  color: var(--welcome-word-text);
  letter-spacing: 0.02em;
  white-space: normal;
  text-align: center;
  background: var(--welcome-word-bg);
  border: 1px solid var(--welcome-word-border);
  border-radius: var(--radius);
  box-shadow:
    var(--shadow),
    inset 0 1px 0 color-mix(in srgb, var(--gold-bright) 18%, transparent);
  transform: rotate(var(--rot)) scale(var(--scale));
  animation: wordFall var(--dur) cubic-bezier(0.25, 0.1, 0.25, 1) var(--delay) infinite;
  text-shadow: 0 0 18px var(--title-glow);
}

.welcome__word--long {
  font-size: clamp(0.78rem, 2.2vw, 1rem);
  max-width: min(80vw, 300px);
}

.welcome__hat-wrap {
  position: absolute;
  left: 50%;
  bottom: max(2vh, 8px);
  width: min(96vw, 460px);
  transform: translateX(-50%);
  animation: hatFloat 5.5s ease-in-out infinite alternate;
}

.welcome__hat-glow {
  position: absolute;
  left: 50%;
  top: 42%;
  width: 70%;
  height: 40%;
  transform: translate(-50%, -50%);
  background: radial-gradient(ellipse, color-mix(in srgb, var(--gold) 35%, transparent), transparent 70%);
  filter: blur(8px);
  animation: hatSuck 2.4s ease-in-out infinite alternate;
}

.welcome__hat {
  position: relative;
  display: block;
  width: 100%;
  height: auto;
  color: var(--bg-deep);
  filter: drop-shadow(0 28px 42px color-mix(in srgb, var(--bg-deep) 55%, transparent));
}

.welcome__hat-shadow {
  opacity: 0.5;
  transform-origin: center;
  animation: shadowPulse 5.5s ease-in-out infinite alternate;
}

.welcome__copy {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: 1rem;
  max-width: 520px;
  margin: 0 auto;
  padding: clamp(4.2rem, 11vh, 6.5rem) 24px 44vh;
}

.welcome__mark {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(4.2rem, 17vw, 6.5rem);
  font-weight: 700;
  line-height: 0.88;
  letter-spacing: 0.04em;
  color: var(--gold-bright);
  text-shadow:
    0 0 56px var(--title-glow),
    0 2px 0 color-mix(in srgb, var(--bg-deep) 45%, transparent);
  animation: brandIn 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.welcome__title {
  margin: 0;
  max-width: 16ch;
  font-family: var(--font-heading);
  font-size: clamp(1.15rem, 3.8vw, 1.45rem);
  font-weight: 500;
  line-height: 1.25;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text);
  animation: brandIn 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both;
}

.welcome__lead {
  margin: 0;
  max-width: 32ch;
  font-size: 1.05rem;
  line-height: 1.5;
  color: var(--text-muted);
  animation: brandIn 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.18s both;
}

.welcome__actions {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  margin-top: 0.35rem;
  animation: brandIn 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.26s both;
}

.welcome__cta {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  align-self: flex-start;
  margin-top: 0;
  padding: 15px 22px;
  text-decoration: none;
  color: var(--btn-primary-text);
  background: linear-gradient(120deg, var(--btn-primary-from) 0%, var(--btn-primary-mid) 55%, var(--btn-primary-to) 100%);
  background-size: 160% 100%;
  border-radius: var(--radius);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--gold-bright) 35%, transparent),
    0 10px 28px var(--btn-primary-glow);
  animation: ctaShimmer 4.5s ease-in-out infinite;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.welcome__cta--secondary {
  color: var(--text);
  background: transparent;
  box-shadow: none;
  border: 1px solid var(--border-strong);
  animation: none;
}

.welcome__cta--resume {
  animation: ctaShimmer 4.5s ease-in-out infinite;
}

.welcome__cta-label {
  font-family: var(--font-heading);
  font-size: 0.95rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.welcome__cta-arrow {
  font-size: 1.2rem;
  transition: transform 0.2s ease;
}

.welcome__cta:hover {
  transform: translateY(-2px);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--gold-bright) 55%, transparent),
    0 14px 36px var(--btn-primary-glow);
}

.welcome__cta:hover .welcome__cta-arrow {
  transform: translateX(4px);
}

@keyframes brandIn {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes wordFall {
  0% {
    transform: translate3d(0, -12%, 0) rotate(var(--rot)) scale(var(--scale));
    opacity: 0;
    filter: blur(2px);
  }
  8% {
    opacity: 0.95;
    filter: blur(0);
  }
  50% {
    transform: translate3d(var(--sway), 50vh, 0) rotate(calc(var(--rot) + 4deg)) scale(var(--scale));
    opacity: 0.85;
  }
  85% {
    opacity: 0.4;
    filter: blur(0.5px);
  }
  100% {
    transform: translate3d(calc(var(--sway) * -0.3), 110vh, 0) rotate(calc(var(--rot) + 12deg)) scale(0.5);
    opacity: 0;
    filter: blur(3px);
  }
}

@keyframes hatFloat {
  from {
    transform: translateX(-50%) translateY(0);
  }
  to {
    transform: translateX(-50%) translateY(-10px);
  }
}

@keyframes hatSuck {
  from {
    opacity: 0.35;
    transform: translate(-50%, -50%) scale(0.9);
  }
  to {
    opacity: 0.7;
    transform: translate(-50%, -50%) scale(1.15);
  }
}

@keyframes shadowPulse {
  from {
    opacity: 0.45;
  }
  to {
    opacity: 0.28;
  }
}

@keyframes spotBreathe {
  from {
    opacity: 0.75;
    transform: translateX(-50%) scale(1);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) scale(1.1);
  }
}

@keyframes beamsPulse {
  from {
    opacity: 0.55;
  }
  to {
    opacity: 0.95;
  }
}

@keyframes smokeDrift {
  from {
    transform: translateX(-54%) translateY(0) scale(1);
    opacity: 0.22;
  }
  to {
    transform: translateX(-46%) translateY(-20px) scale(1.14);
    opacity: 0.48;
  }
}

@keyframes emberRise {
  0% {
    transform: translateY(0) scale(0.6);
    opacity: 0;
  }
  20% {
    opacity: 0.9;
  }
  100% {
    transform: translateY(-42vh) scale(1.2);
    opacity: 0;
  }
}

@keyframes ctaShimmer {
  0%,
  100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .welcome__word,
  .welcome__hat-wrap,
  .welcome__hat-shadow,
  .welcome__hat-glow,
  .welcome__spot,
  .welcome__smoke,
  .welcome__beams,
  .welcome__ember,
  .welcome__cta {
    animation: none !important;
  }

  .welcome__mark,
  .welcome__title,
  .welcome__lead,
  .welcome__cta {
    opacity: 1;
    transform: none;
  }
}

@media (max-width: 480px) {
  .welcome__copy {
    padding: max(2.6rem, env(safe-area-inset-top)) 18px 48vh;
    gap: 0.75rem;
  }

  .welcome__mark {
    font-size: clamp(3.4rem, 15vw, 5rem);
  }

  .welcome__title {
    font-size: clamp(1rem, 4.2vw, 1.2rem);
    max-width: 18ch;
  }

  .welcome__lead {
    font-size: 0.95rem;
    max-width: 28ch;
  }

  .welcome__cta {
    padding: 14px 20px;
    width: 100%;
    justify-content: center;
  }

  .welcome__hat-wrap {
    width: min(112vw, 400px);
    bottom: max(0px, env(safe-area-inset-bottom));
  }

  .welcome__spot {
    top: 8%;
    height: 40vh;
  }

  .welcome__beams {
    bottom: 12%;
    height: 50vh;
  }

  .welcome__smoke {
    bottom: 10%;
    width: 240px;
  }

  .welcome__smoke--b {
    width: 300px;
    bottom: 8%;
  }
}

@media (max-width: 480px) and (max-height: 700px) {
  .welcome__copy {
    padding-top: 2rem;
    padding-bottom: 44vh;
  }

  .welcome__mark {
    font-size: clamp(2.8rem, 13vw, 4rem);
  }
}
</style>
