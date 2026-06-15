<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Время кандидата — настройки</title>
  <script src="https://api.bitrix24.com/api/v1/"></script>
  <style>
    body { font-family: Arial, sans-serif; padding: 24px; }
    h2 { margin-top: 0; }
    label { display:block; margin: 14px 0 6px; font-weight: 600; }
    select, button { padding: 8px 10px; font-size: 14px; min-width: 360px; }
    .buttons { margin-top: 16px; display:flex; gap:10px; flex-wrap: wrap; }
    #status {
      margin-top: 16px;
      white-space: pre-wrap;
      color: #444;
      background: #f6f8fa;
      padding: 12px;
      border-radius: 10px;
      min-height: 70px;
    }
  </style>
</head>
<body>
  <h2>Время кандидата</h2>

  <label for="entity">Сущность</label>
  <select id="entity">
    <option value="lead">Лид</option>
    <option value="deal">Сделка</option>
  </select>

  <label for="cityField">Поле с городом</label>
  <select id="cityField"></select>

  <label for="regionField">Поле с областью / регионом</label>
  <select id="regionField"></select>

  <div class="buttons">
    <button id="saveBtn">Сохранить настройки сущности</button>
    <button id="bindBtn">Создать поле и привязать</button>
  </div>

  <div id="status">Страница загружена.</div>

<script>
const statusEl = document.getElementById('status');

function setStatus(text) {
  statusEl.textContent = text;
}

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

function getEntity() {
  return document.getElementById('entity').value;
}

function getCityField() {
  return document.getElementById('cityField').value;
}

function getRegionField() {
  return document.getElementById('regionField').value;
}

async function loadFields() {
  const entity = getEntity();
  const method = entity === 'deal' ? 'crm.deal.fields' : 'crm.lead.fields';

  setStatus('Загружаю поля...');

  const options = await bxCall('app.option.get', {});
  const fields = await bxCall(method, {});

  const citySelect = document.getElementById('cityField');
  const regionSelect = document.getElementById('regionField');

  citySelect.innerHTML = '';
  regionSelect.innerHTML = '';

  const emptyRegion = document.createElement('option');
  emptyRegion.value = '';
  emptyRegion.textContent = 'Не использовать область';
  regionSelect.appendChild(emptyRegion);

  const savedCity = entity === 'deal'
    ? (options.dealCityField || options.dealField || '')
    : (options.leadCityField || options.leadField || '');

  const savedRegion = entity === 'deal'
    ? (options.dealRegionField || '')
    : (options.leadRegionField || '');

  Object.keys(fields || {}).forEach(code => {
    const title = fields[code].title || fields[code].formLabel || code;

    const cityOpt = document.createElement('option');
    cityOpt.value = code;
    cityOpt.textContent = code + ' — ' + title;
    if (code === savedCity) cityOpt.selected = true;
    citySelect.appendChild(cityOpt);

    const regionOpt = document.createElement('option');
    regionOpt.value = code;
    regionOpt.textContent = code + ' — ' + title;
    if (code === savedRegion) regionOpt.selected = true;
    regionSelect.appendChild(regionOpt);
  });

  setStatus(
    'Поля загружены.\n' +
    'Сущность: ' + (entity === 'deal' ? 'Сделка' : 'Лид')
  );
}

async function saveOptions() {
  const entity = getEntity();
  const cityField = getCityField();
  const regionField = getRegionField();

  if (!cityField) {
    setStatus('Не выбрано поле города.');
    return null;
  }

  setStatus('Сохраняю настройки...');

  const current = await bxCall('app.option.get', {});

  const payload = {
    appName: 'Время кандидата',

    leadCityField: current.leadCityField || current.leadField || '',
    leadRegionField: current.leadRegionField || '',

    dealCityField: current.dealCityField || current.dealField || '',
    dealRegionField: current.dealRegionField || ''
  };

  if (entity === 'lead') {
    payload.leadCityField = cityField;
    payload.leadRegionField = regionField;
  } else {
    payload.dealCityField = cityField;
    payload.dealRegionField = regionField;
  }

  await bxCall('app.option.set', { options: payload });

  setStatus(
    'Настройки сохранены.\n' +
    'Сущность: ' + (entity === 'deal' ? 'Сделка' : 'Лид') + '\n' +
    'Поле города: ' + cityField + '\n' +
    'Поле области: ' + (regionField || 'не используется')
  );

  return { entity, cityField, regionField };
}

BX24.init(async function() {
  try {
    await loadFields();

    document.getElementById('entity').addEventListener('change', async function() {
      try {
        await loadFields();
      } catch (e) {
        setStatus('Ошибка загрузки полей:\n' + String(e));
      }
    });

    document.getElementById('saveBtn').addEventListener('click', async function() {
      try {
        await saveOptions();
      } catch (e) {
        setStatus('Ошибка сохранения:\n' + String(e));
      }
    });

    document.getElementById('bindBtn').addEventListener('click', async function() {
      try {
        const saved = await saveOptions();
        if (!saved) return;

        const auth = BX24.getAuth();

        if (!auth || !auth.access_token || !auth.domain) {
          setStatus('Не удалось получить авторизацию Б24');
          return;
        }

        const r = await fetch('/bind', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            entity: saved.entity,
            field: saved.cityField,
            auth
          })
        });

        const text = await r.text();
        setStatus('Ответ /bind:\nHTTP ' + r.status + '\n\n' + text);
      } catch (e) {
        setStatus('Ошибка создания поля:\n' + String(e));
      }
    });

  } catch (e) {
    setStatus('Ошибка инициализации:\n' + String(e));
  }
});
</script>
</body>
</html>
