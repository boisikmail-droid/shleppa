const males = Array.from({ length: 15 }, (_, i) => {
  const n = String(i + 1).padStart(2, '0')
  return {
    id: `m${n}`,
    gender: 'm',
    label: `Парень ${i + 1}`,
    src: `/avatars/avatar-m${n}.png`,
  }
})

const females = Array.from({ length: 15 }, (_, i) => {
  const n = String(i + 1).padStart(2, '0')
  return {
    id: `f${n}`,
    gender: 'f',
    label: `Девушка ${i + 1}`,
    src: `/avatars/avatar-f${n}.png`,
  }
})

export const AVATARS = [...males, ...females]

export const AVATAR_MALES = males
export const AVATAR_FEMALES = females

const byId = Object.fromEntries(AVATARS.map((a) => [a.id, a]))

export function getAvatar(avatarId) {
  return byId[avatarId] || byId.m01
}

export function pickAvatarIds(count, exclude = []) {
  const blocked = new Set(exclude)
  const pool = AVATARS.map((a) => a.id).filter((id) => !blocked.has(id))
  const shuffled = pool.slice().sort(() => Math.random() - 0.5)
  const result = []
  for (const id of shuffled) {
    if (result.length >= count) break
    result.push(id)
  }
  while (result.length < count) {
    result.push(AVATARS[result.length % AVATARS.length].id)
  }
  return result
}

export function pickOneAvatarId(exclude = []) {
  return pickAvatarIds(1, exclude)[0]
}
