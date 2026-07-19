import { DEFAULT_THEME, themeFontsHref } from '../constants/themes'

const LINK_ID = 'hat-theme-fonts'

/** Подгружает шрифты только для выбранной темы (меняет один <link>). */
export function loadThemeFonts(themeId = DEFAULT_THEME) {
  if (typeof document === 'undefined') return

  const href = themeFontsHref(themeId)
  let link = document.getElementById(LINK_ID)
  if (!link) {
    link = document.createElement('link')
    link.id = LINK_ID
    link.rel = 'stylesheet'
    document.head.appendChild(link)
  }
  if (link.getAttribute('href') !== href) {
    link.setAttribute('href', href)
  }
}
