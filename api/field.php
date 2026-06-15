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
  "Калининград": "Europe/Kaliningrad",
  "Балтийск": "Europe/Kaliningrad",
  "Советск": "Europe/Kaliningrad",

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
  "Нижний Новгород": "Europe/Moscow",
  "Казань": "Europe/Moscow",
  "Набережные Челны": "Europe/Moscow",
  "Чебоксары": "Europe/Moscow",
  "Йошкар-Ола": "Europe/Moscow",
  "Саранск": "Europe/Moscow",
  "Липецк": "Europe/Moscow",
  "Тамбов": "Europe/Moscow",
  "Волгоград": "Europe/Moscow",

  "Самара": "Europe/Samara",
  "Тольятти": "Europe/Samara",
  "Ульяновск": "Europe/Samara",
  "Саратов": "Europe/Samara",
  "Энгельс": "Europe/Samara",
  "Астрахань": "Europe/Samara",
  "Балаково": "Europe/Samara",
  "Балашов": "Europe/Samara",
  "Вольск": "Europe/Samara",
  "Маркс": "Europe/Samara",
  "Пугачев": "Europe/Samara",
  "Ртищево": "Europe/Samara",
  "Ершов": "Europe/Samara",
  "Новоузенск": "Europe/Samara",
  "Озинки": "Europe/Samara",
  "Хвалынск": "Europe/Samara",

  "Ижевск": "Europe/Samara",
  "Сарапул": "Europe/Samara",
  "Воткинск": "Europe/Samara",
  "Можга": "Europe/Samara",

  "Екатеринбург": "Asia/Yekaterinburg",
  "Мелеуз": "Asia/Yekaterinburg",
  "Уфа": "Asia/Yekaterinburg",
  "Стерлитамак": "Asia/Yekaterinburg",
  "Салават": "Asia/Yekaterinburg",
  "Ишимбай": "Asia/Yekaterinburg",
  "Нефтекамск": "Asia/Yekaterinburg",
  "Октябрьский": "Asia/Yekaterinburg",
  "Туймазы": "Asia/Yekaterinburg",
  "Белебей": "Asia/Yekaterinburg",
  "Белорецк": "Asia/Yekaterinburg",
  "Бирск": "Asia/Yekaterinburg",
  "Дюртюли": "Asia/Yekaterinburg",
  "Учалы": "Asia/Yekaterinburg",
  "Янаул": "Asia/Yekaterinburg",

  "Челябинск": "Asia/Yekaterinburg",
  "Магнитогорск": "Asia/Yekaterinburg",
  "Миасс": "Asia/Yekaterinburg",
  "Златоуст": "Asia/Yekaterinburg",
  "Копейск": "Asia/Yekaterinburg",
  "Троицк": "Asia/Yekaterinburg",

  "Тюмень": "Asia/Yekaterinburg",
  "Ишим": "Asia/Yekaterinburg",
  "Тобольск": "Asia/Yekaterinburg",
  "Ялуторовск": "Asia/Yekaterinburg",

  "Курган": "Asia/Yekaterinburg",
  "Шадринск": "Asia/Yekaterinburg",

  "Оренбург": "Asia/Yekaterinburg",
  "Орск": "Asia/Yekaterinburg",
  "Бузулук": "Asia/Yekaterinburg",
  "Бугуруслан": "Asia/Yekaterinburg",
  "Новотроицк": "Asia/Yekaterinburg",

  "Пермь": "Asia/Yekaterinburg",
  "Березники": "Asia/Yekaterinburg",
  "Соликамск": "Asia/Yekaterinburg",
  "Краснокамск": "Asia/Yekaterinburg",
  "Кунгур": "Asia/Yekaterinburg",

  "Сургут": "Asia/Yekaterinburg",
  "Нижневартовск": "Asia/Yekaterinburg",
  "Ханты-Мансийск": "Asia/Yekaterinburg",
  "Нефтеюганск": "Asia/Yekaterinburg",
  "Когалым": "Asia/Yekaterinburg",
  "Мегион": "Asia/Yekaterinburg",
  "Нягань": "Asia/Yekaterinburg",
  "Урай": "Asia/Yekaterinburg",
  "Югорск": "Asia/Yekaterinburg",
  "Пыть-Ях": "Asia/Yekaterinburg",

  "Новый Уренгой": "Asia/Yekaterinburg",
  "Ноябрьск": "Asia/Yekaterinburg",
  "Надым": "Asia/Yekaterinburg",
  "Салехард": "Asia/Yekaterinburg",
  "Лабытнанги": "Asia/Yekaterinburg",
  "Тарко-Сале": "Asia/Yekaterinburg",

  "Омск": "Asia/Omsk",
  "Исилькуль": "Asia/Omsk",
  "Калачинск": "Asia/Omsk",
  "Тара": "Asia/Omsk",

  "Новосибирск": "Asia/Novosibirsk",
  "Бердск": "Asia/Novosibirsk",
  "Искитим": "Asia/Novosibirsk",
  "Обь": "Asia/Novosibirsk",
  "Барабинск": "Asia/Novosibirsk",

  "Барнаул": "Asia/Barnaul",
  "Бийск": "Asia/Barnaul",
  "Рубцовск": "Asia/Barnaul",
  "Белокуриха": "Asia/Barnaul",
  "Горно-Алтайск": "Asia/Barnaul",

  "Томск": "Asia/Tomsk",
  "Северск": "Asia/Tomsk",

  "Кемерово": "Asia/Novokuznetsk",
  "Новокузнецк": "Asia/Novokuznetsk",
  "Прокопьевск": "Asia/Novokuznetsk",
  "Киселёвск": "Asia/Novokuznetsk",
  "Киселевск": "Asia/Novokuznetsk",
  "Междуреченск": "Asia/Novokuznetsk",
  "Белово": "Asia/Novokuznetsk",
  "Ленинск-Кузнецкий": "Asia/Novokuznetsk",
  "Юрга": "Asia/Novokuznetsk",
  "Мыски": "Asia/Novokuznetsk",

  "Абакан": "Asia/Krasnoyarsk",
  "Черногорск": "Asia/Krasnoyarsk",
  "Красноярск": "Asia/Krasnoyarsk",
  "Ачинск": "Asia/Krasnoyarsk",
  "Канск": "Asia/Krasnoyarsk",
  "Назарово": "Asia/Krasnoyarsk",
  "Минусинск": "Asia/Krasnoyarsk",
  "Норильск": "Asia/Krasnoyarsk",
  "Дивногорск": "Asia/Krasnoyarsk",

  "Кызыл": "Asia/Krasnoyarsk",

  "Иркутск": "Asia/Irkutsk",
  "Ангарск": "Asia/Irkutsk",
  "Братск": "Asia/Irkutsk",
  "Усолье-Сибирское": "Asia/Irkutsk",
  "Шелехов": "Asia/Irkutsk",
  "Усть-Кут": "Asia/Irkutsk",
  "Тулун": "Asia/Irkutsk",

  "Улан-Удэ": "Asia/Irkutsk",

  "Чита": "Asia/Chita",

  "Якутск": "Asia/Yakutsk",
  "Благовещенск": "Asia/Yakutsk",

  "Владивосток": "Asia/Vladivostok",
  "Артём": "Asia/Vladivostok",
  "Артем": "Asia/Vladivostok",
  "Уссурийск": "Asia/Vladivostok",
  "Находка": "Asia/Vladivostok",
  "Хабаровск": "Asia/Vladivostok",

  "Южно-Сахалинск": "Asia/Sakhalin",
  "Магадан": "Asia/Magadan",
  "Петропавловск-Камчатский": "Asia/Kamchatka",

  "Бишкек": "Asia/Bishkek",
  "Кызыл-Кия": "Asia/Bishkek",

  "Другой город": "Europe/Moscow"
};

