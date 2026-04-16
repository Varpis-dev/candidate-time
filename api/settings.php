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
      min-height: 60px;
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

    function loadFields(entity, selectedField = '') {
      setStatus('Загружаю поля для: ' + (entity === 'deal' ? 'сделки' : 'лида') + ' ...');

      const method = entity === 'deal' ? 'crm.deal.fields' : 'crm.lead.fields';

      BX24.callMethod(method, {}, function(res) {
        if (res.error()) {
          setStatus('Ошибка загрузки полей:\n' + JSON.stringify(res.error(), null, 2));
          return;
        }

        const select = document.getElementById('field');
        select.innerHTML = '';

        const fields = res.data() || {};
        Object.keys(fields).forEach(code => {
          const option = document.createElement('option');
          option.value = code;
          option.textContent = code + ' — ' + (fields[code].title || code);

          if (selectedField && selectedField === code) {
            option.selected = true;
          }

          select.appendChild(option);
        });

        setStatus('Поля загружены. Количество: ' + Object.keys(fields).length);
      });
    }

    function loadSavedOptions() {
      setStatus('Читаю сохранённые настройки...');

      BX24.callMethod('app.option.get', {}, function(res) {
        if (res.error()) {
          setStatus('Ошибка чтения app.option.get:\n' + JSON.stringify(res.error(), null, 2));
          loadFields('lead');
          return;
        }

        const options = res.data() || {};
        const entity = getSelectedEntity();

        const selectedField = entity === 'deal'
          ? (options.dealField || '')
          : (options.leadField || '');

        appendStatus('Настройки прочитаны.');
        loadFields(entity, selectedField);
      });
    }

    function saveOptions(callback) {
      const entity = getSelectedEntity();
      const field = getSelectedField();

      if (!field) {
        setStatus('Не выбрано поле города.');
        return;
      }

      setStatus('Читаю текущие настройки перед сохранением...');

      BX24.callMethod('app.option.get', {}, function(getRes) {
        if (getRes.error()) {
          setStatus('Ошибка чтения текущих настроек:\n' + JSON.stringify(getRes.error(), null, 2));
          return;
        }

        const current = getRes.data() || {};

        if (entity === 'lead') {
          current.leadField = field;
        } else {
          current.dealField = field;
        }

        current.appName = 'Время кандидата';

        appendStatus('Сохраняю настройки...');

        BX24.callMethod('app.option.set', {
          options: current
        }, function(saveRes) {
          if (saveRes.error()) {
            setStatus('Ошибка сохранения app.option.set:\n' + JSON.stringify(saveRes.error(), null, 2));
            return;
          }

          setStatus(
            'Настройки сохранены.\n' +
            'Сущность: ' + entity + '\n' +
            'Поле: ' + field
          );

          if (callback) callback(entity, field);
        });
      });
    }

    BX24.init(function() {
      setStatus('BX24.init OK');
      loadSavedOptions();

      document.getElementById('entity').addEventListener('change', function() {
        loadSavedOptions();
      });

      document.getElementById('saveBtn').addEventListener('click', function() {
        saveOptions();
      });

      document.getElementById('bindBtn').addEventListener('click', function() {
        saveOptions(function(entity, field) {
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
              entity,
              field,
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
    });
  </script>
</body>
</html>
