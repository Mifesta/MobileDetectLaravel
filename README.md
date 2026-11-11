# mifesta/mobiledetect-laravel

Определяет телефон, планшет и краулер по текущему запросу Laravel. Каталог устройств берётся из [`mifesta/mobiledetect`](https://github.com/Mifesta/MobileDetect): релиз пакета совпадает с мажорной версией Laravel, а клиент сам выбирает ветку `mobiledetectlib`, которая живёт на установленном PHP.

В проекте, где Laravel уже есть в `composer.json`:

```bash
composer require mifesta/mobiledetect-laravel
```

Composer поставит релиз с тем же мажором, что и Laravel. Явное ограничение выглядит так:

```bash
composer require mifesta/mobiledetect-laravel:^10.0
```

| Laravel | PHP | Ограничение | Что встанет |
| --- | --- | --- | --- |
| 5.5 – 5.8 | 7.0 – 7.3 | `^5.8` | `mifesta/mobiledetect` 1.4.1 и mobiledetectlib 2.8 |
| 5.5 – 5.8 | 7.4 | `^5.8` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 3.74 |
| 6 и 7 | 7.2 – 7.3 | `^6.0` или `^7.0` | `mifesta/mobiledetect` 1.4.1 и mobiledetectlib 2.8 |
| 6 и 7 | 7.4 | `^6.0` или `^7.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 3.74 |
| 8 | 7.3 | `^8.0` | `mifesta/mobiledetect` 1.4.1 и mobiledetectlib 2.8 |
| 8 | 7.4 и новее | `^8.0` | `mifesta/mobiledetect` 2.5.1 |
| 9 | 8.0 и новее | `^9.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 4.8+ |
| 10 | 8.1 и новее | `^10.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 4.8+ |
| 11 | 8.2 и новее | `^11.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 4.10+ |
| 12 | 8.2 и новее | `^12.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 4.10+ |
| 13 | 8.3 и новее | `^13.0` | `mifesta/mobiledetect` 2.5.1 и mobiledetectlib 4.10+ |

Релиз `1.4.2` закрывает старое ограничение `^1.4`: из пакета убран `provide: laravel/laravel`, а определение идёт через `mifesta/mobiledetect`. Для нового проекта берите мажор Laravel из таблицы.

Провайдер и фасад `MobileDetect` подключаются сами с Laravel 5.5. Если автозагрузка пакетов выключена, зарегистрируйте их вручную:

```php
'providers' => [
    MifestaClientBrowserData\MobileDetectServiceProvider::class,
],
'aliases' => [
    'MobileDetect' => MifestaClientBrowserData\Facades\MobileDetect::class,
],
```

## Использование

```php
use MifestaClientBrowserData\ClientBrowserData;

if (ClientBrowserData::isBot()) {
    // обычный краулер, не мобильный
}

if (ClientBrowserData::isMobile()) {
    // телефон, планшет или мобильный краулер
}

if (ClientBrowserData::isTablet()) {
    // планшет; isMobile() для него тоже true
}

ClientBrowserData::is('iPhone');
ClientBrowserData::is('AndroidOS');
ClientBrowserData::version('iOS');
```

Тот же вызов доступен через фасад: `MobileDetect::isMobile()`.

`is('mobile')`, `is('tablet')`, `is('bot')`, `is('anybot')` и `is('mobilebot')` не зависят от регистра.

Мобильный краулер (`Googlebot` в UA телефона, `Googlebot-Mobile`, `AdsBot-Google-Mobile`, `YandexMobileBot`) попадает в `isAnyBot()` и `isMobile()`, но не в `isBot()`. Имена телефонов вроде Cubot и iRobot краулерами не считаются. Пустой User-Agent ботом не является.

Заголовки читаются из текущего запроса Laravel, включая `User-Agent`, `X-Device-User-Agent` и `X-OperaMini-Phone-UA`. В одном процессе следующий запрос получает свой результат. `setUserAgent()` действует до конца этого запроса или задания очереди и сбрасывается перед следующим.

В консоли и в очередях запроса браузера нет, поэтому без `setUserAgent()` устройство не определяется:

```php
ClientBrowserData::setUserAgent($userAgent);
```

## Credits

Sergei Gruzdov — <https://github.com/cardee2000>

## Security

Sergei Gruzdov <cardee@mifesta.com>
