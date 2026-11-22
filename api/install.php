<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Установка приложения</title>
  <script src="https://api.bitrix24.com/api/v1/"></script>
  <script src="/registration.js"></script>
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; }
    button {
      padding: 10px 16px;
      font-size: 14px;
      margin-top: 20px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<h2>Установка приложения «Время клиента К2»</h2>
<p id="status" role="status" style="white-space: pre-wrap">Подключаемся к Битрикс24…</p>

<button id="retryBtn" hidden>Повторить установку</button>

<script>
const statusEl = document.getElementById('status');
const retryBtn = document.getElementById('retryBtn');
let installing = false;

async function runInstall() {
  if (installing) return;
  installing = true;
  retryBtn.hidden = true;
  try {
    await CandidateTimeRegistration.install(BX24, window.location.origin, text => {
      statusEl.textContent = text;
    });
    statusEl.textContent = 'Установка завершена. Откройте настройки приложения и выберите поля города и региона для лидов и сделок.';
  } catch (error) {
    statusEl.textContent = 'Установка не завершена. Проверьте права приложения (CRM и встраивание приложений) и повторите попытку.\n' + error.message;
    retryBtn.hidden = false;
  } finally {
    installing = false;
  }
}

retryBtn.addEventListener('click', runInstall);
BX24.init(runInstall);
</script>

</body>
</html>

