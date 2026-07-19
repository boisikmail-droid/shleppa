export const THEMES = [
  {
    id: 'noir',
    label: 'Тёмная',
    hint: 'золото и бархат',
  },
  {
    id: 'neon',
    label: 'Неон',
    hint: 'аркадный стол',
  },
  {
    id: 'daylight',
    label: 'День',
    hint: 'мягкий светлый тон',
  },
  {
    id: 'fog',
    label: 'Туман',
    hint: 'серый полутон',
  },
  {
    id: 'grove',
    label: 'Роща',
    hint: 'ночной лес',
  },
]

export const DEFAULT_THEME = 'noir'
export const THEME_STORAGE_KEY = 'hat-theme'

/** Google Fonts query fragments — только семьи активной темы */
export const THEME_FONT_QUERIES = {
  noir: [
    'family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600',
    'family=Oswald:wght@400;500;600;700',
    'family=IBM+Plex+Sans:wght@400;500;600',
  ],
  neon: [
    'family=Syne:wght@600;700;800',
    'family=Space+Grotesk:wght@400;500;600;700',
  ],
  daylight: [
    'family=Fraunces:opsz,wght@9..144,600;9..144,700',
    'family=Manrope:wght@400;500;600;700',
  ],
  fog: [
    'family=Literata:opsz,wght@7..72,600;7..72,700',
    'family=Source+Sans+3:wght@400;500;600;700',
  ],
  grove: [
    'family=Spectral:wght@600;700',
    'family=Figtree:wght@400;500;600;700',
  ],
}

export function themeFontsHref(themeId) {
  const parts = THEME_FONT_QUERIES[themeId] || THEME_FONT_QUERIES[DEFAULT_THEME]
  return `https://fonts.googleapis.com/css2?${parts.join('&')}&display=swap`
}
