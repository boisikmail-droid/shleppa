export const MAX_DIFFICULTY = 6

export const DIFFICULTY_LEVELS = [
  { id: 1, label: 'Очень простые', examples: 'кот, стол, дом' },
  { id: 2, label: 'Простые', examples: 'хомяк, творог, скутер' },
  { id: 3, label: 'Средние−', examples: 'паркет, флешка, варан' },
  { id: 4, label: 'Средние+', examples: 'перфоратор, панакота, соболь' },
  { id: 5, label: 'Сложные', examples: 'аксолотль, амфитеатр, интерфейс' },
  { id: 6, label: 'ЖОСКИЕ', examples: 'эпистемология, меандр, полиморфизм' },
]

export const CATEGORIES = [
  { id: 'everyday', label: 'Повседневность', icon: 'everyday' },
  { id: 'food', label: 'Еда', icon: 'food' },
  { id: 'animals', label: 'Животные', icon: 'animals' },
  { id: 'nature', label: 'Природа', icon: 'nature' },
  { id: 'movies', label: 'Кино', icon: 'movies' },
  { id: 'places', label: 'Места', icon: 'places' },
  { id: 'transport', label: 'Транспорт', icon: 'transport' },
  { id: 'clothes', label: 'Одежда', icon: 'clothes' },
  { id: 'furniture', label: 'Мебель', icon: 'furniture' },
  { id: 'profession', label: 'Профессии', icon: 'profession' },
  { id: 'school', label: 'Школа', icon: 'school' },
  { id: 'celebrities', label: 'Звёзды', icon: 'celebrities' },
  { id: 'feelings', label: 'Чувства', icon: 'feelings' },
  { id: 'sport', label: 'Спорт', icon: 'sport' },
  { id: 'tech', label: 'Техника', icon: 'tech' },
  { id: 'phrases', label: 'Фразы', icon: 'phrases' },
  { id: 'random_phrases', label: 'Рандом', icon: 'random' },
]

export function formatDifficultyStars(level) {
  const d = Math.min(Math.max(level || 1, 1), MAX_DIFFICULTY)
  return '★'.repeat(d) + '☆'.repeat(MAX_DIFFICULTY - d)
}

export function difficultyLabel(level) {
  return DIFFICULTY_LEVELS.find((l) => l.id === level)?.label || `Уровень ${level}`
}
