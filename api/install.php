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
    body { font-family: Arial; padding: 30px; }
    button {
      padding: 10px 16px;
      font-size: 14px;
      margin-top: 20px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<h2>Установка приложения «Время кандидата»</h2>
<p>Установка завершена.</p>

<button onclick="openSettings()">Открыть настройки</button>

<script>
  BX24.init(function () {
    BX24.installFinish();
  });

  function openSettings() {
    BX24.openApplication({
      application: 'settings'
    });
  }
</script>

</body>
</html>
