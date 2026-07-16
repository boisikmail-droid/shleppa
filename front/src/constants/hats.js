export const HATS = [
  { id: 'tophat', label: 'Цилиндр', src: '/hats/hat-tophat.png' },
  { id: 'space', label: 'Скафандр', src: '/hats/hat-space.png' },
  { id: 'knight', label: 'Рыцарь', src: '/hats/hat-knight.png' },
  { id: 'hardhat', label: 'Каска', src: '/hats/hat-hardhat.png' },
  { id: 'sombrero', label: 'Сомбреро', src: '/hats/hat-sombrero.png' },
  { id: 'wizard', label: 'Волшебник', src: '/hats/hat-wizard.png' },
  { id: 'cowboy', label: 'Ковбой', src: '/hats/hat-cowboy.png' },
  { id: 'moto', label: 'Мотошлем', src: '/hats/hat-moto.png' },
  { id: 'chef', label: 'Повар', src: '/hats/hat-chef.png' },
  { id: 'ushanka', label: 'Ушанка', src: '/hats/hat-ushanka.png' },
  { id: 'crown', label: 'Корона', src: '/hats/hat-crown.png' },
  { id: 'diving', label: 'Водолаз', src: '/hats/hat-diving.png' },
  { id: 'viking', label: 'Викинг', src: '/hats/hat-viking.png' },
  { id: 'jester', label: 'Шут', src: '/hats/hat-jester.png' },
  { id: 'safari', label: 'Сафари', src: '/hats/hat-safari.png' },
  { id: 'fire', label: 'Пожарный', src: '/hats/hat-fire.png' },
  { id: 'cap', label: 'Кепка', src: '/hats/hat-cap.png' },
  { id: 'beanie', label: 'Шапка', src: '/hats/hat-beanie.png' },
  { id: 'pirate', label: 'Пират', src: '/hats/hat-pirate.png' },
  { id: 'police', label: 'Полиция', src: '/hats/hat-police.png' },
  { id: 'bike', label: 'Велошлем', src: '/hats/hat-bike.png' },
  { id: 'grad', label: 'Выпускник', src: '/hats/hat-grad.png' },
  { id: 'fedora', label: 'Федора', src: '/hats/hat-fedora.png' },
  { id: 'santa', label: 'Санта', src: '/hats/hat-santa.png' },
  { id: 'samurai', label: 'Самурай', src: '/hats/hat-samurai.png' },
  { id: 'bowler', label: 'Котелок', src: '/hats/hat-bowler.png' },
  { id: 'welder', label: 'Сварщик', src: '/hats/hat-welder.png' },
  { id: 'nurse', label: 'Медсестра', src: '/hats/hat-nurse.png' },
  { id: 'pharaoh', label: 'Фараон', src: '/hats/hat-pharaoh.png' },
  { id: 'hockey', label: 'Хоккей', src: '/hats/hat-hockey.png' },
  { id: 'bucket', label: 'Панама', src: '/hats/hat-bucket.png' },
  { id: 'propeller', label: 'Пропеллер', src: '/hats/hat-propeller.png' },
  // humorous / themed pack
  { id: 'pickle-tophat', label: 'Огурец в цилиндре', src: '/hats/hat-pickle-tophat.png' },
  { id: 'pickle-helm', label: 'Огуречный шлем', src: '/hats/hat-pickle-helm.png' },
  { id: 'pickle-hard', label: 'Огуречная каска', src: '/hats/hat-pickle-hard.png' },
  { id: 'sith', label: 'Тёмный лорд', src: '/hats/hat-sith.png' },
  { id: 'trooper', label: 'Штурмовик', src: '/hats/hat-trooper2.png' },
  { id: 'goblin', label: 'Зелёный мудрец', src: '/hats/hat-goblin.png' },
  { id: 'robot', label: 'Золотой дроид', src: '/hats/hat-robot.png' },
  { id: 'plumber', label: 'Сантехник', src: '/hats/hat-plumber.png' },
  { id: 'spikes', label: 'Иглы', src: '/hats/hat-spikes.png' },
  { id: 'sorting', label: 'Распределяющая', src: '/hats/hat-sorting.png' },
  { id: 'tinfoil', label: 'Фольга', src: '/hats/hat-tinfoil.png' },
  { id: 'banana', label: 'Банан', src: '/hats/hat-banana.png' },
  { id: 'frog', label: 'Лягушка', src: '/hats/hat-frog.png' },
  { id: 'mushroom', label: 'Гриб', src: '/hats/hat-mushroom.png' },
  { id: 'catears', label: 'Ушки', src: '/hats/hat-catears.png' },
  { id: 'cone', label: 'Конус', src: '/hats/hat-cone.png' },
  { id: 'duck', label: 'Уточка', src: '/hats/hat-duck.png' },
  { id: 'pizza', label: 'Пицца', src: '/hats/hat-pizza.png' },
  { id: 'ice', label: 'Ледяная корона', src: '/hats/hat-ice.png' },
  { id: 'racer', label: 'Гонщик', src: '/hats/hat-racer.png' },
]

const byId = Object.fromEntries(HATS.map((h) => [h.id, h]))

export function getHat(hatId) {
  return byId[hatId] || byId.tophat
}

export function pickHatIds(count, exclude = []) {
  const blocked = new Set(exclude)
  const pool = HATS.map((h) => h.id).filter((id) => !blocked.has(id))
  const shuffled = pool.slice().sort(() => Math.random() - 0.5)
  const result = []
  for (const id of shuffled) {
    if (result.length >= count) break
    result.push(id)
  }
  while (result.length < count) {
    result.push(HATS[result.length % HATS.length].id)
  }
  return result
}

export function pickOneHatId(exclude = []) {
  return pickHatIds(1, exclude)[0]
}
