# Temp File Storage

Веб-приложение для временного хранения PDF/DOCX: асинхронная загрузка, CRUD-страница, автоудаление через 24 часа и уведомление об удалении через RabbitMQ.

**Стек:** Laravel 12 · PHP 8.4 · MySQL 8.4 · RabbitMQ 3.13 · Bootstrap 5 + jQuery · Docker Compose

## Запуск

```bash
make init          # .env из .env.example + APP_KEY (если ещё нет) + docker compose up -d --build
```

Без make:

```bash
cp .env.example .env
# вписать в .env строку APP_KEY=base64:<вывод openssl rand -base64 32>
docker compose up -d --build
```

> Если запустить `docker compose up` с пустым `APP_KEY`, приложение поднимется, но каждый контейнер сгенерирует себе временный ключ: сессии (и выбранный язык) будут сбрасываться при каждом рестарте. Поэтому начинать стоит с `make init`.

Миграции выполняются автоматически при старте контейнера `app`.

| Что | Адрес |
|---|---|
| Приложение | http://localhost:8080 |
| RabbitMQ UI | http://localhost:15672 (логин/пароль из `RABBITMQ_USER` / `RABBITMQ_PASSWORD`) |
| Mailpit (пришедшие письма) | http://localhost:8025 |

Тесты: `make test` (SQLite in-memory, брокер подменяется фейком).

## Контейнеры

| Сервис | Роль |
|---|---|
| `app` | PHP-FPM, при старте `migrate` |
| `nginx` | веб-сервер, `client_max_body_size 13M` |
| `scheduler` | `schedule:work` → каждую минуту `files:purge-expired` |
| `worker` | `notifications:consume-email` — читает очередь и шлёт письма |
| `mysql`, `rabbitmq`, `mailpit` | инфраструктура |

`app`, `scheduler` и `worker` собираются из одного `Dockerfile` и делят volume `uploads` (`storage/app`), чтобы планировщик видел файлы, загруженные через веб.

## Страницы

- `/upload` — загрузка: drag & drop, несколько файлов сразу, прогресс-бар на каждый файл, ошибки валидации из ответа сервера.
- `/files` — управление: список с пагинацией, обратный отсчёт до удаления, скачивание, удаление через AJAX с подтверждением в модальном окне.

## Языки

Интерфейс на английском (по умолчанию) и русском, переключатель — в шапке справа (`/locale/{en|ru}`, выбор хранится в сессии).

- Переводы — `lang/en/app.php`, `lang/ru/app.php`; строки для JS лежат там же в секции `js` и попадают на страницу через `window.I18n` (`App.t('key', {param: ...})`).
- Язык по умолчанию — `APP_LOCALE`, список доступных — `config/app.php` (`available_locales`).
- Уведомления пишутся на языке `NOTIFICATION_LOCALE` (по умолчанию = `APP_LOCALE`), а не на языке посетителя: файл может удалить и человек, и планировщик, а письмо получает один и тот же адресат.

## Валидация — `App\Http\Requests\StoreFileRequest`

```php
'file' => ['required', 'file', 'mimes:pdf,docx', 'extensions:pdf,docx', 'max:10240']
```

- `mimes` проверяет тип **по содержимому** (finfo), поэтому переименованный `photo.png → photo.pdf` не пройдёт;
- `extensions` проверяет расширение в имени файла;
- `max` — размер в КБ (`FILES_MAX_SIZE_KB`, по умолчанию 10 МБ);
- длина исходного имени — не больше 255 символов (размер колонки `original_name`), иначе была бы ошибка БД вместо понятного сообщения.

Лимиты PHP (12M) и nginx (13M) нарочно немного выше лимита приложения, чтобы превышение ловил Form Request и пользователь видел понятное сообщение. Тот же лимит проверяется в JS до отправки — это только для удобства, решает сервер.

## Удаление и уведомления

```
Ручное удаление (DELETE /files/{id}) ─┐
                                      ├─> FileStorageService::delete()
files:purge-expired (каждую минуту) ──┘        │
                                               ├─ удалить запись в БД + файл с диска
                                               └─ Notifier::notify(NotificationMessage)
                                                       │
                                     MultiChannelNotifier — все включённые каналы
                                                       │
                                     EmailChannel ──> MessagePublisher (RabbitMQ)
                                                       │  очередь notifications.email
                                                       ▼
                                     worker: notifications:consume-email ──> Mailer ──> Mailpit
```

