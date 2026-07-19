import bridge from '@vkontakte/vk-bridge'

let initialized = false
let user = null

function isVkEnvironment() {
  if (typeof window === 'undefined') return false
  const params = new URLSearchParams(window.location.search)
  if (
    params.has('vk_user_id') ||
    params.has('vk_app_id') ||
    params.has('vk_platform') ||
    Boolean(window.vkBridge)
  ) {
    return true
  }
  try {
    if (window.parent && window.parent !== window) {
      const ref = document.referrer || ''
      if (/vk\.(com|ru)|vk-apps\.com|vkontakte\.ru|vkuser\.net/i.test(ref)) {
        return true
      }
    }
  } catch {
    /* cross-origin */
  }
  return /vk\.(com|ru)|vk-apps\.com|vkontakte\.ru/i.test(document.referrer)
}

function applyVkDocumentClass(inVk) {
  if (typeof document === 'undefined') return
  document.documentElement.classList.toggle('vk-miniapps', Boolean(inVk))
  document.body?.classList.toggle('vk-miniapps', Boolean(inVk))
}

async function configureVkChrome() {
  try {
    await bridge.send('VKWebAppSetViewSettings', {
      status_bar_style: 'light',
      action_bar_color: '#050506',
      navigation_bar_color: '#050506',
    })
  } catch {
    /* desktop / unsupported */
  }

  try {
    await bridge.send('VKWebAppDisableSwipeBack')
  } catch {
    /* optional */
  }
}

export async function initVk() {
  if (initialized) {
    return { ok: true, user, inVk: isVkEnvironment() }
  }

  const inVk = isVkEnvironment()
  applyVkDocumentClass(inVk)

  try {
    await bridge.send('VKWebAppInit')
    initialized = true

    if (inVk) {
      await configureVkChrome()
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
    applyVkDocumentClass(false)
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
