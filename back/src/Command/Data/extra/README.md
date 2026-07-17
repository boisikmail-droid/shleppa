# Extra words (append-only)

Put new words here without wiping the main dictionary:

```
extra/{category}/level_{1..6}.php
```

Categories: clothes, furniture, profession, animals, school, celebrities,
movies, feelings, food, sport, tech, nature.

Each file:

```php
<?php
return [
    'новое слово',
    'ещё одно',
];
```

Import (does **not** clear word_pool):

```bash
php bin/console app:import-extra-words
# or
php bin/console app:import-extra-words --dir=/path/to/extra
```

Full rebuild of the main dictionary:

```bash
php bin/console app:import-words
```
