import { defineStore } from 'pinia'
import api from '../services/api'
import {
  applyGuessDifficultyUpdate,
  pickNextWord,
} from '../services/turnEngine'
import {
  clearPendingFinish,
  loadPendingFinish,
  savePendingFinish,
} from '../services/turnSync'

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
    difficultyCycle: [],
    selectedDifficulties: [],
    /** Снимок неотгаданных слов на текущий ход */
    wordPool: [],
    timeLimit: 60,
    isTurnActive: false,
    turnTimeRemaining: null,
    currentWord: null,
    shownWordIds: [],
    remainingWords: 0,
    wordsGuessedThisTurn: 0,
    currentTurnLog: [],
    /** { word_id, award_team_id } | null — синхронизируется в finishTurn */
    pendingLastWord: null,
    turnId: null,
    gameFinished: false,
    finalScores: null,
    teams: [],
    skipPenalty: 2,
    lastWordCommon: true,
    screen: 'waiting',
    showRoundTransition: false,
    previousStatus: null,
    error: null,
    /** Ошибка отправки итогов хода (сеть) */
    syncError: null,
    /** null | 'starting' | 'finishing' — оверлей синхронизации */
    syncPhase: null,
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
      this.timeLimit = data.time_limit
      this.teams = data.teams || []
      if (data.settings) {
        this.skipPenalty = data.settings.skip_penalty ?? 2
        this.lastWordCommon = data.settings.last_word_common ?? true
        this.selectedDifficulties = data.settings.difficulties || []
      }
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

    /** Восстановить несохранённый finish после обрыва / F5 */
    hydrateFromPending(pending) {
      if (!pending) return false
      this.sessionId = Number(pending.sessionId)
      this.turnId = pending.turnId
      this.currentTurnLog = Array.isArray(pending.turnLog) ? pending.turnLog : []
      this.pendingLastWord = pending.lastWord || null
      this.syncError = 'Итоги хода не отправились — нажмите «Отправить снова»'
      this.isTurnActive = false
      this.screen = 'correction'
      return true
    },

    tryRestorePendingForSession(sessionId) {
      const pending = loadPendingFinish()
      if (!pending || Number(pending.sessionId) !== Number(sessionId)) {
        return false
      }
      return this.hydrateFromPending(pending)
    },

    getShownWordIds() {
      return this.shownWordIds.slice()
    },

    addShownWord(wordId) {
      if (wordId && !this.shownWordIds.includes(wordId)) {
        this.shownWordIds.push(wordId)
      }
    },

    applyLocalWord(word) {
      if (!word) {
        this.currentWord = null
        return
      }
      this.currentWord = {
        word_id: word.id,
        word_text: word.text,
        difficulty: word.difficulty,
        category: word.category,
      }
      this.addShownWord(word.id)
    },

    pickLocalNextWord() {
      const allowed =
        this.selectedDifficulties?.length
          ? this.selectedDifficulties
          : [...new Set(this.wordPool.map((w) => w.difficulty))].sort((a, b) => a - b)

      const word = pickNextWord(
        this.wordPool,
        this.teamDifficulty.currentDifficulty,
        allowed,
        this.getShownWordIds()
      )

      if (!word) {
        this.currentWord = null
        return null
      }

      this.applyLocalWord(word)
      return word
    },

    async startTurn() {
      this.syncPhase = 'starting'
      this.syncError = null
      try {
        const { data } = await api.startTurn(this.sessionId, this.currentPlayer.id)
        this.turnId = data.turn_id
        this.currentTurnLog = []
        this.shownWordIds = []
        this.wordsGuessedThisTurn = 0
        this.pendingLastWord = null
        this.wordPool = (data.words || []).map((w) => ({
          id: w.id,
          text: w.text,
          difficulty: w.difficulty,
          category: w.category,
        }))
        this.remainingWords = data.remaining_words ?? this.wordPool.length
        this.difficultyCycle = data.difficulty_cycle || []
        if (data.selected_difficulties?.length) {
          this.selectedDifficulties = data.selected_difficulties
        }

        if (data.team_difficulty_state) {
          const s = data.team_difficulty_state
          this.teamDifficulty = {
            currentDifficulty: s.current_difficulty ?? 1,
            wordsGuessedInCycle: s.words_guessed_in_cycle ?? 0,
            nextResetAt: s.next_reset_at ?? (this.difficultyCycle.length || 10),
          }
        }

        this.isTurnActive = true
        this.turnTimeRemaining = this.timeLimit
        this.screen = 'gameplay'

        const word = this.pickLocalNextWord()
        if (!word) {
          this.isTurnActive = false
          this.screen = 'correction'
        }
      } catch (err) {
        this.syncError =
          err.response?.data?.error ||
          err.message ||
          'Не удалось начать ход. Проверьте сеть.'
        this.screen = 'waiting'
        throw err
      } finally {
        this.syncPhase = null
      }
    },

    async submitAction(wordId, action) {
      this.addShownWord(wordId)

      const logEntry = {
        word_id: wordId,
        word_text: this.currentWord?.word_text,
        action,
        status: action === 'guess' ? 'guessed' : 'skipped',
      }
      this.currentTurnLog.push(logEntry)

      if (action === 'guess') {
        this.wordsGuessedThisTurn += 1
        this.wordPool = this.wordPool.filter((w) => w.id !== wordId)
        this.remainingWords = this.wordPool.length
        this.teamDifficulty = {
          ...applyGuessDifficultyUpdate(this.teamDifficulty, this.difficultyCycle),
        }
      }

      const word = this.pickLocalNextWord()
      if (!word) {
        await this.endTurnOrCorrect()
        return { finished: true }
      }

      return { finished: false }
    },

    endTurnLocally() {
      this.isTurnActive = false
      this.currentWord = null
      this.turnTimeRemaining = null
      this.screen = 'correction'
    },

    async endTurnOrCorrect() {
      if (!this.currentTurnLog.length && this.turnId && !this.pendingLastWord) {
        this.isTurnActive = false
        this.currentWord = null
        this.turnTimeRemaining = null
        await this.finishTurn([])
        return
      }
      this.endTurnLocally()
    },

    async resolveLastWord(teamId = null) {
      const word = this.currentWord
      if (!word || !this.turnId) {
        await this.endTurnOrCorrect()
        return { awarded: false }
      }

      this.pendingLastWord = {
        word_id: word.word_id,
        award_team_id: teamId,
      }

      if (teamId != null) {
        const team = this.teams.find((t) => t.id === teamId)
        if (team) {
          team.score += 1
        }
        this.wordPool = this.wordPool.filter((w) => w.id !== word.word_id)
        this.remainingWords = this.wordPool.length

        if (teamId === this.currentTeam?.id) {
          this.teamDifficulty = {
            ...applyGuessDifficultyUpdate(this.teamDifficulty, this.difficultyCycle),
          }
        }
      }

      await this.endTurnOrCorrect()
      return {
        awarded: teamId != null,
        awarded_team_id: teamId,
        remaining_words: this.remainingWords,
      }
    },

    buildActionsPayload() {
      return this.currentTurnLog.map((log) => ({
        word_id: log.word_id,
        action: log.action || (log.status === 'guessed' ? 'guess' : 'skip'),
      }))
    },

    async finishTurn(corrections) {
      const actions = this.buildActionsPayload()
      const lastWord = this.pendingLastWord
      const turnId = this.turnId

      savePendingFinish({
        sessionId: this.sessionId,
        turnId,
        actions,
        corrections: corrections || [],
        lastWord,
        turnLog: this.currentTurnLog.slice(),
      })

      this.syncPhase = 'finishing'
      try {
        const { data } = await api.finishTurn(
          this.sessionId,
          turnId,
          corrections,
          actions,
          lastWord
        )

        clearPendingFinish()
        this.syncError = null
        this.turnId = null
        this.currentTurnLog = []
        this.shownWordIds = []
        this.wordPool = []
        this.pendingLastWord = null
        this.isTurnActive = false

        this.applyState(data)

        if (data.game_finished || data.status === 'finished') {
          this.gameFinished = true
          this.screen = 'results'
        } else {
          this.screen = 'waiting'
        }

        return data
      } catch (err) {
        this.syncError =
          err.response?.data?.error ||
          err.message ||
          'Не удалось отправить итоги. Проверьте сеть и нажмите ещё раз.'
        this.screen = 'correction'
        throw err
      } finally {
        this.syncPhase = null
      }
    },

    leaveToSetup() {
      clearPendingFinish()
      this.$reset()
    },

    resetGame() {
      clearPendingFinish()
      this.$reset()
    },
  },
})