const REGION_TZ = {
  "калининград": "Europe/Kaliningrad",

  "москва": "Europe/Moscow",
  "московск": "Europe/Moscow",
  "санкт-петербург": "Europe/Moscow",
  "ленинград": "Europe/Moscow",
  "краснодар": "Europe/Moscow",
  "ростов": "Europe/Moscow",
  "воронеж": "Europe/Moscow",
  "белгород": "Europe/Moscow",
  "курск": "Europe/Moscow",
  "орлов": "Europe/Moscow",
  "тульск": "Europe/Moscow",
  "рязан": "Europe/Moscow",
  "ярослав": "Europe/Moscow",
  "твер": "Europe/Moscow",
  "брянск": "Europe/Moscow",
  "смоленск": "Europe/Moscow",
  "иванов": "Europe/Moscow",
  "владимир": "Europe/Moscow",
  "нижегород": "Europe/Moscow",
  "татарстан": "Europe/Moscow",
  "чуваш": "Europe/Moscow",
  "марий": "Europe/Moscow",
  "мордов": "Europe/Moscow",
  "киров": "Europe/Moscow",
  "пенз": "Europe/Moscow",
  "липецк": "Europe/Moscow",
  "тамбов": "Europe/Moscow",
  "крым": "Europe/Moscow",
  "севастопол": "Europe/Moscow",
  "ставропол": "Europe/Moscow",
  "дагестан": "Europe/Moscow",
  "чечен": "Europe/Moscow",
  "ингуш": "Europe/Moscow",
  "осет": "Europe/Moscow",
  "кабардино": "Europe/Moscow",
  "карачаево": "Europe/Moscow",
  "адыге": "Europe/Moscow",
  "калмык": "Europe/Moscow",
  "архангел": "Europe/Moscow",
  "мурман": "Europe/Moscow",
  "карел": "Europe/Moscow",
  "коми": "Europe/Moscow",
  "вологод": "Europe/Moscow",
  "новгород": "Europe/Moscow",
  "псков": "Europe/Moscow",
  "волгоград": "Europe/Moscow",

  "самар": "Europe/Samara",
  "саратов": "Europe/Samara",
  "астрахан": "Europe/Samara",
  "ульянов": "Europe/Samara",
  "удмурт": "Europe/Samara",

  "свердлов": "Asia/Yekaterinburg",
  "челябин": "Asia/Yekaterinburg",
  "тюмен": "Asia/Yekaterinburg",
  "курган": "Asia/Yekaterinburg",
  "башкортостан": "Asia/Yekaterinburg",
  "башкир": "Asia/Yekaterinburg",
  "оренбург": "Asia/Yekaterinburg",
  "перм": "Asia/Yekaterinburg",
  "ханты": "Asia/Yekaterinburg",
  "югра": "Asia/Yekaterinburg",
  "ямало": "Asia/Yekaterinburg",
  "ненец": "Asia/Yekaterinburg",

  "омск": "Asia/Omsk",

  "новосибир": "Asia/Novosibirsk",
  "алтайский": "Asia/Barnaul",
  "республика алтай": "Asia/Barnaul",
  "томск": "Asia/Tomsk",
  "кемеров": "Asia/Novokuznetsk",
  "кузбасс": "Asia/Novokuznetsk",
  "краснояр": "Asia/Krasnoyarsk",
  "хакас": "Asia/Krasnoyarsk",
  "тыва": "Asia/Krasnoyarsk",
  "тува": "Asia/Krasnoyarsk",

  "иркут": "Asia/Irkutsk",
  "бурят": "Asia/Irkutsk",

  "забайкал": "Asia/Chita",

  "якут": "Asia/Yakutsk",
  "саха": "Asia/Yakutsk",
  "амур": "Asia/Yakutsk",
  "еврей": "Asia/Yakutsk",

  "примор": "Asia/Vladivostok",
  "хабаров": "Asia/Vladivostok",

  "сахалин": "Asia/Sakhalin",
  "магадан": "Asia/Magadan",
  "камчат": "Asia/Kamchatka",
  "чукот": "Asia/Anadyr"
};

