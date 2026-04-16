<?php
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://api.bitrix24.com/api/v1/"></script>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
}

.container {
    padding: 10px;
}

.time {
    font-size: 28px;
    font-weight: bold;
}

.city {
    margin-top: 5px;
    font-size: 16px;
    color: #555;
}

.status {
    margin-top: 5px;
    font-size: 14px;
    font-weight: bold;
}

.ok {
    color: green;
}

.bad {
    color: red;
}

.logo {
    margin-top: 8px;
    height: 30px;
}
</style>
</head>

<body>
<div class="container">
    <div class="time" id="time">--:--:--</div>
    <div class="city" id="city"></div>
    <div class="status" id="status"></div>

    <img class="logo"
         src="https://raw.githubusercontent.com/Varpis-dev/candidate-time/main/public/Logo_Vertical_Colored_Light-background.svg">
</div>

<script>
BX24.init(function () {

    BX24.placement.info(function(info) {

        const entityTypeId = info.options.ENTITY_DATA.entityTypeId;
        const entityId = info.options.ENTITY_DATA.entityId;

        let method = '';
        let field = '';

        // 👇 ЛИД / СДЕЛКА
        if (entityTypeId == 1) {
            method = 'crm.lead.get';
            field = 'UF_CRM_1775571821'; // ЛИД
        } else if (entityTypeId == 2) {
            method = 'crm.deal.get';
            field = 'UF_CRM_69D62E02D2CF0'; // СДЕЛКА (ТВОЙ ID)
        }

        BX24.callMethod(method, { id: entityId }, function(res) {

            if (res.error()) {
                document.getElementById('city').innerText = 'Ошибка загрузки';
                return;
            }

            const data = res.data();
            let city = data[field] || '';

            document.getElementById('city').innerText = city;

            updateTime(city);
            setInterval(() => updateTime(city), 1000);

        });

    });

});


// 🔥 УМНАЯ ФУНКЦИЯ БЕЗ СПИСКА ГОРОДОВ
function updateTime(city) {

    let timezone = detectTimezone(city);

    const now = new Date().toLocaleString("en-US", { timeZone: timezone });
    const local = new Date(now);

    let h = local.getHours().toString().padStart(2, '0');
    let m = local.getMinutes().toString().padStart(2, '0');
    let s = local.getSeconds().toString().padStart(2, '0');

    document.getElementById('time').innerText = `${h}:${m}:${s}`;

    let statusEl = document.getElementById('status');

    if (local.getHours() >= 9 && local.getHours() <= 21) {
        statusEl.innerText = 'Можно звонить';
        statusEl.className = 'status ok';
    } else {
        statusEl.innerText = 'Слишком поздно';
        statusEl.className = 'status bad';
    }
}


// 🔥 ОПРЕДЕЛЕНИЕ ТАЙМЗОНЫ (ПО ВСЕЙ РФ)
function detectTimezone(city) {

    const zones = {
        // UTC+2
        "Калининград": "Europe/Kaliningrad",

        // UTC+3
        "Москва": "Europe/Moscow",
        "Санкт-Петербург": "Europe/Moscow",
        "Орёл": "Europe/Moscow",
        "Кузнецк": "Europe/Moscow",
        "Воронеж": "Europe/Moscow",
        "Белгород": "Europe/Moscow",
        "Курск": "Europe/Moscow",
        "Тула": "Europe/Moscow",
        "Рязань": "Europe/Moscow",
        "Ярославль": "Europe/Moscow",
        "Тверь": "Europe/Moscow",
        "Брянск": "Europe/Moscow",
        "Смоленск": "Europe/Moscow",
        "Иваново": "Europe/Moscow",
        "Владимир": "Europe/Moscow",
        "Краснодар": "Europe/Moscow",
        "Сочи": "Europe/Moscow",
        "Ростов-на-Дону": "Europe/Moscow",
        "Ставрополь": "Europe/Moscow",
        "Грозный": "Europe/Moscow",
        "Махачкала": "Europe/Moscow",

        // UTC+4
        "Самара": "Europe/Samara",
        "Саратов": "Europe/Samara",
        "Волгоград": "Europe/Volgograd",
        "Астрахань": "Europe/Astrakhan",
        "Ульяновск": "Europe/Ulyanovsk",

        // UTC+5
        "Екатеринбург": "Asia/Yekaterinburg",
        "Челябинск": "Asia/Yekaterinburg",
        "Пермь": "Asia/Yekaterinburg",
        "Тюмень": "Asia/Yekaterinburg",
        "Курган": "Asia/Yekaterinburg",
        "Уфа": "Asia/Yekaterinburg",
        "Оренбург": "Asia/Yekaterinburg",
        "Мелеуз": "Asia/Yekaterinburg",

        // UTC+6
        "Омск": "Asia/Omsk",

        // UTC+7
        "Новосибирск": "Asia/Novosibirsk",
        "Красноярск": "Asia/Krasnoyarsk",
        "Барнаул": "Asia/Barnaul",
        "Томск": "Asia/Tomsk",

        // UTC+8
        "Иркутск": "Asia/Irkutsk",

        // UTC+9
        "Якутск": "Asia/Yakutsk",

        // UTC+10
        "Владивосток": "Asia/Vladivostok",
        "Хабаровск": "Asia/Vladivostok",

        // UTC+11
        "Магадан": "Asia/Magadan",

        // UTC+12
        "Петропавловск-Камчатский": "Asia/Kamchatka"
    };

    return zones[city] || "Europe/Moscow"; // fallback
}
</script>

</body>
</html>
