import { spawn } from 'node:child_process'
import { existsSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const frontRoot = join(dirname(fileURLToPath(import.meta.url)), '..')
const isWindows = process.platform === 'win32'
const inDocker = existsSync('/.dockerenv')

if (inDocker) {
  console.error('Туннель нельзя запускать внутри Docker-контейнера.')
  console.error('Выйди из контейнера и на хосте выполни:')
  console.error('  cd C:\\vibe\\hat\\front')
  console.error('  npm run tunnel')
  process.exit(1)
}

const localBin = join(
  frontRoot,
  'bin',
  isWindows ? 'cloudflared.exe' : 'cloudflared',
)
const args = ['tunnel', '--protocol', 'http2', '--url', 'http://127.0.0.1:5173']
const command = existsSync(localBin) ? localBin : 'cloudflared'

if (!existsSync(localBin)) {
  console.log('Локальный front/bin/cloudflared не найден — пробую cloudflared из PATH.')
  if (isWindows) {
    console.log('Скачай cloudflared-windows-amd64.exe → front/bin/cloudflared.exe')
  } else {
    console.log('Установи cloudflared в PATH или положи бинарник в front/bin/cloudflared')
  }
  console.log('https://github.com/cloudflare/cloudflared/releases/latest')
  console.log('')
}

const child = spawn(command, args, { stdio: 'inherit', shell: false })
child.on('error', (err) => {
  console.error('Не удалось запустить cloudflared:', err.message)
  if (isWindows) {
    console.error('Положи cloudflared.exe в C:\\vibe\\hat\\front\\bin\\')
  }
  process.exit(1)
})
child.on('exit', (code) => process.exit(code ?? 1))
