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
    select, button { padding: 8px 10px; font-size: 14px; }
    .buttons { margin-top: 16px; display:flex; gap:10px; flex-wrap: wrap; }
    #status {
      margin-top: 16px;
      white-space: pre-wrap;
      color: #444;
      background: #f6f8fa;
      padding: 12px;
      border-radius: 10px;
      min-height: 80px;
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

  <label for="field">Поле с городом</label>
  <select id="field"></select>

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

    function appendStatus(text) {
      statusEl.textContent += '\n' + text;
    }

    function getSelectedEntity() {
      return document.getElementById('entity').value;
    }

    function getSelectedField() {
      return document.getElementById('field').value;
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

    async function loadFields(entity, selectedField = '') {
      try {
        setStatus('Загружаю поля для: ' + (entity === 'deal' ? 'сделки' : 'лида') + ' ...');

        const method = entity === 'deal' ? 'crm.deal.fields' : 'crm.lead.fields';
        const fields = await bxCall(method, {});

        const select = document.getElementById('field');
        select.innerHTML = '';

        Object.keys(fields || {}).forEach(code => {
          const option = document.createElement('option');
          option.value = code;
          option.textContent = code + ' — ' + (fields[code].title || code);

          if (selectedField && selectedField === code) {
            option.selected = true;
          }

          select.appendChild(option);
        });

        setStatus('Поля загружены. Количество: ' + Object.keys(fields || {}).length);
      } catch (e) {
        setStatus('Ошибка загрузки полей:\n' + String(e));
      }
    }

    async function loadSavedOptions() {
      try {
        setStatus('Читаю сохранённые настройки...');
        const options = await bxCall('app.option.get', {});
        const entity = getSelectedEntity();

        const selectedField = entity === 'deal'
          ? (options.dealField || '')
          : (options.leadField || '');

        appendStatus('Настройки прочитаны.');
        await loadFields(entity, selectedField);
      } catch (e) {
        setStatus('Ошибка чтения app.option.get:\n' + String(e));
        await loadFields('lead');
      }
    }

    async function saveOptions() {
      const entity = getSelectedEntity();
      const field = getSelectedField();

      if (!field) {
        setStatus('Не выбрано поле города.');
        return null;
      }

      try {
        setStatus('Читаю текущие настройки перед сохранением...');
        const current = await bxCall('app.option.get', {});

        const payload = {
          appName: 'Время кандидата',
          leadField: current.leadField || '',
          dealField: current.dealField || ''
        };

        if (entity === 'lead') {
          payload.leadField = field;
        } else {
          payload.dealField = field;
        }

        appendStatus('Сохраняю настройки...');
        await bxCall('app.option.set', { options: payload });

        setStatus(
          'Настройки сохранены.\n' +
          'Сущность: ' + entity + '\n' +
          'Поле: ' + field + '\n' +
          'leadField: ' + payload.leadField + '\n' +
          'dealField: ' + payload.dealField
        );

        return { entity, field };
      } catch (e) {
        setStatus('Ошибка сохранения:\n' + String(e));
        return null;
      }
    }

    BX24.init(async function() {
      setStatus('BX24.init OK');
      await loadSavedOptions();

      document.getElementById('entity').addEventListener('change', async function() {
        await loadSavedOptions();
      });

      document.getElementById('saveBtn').addEventListener('click', async function() {
        await saveOptions();
      });

      document.getElementById('bindBtn').addEventListener('click', async function() {
        const saved = await saveOptions();
        if (!saved) return;

        appendStatus('Получаю BX24 auth...');

        const auth = BX24.getAuth();

        if (!auth || !auth.access_token || !auth.domain) {
          setStatus('Не удалось получить авторизацию через BX24.getAuth()');
          return;
        }

        appendStatus('Auth получен. Отправляю запрос в /bind ...');

        fetch('/bind', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            entity: saved.entity,
            field: saved.field,
            auth
          })
        })
        .then(async (r) => {
          const text = await r.text();
          setStatus(
            'Ответ /bind:\n' +
            'HTTP ' + r.status + '\n\n' +
            text
          );
        })
        .catch(err => {
          setStatus('Ошибка fetch(/bind):\n' + String(err));
        });
      });
    });
  </script>
</body>
</html>
