<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Установка приложения</title>
  <script src="https://api.bitrix24.com/api/v1/"></script>
  <style>
    body { font-family: Arial, sans-serif; padding: 24px; }
  </style>
</head>
<body>
  <h2>Установка приложения «Время кандидата»</h2>
  <div id="status">Завершаем установку...</div>

  <script>
    BX24.init(function () {
      BX24.installFinish();
      document.getElementById('status').textContent =
        'Установка завершена. Теперь открой настройки приложения.';
    });
  </script>
</body>
</html>
