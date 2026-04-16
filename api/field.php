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
    body { margin:0; font-family: Arial, sans-serif; padding: 12px; }
    .label { color:#8c99a5; font-size:13px; margin-bottom:8px; }
    .time { font-size:34px; font-weight:700; }
    .city { margin-top:6px; font-size:16px; color:#555; }
    .status { margin-top:8px; font-size:14px; font-weight:500; }
    .ok { color:#0b8f3c; }
    .bad { color:#d92d20; }
  </style>
</head>
<body>
  <div class="label">Время кандидата</div>
  <div class="time" id="time">--:--:--</div>
  <div class="city" id="city">Загрузка...</div>
  <div class="status" id="status"></div>

  <script>
    const timeEl = document.getElementById('time');
    const cityEl = document.getElementById('city');
    const statusEl = document.getElementById('status');

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
      const cached = getCachedTZ(city);
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
        setCachedTZ(city, tz);
        return tz;
      } catch (e) {
        return 'Europe/Moscow';
      }
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
          return;
        }

        const method = entity === 'deal' ? 'crm.deal.get' : 'crm.lead.get';
        BX24.callMethod(method, { id: entityId }, function(res) {
          if (res.error()) {
            cityEl.textContent = 'Ошибка чтения карточки';
            return;
          }

          const item = res.data() || {};
          let city = item[field];

          if (Array.isArray(city)) city = city[0] || '';
          else if (typeof city === 'object' && city !== null) city = city.VALUE || city.value || '';
          else city = city || '';

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
