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
    :root {
      --navy: #16355a;
      --navy-dark: #0f2742;
      --teal: #33c9d0;
      --teal-soft: #e6f8f9;
      --text: #1d2b3a;
      --muted: #5f7186;
      --border: #d5e0ea;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 24px;
      font-family: Arial, sans-serif;
      color: var(--text);
      background: #f4f8fb;
    }

    .card {
      max-width: 620px;
      background: #fff;
      border: 1px solid var(--border);
      border-top: 4px solid var(--teal);
      border-radius: 16px;
      padding: 22px 24px 24px;
      box-shadow: 0 6px 20px rgba(22, 53, 90, 0.08);
    }

    .head {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 18px;
    }

    .head img {
      height: 36px;
      width: auto;
      display: block;
    }

    h2 {
      margin: 0;
      font-size: 20px;
      color: var(--navy);
    }

    .hint {
      margin: 2px 0 0;
      font-size: 13px;
      color: var(--muted);
    }

    label {
      display: block;
      margin: 16px 0 6px;
      font-weight: 700;
      font-size: 14px;
      color: var(--navy);
    }

    select {
      width: 100%;
      padding: 9px 10px;
      font-size: 14px;
      color: var(--text);
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 10px;
      outline: none;
    }

    select:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(51, 201, 208, 0.25);
    }

    .buttons {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    button {
      padding: 10px 16px;
      font-size: 14px;
      font-weight: 700;
      border-radius: 10px;
      cursor: pointer;
      border: 1px solid var(--navy);
    }

    #saveBtn {
      background: var(--navy);
      color: #fff;
    }

    #saveBtn:hover { background: var(--navy-dark); }

    #bindBtn {
      background: #fff;
      color: var(--navy);
    }

    #bindBtn:hover { background: var(--teal-soft); }

    #status {
      margin-top: 18px;
      white-space: pre-wrap;
      font-size: 13px;
      color: var(--text);
      background: var(--teal-soft);
      border-left: 4px solid var(--teal);
      padding: 12px 14px;
      border-radius: 10px;
      min-height: 60px;
    }
  </style>
</head>
<body>
<div class="card">
  <div class="head">
    <img src="/logo-k2.png" alt="">
    <div>
      <h2>Время кандидата</h2>
      <p class="hint">Из каких полей брать город и регион кандидата</p>
    </div>
  </div>

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
    <button id="saveBtn">Сохранить настройки</button>
    <button id="bindBtn">Создать поле и привязать</button>
  </div>

  <div id="status">Страница загружена.</div>
</div>

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

function fieldName(meta, code) {
  meta = meta || {};
  const candidates = [meta.listLabel, meta.formLabel, meta.filterLabel, meta.editorLabel, meta.title];
  for (const c of candidates) {
    const v = typeof c === 'object' && c !== null ? (c.ru || Object.values(c)[0]) : c;
    if (v && String(v).trim() && String(v) !== code) return String(v).trim();
  }
  return code;
}

function fillSelect(select, list, saved) {
  const groups = [
    { label: 'Пользовательские поля', items: list.filter(f => f.custom) },
    { label: 'Стандартные поля', items: list.filter(f => !f.custom) }
  ];

  groups.forEach(g => {
    if (!g.items.length) return;
    const og = document.createElement('optgroup');
    og.label = g.label;
    g.items.forEach(f => {
      const opt = document.createElement('option');
      opt.value = f.code;
      opt.textContent = f.name === f.code ? f.code : f.name + ' (' + f.code + ')';
      if (f.code === saved) opt.selected = true;
      og.appendChild(opt);
    });
    select.appendChild(og);
  });
}

function selectedLabel(selectId) {
  const sel = document.getElementById(selectId);
  const opt = sel.options[sel.selectedIndex];
  return opt && opt.value ? opt.textContent : '';
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

  const list = Object.keys(fields || {}).map(code => ({
    code,
    name: fieldName(fields[code], code),
    custom: code.indexOf('UF_') === 0
  }));

  list.sort((x, y) => x.name.localeCompare(y.name, 'ru'));

  fillSelect(citySelect, list, savedCity);
  fillSelect(regionSelect, list, savedRegion);

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
    'Поле города: ' + selectedLabel('cityField') + '\n' +
    'Поле области: ' + (regionField ? selectedLabel('regionField') : 'не используется')
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
