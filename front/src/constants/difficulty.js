export const MAX_DIFFICULTY = 6

export const DIFFICULTY_LEVELS = [
  { id: 1, label: 'Очень простые', examples: 'кот, стол, дом' },
  { id: 2, label: 'Простые', examples: 'шкаф, куртка, врач' },
  { id: 3, label: 'Простые, но уже не для идиотов', examples: 'эскалатор, фломастер' },
  { id: 4, label: 'Средние', examples: 'амфитеатр, гироскоп' },
  { id: 5, label: 'Сложные', examples: 'консенсус, квантор' },
  { id: 6, label: 'ЖОСКИЕ', examples: 'эпистемология, олигополия' },
]

export const CATEGORIES = [
  { id: 'everyday', label: 'Повседневность' },
  { id: 'food', label: 'Еда и напитки' },
  { id: 'animals', label: 'Животные' },
  { id: 'nature', label: 'Природа и погода' },
  { id: 'movies', label: 'Мультфильмы и кино' },
  { id: 'places', label: 'Места' },
  { id: 'transport', label: 'Транспорт' },
  { id: 'clothes', label: 'Одежда' },
  { id: 'furniture', label: 'Мебель' },
  { id: 'profession', label: 'Профессия' },
  { id: 'school', label: 'Школьная программа (5–11)' },
  { id: 'celebrities', label: 'Знаменитости' },
  { id: 'feelings', label: 'Чувства и ощущения' },
  { id: 'sport', label: 'Спорт' },
  { id: 'tech', label: 'Техника и гаджеты' },
  { id: 'phrases', label: 'Адекватные словосочетания' },
  { id: 'random_phrases', label: 'Случайные словосочетания' },
]

export function formatDifficultyStars(level) {
  const d = Math.min(Math.max(level || 1, 1), MAX_DIFFICULTY)
  return '★'.repeat(d) + '☆'.repeat(MAX_DIFFICULTY - d)
}

export function difficultyLabel(level) {
  return DIFFICULTY_LEVELS.find((l) => l.id === level)?.label || `Уровень ${level}`
}