function bxCall(method, params = {}, timeoutMs = 15000) {
  return new Promise((resolve, reject) => {
    let finished = false;

    const timer = setTimeout(() => {
      if (!finished) {
        finished = true;
        reject(new Error('Таймаут вызова ' + method));
      }
    }, timeoutMs);

    try {
      BX24.callMethod(method, params, function(res) {
        if (finished) return;

        finished = true;
        clearTimeout(timer);

        if (!res) {
          reject(new Error('Пустой ответ от ' + method));
          return;
        }

        if (res.error()) {
          reject(new Error(JSON.stringify(res.error(), null, 2)));
          return;
        }

        resolve(res.data());
      });
    } catch (e) {
      if (!finished) {
        finished = true;
        clearTimeout(timer);
        reject(e);
      }
    }
  });
}

function normalizeCity(city) {
  return String(city || '')
    .replace(/\s+/g, ' ')
    .replace(/[ёЁ]/g, 'е')
    .trim();
}

function normalizeRegion(region) {
  return String(region || '')
    .toLowerCase()
    .replace(/[ё]/g, 'е')
    .replace(/\s+/g, ' ')
    .replace(/область|обл\.?|республика|респ\.?|край|ао|автономный округ|г\.|город/gi, '')
    .replace(/[()"«»]/g, '')
    .trim();
}

function getTimezoneByCity(city) {
  const normalized = normalizeCity(city);

  for (const key in MANUAL_TZ) {
    if (normalizeCity(key) === normalized) {
      return MANUAL_TZ[key];
    }
  }

  return null;
}

function getTimezoneByRegion(region) {
  const normalized = normalizeRegion(region);

  if (!normalized) return null;

  for (const key in REGION_TZ) {
    if (normalized.includes(key) || key.includes(normalized)) {
      return REGION_TZ[key];
    }
  }

  return null;
}

function getCachedTZ(city, region) {
  const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
  return cache[normalizeCity(city) + '|' + normalizeRegion(region)];
}

function setCachedTZ(city, region, tz) {
  const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
  cache[normalizeCity(city) + '|' + normalizeRegion(region)] = tz;
  localStorage.setItem('tz_cache', JSON.stringify(cache));
}

async function getTimezone(city, region) {
  const regionTz = getTimezoneByRegion(region);
  if (regionTz) return regionTz;

  const cityTz = getTimezoneByCity(city);
  if (cityTz) return cityTz;

  const cached = getCachedTZ(city, region);
  if (cached) return cached;

  try {
    const query = region
      ? city + ', ' + region + ', Россия'
      : city + ', Россия';

    const geoRes = await fetch(
      'https://nominatim.openstreetmap.org/search?format=json&q=' +
      encodeURIComponent(query)
    );

    const geoData = await geoRes.json();

    if (!geoData.length) return 'Europe/Moscow';

    const lat = geoData[0].lat;
    const lon = geoData[0].lon;

    const tzRes = await fetch(
      'https://timeapi.io/api/Time/current/coordinate?latitude=' +
      lat + '&longitude=' + lon
    );

    const tzData = await tzRes.json();
    const tz = tzData.timeZone || 'Europe/Moscow';

    setCachedTZ(city, region, tz);
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

function getFieldEnumItems(fieldMeta) {
  if (!fieldMeta) return [];

  return (
    fieldMeta.items ||
    fieldMeta.ITEMS ||
    fieldMeta.list ||
    fieldMeta.LIST ||
    fieldMeta.values ||
    fieldMeta.VALUES ||
    []
  );
}

function parseFieldValue(rawValue, fieldCode, fieldsMeta) {
  if (Array.isArray(rawValue)) {
    rawValue = rawValue[0] || '';
  }

  if (typeof rawValue === 'object' && rawValue !== null) {
    rawValue = rawValue.VALUE || rawValue.value || rawValue.ID || rawValue.id || '';
  }

  let value = rawValue || '';

  const fieldMeta = fieldsMeta ? fieldsMeta[fieldCode] : null;
  const items = getFieldEnumItems(fieldMeta);

  if (items && items.length) {
    const found = items.find(item => {
      const id = String(item.ID || item.id || item.VALUE_ID || item.valueId || '');
      const val = String(item.VALUE || item.value || item.NAME || item.name || '');
      return id === String(value) || val === String(value);
    });

    if (found) {
      return found.VALUE || found.value || found.NAME || found.name || value;
    }
  }

  return value;
}

BX24.init(async function() {
  try {
    const info = BX24.placement.info();
    const options = info && info.options ? info.options : {};

    const entity = detectEntity(options);
    const entityValueId = extractEntityValueId(options);

    const appOptions = await bxCall('app.option.get', {});

    const cityField = entity === 'deal'
      ? (appOptions.dealCityField || appOptions.dealField)
      : (appOptions.leadCityField || appOptions.leadField);

    const regionField = entity === 'deal'
      ? appOptions.dealRegionField
      : appOptions.leadRegionField;

    if (!entityValueId || !cityField) {
      cityEl.textContent = 'Нет ID или не выбрано поле';
      return;
    }

    const getMethod = entity === 'deal' ? 'crm.deal.get' : 'crm.lead.get';
    const fieldsMethod = entity === 'deal' ? 'crm.deal.fields' : 'crm.lead.fields';

    const item = await bxCall(getMethod, { id: entityValueId });
    const fieldsMeta = await bxCall(fieldsMethod, {});

    const rawCityValue = item[cityField];
    const rawRegionValue = regionField ? item[regionField] : '';

    const city = parseFieldValue(rawCityValue, cityField, fieldsMeta);
    const region = regionField ? parseFieldValue(rawRegionValue, regionField, fieldsMeta) : '';

    if (!city) {
      cityEl.textContent = 'Город не заполнен';
      return;
    }

    cityEl.textContent = region ? city + ', ' + region : city;

    const tz = await getTimezone(city, region);
    startClock(tz);

  } catch (e) {
    cityEl.textContent = 'Ошибка загрузки';
  }
});
</script>

</body>
</html>
