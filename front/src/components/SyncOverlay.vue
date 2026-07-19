<template>
  <Teleport to="body">
    <Transition name="sync-fade">
      <div
        v-if="visible"
        class="sync-overlay"
        role="status"
        aria-live="polite"
        aria-busy="true"
      >
        <div class="sync-overlay__card">
          <div class="sync-overlay__hat" aria-hidden="true">
            <span class="sync-overlay__brim" />
            <span class="sync-overlay__crown" />
            <span class="sync-overlay__band" />
          </div>
          <p class="sync-overlay__title">{{ title }}</p>
          <p class="sync-overlay__hint">{{ hint }}</p>
          <div class="sync-overlay__dots" aria-hidden="true">
            <i /><i /><i />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  phase: {
    type: String,
    default: null,
    validator: (v) =>
      v == null || v === 'starting' || v === 'finishing' || v === 'creating',
  },
})

const visible = computed(() => Boolean(props.phase))

const copy = {
  creating: {
    title: 'Создаём игру',
    hint: 'Подбираем слова в шляпу…',
  },
  starting: {
    title: 'Готовим ход',
    hint: 'Загружаем слова из шляпы…',
  },
  finishing: {
    title: 'Сохраняем ход',
    hint: 'Синхронизация с сервером…',
  },
}

const title = computed(() => copy[props.phase]?.title || 'Подождите')
const hint = computed(() => copy[props.phase]?.hint || 'Синхронизация…')
</script>

<style scoped>
.sync-overlay {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background:
    radial-gradient(ellipse at 50% 40%, var(--round-glow), transparent 55%),
    var(--overlay-scrim);
  backdrop-filter: blur(8px);
}

.sync-overlay__card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 22rem;
  text-align: center;
}

.sync-overlay__hat {
  position: relative;
  width: 72px;
  height: 56px;
  margin-bottom: 8px;
  animation: sync-bob 1.1s ease-in-out infinite;
}

.sync-overlay__brim {
  position: absolute;
  left: 4px;
  right: 4px;
  bottom: 4px;
  height: 14px;
  border-radius: 50%;
  background: var(--hat-body, var(--bg-elevated));
  border: 1.5px solid var(--hat-stroke, var(--gold));
  box-shadow: 0 0 18px color-mix(in srgb, var(--gold) 25%, transparent);
}

.sync-overlay__crown {
  position: absolute;
  left: 18px;
  right: 18px;
  top: 2px;
  bottom: 14px;
  border-radius: 10px 10px 4px 4px;
  background: var(--hat-crown, var(--bg-card));
  border: 1.5px solid var(--hat-stroke, var(--gold));
}

.sync-overlay__band {
  position: absolute;
  left: 18px;
  right: 18px;
  bottom: 16px;
  height: 8px;
  background: var(--hat-band, var(--crimson));
  opacity: 0.9;
}

.sync-overlay__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 6vw, 2.2rem);
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--gold-bright);
}

.sync-overlay__hint {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 0.75rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.sync-overlay__dots {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.sync-overlay__dots i {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--gold);
  opacity: 0.35;
  animation: sync-dot 1s ease-in-out infinite;
}

.sync-overlay__dots i:nth-child(2) {
  animation-delay: 0.15s;
}

.sync-overlay__dots i:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes sync-bob {
  0%,
  100% {
    transform: translateY(0) rotate(-2deg);
  }
  50% {
    transform: translateY(-8px) rotate(2deg);
  }
}

@keyframes sync-dot {
  0%,
  100% {
    opacity: 0.3;
    transform: scale(0.85);
  }
  50% {
    opacity: 1;
    transform: scale(1.15);
  }
}

.sync-fade-enter-active,
.sync-fade-leave-active {
  transition: opacity 0.22s ease;
}

.sync-fade-enter-from,
.sync-fade-leave-to {
  opacity: 0;
}
</style>
