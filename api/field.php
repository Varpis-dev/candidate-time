<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Время кандидата</title>
  <script src="https://api.bitrix24.com/api/v1/"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background: transparent;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }

    .wrap {
      box-sizing: border-box;
      width: 100%;
      padding: 8px 10px;
      border-radius: 14px;
      background: #fff;
    }

    .time {
      font-size: 28px;
      line-height: 1;
      font-weight: 700;
      color: #111;
      margin: 0 0 8px 0;
    }

    .city {
      font-size: 16px;
      line-height: 1.2;
      color: #444;
      margin: 0;
    }

    .status {
      margin-top: 6px;
      font-size: 13px;
      line-height: 1.2;
      font-weight: 500;
    }

    .ok { color: #0b8f3c; }
    .bad { color: #d92d20; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="time" id="time">--:--:--</div>
    <div class="city" id="city">Загрузка...</div>
    <div class="status" id="status"></div>
  </div>

  <script>
    const timeEl = document.getElementById('time');
    const cityEl = document.getElementById('city');
    const statusEl = document.getElementById('status');

    // 1) Явная карта таймзон по твоему списку.
    // Всё, чего тут нет, дальше пойдет в API fallback, а если и там не найдет — в Europe/Moscow.
    const MANUAL_TZ = {
      // Калининград
      "Калининград": "Europe/Kaliningrad",
      "Балтийск": "Europe/Kaliningrad",

      // Самара / Ульяновск / Саратов / Астрахань
      "Самара": "Europe/Samara",
      "Тольятти": "Europe/Samara",
      "Сызрань": "Europe/Samara",
      "Жигулевск": "Europe/Samara",
      "Чапаевск": "Europe/Samara",
      "Новокуйбышевск": "Europe/Samara",
      "Безенчук": "Europe/Samara",
      "Большая Черниговка": "Europe/Samara",
      "Похвистнево": "Europe/Samara",
      "Отрадный": "Europe/Samara",
      "Суходол": "Europe/Samara",
      "Ульяновск": "Europe/Samara",
      "Димитровград": "Europe/Samara",
      "Новоульяновск": "Europe/Samara",
      "Саратов": "Europe/Samara",
      "Энгельс": "Europe/Samara",
      "Балаково": "Europe/Samara",
      "Ершов": "Europe/Samara",
      "Ртищево": "Europe/Samara",
      "Маркс": "Europe/Samara",
      "Астрахань": "Europe/Samara",

      // Урал / Башкирия / Пермь / ХМАО / ЯНАО / Оренбург / Челябинск / Курган / Тюмень
      "Екатеринбург": "Asia/Yekaterinburg",
      "Алапаевск": "Asia/Yekaterinburg",
      "Арамиль": "Asia/Yekaterinburg",
      "Артемовский": "Asia/Yekaterinburg",
      "Арти": "Asia/Yekaterinburg",
      "Асбест": "Asia/Yekaterinburg",
      "Бисерть": "Asia/Yekaterinburg",
      "Богданович": "Asia/Yekaterinburg",
      "Буланаш": "Asia/Yekaterinburg",
      "Верхний Тагил": "Asia/Yekaterinburg",
      "Верхняя Пышма": "Asia/Yekaterinburg",
      "Верхняя Салда": "Asia/Yekaterinburg",
      "Верхняя Тура": "Asia/Yekaterinburg",
      "Верхотурье": "Asia/Yekaterinburg",
      "Ивдель": "Asia/Yekaterinburg",
      "Ирбит": "Asia/Yekaterinburg",
      "Каменск-Уральский": "Asia/Yekaterinburg",
      "Карпинск": "Asia/Yekaterinburg",
      "Качканар": "Asia/Yekaterinburg",
      "Краснотурьинск": "Asia/Yekaterinburg",
      "Красноуральск": "Asia/Yekaterinburg",
      "Красноуфимск": "Asia/Yekaterinburg",
      "Кировград": "Asia/Yekaterinburg",
      "Лесной": "Asia/Yekaterinburg",
      "Нижний Тагил": "Asia/Yekaterinburg",
      "Нижняя Салда": "Asia/Yekaterinburg",
      "Нижняя Тура": "Asia/Yekaterinburg",
      "Новая Ляля": "Asia/Yekaterinburg",
      "Новоуральск": "Asia/Yekaterinburg",
      "Первоуральск": "Asia/Yekaterinburg",
      "Полевской": "Asia/Yekaterinburg",
      "Ревда": "Asia/Yekaterinburg",
      "Реж": "Asia/Yekaterinburg",
      "Североуральск": "Asia/Yekaterinburg",
      "Серов": "Asia/Yekaterinburg",
      "Сухой Лог": "Asia/Yekaterinburg",
      "Сысерть": "Asia/Yekaterinburg",
      "Тавда": "Asia/Yekaterinburg",
      "Туринск": "Asia/Yekaterinburg",
      "Туринская Слобода": "Asia/Yekaterinburg",
      "Волчанск": "Asia/Yekaterinburg",

      "Тюмень": "Asia/Yekaterinburg",
      "Заводоуковск": "Asia/Yekaterinburg",
      "Ишим": "Asia/Yekaterinburg",
      "Тобольск": "Asia/Yekaterinburg",
      "Ялуторовск": "Asia/Yekaterinburg",
      "Исетское": "Asia/Yekaterinburg",
      "Нижняя Тавда": "Asia/Yekaterinburg",

      "Ханты-Мансийск": "Asia/Yekaterinburg",
      "Сургут": "Asia/Yekaterinburg",
      "Нижневартовск": "Asia/Yekaterinburg",
      "Нефтеюганск": "Asia/Yekaterinburg",
      "Когалым": "Asia/Yekaterinburg",
      "Лангепас": "Asia/Yekaterinburg",
      "Лянтор": "Asia/Yekaterinburg",
      "Мегион": "Asia/Yekaterinburg",
      "Нягань": "Asia/Yekaterinburg",
      "Урай": "Asia/Yekaterinburg",
      "Югорск": "Asia/Yekaterinburg",
      "Пыть-Ях": "Asia/Yekaterinburg",
      "Покачи": "Asia/Yekaterinburg",
      "Радужный": "Asia/Yekaterinburg",
      "Нижнесортымский": "Asia/Yekaterinburg",
      "Излучинск": "Asia/Yekaterinburg",
      "Приобье": "Asia/Yekaterinburg",
      "Игрим": "Asia/Yekaterinburg",
      "Междуреченский": "Asia/Yekaterinburg",
      "Федоровский": "Asia/Yekaterinburg",

      "Салехард": "Asia/Yekaterinburg",
      "Лабытнанги": "Asia/Yekaterinburg",
      "Надым": "Asia/Yekaterinburg",
      "Новый Уренгой": "Asia/Yekaterinburg",
      "Ноябрьск": "Asia/Yekaterinburg",
      "Муравленко": "Asia/Yekaterinburg",
      "Губкинский": "Asia/Yekaterinburg",
      "Тарко-Сале": "Asia/Yekaterinburg",
      "Пангоды": "Asia/Yekaterinburg",
      "Пангода": "Asia/Yekaterinburg",
      "Тазовский": "Asia/Yekaterinburg",
      "Коротчаево": "Asia/Yekaterinburg",
      "Уренгой": "Asia/Yekaterinburg",
      "Аксарка": "Asia/Yekaterinburg",
      "Лабытнанги": "Asia/Yekaterinburg",

      "Уфа": "Asia/Yekaterinburg",
      "Стерлитамак": "Asia/Yekaterinburg",
      "Салават": "Asia/Yekaterinburg",
      "Ишимбай": "Asia/Yekaterinburg",
      "Кумертау": "Asia/Yekaterinburg",
      "Мелеуз": "Asia/Yekaterinburg",
      "Нефтекамск": "Asia/Yekaterinburg",
      "Октябрьский": "Asia/Yekaterinburg",
      "Белебей": "Asia/Yekaterinburg",
      "Белорецк": "Asia/Yekaterinburg",
      "Бирск": "Asia/Yekaterinburg",
      "Давлеканово": "Asia/Yekaterinburg",
      "Дюртюли": "Asia/Yekaterinburg",
      "Туймазы": "Asia/Yekaterinburg",
      "Учалы": "Asia/Yekaterinburg",
      "Янаул": "Asia/Yekaterinburg",
      "Иглино": "Asia/Yekaterinburg",
      "Кушнаренково": "Asia/Yekaterinburg",
      "Буздяк": "Asia/Yekaterinburg",
      "Кармаскалы": "Asia/Yekaterinburg",
      "Верхние Татышлы": "Asia/Yekaterinburg",
      "Чекмагуш": "Asia/Yekaterinburg",
      "Шаран": "Asia/Yekaterinburg",

      "Пермь": "Asia/Yekaterinburg",
      "Березники": "Asia/Yekaterinburg",
      "Соликамск": "Asia/Yekaterinburg",
      "Краснокамск": "Asia/Yekaterinburg",
      "Кунгур": "Asia/Yekaterinburg",
      "Кудымкар": "Asia/Yekaterinburg",
      "Добрянка": "Asia/Yekaterinburg",
      "Кизел": "Asia/Yekaterinburg",
      "Губаха": "Asia/Yekaterinburg",
      "Красновишерск": "Asia/Yekaterinburg",
      "Чайковский": "Asia/Yekaterinburg",
      "Оса": "Asia/Yekaterinburg",
      "Нытва": "Asia/Yekaterinburg",
      "Верещагино": "Asia/Yekaterinburg",
      "Кондратово": "Asia/Yekaterinburg",
      "Култаево": "Asia/Yekaterinburg",
      "Юго-Камский": "Asia/Yekaterinburg",
      "Сива": "Asia/Yekaterinburg",
      "Суксун": "Asia/Yekaterinburg",
      "Чусовой": "Asia/Yekaterinburg",
      "Лысьва": "Asia/Yekaterinburg",
      "Омутнинск": "Asia/Yekaterinburg",

      "Оренбург": "Asia/Yekaterinburg",
      "Орск": "Asia/Yekaterinburg",
      "Бузулук": "Asia/Yekaterinburg",
      "Бугуруслан": "Asia/Yekaterinburg",
      "Новотроицк": "Asia/Yekaterinburg",
      "Соль-Илецк": "Asia/Yekaterinburg",
      "Ясный": "Asia/Yekaterinburg",
      "Кувандык": "Asia/Yekaterinburg",
      "Гай": "Asia/Yekaterinburg",
      "Сорочинск": "Asia/Yekaterinburg",
      "Саракташ": "Asia/Yekaterinburg",
      "Тюльган": "Asia/Yekaterinburg",
      "Переволоцкий": "Asia/Yekaterinburg",
      "Новосергиевка": "Asia/Yekaterinburg",

      "Челябинск": "Asia/Yekaterinburg",
      "Магнитогорск": "Asia/Yekaterinburg",
      "Миасс": "Asia/Yekaterinburg",
      "Златоуст": "Asia/Yekaterinburg",
      "Еманжелинск": "Asia/Yekaterinburg",
      "Копейск": "Asia/Yekaterinburg",
      "Коркино": "Asia/Yekaterinburg",
      "Кыштым": "Asia/Yekaterinburg",
      "Озерск": "Asia/Yekaterinburg",
      "Снежинск": "Asia/Yekaterinburg",
      "Трехгорный": "Asia/Yekaterinburg",
      "Чебаркуль": "Asia/Yekaterinburg",
      "Южноуральск": "Asia/Yekaterinburg",
      "Касли": "Asia/Yekaterinburg",
      "Верхнеуральск": "Asia/Yekaterinburg",
      "Катав-Ивановск": "Asia/Yekaterinburg",
      "Карталы": "Asia/Yekaterinburg",
      "Аша": "Asia/Yekaterinburg",
      "Усть-Катав": "Asia/Yekaterinburg",

      "Курган": "Asia/Yekaterinburg",
      "Шадринск": "Asia/Yekaterinburg",

      // Омск
      "Омск": "Asia/Omsk",
      "Азово": "Asia/Omsk",
      "Исилькуль": "Asia/Omsk",
      "Калачинск": "Asia/Omsk",
      "Тара": "Asia/Omsk",
      "Тюкалинск": "Asia/Omsk",
      "Марьяновка": "Asia/Omsk",
      "Любинский": "Asia/Omsk",

      // Новосибирск / Алтай / Томск / Кузбасс / Красноярск / Хакасия
      "Новосибирск": "Asia/Novosibirsk",
      "Бердск": "Asia/Novosibirsk",
      "Искитим": "Asia/Novosibirsk",
      "Карасук": "Asia/Novosibirsk",
      "Каргат": "Asia/Novosibirsk",
      "Коченево": "Asia/Novosibirsk",
      "Маслянино": "Asia/Novosibirsk",
      "Мошково": "Asia/Novosibirsk",
      "Обь": "Asia/Novosibirsk",
      "Татарск": "Asia/Novosibirsk",
      "Тогучин": "Asia/Novosibirsk",
      "Барабинск": "Asia/Novosibirsk",
      "Венгерово": "Asia/Novosibirsk",
      "Верх-Тула": "Asia/Novosibirsk",
      "Кольцово": "Asia/Novosibirsk",
      "Краснообск": "Asia/Novosibirsk",
      "Куйбышев": "Asia/Novosibirsk",
      "Коченево": "Asia/Novosibirsk",

      "Барнаул": "Asia/Barnaul",
      "Алейск": "Asia/Barnaul",
      "Бийск": "Asia/Barnaul",
      "Белокуриха": "Asia/Barnaul",
      "Горняк": "Asia/Barnaul",
      "Заринск": "Asia/Barnaul",
      "Камень-на-Оби": "Asia/Barnaul",
      "Новоалтайск": "Asia/Barnaul",
      "Рубцовск": "Asia/Barnaul",
      "Славгород": "Asia/Barnaul",
      "Яровое": "Asia/Barnaul",
      "Алтайское": "Asia/Barnaul",
      "Благовещенка": "Asia/Barnaul",
      "Кулунда": "Asia/Barnaul",
      "Шипуново": "Asia/Barnaul",
      "Залесово": "Asia/Barnaul",
      "ЗАТО Сибирский": "Asia/Barnaul",
      "Завьялово": "Asia/Barnaul",

      "Горно-Алтайск": "Asia/Barnaul",

      "Томск": "Asia/Tomsk",
      "Асино": "Asia/Tomsk",
      "Каргасок": "Asia/Tomsk",
      "Колпашево": "Asia/Tomsk",
      "Стрежевой": "Asia/Tomsk",
      "Северск": "Asia/Tomsk",
      "Зональная Станция": "Asia/Tomsk",
      "Молчаново": "Asia/Tomsk",

      "Кемерово": "Asia/Novokuznetsk",
      "Анжеро-Судженск": "Asia/Novokuznetsk",
      "Белово": "Asia/Novokuznetsk",
      "Грамотеино": "Asia/Novokuznetsk",
      "Киселёвск": "Asia/Novokuznetsk",
      "Ленинск-Кузнецкий": "Asia/Novokuznetsk",
      "Междуреченск": "Asia/Novokuznetsk",
      "Мыски": "Asia/Novokuznetsk",
      "Новокузнецк": "Asia/Novokuznetsk",
      "Полысаево": "Asia/Novokuznetsk",
      "Прокопьевск": "Asia/Novokuznetsk",
      "Таштагол": "Asia/Novokuznetsk",
      "Тайга": "Asia/Novokuznetsk",
      "Топки": "Asia/Novokuznetsk",
      "Юрга": "Asia/Novokuznetsk",
      "Осинники": "Asia/Novokuznetsk",
      "Мариинск": "Asia/Novokuznetsk",
      "Калтан": "Asia/Novokuznetsk",
      "Салаир": "Asia/Novokuznetsk",

      "Абакан": "Asia/Krasnoyarsk",
      "Черногорск": "Asia/Krasnoyarsk",
      "Ачинск": "Asia/Krasnoyarsk",
      "Боготол": "Asia/Krasnoyarsk",
      "Дивногорск": "Asia/Krasnoyarsk",
      "Емельяново": "Asia/Krasnoyarsk",
      "Енисейск": "Asia/Krasnoyarsk",
      "Канск": "Asia/Krasnoyarsk",
      "Красноярск": "Asia/Krasnoyarsk",
      "Лесосибирск": "Asia/Krasnoyarsk",
      "Минусинск": "Asia/Krasnoyarsk",
      "Назарово": "Asia/Krasnoyarsk",
      "Норильск": "Asia/Krasnoyarsk",
      "Шарыпово": "Asia/Krasnoyarsk",
      "Заозерный": "Asia/Krasnoyarsk",
      "Енисейск": "Asia/Krasnoyarsk",
      "Дивногорск": "Asia/Krasnoyarsk",

      // Иркутск / Бурятия
      "Иркутск": "Asia/Irkutsk",
      "Ангарск": "Asia/Irkutsk",
      "Братск": "Asia/Irkutsk",
      "Зима": "Asia/Irkutsk",
      "Тайшет": "Asia/Irkutsk",
      "Тулун": "Asia/Irkutsk",
      "Усолье-Сибирское": "Asia/Irkutsk",
      "Усть-Кут": "Asia/Irkutsk",
      "Шелехов": "Asia/Irkutsk",
      "Улан-Удэ": "Asia/Irkutsk",

      // Чита / Забайкалье
      "Чита": "Asia/Chita",

      // Якутия / Амур / ЕАО
      "Якутск": "Asia/Yakutsk",
      "Благовещенск": "Asia/Yakutsk",
      "Белогорск": "Asia/Yakutsk",
      "Биробиджан": "Asia/Yakutsk",

      // Владивосток / Хабаровск / Приморье
      "Владивосток": "Asia/Vladivostok",
      "Артём": "Asia/Vladivostok",
      "Уссурийск": "Asia/Vladivostok",
      "Находка": "Asia/Vladivostok",
      "Хабаровск": "Asia/Vladivostok",
      "Комсомольский": "Asia/Vladivostok",

      // Сахалин / Магадан / Камчатка
      "Южно-Сахалинск": "Asia/Sakhalin",
      "Магадан": "Asia/Magadan",
      "Петропавловск-Камчатский": "Asia/Kamchatka",

      // Кыргызстан
      "Бишкек": "Asia/Bishkek",
      "Кызыл-Кия": "Asia/Bishkek",

      // Важные фиксы на спорные случаи
      "Кузнецк": "Europe/Moscow",
      "Пенза": "Europe/Moscow",
      "Кемеровская область - Кузбасс": "Asia/Novokuznetsk",
      "Пензенская обл.": "Europe/Moscow",
      "Ленинградская область": "Europe/Moscow",
      "Станица Воронежская (Краснодарский край)": "Europe/Moscow",
      "Чалтырь": "Europe/Moscow",
      "Другой город": "Europe/Moscow"
    };

    function normalizeCity(city) {
      return String(city || '')
        .replace(/\s+/g, ' ')
        .replace(/[ёЁ]/g, 'е')
        .trim();
    }

    function getCachedTZ(city) {
      const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
      return cache[city];
    }

    function setCachedTZ(city, tz) {
      const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
      cache[city] = tz;
      localStorage.setItem('tz_cache', JSON.stringify(cache));
    }

    async function getTimezone(city) {
      const normalized = normalizeCity(city);

      // 1. Сначала ищем в ручной карте
      for (const key in MANUAL_TZ) {
        if (normalizeCity(key) === normalized) {
          return MANUAL_TZ[key];
        }
      }

      // 2. Потом кэш
      const cached = getCachedTZ(normalized);
      if (cached) return cached;

      // 3. Потом API fallback
      try {
        const geoRes = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(city));
        const geoData = await geoRes.json();

        if (!geoData.length) return 'Europe/Moscow';

        const lat = geoData[0].lat;
        const lon = geoData[0].lon;

        const tzRes = await fetch('https://timeapi.io/api/Time/current/coordinate?latitude=' + lat + '&longitude=' + lon);
        const tzData = await tzRes.json();

        const tz = tzData.timeZone || 'Europe/Moscow';
        setCachedTZ(normalized, tz);
        return tz;
      } catch (e) {
        return 'Europe/Moscow';
      }
    }

    function renderStatusByHour(hour) {
      let text = 'Можно звонить';
      let cls = 'ok';

      if (hour < 9) {
        text = 'Слишком рано';
        cls = 'bad';
      }

      if (hour >= 21) {
        text = 'Слишком поздно';
        cls = 'bad';
      }

      statusEl.textContent = text;
      statusEl.className = 'status ' + cls;
    }

    function startClock(tz) {
      function render() {
        const now = new Date();

        const time = new Intl.DateTimeFormat('ru-RU', {
          timeZone: tz,
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        }).format(now);

        timeEl.textContent = time;

        const hour = Number(new Intl.DateTimeFormat('en-GB', {
          timeZone: tz,
          hour: '2-digit',
          hour12: false
        }).format(now));

        renderStatusByHour(hour);

        if (window.BX24 && BX24.fitWindow) {
          BX24.fitWindow();
        }
      }

      render();
      setInterval(render, 1000);
    }

    BX24.init(function() {
      const info = BX24.placement.info();
      const options = info && info.options ? info.options : {};

      const entityId =
        options.ENTITY_VALUE_ID ||
        options.ID ||
        options.id ||
        options.LEAD_ID ||
        options.DEAL_ID ||
        null;

      BX24.callMethod('app.option.get', {}, function(optRes) {
        const appOptions = optRes.data() || {};
        const entity = appOptions.entity || 'lead';
        const field = appOptions.field;

        if (!entityId || !field) {
          cityEl.textContent = 'Нет ID или не выбрано поле';
          if (window.BX24 && BX24.fitWindow) BX24.fitWindow();
          return;
        }

        const method = entity === 'deal' ? 'crm.deal.get' : 'crm.lead.get';

        BX24.callMethod(method, { id: entityId }, function(res) {
          if (res.error()) {
            cityEl.textContent = 'Ошибка чтения карточки';
            if (window.BX24 && BX24.fitWindow) BX24.fitWindow();
            return;
          }

          const item = res.data() || {};
          let city = item[field];

          if (Array.isArray(city)) {
            city = city[0] || '';
          } else if (typeof city === 'object' && city !== null) {
            city = city.VALUE || city.value || '';
          } else {
            city = city || '';
          }

          if (!city) {
            cityEl.textContent = 'Город не заполнен';
            if (window.BX24 && BX24.fitWindow) BX24.fitWindow();
            return;
          }

          cityEl.textContent = city;
          if (window.BX24 && BX24.fitWindow) BX24.fitWindow();

          getTimezone(city).then(startClock);
        });
      });
    });
  </script>
</body>
</html>
