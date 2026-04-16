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
      margin-bottom: 6px;
    }

    .city {
      font-size: 15px;
      color: #444;
      line-height: 1.2;
    }

    .status-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 6px;
    }

    .status {
      font-size: 13px;
      font-weight: 500;
      line-height: 1.2;
    }

    .ok { color: #0b8f3c; }
    .bad { color: #d92d20; }

    .logo {
      height: 20px;
      width: auto;
      flex-shrink: 0;
    }
  </style>
</head>

<body>
<div class="wrap">
  <div class="time" id="time">--:--:--</div>
  <div class="city" id="city">Загрузка...</div>

  <div class="status-wrap">
    <img
      class="logo"
      src="https://raw.githubusercontent.com/Varpis-dev/candidate-time/main/public/Logo_Vertical_Colored_Light-background.svg"
      alt=""
    >
    <div id="status" class="status"></div>
  </div>
</div>

<script>
const timeEl = document.getElementById('time');
const cityEl = document.getElementById('city');
const statusEl = document.getElementById('status');

const MANUAL_TZ = {
  // UTC+2
  "Калининград": "Europe/Kaliningrad",
  "Балтийск": "Europe/Kaliningrad",
  "Советск": "Europe/Kaliningrad",

  // UTC+3
  "Москва": "Europe/Moscow",
  "Санкт-Петербург": "Europe/Moscow",
  "Кузнецк": "Europe/Moscow",
  "Пенза": "Europe/Moscow",
  "Орёл": "Europe/Moscow",
  "Орел": "Europe/Moscow",
  "Краснодар": "Europe/Moscow",
  "Сочи": "Europe/Moscow",
  "Ростов-на-Дону": "Europe/Moscow",
  "Воронеж": "Europe/Moscow",
  "Белгород": "Europe/Moscow",
  "Курск": "Europe/Moscow",
  "Тула": "Europe/Moscow",
  "Рязань": "Europe/Moscow",
  "Ярославль": "Europe/Moscow",
  "Тверь": "Europe/Moscow",
  "Брянск": "Europe/Moscow",
  "Смоленск": "Europe/Moscow",
  "Иваново": "Europe/Moscow",
  "Владимир": "Europe/Moscow",

  // UTC+4
  "Самара": "Europe/Samara",
  "Тольятти": "Europe/Samara",
  "Ульяновск": "Europe/Samara",
  "Саратов": "Europe/Samara",
  "Энгельс": "Europe/Samara",
  "Астрахань": "Europe/Samara",
  "Балаково": "Europe/Samara",
  "Безенчук": "Europe/Samara",
  "Большая Черниговка": "Europe/Samara",
  "Жигулевск": "Europe/Samara",
  "Кинель-Черкассы": "Europe/Samara",
  "Красный Кут": "Europe/Samara",
  "Красный Яр": "Europe/Samara",
  "Маркс": "Europe/Samara",
  "Мокроус": "Europe/Samara",
  "Нариманов": "Europe/Samara",
  "Нефтегорск": "Europe/Samara",
  "Новоузенск": "Europe/Samara",
  "Озинки": "Europe/Samara",
  "Октябрьск": "Europe/Samara",
  "Пугачев": "Europe/Samara",
  "Сарапул": "Europe/Samara",
  "Старая Майна": "Europe/Samara",
  "Степное": "Europe/Samara",
  "Татищево": "Europe/Samara",
  "Чапаевск": "Europe/Samara",

  // UTC+5
  "Екатеринбург": "Asia/Yekaterinburg",
  "Мелеуз": "Asia/Yekaterinburg",
  "Уфа": "Asia/Yekaterinburg",
  "Стерлитамак": "Asia/Yekaterinburg",
  "Салават": "Asia/Yekaterinburg",
  "Челябинск": "Asia/Yekaterinburg",
  "Тюмень": "Asia/Yekaterinburg",
  "Курган": "Asia/Yekaterinburg",
  "Оренбург": "Asia/Yekaterinburg",
  "Орск": "Asia/Yekaterinburg",
  "Магнитогорск": "Asia/Yekaterinburg",
  "Нефтекамск": "Asia/Yekaterinburg",
  "Сургут": "Asia/Yekaterinburg",
  "Нижневартовск": "Asia/Yekaterinburg",
  "Ханты-Мансийск": "Asia/Yekaterinburg",
  "Новый Уренгой": "Asia/Yekaterinburg",
  "Ноябрьск": "Asia/Yekaterinburg",
  "Надым": "Asia/Yekaterinburg",
  "Салехард": "Asia/Yekaterinburg",
  "Лабытнанги": "Asia/Yekaterinburg",
  "Тарко-Сале": "Asia/Yekaterinburg",
  "Аксарка": "Asia/Yekaterinburg",
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
  "Нижняя Тавда": "Asia/Yekaterinburg",
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
  "Троицк": "Asia/Yekaterinburg",
  "Туринск": "Asia/Yekaterinburg",
  "Туринская Слобода": "Asia/Yekaterinburg",
  "Учалы": "Asia/Yekaterinburg",
  "Чишмы": "Asia/Yekaterinburg",
  "Ясный": "Asia/Yekaterinburg",

  // UTC+6
  "Омск": "Asia/Omsk",
  "Азово": "Asia/Omsk",
  "Исилькуль": "Asia/Omsk",
  "Калачинск": "Asia/Omsk",
  "Тара": "Asia/Omsk",
  "Тюкалинск": "Asia/Omsk",

  // UTC+7
  "Новосибирск": "Asia/Novosibirsk",
  "Бердск": "Asia/Novosibirsk",
  "Искитим": "Asia/Novosibirsk",
  "Обь": "Asia/Novosibirsk",
  "Барабинск": "Asia/Novosibirsk",
  "Куйбышев": "Asia/Novosibirsk",
  "Татарск": "Asia/Novosibirsk",
  "Тогучин": "Asia/Novosibirsk",
  "Барнаул": "Asia/Barnaul",
  "Бийск": "Asia/Barnaul",
  "Рубцовск": "Asia/Barnaul",
  "Славгород": "Asia/Barnaul",
  "Яровое": "Asia/Barnaul",
  "Белокуриха": "Asia/Barnaul",
  "Горно-Алтайск": "Asia/Barnaul",
  "Томск": "Asia/Tomsk",
  "Северск": "Asia/Tomsk",
  "Кемерово": "Asia/Novokuznetsk",
  "Новокузнецк": "Asia/Novokuznetsk",
  "Прокопьевск": "Asia/Novokuznetsk",
  "Киселёвск": "Asia/Novokuznetsk",
  "Междуреченск": "Asia/Novokuznetsk",
  "Белово": "Asia/Novokuznetsk",
  "Ленинск-Кузнецкий": "Asia/Novokuznetsk",
  "Юрга": "Asia/Novokuznetsk",
  "Абакан": "Asia/Krasnoyarsk",
  "Красноярск": "Asia/Krasnoyarsk",
  "Ачинск": "Asia/Krasnoyarsk",
  "Канск": "Asia/Krasnoyarsk",
  "Назарово": "Asia/Krasnoyarsk",
  "Минусинск": "Asia/Krasnoyarsk",
  "Лесосибирск": "Asia/Krasnoyarsk",
  "Норильск": "Asia/Krasnoyarsk",
  "Дивногорск": "Asia/Krasnoyarsk",
  "Енисейск": "Asia/Krasnoyarsk",
  "Шарыпово": "Asia/Krasnoyarsk",
  "Заозерный": "Asia/Krasnoyarsk",
  "Ужур": "Asia/Krasnoyarsk",

  // UTC+8
  "Иркутск": "Asia/Irkutsk",
  "Ангарск": "Asia/Irkutsk",
  "Братск": "Asia/Irkutsk",
  "Усолье-Сибирское": "Asia/Irkutsk",
  "Шелехов": "Asia/Irkutsk",
  "Усть-Кут": "Asia/Irkutsk",
  "Тайшет": "Asia/Irkutsk",
  "Тулун": "Asia/Irkutsk",
  "Зима": "Asia/Irkutsk",
  "Улан-Удэ": "Asia/Irkutsk",

  // UTC+9
  "Чита": "Asia/Chita",
  "Якутск": "Asia/Yakutsk",
  "Благовещенск": "Asia/Yakutsk",

  // UTC+10
  "Владивосток": "Asia/Vladivostok",
  "Артём": "Asia/Vladivostok",
  "Артем": "Asia/Vladivostok",
  "Уссурийск": "Asia/Vladivostok",
  "Находка": "Asia/Vladivostok",
  "Хабаровск": "Asia/Vladivostok",

  // UTC+11
  "Южно-Сахалинск": "Asia/Sakhalin",
  "Магадан": "Asia/Magadan",

  // UTC+12
  "Петропавловск-Камчатский": "Asia/Kamchatka",

  // Не Россия
  "Бишкек": "Asia/Bishkek",
  "Кызыл-Кия": "Asia/Bishkek",

  // fallback
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

  for (const key in MANUAL_TZ) {
    if (normalizeCity(key) === normalized) {
      return MANUAL_TZ[key];
    }
  }

  const cached = getCachedTZ(normalized);
  if (cached) return cached;

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

function renderStatus(hour) {
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

    renderStatus(hour);

    if (window.BX24 && BX24.fitWindow) {
      BX24.fitWindow();
    }
  }

  render();
  setInterval(render, 1000);
}

function detectEntity(options) {
  const placement = String(options.placement || '').toUpperCase();
  const entityId = String(options.ENTITY_ID || '').toUpperCase();
  const hasDealId = !!options.DEAL_ID;
  const hasLeadId = !!options.LEAD_ID;
  const entityTypeId =
    String(options.ENTITY_TYPE_ID || '') ||
    String((options.ENTITY_DATA && options.ENTITY_DATA.entityTypeId) || '');

  if (
    entityId === 'DEAL' ||
    entityId === 'CRM_DEAL' ||
    hasDealId ||
    entityTypeId === '2' ||
    placement.includes('DEAL')
  ) {
    return 'deal';
  }

  if (
    entityId === 'LEAD' ||
    entityId === 'CRM_LEAD' ||
    hasLeadId ||
    entityTypeId === '1' ||
    placement.includes('LEAD')
  ) {
    return 'lead';
  }

  return 'lead';
}

function extractEntityValueId(options) {
  return (
    options.ENTITY_VALUE_ID ||
    options.ID ||
    options.id ||
    options.LEAD_ID ||
    options.DEAL_ID ||
    (options.ENTITY_DATA && (options.ENTITY_DATA.entityId || options.ENTITY_DATA.id)) ||
    null
  );
}

function parseCityValue(rawValue) {
  if (Array.isArray(rawValue)) {
    return rawValue[0] || '';
  }

  if (typeof rawValue === 'object' && rawValue !== null) {
    return rawValue.VALUE || rawValue.value || '';
  }

  return rawValue || '';
}

BX24.init(function() {
  const info = BX24.placement.info();
  const options = info && info.options ? info.options : {};

  const entity = detectEntity(options);
  const entityValueId = extractEntityValueId(options);

  BX24.callMethod('app.option.get', {}, function(optRes) {
    if (optRes.error()) {
      cityEl.textContent = 'Ошибка настроек';
      return;
    }

    const appOptions = optRes.data() || {};
    const field = entity === 'deal'
      ? appOptions.dealField
      : appOptions.leadField;

    if (!entityValueId || !field) {
      cityEl.textContent = 'Нет ID или не выбрано поле';
      return;
    }

    const method = entity === 'deal' ? 'crm.deal.get' : 'crm.lead.get';

    BX24.callMethod(method, { id: entityValueId }, function(res) {
      if (res.error()) {
        cityEl.textContent = 'Ошибка чтения карточки';
        return;
      }

      const item = res.data() || {};
      const rawCityValue = item[field];
      const city = parseCityValue(rawCityValue);

      if (!city) {
        cityEl.textContent = 'Город не заполнен';
        return;
      }

      cityEl.textContent = city;
      getTimezone(city).then(startClock);
    });
  });
});
</script>

</body>
</html>
