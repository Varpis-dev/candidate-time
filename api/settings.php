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
    #status { margin-top: 16px; white-space: pre-wrap; color: #555; }
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

  <div style="margin-top: 16px;">
    <button id="saveBtn">Сохранить настройки сущности</button>
    <button id="bindBtn">Создать поле и привязать</button>
  </div>

  <div id="status"></div>

  <script>
    function setStatus(text) {
      document.getElementById('status').textContent = text;
    }

    function getSelectedEntity() {
      return document.getElementById('entity').value;
    }

    function getSelectedField() {
      return document.getElementById('field').value;
    }

    function loadFields(entity, selectedField = '') {
      const method = entity === 'deal' ? 'crm.deal.fields' : 'crm.lead.fields';

      BX24.callMethod(method, {}, function(res) {
        if (res.error()) {
          setStatus('Ошибка загрузки полей: ' + JSON.stringify(res.error()));
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
      });
    }

    function loadSavedOptions() {
      BX24.callMethod('app.option.get', {}, function(res) {
        if (res.error()) {
          setStatus('Ошибка чтения настроек: ' + JSON.stringify(res.error()));
          loadFields('lead');
          return;
        }

        const options = res.data() || {};
        const entity = getSelectedEntity();

        const selectedField = entity === 'deal'
          ? (options.dealField || '')
          : (options.leadField || '');

        loadFields(entity, selectedField);
      });
    }

    function saveOptions(callback) {
      const entity = getSelectedEntity();
      const field = getSelectedField();

      BX24.callMethod('app.option.get', {}, function(getRes) {
        if (getRes.error()) {
          setStatus('Ошибка чтения текущих настроек: ' + JSON.stringify(getRes.error()));
          return;
        }

        const current = getRes.data() || {};

        if (entity === 'lead') {
          current.leadField = field;
        } else {
          current.dealField = field;
        }

        current.appName = 'Время кандидата';

        BX24.callMethod('app.option.set', {
          options: current
        }, function(saveRes) {
          if (saveRes.error()) {
            setStatus('Ошибка сохранения: ' + JSON.stringify(saveRes.error()));
            return;
          }

          setStatus('Настройки для ' + (entity === 'lead' ? 'лида' : 'сделки') + ' сохранены.');
          if (callback) callback(entity, field);
        });
      });
    }

    BX24.init(function() {
      loadSavedOptions();

      document.getElementById('entity').addEventListener('change', function() {
        loadSavedOptions();
      });

      document.getElementById('saveBtn').addEventListener('click', function() {
        saveOptions();
      });

      document.getElementById('bindBtn').addEventListener('click', function() {
        saveOptions(function(entity, field) {
          const auth = BX24.getAuth();

          if (!auth || !auth.access_token || !auth.domain) {
            setStatus('Не удалось получить авторизацию через BX24.getAuth()');
            return;
          }

          fetch('/bind', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              entity,
              field,
              auth
            })
          })
          .then(r => r.text())
          .then(text => setStatus(text))
          .catch(err => setStatus('Ошибка bind: ' + err));
        });
      });
    });
  </script>
</body>
</html>
