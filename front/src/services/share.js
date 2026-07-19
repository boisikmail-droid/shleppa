import { bridge, isRunningInVk } from './vk'

/** Текст итогов партии для шаринга. */
export function buildShareText({ teams = [], highlights = [], rounds = [] } = {}) {
  const sorted = [...teams].sort((a, b) => b.score - a.score)
  const lines = ['🎩 Шляпа — итоги партии', '']

  if (sorted.length) {
    lines.push('Счёт:')
    sorted.forEach((t, i) => {
      const mark = i === 0 ? '★ ' : '· '
      lines.push(`${mark}${t.name}: ${t.score}`)
    })
    lines.push('')
  }

  if (rounds.length) {
    const parts = rounds.map((r) => `р.${r.round}: ${r.guessed} угадано`)
    lines.push(parts.join(' · '))
    lines.push('')
  }

  if (highlights.length) {
    lines.push('Слова с доски:')
    highlights.slice(0, 8).forEach((h) => {
      lines.push(`«${h.word}» — ${h.player}`)
    })
  }

  return lines.filter((l, i, arr) => !(l === '' && arr[i - 1] === '')).join('\n').trim()
}

/**
 * Шаринг в VK (стена) или Web Share / буфер обмена вне VK.
 * @returns {Promise<{ok: boolean, mode: 'vk'|'web'|'clipboard'|'none', error?: unknown}>}
 */
export async function shareGameResult(text) {
  if (!text) return { ok: false, mode: 'none' }

  if (isRunningInVk()) {
    try {
      await bridge.send('VKWebAppShowWallPostBox', { message: text })
      return { ok: true, mode: 'vk' }
    } catch (error) {
      // Пользователь закрыл диалог или метод недоступен — fallback
      try {
        await bridge.send('VKWebAppShare', { link: window.location.href })
        return { ok: true, mode: 'vk' }
      } catch (err2) {
        return { ok: false, mode: 'vk', error: err2 }
      }
    }
  }

  if (typeof navigator !== 'undefined' && navigator.share) {
    try {
      await navigator.share({ title: 'Шляпа', text })
      return { ok: true, mode: 'web' }
    } catch (error) {
      if (error?.name === 'AbortError') return { ok: false, mode: 'web', error }
    }
  }

  try {
    await navigator.clipboard.writeText(text)
    return { ok: true, mode: 'clipboard' }
  } catch (error) {
    return { ok: false, mode: 'clipboard', error }
  }
}
