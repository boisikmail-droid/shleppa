import bridge from '@vkontakte/vk-bridge'

let initialized = false
let user = null

function isVkEnvironment() {
  if (typeof window === 'undefined') return false
  const params = new URLSearchParams(window.location.search)
  return (
    params.has('vk_user_id') ||
    params.has('vk_app_id') ||
    Boolean(window.vkBridge) ||
    /vk\.(com|ru)|vk-apps\.com|vkontakte\.ru/i.test(document.referrer)
  )
}

export async function initVk() {
  if (initialized) {
    return { ok: true, user, inVk: isVkEnvironment() }
  }

  const inVk = isVkEnvironment()

  try {
    await bridge.send('VKWebAppInit')
    initialized = true

    if (inVk) {
      try {
        const data = await bridge.send('VKWebAppGetUserInfo')
        user = {
          id: data.id,
          firstName: data.first_name || '',
          lastName: data.last_name || '',
          photo: data.photo_200 || data.photo_100 || '',
          displayName: [data.first_name, data.last_name].filter(Boolean).join(' ').trim(),
        }
      } catch {
        // Outside VK or permission denied — party mode still works
      }
    }

    return { ok: true, user, inVk }
  } catch (err) {
    // Browser / Docker without VK iframe — game works as usual
    initialized = true
    return { ok: false, user: null, inVk: false, error: err }
  }
}

export function getVkUser() {
  return user
}

export function isRunningInVk() {
  return isVkEnvironment()
}

export { bridge }
