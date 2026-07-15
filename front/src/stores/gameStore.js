import { defineStore } from 'pinia'
import api from '../services/api'

export const useGameStore = defineStore('game', {
  state: () => ({
    sessionId: null,
    status: null,
    round: 1,
    currentTeam: null,
    currentPlayer: null,
    teamDifficulty: {
      currentDifficulty: 1,
      wordsGuessedInCycle: 0,
      nextResetAt: 10,
    },
    nextPlayers: [],
    timeLimit: 60,
    isTurnActive: false,
    turnTimeRemaining: null,
    currentWord: null,
    shownWordIds: [],
    remainingWords: 0,
    wordsGuessedThisTurn: 0,
    currentTurnLog: [],
    turnId: null,
    gameFinished: false,
    finalScores: null,
    teams: [],
    screen: 'waiting',
    showRoundTransition: false,
    previousStatus: null,
    error: null,
  }),

  actions: {
    applyState(data) {
      const prevStatus = this.status
      this.sessionId = data.session_id
      this.status = data.status
      this.round = data.round || 1
      this.currentTeam = data.current_team
      this.currentPlayer = data.current_player
      if (data.team_difficulty_state) {
        const s = data.team_difficulty_state
        this.teamDifficulty = {
          currentDifficulty: s.current_difficulty ?? s.currentDifficulty ?? 1,
          wordsGuessedInCycle: s.words_guessed_in_cycle ?? s.wordsGuessedInCycle ?? 0,
          nextResetAt: s.next_reset_at ?? s.nextResetAt ?? 10,
        }
      }
      this.nextPlayers = data.next_players || []
      this.timeLimit = data.time_limit
      this.teams = data.teams || []
      if (data.remaining_words !== undefined && data.remaining_words !== null) {
        this.remainingWords = data.remaining_words
      }

      if (prevStatus && prevStatus !== data.status && data.status !== 'finished') {
        this.showRoundTransition = true
        setTimeout(() => {
          this.showRoundTransition = false
        }, 2000)
      }

      if (data.status === 'finished') {
        this.gameFinished = true
        this.finalScores = this.teams
        this.isTurnActive = false
        this.screen = 'results'
      }
    },

    async fetchGameState(sessionId) {
      const { data } = await api.getSessionState(sessionId)
      this.applyState(data)
      return data
    },

    getShownWordIds() {
      return this.shownWordIds.slice()
    },

    addShownWord(wordId) {
      if (wordId && !this.shownWordIds.includes(wordId)) {
        this.shownWordIds.push(wordId)
      }
    },

    applyWordResponse(data) {
      this.currentWord = {
        word_id: data.word_id,
        word_text: data.word_text,
        difficulty: data.difficulty,
      }
      this.remainingWords = data.remaining_words ?? 0
      this.addShownWord(data.word_id)
    },

    async startTurn() {
      const { data } = await api.startTurn(this.sessionId, this.currentPlayer.id)
      this.turnId = data.turn_id
      this.currentTurnLog = []
      this.shownWordIds = []
      this.wordsGuessedThisTurn = 0
      this.isTurnActive = true
      this.turnTimeRemaining = this.timeLimit
      this.screen = 'gameplay'

      const wordRes = await api.getNextWord(
        this.sessionId,
        this.currentTeam.id,
        this.round
      )

      if (wordRes.data.finished) {
        this.isTurnActive = false
        this.remainingWords = wordRes.data.remaining_words ?? 0
        this.screen = 'correction'
        return
      }

      this.applyWordResponse(wordRes.data)
    },

    async submitAction(wordId, action) {
      // Сразу исключаем слово из текущего хода (пропуск или угаданное)
      this.addShownWord(wordId)

      await api.submitAction(
        this.sessionId,
        this.currentPlayer.id,
        wordId,
        action,
        this.turnId
      )

      const logEntry = {
        word_id: wordId,
        word_text: this.currentWord?.word_text,
        action,
        status: action === 'guess' ? 'guessed' : 'skipped',
      }
      this.currentTurnLog.push(logEntry)

      if (action === 'guess') {
        this.wordsGuessedThisTurn += 1
      }

      const wordRes = await api.getNextWord(
        this.sessionId,
        this.currentTeam.id,
        this.round,
        this.getShownWordIds()
      )

      if (wordRes.data.finished) {
        this.remainingWords = wordRes.data.remaining_words ?? this.remainingWords
        this.endTurnLocally()
        return { finished: true }
      }

      this.applyWordResponse(wordRes.data)

      return { finished: false }
    },

    endTurnLocally() {
      this.isTurnActive = false
      this.currentWord = null
      this.shownWordIds = []
      this.wordsGuessedThisTurn = 0
      this.turnTimeRemaining = null
      this.screen = 'correction'
    },

    async finishTurn(corrections) {
      const { data } = await api.finishTurn(this.sessionId, this.turnId, corrections)
      this.turnId = null
      this.currentTurnLog = []
      this.shownWordIds = []
      this.isTurnActive = false
      this.screen = 'waiting'

      await this.fetchGameState(this.sessionId)

      if (data.game_finished) {
        this.gameFinished = true
        this.screen = 'results'
      }

      return data
    },

    resetGame() {
      this.$reset()
    },
  },
})
