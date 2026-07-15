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
  { id: 'clothes', label: 'Одежда' },
  { id: 'furniture', label: 'Мебель' },
  { id: 'profession', label: 'Профессия' },
  { id: 'animals', label: 'Животные' },
  { id: 'school', label: 'Школьная программа (5–11)' },
  { id: 'celebrities', label: 'Знаменитости' },
  { id: 'movies', label: 'Мультфильмы и кино' },
  { id: 'feelings', label: 'Чувства и ощущения' },
  { id: 'food', label: 'Еда и напитки' },
  { id: 'sport', label: 'Спорт' },
  { id: 'tech', label: 'Техника и гаджеты' },
  { id: 'nature', label: 'Природа и погода' },
]

export function formatDifficultyStars(level) {
  const d = Math.min(Math.max(level || 1, 1), MAX_DIFFICULTY)
  return '★'.repeat(d) + '☆'.repeat(MAX_DIFFICULTY - d)
}

export function difficultyLabel(level) {
  return DIFFICULTY_LEVELS.find((l) => l.id === level)?.label || `Уровень ${level}`
}
