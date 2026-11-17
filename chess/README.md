# Chess for Cotonti

A chess plugin for **Cotonti 1.0 CMF**: play against the computer or another registered user. The pieces are rendered entirely with CSS (no images or font glyphs). Features include chess clocks, chat between human players, a humorous “commentator,” and a “car horn” that wakes up a player who has “fallen asleep” during their turn.

---

[![Version](https://img.shields.io/badge/version-1.1.1-green.svg)](https://github.com/webitproff/chess-cotonti/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-v.1-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/chess-cotonti/blob/main/LICENSE)

---

- **Plugin code:** `chess`
- **Version:** 1.1.1 (2026-11-17)
- **Author:** webitproff
- **License:** BSD
- **[Download source code](https://github.com/webitproff/chess-cotonti)**

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Access Permissions](#access-permissions)
4. [Plugin Settings](#plugin-settings)
5. [Pages and URLs](#pages-and-urls)
6. [File Structure](#file-structure)
7. [How to Play](#how-to-play)
8. [Computer Opponent](#computer-opponent)
9. [Chess Clocks](#chess-clocks)
10. [Chat](#chat)
11. [Sounds](#sounds)
12. [Wake Up — Car Horn](#wake-up--car-horn)
13. [Statistics and Rating](#statistics-and-rating)
14. [Game Completion and Draws](#game-completion-and-draws)
15. [AJAX API](#ajax-api)
16. [Database Tables](#database-tables)
17. [Admin Tools](#admin-tools)
18. [Security and CSRF](#security-and-csrf)
19. [Known Features and Limitations](#known-features-and-limitations)

---

## Requirements

- **Cotonti** 1.0.x, PHP **8.5+**, MySQL **8.4+** (or compatible MariaDB).
- A **Bootstrap 5.3** site theme (modals and toasts degrade gracefully if `bootstrap.bundle.js` is unavailable).
- Write permissions for the `plugins/chess/` directory.

---

## Installation

1. Copy the `plugins/chess` directory into your site's `plugins/` directory.
2. Go to **Administration → Extensions → Chess → Install**.
3. Add a link to the game anywhere on your site, for example:

```html
<!-- IF {PHP|cot_plugin_active('chess')} -->
<li>
    <a class="nav-link" href="{PHP|cot_url('chess')}" title="{PHP.L.chess_desc}">
        <span class="me-2">
            <i class="fa-solid fa-chess"></i>
        </span>{PHP.L.chess_title}
    </a>
</li>
<!-- ENDIF -->
```

---

## Access Permissions

Authentication is required to play and use the API (`Cot::$usr['id'] > 0`). Otherwise, AJAX requests return `err_auth`.

---

## Plugin Settings

| Key | Values | Default | Description |
|---|---|---|---|
| `max_active_games` | 1, 2, 3, 5, 10 | **3** | Maximum number of active or waiting games per player |
| `ai_level` | `easy`, `normal`, `hard` | **normal** | Default computer difficulty |
| `abandon_hours` | 1, 6, 24, 72, 168 | **72** | Close inactive games after N hours (status `finished`, reason `abandon`) |
| `chat_enabled` | 0 / 1 | **1** | Enable chat between real players in PvP games |
| `chat_cooldown` | 3, 5, 10, 15 | **5** | Minimum interval between messages from the same user (seconds) |
| `idle_enabled` | 0 / 1 | **1** | Enable the car horn and joke when a player is inactive during their turn |
| `idle_seconds` | 5, 10, 15, 30, 60 | **30** | Number of seconds of inactivity before the first alert |

> The JavaScript client has fallback values (e.g. `idleSeconds || 5`), but these are only used if the setting is not provided. After installation, the configured default value is used.

---

## Pages and URLs

| Mode | URL | Description |
|---|---|---|
| `home` | `index.php?e=chess` | Lobby: create a game, view open games, my games, statistics, and top 10 players |
| `my` | `index.php?e=chess&m=my` | Server-rendered list of my games (without JS), with Continue and Cancel/Resign buttons |
| `play` | `index.php?e=chess&m=play&id=N` | The game itself: board, clocks, chat, move log, help, and rules |

Admin panel: **Administration → Other → Chess** (`admin.php?m=other&p=chess`).

CSS and JavaScript are loaded only for `home` and `play` modes. The `my` mode does not require the chessboard.

---

## File Structure

```text
plugins/chess/
├── chess.setup.php            # Plugin metadata and configuration
├── chess.php                  # Standalone router: home / my / play
├── chess.ajax.php             # JSON API (hook ajax)
├── chess.admin.php            # Admin tools (hook tools)
├── setup/
│   └── chess.install.sql      # Tables cot_chess_games / _stats / _chat
├── inc/
│   ├── chess.engine.php       # Rules: FEN, move generation, SAN, game endings
│   ├── chess.ai.php           # Computer opponent: alpha-beta + PST
│   ├── chess.functions.php    # Game flow, clocks, chat, rating, lobby, payload
│   ├── chess.home.php         # Home mode
│   ├── chess.my.php           # My games mode
│   └── chess.play.php         # Play mode
├── js/
│   └── chess.js               # Client: board, lobby, clocks, chat, sounds, nag
├── css/
│   └── chess.css              # Styles, pieces rendered with clip-path
├── tpl/
│   ├── chess.home.tpl
│   ├── chess.my.tpl
│   ├── chess.play.tpl
│   └── chess.admin.tpl
└── lang/
    ├── chess.ru.lang.php
    └── chess.en.lang.php
```

---

## How to Play

1. Open the lobby at `index.php?e=chess` (or `chess`).
2. Choose your color (**Random / White / Black**), time control, and (when playing against the computer) difficulty.
3. Click **Play Against Computer** or **Create Game for Another Player**.
   - In PvP mode, the game is created with the `waiting` status.
   - Another player will see it under Open Games and click **Join**, changing the game status to `active`.
4. To make a move, **click a piece, then click the destination square**. Visual hints:
   - A dot on an empty square indicates a legal move.
   - A ring around a piece indicates a possible capture.
   - A red glow beneath the king indicates check.
   - The last move is highlighted.
5. Pawn promotion opens a selection dialog: **Q / R / B / N**.
6. Available buttons: **Flip Board**, **Resign** (double confirmation), **Offer Draw** (PvP), **Lobby**, and **Sound On/Off**.

The **server is solely responsible for enforcing the rules**. The client sends only `from/to/promo` and renders the state returned by the server (legal moves, check, game completion). Illegal moves are rejected (`err_illegal`).

---

## Computer Opponent

Implemented in `inc/chess.ai.php` using alpha-beta search, material evaluation, and piece-square tables.

| Difficulty | Behaviour |
|---|---|
| `easy` | One ply + random noise ±90 |
| `normal` | Search depth of 2 plies + capture quiescence (up to 3), 1.0-second budget |
| `hard` | Iterative deepening up to 4 plies, quiescence up to 4, 2.0-second budget |

- Time cutoff (`$ctx['dl']`) and interruption via the `abort` flag.
- Move ordering: captures sorted by MVV-LVA, promotions prioritized.
- Forced checkmate is evaluated as `100000 - ply`.
- `easy` works differently from `normal`/`hard`: `easy` evaluates all legal moves with random noise, while `normal`/`hard` use iterative deepening and retain the best move between iterations.

If the computer plays White (`white_id = 0`), its first move is made **on the server when the game is created**.

---

## Chess Clocks

- **Time control** uses the `M+I` format (minutes + increment in seconds per move). Available lobby presets: `0+0`, `1+0`, `3+2`, `5+0`, `10+0`, `15+10`, `30+0`.
- `tc_base` is stored in **seconds**, `tc_inc` in seconds, and `clock_*` in **milliseconds**.
- Clocks **do not start** until both players have made their first move (`ply >= 2`). This is the “first move is free” rule.
- After each move, the increment is added to the remaining time (`tc_inc * 1000`).
- Flag-fall check: when time expires, `chess_check_flag` is called. The result is:
  - If the **opponent** has insufficient material to checkmate, the game is a **draw** (`1/2-1/2`, `timeout`).
  - Otherwise, the player who ran out of time loses.
- On the client:
  - `is-low` (flashing) — less than 20 seconds remaining.
  - Tick sound — during the last 10 seconds.
  - Low-time warning — 30 seconds before flag fall.
- In `0+0` mode (no clocks), `∞` is displayed.

---

## Chat

- Available only in **PvP** games while the game status is `active`. Chat is not displayed in games against the computer or after a game has ended.
- Maximum message length: **255 characters** (`chess_chat_max`).
- Message cooldown per user is controlled by the settings (**5 seconds** by default).
- The **200 most recent messages** are retained per game; older messages are deleted.
- When a game ends, `chess_finish()` calls `chess_chat_purge()` to delete its chat history.
- Additionally, `chess_chat_sweep()` (called from `chess_cleanup()`) removes chat from finished and orphaned games.
- The client polls for new messages every **2.5 seconds** (every 8 seconds in an inactive browser tab).
- Sounds for sending and receiving messages use a radio-style effect (Quindar tones).

---

## Sounds

All sounds are generated using the **Web Audio API**, with no external audio files. The on/off state is stored in `localStorage` (`cx_sound`).

Available sounds: `select`, `move`, `capture`, `castle`, `check`, `promote`, `illegal`, `tick`, `start`, `win`, `lose`, `draw`, `horn`, `send`, `recv`.

The `AudioContext` is created or resumed on `pointerdown` (as required by browsers).

A **Sound** button is available in both the lobby and the game.

---

## Wake Up — Car Horn

If it is **your turn** and you remain inactive, the client plays a “beep-beep” horn (two tones at 400 + 500 Hz) after `idle_seconds` and displays a joke in the move log/toast.

Repeated alerts are delayed as follows: `need = idle_seconds + nagCount * 15`, with **up to 4 reminders**. The third and fourth reminders use the later-stage phrases (`phr_nag_late`).

The nag timer resets after every move (`touch()` in `applyState`/`onSquare`).

Additional reminders:
- In **unrated PvP** (without clocks, with moves already made), a gentle reminder (`phr_opp_slow`) appears after **90 seconds** of waiting for the opponent.
- A low-time warning (`phr_low_time`) appears 30 seconds before flag fall.

This feature can be disabled using `idle_enabled = 0`.

---

## Statistics and Rating

The `cot_chess_stats` table contains one row per player:

- `wins / losses / draws` — PvP results.
- `ai_wins / ai_losses / ai_draws` — results against the computer.
- `rating` — PvP rating, starting at **1000**.

PvP ratings are calculated using the Elo formula with `K = 24`:

```text
expected = 1 / (1 + 10^((rating_opp − rating_me) / 400))
delta    = round(24 * (score − expected))
rating   = max(0, rating + delta)
```

- Games against the computer **do not affect the rating**; only `ai_*` statistics are updated.
- Games ending with the `abandon` reason are **excluded from statistics**.
- The lobby's top 10 and the admin panel's top 15 are sorted by `rating DESC, wins DESC`, provided `wins+losses+draws > 0`.

---

## Game Completion and Draws

`chess_game_over()` (engine) automatically ends a game under the following conditions:

| Condition | `result` | `finish_reason` |
|---|---|---|
| No legal moves + check | `0-1` / `1-0` | `checkmate` |
| No legal moves without check | `1/2-1/2` | `stalemate` |
| Insufficient material (king only; king + bishop/knight; bishops on same-colored squares only) | `1/2-1/2` | `insufficient` |
| 50 moves without captures or pawn moves | `1/2-1/2` | `fifty` |
| Threefold repetition | `1/2-1/2` | `repetition` |
| Draw by agreement (PvP) | `1/2-1/2` | `agreement` |
| Resignation | `1-0` / `0-1` | `resign` |
| Time expired | `0-1` / `1-0` (or draw — see [Chess Clocks](#chess-clocks)) | `timeout` |
| Inactivity exceeding `abandon_hours` | — (not recorded in statistics) | `abandon` |

**Stalemate** and **positional draws** are detected on the server before the response is sent to the client.

Draw by agreement: White sets `draw_offer = 1`, Black sets it to `2`. Any move clears the draw offer.

---

## AJAX API

Single endpoint: `plugins/chess/chess.ajax.php` (`?e=chess&r=chess`), POST method, `action` field.

All requests require:
- Authentication.
- Parameter `x` = `Cot::$sys['xk']` (CSRF token).

| `action` | Parameters | Returns |
|---|---|---|
| `lobby` | — | `open`, `mine`, `top`, `stats` |
| `create` | `mode` (ai/pvp), `color` (w/b/r), `tc` (`M+I`), `level` | `game_id` |
| `join` | `game_id` | `game_id` |
| `move` | `game_id`, `from`, `to`, `promo` | Full game `payload` |
| `state` | `game_id`, `rev` | `payload` or `{unchanged:true,...}` |
| `resign` | `game_id` | `payload` / `{deleted:true}` |
| `draw` | `game_id`, `do` (offer/accept/decline) | `payload` |
| `chatsend` | `game_id`, `msg` | `chat` |
| `chatget` | `game_id`, `after` | `items` |

Response: `application/json; charset=utf-8`, `Cache-Control: no-store`.

Error codes (keys `chess_err_*`): `auth`, `no_game`, `access`, `own_game`, `limit`, `coords`, `illegal`, `promo`, `not_your_turn`, `draw`, `unknown`, `network`, `chat_off`, `chat_empty`, `chat_long`, `chat_closed`, `chat_wait`.

---

## Database Tables

Installed from `setup/chess.install.sql` (`ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`).

### `cot_chess_games`

| Field | Type | Description |
|---|---|---|
| `game_id` | INT PK AI | Identifier |
| `white_id` / `black_id` | INT NULL | `NULL` — open player slot, `0` — computer |
| `is_ai` | TINYINT(1) | 1 — game against the computer |
| `ai_level` | VARCHAR(8) | easy / normal / hard |
| `fen` | VARCHAR(120) | Current position |
| `hist` | MEDIUMTEXT | Position-key history (JSON) for repetition detection |
| `moves` | MEDIUMTEXT | Move log (JSON) |
| `status` | ENUM | `waiting` / `active` / `finished` |
| `result` | VARCHAR(8) | `1-0` / `0-1` / `1/2-1/2` / `''` |
| `finish_reason` | VARCHAR(16) | See [Game Completion](#game-completion-and-draws) |
| `winner_id` | INT NULL | Winner (NULL for a draw) |
| `tc_base` / `tc_inc` | INT | Seconds |
| `clock_w` / `clock_b` | BIGINT | Remaining time, ms |
| `clock_ts` | BIGINT | Last clock start timestamp, ms |
| `draw_offer` | TINYINT | 0 / 1 (White) / 2 (Black) |
| `rev` | INT | Revision (for `state`) |
| `created_at` / `updated_at` | DATETIME | |

Indexes: `status`, `white`, `black`, `updated_at`.

### `cot_chess_stats`

`user_id` PK, `wins`, `losses`, `draws`, `ai_wins`, `ai_losses`, `ai_draws`, `rating` (default 1000).

### `cot_chess_chat`

`msg_id` PK AI, `game_id`, `user_id`, `message` VARCHAR(255), `created_ts` INT.

Indexes: `game_msg (game_id, msg_id)`, `user_ts (user_id, created_ts)`.

---

## Admin Tools

`chess.admin.php` (hook `tools`, accessible only to `Cot::$usr['isadmin']`):

- Game list with filters: `all` / `waiting` / `active` / `finished`.
- Delete an individual game (including its chat).
- Bulk cleanup of all finished games (`purge_finished`).
- Pagination (30 per page).
- Top 15 players by rating.

All actions are protected by `cot_check_xg()` (CSRF). Buttons use `data-confirm` with `window.confirm()`.

---

## Security and CSRF

- `defined('COT_CODE') or die('Wrong URL.')` in every PHP file.
- `cot_auth('plug', 'chess')` on pages, `cot_block()` when read access is denied.
- AJAX: mandatory `x` = `Cot::$sys['xk']` (or `xk_prev`).
- Mutating GET actions (`cancel`, `resign` from `my`, `delete`, `purge` in the admin panel) are protected by `cot_check_xg()`.
- Transactions with row locking via `SELECT … FOR UPDATE` are used when creating/joining games, making moves, resigning, handling draws, and sending chat messages (to serialize rate-limit checks).
- All SQL queries use prepared statements or `$db->quote()`.
- `chess_chat_clean()` removes control characters and normalizes whitespace; message length is limited by `chess_chat_max()`.

---

## Known Features and Limitations

- **Clocks do not start** until both players have made their first move (the first move is free).
- **The AI thinks on the server** — response time for a computer move is limited by its time budget (`hard` ≈ 2 seconds, `normal` ≈ 1 second).
- **The JavaScript client does not enforce chess rules** — it only renders server responses. An illegal click simply clears the selection and plays the `illegal` sound.
- **`state` with `rev`**: the client may receive `{unchanged:true}` to save bandwidth; however, while clocks are running, the server still returns current `clock` values.
- **Position repetitions** are calculated using the first 4 FEN fields (`chess_pos_key`).
- **Time forfeits**: the server calls `chess_check_flag` from `state` and `move`, so time expiration is recorded even if the client is not watching.
- **Games exceeding `abandon_hours`** are closed whenever the lobby or a game is accessed (`chess_cleanup` in `chess_create_game`, `chess_join_game`, `chess_lobby`, `my`).
- **`0+0`** means no clocks; `∞` is displayed on the board.

---

## License and Author

- **Author:** webitproff
- **License:** BSD
- **[Source code / Updates](https://github.com/webitproff/chess-cotonti)**

___

Русский

# Chess для Cotonti

Плагин шахмат для CMF **Cotonti 1.0**: игра против компьютера или другого зарегистрированного пользователя. Фигуры нарисованы на чистом CSS (без картинок и шрифтовых глифов), есть шахматные часы, чат между реальными игроками, шуточный «комментатор» и «автомобильный сигнал», который будит игрока, если он «уснул» на своём ходу.
---

[![Version](https://img.shields.io/badge/version-1.1.1-green.svg)](https://github.com/webitproff/chess-cotonti/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-v.1-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/chess-cotonti/blob/main/LICENSE)

---
- **Код плагина:** `chess`
- **Версия:** 1.1.1 (2026-11-17)
- **Автор:** webitproff
- **Лицензия:** BSD
- **[Скачать исходный код](https://github.com/webitproff/chess-cotonti)** 

---

## Содержание

1. [Требования](#требования)
2. [Установка](#установка)
3. [Права доступа](#права-доступа)
4. [Настройки плагина](#настройки-плагина)
5. [Страницы и URL](#страницы-и-url)
6. [Структура файлов](#структура-файлов)
7. [Как играть](#как-играть)
8. [Компьютерный соперник](#компьютерный-соперник)
9. [Часы](#часы)
10. [Чат](#чат)
11. [Звуки](#звуки)
12. [«Разбудить» — автомобильный сигнал](#разбудить--автомобильный-сигнал)
13. [Статистика и рейтинг](#статистика-и-рейтинг)
14. [Окончание партии и ничьи](#окончание-партии-и-ничьи)
15. [AJAX API](#ajax-api)
16. [Таблицы БД](#таблицы-бд)
17. [Админ-инструменты](#админ-инструменты)
18. [Безопасность и CSRF](#безопасность-и-csrf)
19. [Известные особенности](#известные-особенности)

---

## Требования

- **Cotonti** 1.0.x, PHP **8.5+**, MySQL **8.4+** (или совместимая MariaDB)
- Тема сайта на **Bootstrap 5.3** (модальные окна и тосты деградируют корректно, если `bootstrap.bundle.js` отсутствует)
- Права на запись в каталог `plugins/chess/`

---

## Установка

1. Скопируйте папку `plugins/chess` в `plugins/` вашего сайта.
2. **Админ → Расширения → Chess → Установить.**
3. Добавьте ссылку на игру в любом месте сайта примерно такого вида:
```html
<!-- IF {PHP|cot_plugin_active('chess')} -->
<li>
	<a class="nav-link" href="{PHP|cot_url('chess')}" title="{PHP.L.chess_desc}">
		<span class="me-2">
			<i class="fa-solid fa-chess"></i>
		</span>{PHP.L.chess_title} 
	</a>
</li>
<!-- ENDIF -->
```

---

## Права доступа

Для игры и API требуется авторизация (`Cot::$usr['id'] > 0`) — иначе AJAX возвращает `err_auth`.

---

## Настройки плагина

| Ключ               | Значения                         | По умолчанию | Описание |
|--------------------|----------------------------------|--------------|----------|
| `max_active_games` | 1, 2, 3, 5, 10                   | **3**        | Максимум активных/ожидающих партий у одного игрока |
| `ai_level`         | `easy`, `normal`, `hard`         | **normal**   | Уровень компьютера по умолчанию |
| `abandon_hours`    | 1, 6, 24, 72, 168                | **72**       | Закрывать неактивные партии через N часов (статус `finished`, причина `abandon`) |
| `chat_enabled`     | 0 / 1                            | **1**        | Чат между реальными игроками в PvP |
| `chat_cooldown`    | 3, 5, 10, 15                     | **5**        | Минимальный интервал между сообщениями одного пользователя (сек.) |
| `idle_enabled`     | 0 / 1                            | **1**        | Автомобильный сигнал и шутка, если игрок «уснул» на своём ходу |
| `idle_seconds`     | 5, 10, 15, 30, 60                | **30**       | Через сколько секунд бездействия сигналить (первый раз) |

> В JS-клиенте есть резервные значения (например, `idleSeconds || 5`), но применяются они только если настройка не задана. После установки по умолчанию используется значение из конфига.

---

## Страницы и URL

| Режим  | URL                              | Что показывает |
|--------|----------------------------------|----------------|
| `home` | `index.php?e=chess`              | Лобби: создать игру, открытые партии, мои партии, статистика, топ-10 |
| `my`   | `index.php?e=chess&m=my`         | Серверный список моих партий (без JS), кнопки «Продолжить» / «Отменить/Сдаться» |
| `play` | `index.php?e=chess&m=play&id=N`  | Сама партия, доска, часы, чат, лог, справка и правила |

Админка: **Админ → Прочее → Chess** (`admin.php?m=other&p=chess`).

CSS и JS подключаются только для `home` и `play` (в `my` доска не нужна).

---

## Структура файлов

```
plugins/chess/
├── chess.setup.php            # Метаданные плагина и конфигурация
├── chess.php                  # Standalone-роутер: home / my / play
├── chess.ajax.php             # JSON API (hook ajax)
├── chess.admin.php            # Админ-инструменты (hook tools)
├── setup/
│   └── chess.install.sql      # Таблицы cot_chess_games / _stats / _chat
├── inc/
│   ├── chess.engine.php       # Правила: FEN, генерация ходов, SAN, окончания
│   ├── chess.ai.php           # Компьютер: alpha-beta + PST
│   ├── chess.functions.php    # Поток игры, часы, чат, рейтинг, лобби, payload
│   ├── chess.home.php         # Режим home
│   ├── chess.my.php           # Режим my
│   └── chess.play.php         # Режим play
├── js/
│   └── chess.js               # Клиент: доска, лобби, часы, чат, звуки, «наг»
├── css/
│   └── chess.css              # Стили, фигуры на clip-path
├── tpl/
│   ├── chess.home.tpl
│   ├── chess.my.tpl
│   ├── chess.play.tpl
│   └── chess.admin.tpl
└── lang/
    ├── chess.ru.lang.php
    └── chess.en.lang.php
```

---

## Как играть

1. Откройте лобби `index.php?e=chess` (or `chess`).
2. Выберите цвет (`Случайный` / `Белые` / `Чёрные`), контроль времени и (для игры с компьютером) сложность.
3. Нажмите **«Играть с компьютером»** или **«Создать партию для игрока»**.
   - В режиме PvP партия создаётся со статусом `waiting`.
   - Другой участник увидит её в «Открытые партии» и нажмёт **«Присоединиться»** — партия станет `active`.
4. Ход делается **кликом по фигуре и кликом по целевой клетке**. Подсказки:
   - точка на пустой клетке — можно пойти,
   - кольцо вокруг фигуры — можно взять,
   - красное свечение под королём — шах,
   - подсветка последнего хода.
5. Превращение пешки — всплывающий выбор **Q / R / B / N**.
6. Кнопки: **Повернуть доску**, **Сдать** (двойное подтверждение), **Предложить ничью** (PvP), **В лобби**, **Звук вкл/выкл**.

Правила игры полностью определяет **сервер**: клиент отправляет только `from/to/promo`, а рисует то, что вернул сервер (легальные ходы, шах, окончание). Некорректный ход отвергается (`err_illegal`).

---

## Компьютерный соперник

Реализован в `inc/chess.ai.php` — alpha-beta + материал + piece-square таблицы.

| Уровень  | Поведение |
|----------|-----------|
| `easy`   | Один полуход + случайный шум ±90 |
| `normal` | Глубина 2 полухода + «квиесценс» взятий (до 3), бюджет 1.0 с |
| `hard`   | Итеративное углубление до 4 полуходов, «квиесценс» до 4, бюджет 2.0 с |

- Отсечение по времени (`$ctx['dl']`), прерывание по флагу `abort`.
- Упорядочивание ходов: взятия по MVV-LVA, превращения первыми.
- Принудительный мат оценивается как `100000 - ply`.
- `easy` и `normal`/`hard` работают по-разному: `easy` перебирает все легальные ходы с шумом, а `normal`/`hard` — итеративное углубление с сохранением лучшего хода между итерациями.

Если компьютер играет белыми (`white_id = 0`), его первый ход выполняется **при создании партии** на сервере.

---

## Часы

- **Контроль времени** — строка вида `M+I` (минуты + секунды за ход). В лобби доступны: `0+0`, `1+0`, `3+2`, `5+0`, `10+0`, `15+10`, `30+0`.
- `tc_base` хранится в **секундах**, `tc_inc` — в секундах, `clock_*` — в **миллисекундах**.
- Часы **не идут**, пока обе стороны не сделали по первому ходу (`ply >= 2`). Это правило «первый ход бесплатно».
- При ходе к остатку прибавляется инкремент (`tc_inc * 1000`).
- Проверка просрочки: если время истекло — вызывается `chess_check_flag`. Результат:
  - если **у соперника** нет материала для мата — **ничья** (`1/2-1/2`, `timeout`);
  - иначе — **поражение** просрочившего.
- На клиенте:
  - `is-low` (мигание) — меньше 20 секунд,
  - тик-звук — в последние 10 секунд,
  - предупреждение о низком времени — за 30 секунд.
- В режиме `0+0` (без часов) отображается `∞`.

---

## Чат

- Только **PvP** и только пока партия `active`. В игре с компьютером и после завершения чат не показывается.
- Максимум **255 символов** (`chess_chat_max`).
- Кулдаун между сообщениями одного пользователя — из настроек (**5 с** по умолчанию).
- Хранится **200 последних сообщений** на партию — более старые удаляются.
- При завершении партии `chess_finish()` вызывает `chess_chat_purge()` — история чата удаляется.
- Дополнительно `chess_chat_sweep()` (вызывается из `chess_cleanup()`) чистит чат завершённых и осиротевших игр.
- Клиент опрашивает чат каждые **2.5 с** (в неактивной вкладке — 8 с).
- Звук: отправка / приём — «радио-стиль» (Quindar-тоны).

---

## Звуки

Всё через **Web Audio API**, без внешних файлов. Состояние «вкл/выкл» хранится в `localStorage` (`cx_sound`).

Список: `select`, `move`, `capture`, `castle`, `check`, `promote`, `illegal`, `tick`, `start`, `win`, `lose`, `draw`, `horn`, `send`, `recv`.

Контекст `AudioContext` создаётся/возобновляется по `pointerdown` (требование браузеров).

Кнопка «Звук» есть в лобби и в игре.

---

## «Разбудить» — автомобильный сигнал

Если **ваш ход** и вы бездействуете, клиент через `idle_seconds` секунд проигрывает «би-бип» (два тона 400 + 500 Гц) и показывает шутку в лог/тост.

Повторы с задержкой: `need = idle_seconds + nagCount * 15`, всего **до 4 напоминаний**. Третье и четвёртое используют «поздние» фразы (`phr_nag_late`).

Таймер «нага» обнуляется при каждом ходе (`touch()` в `applyState`/`onSquare`).

Дополнительно:
- в **незачётном PvP** (без часов, есть ходы) через **90 секунд** ожидания соперника показывается мягкое напоминание (`phr_opp_slow`);
- предупреждение о низком времени — за 30 секунд до просрочки (`phr_low_time`).

Отключается настройкой `idle_enabled = 0`.

---

## Статистика и рейтинг

Таблица `cot_chess_stats` (одна строка на игрока):

- `wins / losses / draws` — результаты **PvP**;
- `ai_wins / ai_losses / ai_draws` — результаты **против компьютера**;
- `rating` — рейтинг PvP, старт **1000**.

Рейтинг PvP считается по формуле Эло с `K = 24`:

```
expected = 1 / (1 + 10^((rating_opp − rating_me) / 400))
delta    = round(24 * (score − expected))
rating   = max(0, rating + delta)
```

- В играх против компьютера рейтинг **не меняется**, учитываются только `ai_*`.
- Партии с причиной `abandon` в статистику **не попадают**.
- Топ-10 для лобби и топ-15 для админки считаются по `rating DESC, wins DESC` при `wins+losses+draws > 0`.

---

## Окончание партии и ничьи

`chess_game_over()` (движок) автоматически завершает партию:

| Условие | `result` | `finish_reason` |
|---------|----------|-----------------|
| Нет легальных ходов + шах | `0-1` / `1-0` | `checkmate` |
| Нет легальных ходов без шаха | `1/2-1/2` | `stalemate` |
| Недостаточно материала (только короли; король+слон/конь; только одноцветные слоны) | `1/2-1/2` | `insufficient` |
| 50 ходов без взятий и без ходов пешками | `1/2-1/2` | `fifty` |
| Троекратное повторение позиции | `1/2-1/2` | `repetition` |
| Ничья по соглашению (PvP) | `1/2-1/2` | `agreement` |
| Сдача | `1-0` / `0-1` | `resign` |
| Просрочка времени | `0-1` / `1-0` (или ничья — см. [Часы](#часы)) | `timeout` |
| Неактивность более `abandon_hours` | — (без записи в статистику) | `abandon` |

**Пат** (stalemate) и **позиционные ничьи** определяются на сервере до отправки ответа клиенту.

Ничья по соглашению: белые ставят `draw_offer = 1`, чёрные — `2`. Предложение сбрасывается при любом ходе.

---

## AJAX API

Единая точка: `plugins/chess/chess.ajax.php` (`?e=chess&r=chess`), метод POST, поле `action`.

Все запросы требуют:
- авторизацию;
- параметр `x` = `Cot::$sys['xk']` (CSRF).

| `action` | Параметры | Возвращает |
|----------|-----------|------------|
| `lobby`     | — | `open`, `mine`, `top`, `stats` |
| `create`    | `mode` (ai/pvp), `color` (w/b/r), `tc` (`M+I`), `level` | `game_id` |
| `join`      | `game_id` | `game_id` |
| `move`      | `game_id`, `from`, `to`, `promo` | Полный `payload` партии |
| `state`     | `game_id`, `rev` | `payload` или `{unchanged:true,...}` |
| `resign`    | `game_id` | `payload` / `{deleted:true}` |
| `draw`      | `game_id`, `do` (offer/accept/decline) | `payload` |
| `chatsend`  | `game_id`, `msg` | `chat` |
| `chatget`   | `game_id`, `after` | `items` |

Ответ — `application/json; charset=utf-8`, `Cache-Control: no-store`.

Коды ошибок (ключи `chess_err_*`): `auth`, `no_game`, `access`, `own_game`, `limit`, `coords`, `illegal`, `promo`, `not_your_turn`, `draw`, `unknown`, `network`, `chat_off`, `chat_empty`, `chat_long`, `chat_closed`, `chat_wait`.

---

## Таблицы БД

Устанавливаются из `setup/chess.install.sql` (`ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`).

### `cot_chess_games`

| Поле | Тип | Описание |
|------|-----|----------|
| `game_id` | INT PK AI | Идентификатор |
| `white_id` / `black_id` | INT NULL | `NULL` — свободное место, `0` — компьютер |
| `is_ai` | TINYINT(1) | 1 — игра с компьютером |
| `ai_level` | VARCHAR(8) | easy / normal / hard |
| `fen` | VARCHAR(120) | Текущая позиция |
| `hist` | MEDIUMTEXT | История ключей позиций (JSON) для повторений |
| `moves` | MEDIUMTEXT | Журнал ходов (JSON) |
| `status` | ENUM | `waiting` / `active` / `finished` |
| `result` | VARCHAR(8) | `1-0` / `0-1` / `1/2-1/2` / `''` |
| `finish_reason` | VARCHAR(16) | см. [Окончание партии](#окончание-партии-и-ничьи) |
| `winner_id` | INT NULL | Победитель (NULL для ничьей) |
| `tc_base` / `tc_inc` | INT | Секунды |
| `clock_w` / `clock_b` | BIGINT | Остаток времени, мс |
| `clock_ts` | BIGINT | Момент последнего старта часов, мс |
| `draw_offer` | TINYINT | 0 / 1 (w) / 2 (b) |
| `rev` | INT | Ревизия (для `state`) |
| `created_at` / `updated_at` | DATETIME | |

Ключи: `status`, `white`, `black`, `updated_at`.

### `cot_chess_stats`

`user_id` PK, `wins`, `losses`, `draws`, `ai_wins`, `ai_losses`, `ai_draws`, `rating` (default 1000).

### `cot_chess_chat`

`msg_id` PK AI, `game_id`, `user_id`, `message` VARCHAR(255), `created_ts` INT.
Ключи: `game_msg (game_id, msg_id)`, `user_ts (user_id, created_ts)`.

---

## Админ-инструменты

`chess.admin.php` (hook `tools`, доступ только `Cot::$usr['isadmin']`):

- Список партий с фильтром `all` / `waiting` / `active` / `finished`.
- Удаление отдельной партии (с её чатом).
- Массовая очистка всех завершённых (`purge_finished`).
- Пагинация (30 на страницу).
- Топ-15 игроков по рейтингу.

Все действия защищены `cot_check_xg()` (CSRF), кнопки используют `data-confirm` с `window.confirm()`.

---

## Безопасность и CSRF

- `defined('COT_CODE') or die('Wrong URL.')` в каждом PHP-файле.
- `cot_auth('plug', 'chess')` на страницах, `cot_block()` при отсутствии чтения.
- AJAX: обязательный `x` = `Cot::$sys['xk']` (или `xk_prev`).
- Мутирующие GET-действия (`cancel`, `resign` из `my`, `delete`, `purge` из админки) защищены `cot_check_xg()`.
- Транзакции с блокировкой строки `SELECT … FOR UPDATE` — при создании/присоединении, при ходе, при сдаче, при ничьей, при отправке чата (сериализация rate-limit).
- Все SQL-запросы используют prepared statements или `$db->quote()`.
- `chess_chat_clean()` вычищает управляющие символы и сжимает пробелы; длина ограничена `chess_chat_max()`.

---

## Известные особенности

- **Часы не идут** до второго полухода (первый ход каждого игрока бесплатный).
- **AI думает на сервере** — время ответа на ход компьютера ограничено бюджетом (`hard` ≈ 2 с, `normal` ≈ 1 с).
- **JS-клиент ничего не решает по правилам** — только рисует ответы сервера; «нелегальный» клик просто очищает выделение и проигрывает звук `illegal`.
- **`state` с `rev`**: клиент может получить `{unchanged:true}` — это экономит трафик; при этом, если идут часы, сервер всё равно отдаёт актуальные `clock`-значения.
- **Повторения позиций** считаются по первым 4 полям FEN (`chess_pos_key`).
- **Форфейт**: сервер сам вызывает `chess_check_flag` на `state` и `move` — просрочка фиксируется даже если клиент «не смотрит».
- **Игры с истёкшим `abandon_hours`** закрываются при любом заходе в лобби/игру (`chess_cleanup` в `chess_create_game`, `chess_join_game`, `chess_lobby`, `my`).
- **`0+0`** — без часов; на доске отображается `∞`.

---

## Лицензия и автор

- **Автор:** webitproff
- **Лицензия:** BSD 
- **[Исходники / обновления](https://github.com/webitproff/chess-cotonti)**