- Адрес получателя — `NOTIFICATION_EMAIL` в `.env`.
- Планировщик ходит раз в минуту, поэтому между истечением срока и удалением есть окно до минуты. В это время файл уже нельзя скачать (`410 Gone`), а кнопка скачивания в списке неактивна.
- Запись удаляется через `DELETE ... WHERE id = ?` и проверку числа затронутых строк. Если планировщик и пользователь удаляют один файл одновременно, уведомление уйдёт только один раз.
- Если RabbitMQ недоступен, файл всё равно удаляется, а ошибка канала пишется в лог. Удаление не откатывается из-за уведомления.
- Сообщения в очереди persistent, очередь durable, у воркера ручной ack и `prefetch=1`.

### Повторы и «мёртвые» сообщения

Если письмо отправить не удалось (например, SMTP недоступен), сообщение не теряется:

```
notifications.email ──ошибка──> notifications.email.retry (TTL = RABBITMQ_RETRY_DELAY_MS)
        ▲                                  │ по истечении TTL RabbitMQ сам
        └──────────────────────────────────┘ возвращает сообщение (dead-letter)

после RABBITMQ_RETRY_ATTEMPTS неудач ──> notifications.email.failed
```

- Число неудачных попыток хранится в заголовке `x-failures`, последняя ошибка — в `x-last-error`.
- Битый JSON и невалидный payload (`UnprocessableMessageException`) уходят в `.failed` сразу: повтор их не исправит.
- Сообщения из `.failed` видны в RabbitMQ UI, их можно разобрать вручную или переложить обратно в основную очередь (Move messages).
- TTL ставится на каждое сообщение, а не на очередь, поэтому задержку можно менять в `.env` без пересоздания очередей.
- Доставка at-least-once: копия публикуется до ack оригинала, поэтому при сбое между этими шагами письмо может прийти дважды, но не потеряется.

## Как добавить новый канал (например, Telegram)

Бизнес-код (`FileStorageService`) зависит только от интерфейса `Notifier`, а он — от набора `NotificationChannel`. Каналы собираются из конфига в `NotificationServiceProvider`.

1. Написать класс, реализующий `App\Notifications\Contracts\NotificationChannel`.
2. Зарегистрировать его в `config/notifications.php` → `drivers`.
3. Включить через `.env`: `NOTIFICATION_CHANNELS=email,telegram`.

`TelegramChannel` уже лежит в проекте как пример. Чтобы он заработал, достаточно заполнить `TELEGRAM_BOT_TOKEN` и `TELEGRAM_CHAT_ID`. Если Telegram тоже нужно пустить через очередь, канал публикует сообщение в свою очередь через тот же `MessagePublisher`, а для неё пишется свой consumer.

Брокер тоже за абстракцией (`MessagePublisher` / `MessageConsumer`): чтобы заменить RabbitMQ, нужно поменять биндинг в `MessagingServiceProvider`.

## Структура

```
app/
├── Console/Commands/      PurgeExpiredFiles, ConsumeEmailNotifications
├── Enums/                 DeletionReason (manual / expired)
├── Http/Controllers/      UploadController, FileController, LocaleController
├── Http/Middleware/       SetLocale
├── Http/Requests/         StoreFileRequest
├── Messaging/             Contracts + RabbitMq (connection, publisher, consumer)
├── Models/                StoredFile
├── Notifications/         Contracts, Channels (Email, Telegram), MultiChannelNotifier, NotificationMessage
├── Providers/             Messaging / Notification service providers
└── Services/              FileStorageService
config/                    app.php (языки), files.php, rabbitmq.php, notifications.php
lang/                      en, ru
docker/                    nginx, php.ini, entrypoint
```

## Полезное

```bash
make logs     # логи app / worker / scheduler
make purge    # запустить автоудаление вручную
```

Чтобы проверить автоудаление без ожидания 24 часов, поставьте `FILES_TTL_HOURS=0` и перезапустите контейнеры. Файлы удалятся в течение минуты, письмо появится в Mailpit.
