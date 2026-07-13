const ROUND_TITLES = {
  round1_words: {
    icon: '🎩',
    text: 'Раунд 1: Объяснять словами',
    short: 'Словами',
  },
  round2_gestures: {
    icon: '🎭',
    text: 'Раунд 2: Объяснять жестами',
    short: 'Жестами',
  },
  round3_oneword: {
    icon: '💬',
    text: 'Раунд 3: Объяснять одним словом',
    short: 'Одним словом',
  },
}

export function getRoundTitle(status, round = 1) {
  return ROUND_TITLES[status] || { icon: '🎩', text: `Раунд ${round}`, short: `Раунд ${round}` }
}
