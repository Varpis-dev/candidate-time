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
    select, button { padding: 8px 10px; font-size: 14px; min-width: 320px; }
    button { min-width: auto; margin-top: 12px; }
    #status { margin-top: 14px; color: #555; white-space: pre-wrap; }
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

  <div>
    <button id="saveBtn">Сохранить</button>
    <button id="bindBtn">Создать поле и привязать</button>
  </div>

  <div id="status"></div>

  <script>
    function setStatus(text) {
      document.getElementById('status').textContent = text;
    }

    function loadFields(entity) {
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
          select.appendChild(option);
        });
      });
    }

    function saveOptions(callback) {
      const entity = document.getElementById('entity').value;
      const field = document.getElementById('field').value;

      BX24.callMethod('app.option.set', {
        options: { entity, field, appName: 'Время кандидата' }
      }, function(res) {
        if (res.error()) {
          setStatus('Ошибка сохранения: ' + JSON.stringify(res.error()));
          return;
        }

        setStatus('Настройки сохранены.');
        if (callback) callback(entity, field);
      });
    }

    BX24.init(function() {
      loadFields('lead');

      document.getElementById('entity').addEventListener('change', function() {
        loadFields(this.value);
      });

      document.getElementById('saveBtn').addEventListener('click', function() {
        saveOptions();
      });

      document.getElementById('bindBtn').addEventListener('click', function() {
        saveOptions(function(entity, field) {
          fetch('/bind', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entity, field })
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
