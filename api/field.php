<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Время кандидата</title>
  <script src="https://api.bitrix24.com/api/v1/"></script>

  <style>
    html, body {
      margin: 0;
      padding: 0;
      background: transparent;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }

    .wrap {
      box-sizing: border-box;
      width: 100%;
      padding: 8px 10px;
      border-radius: 14px;
      background: #fff;
    }

    .time {
      font-size: 28px;
      line-height: 1;
      font-weight: 700;
      color: #111;
      margin-bottom: 6px;
    }

    .city {
      font-size: 15px;
      color: #444;
      line-height: 1.2;
    }

    .status-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 6px;
    }

    .status {
      font-size: 13px;
      font-weight: 500;
      line-height: 1.2;
    }

    .ok { color: #0b8f3c; }
    .bad { color: #d92d20; }

    .logo {
      height: 20px;
      width: auto;
      flex-shrink: 0;
    }
  </style>
</head>

<body>
<div class="wrap">
  <div class="time" id="time">--:--:--</div>
  <div class="city" id="city">Загрузка...</div>

  <div class="status-wrap">
    <img
      class="logo"
      src="https://raw.githubusercontent.com/Varpis-dev/candidate-time/main/public/Logo_Vertical_Colored_Light-background.svg"
      alt=""
    >
    <div id="status" class="status"></div>
  </div>
</div>

<script>
const timeEl = document.getElementById('time');
const cityEl = document.getElementById('city');
const statusEl = document.getElementById('status');

// Основная карта таймзон.
// Всё, чего нет здесь, определяем через API fallback ниже.
const MANUAL_TZ = {
  // UTC+2
  "Калининград": "Europe/Kaliningrad",
  "Балтийск": "Europe/Kaliningrad",
  "Советск": "Europe/Kaliningrad",

  // UTC+3
  "Москва": "Europe/Moscow",
  "Санкт-Петербург": "Europe/Moscow",
  "Абинск": "Europe/Moscow",
  "Адлер": "Europe/Moscow",
  "Азов": "Europe/Moscow",
  "Александров": "Europe/Moscow",
  "Алексин": "Europe/Moscow",
  "Анапа": "Europe/Moscow",
  "Анна": "Europe/Moscow",
  "Апатиты": "Europe/Moscow",
  "Апрелевка": "Europe/Moscow",
  "Арзамас": "Europe/Moscow",
  "Аркадак": "Europe/Moscow",
  "Армавир": "Europe/Moscow",
  "Архангельск": "Europe/Moscow",
  "Архипо-Осиповка": "Europe/Moscow",
  "Афипский": "Europe/Moscow",
  "Ахтубинск": "Europe/Moscow",
  "Бабаево": "Europe/Moscow",
  "Багаевская": "Europe/Moscow",
  "Базарный Карабулак": "Europe/Moscow",
  "Балахна": "Europe/Moscow",
  "Балашиха": "Europe/Moscow",
  "Балашов": "Europe/Moscow",
  "Батайск": "Europe/Moscow",
  "Бахчисарай": "Europe/Moscow",
  "Бежецк": "Europe/Moscow",
  "Белая Глина": "Europe/Moscow",
  "Белая Калитва": "Europe/Moscow",
  "Белгород": "Europe/Moscow",
  "Белозерск": "Europe/Moscow",
  "Беломорск": "Europe/Moscow",
  "Белореченск": "Europe/Moscow",
  "Береза": "Europe/Moscow",
  "Беслан": "Europe/Moscow",
  "Бестужевское": "Europe/Moscow",
  "Бобров": "Europe/Moscow",
  "Боброво": "Europe/Moscow",
  "Богородицк": "Europe/Moscow",
  "Богородск": "Europe/Moscow",
  "Богучар": "Europe/Moscow",
  "Бокситогорск": "Europe/Moscow",
  "Бор": "Europe/Moscow",
  "Борисовка": "Europe/Moscow",
  "Борисоглебск": "Europe/Moscow",
  "Боровичи": "Europe/Moscow",
  "Боровск": "Europe/Moscow",
  "Брянск": "Europe/Moscow",
  "Бугры": "Europe/Moscow",
  "Буденновск": "Europe/Moscow",
  "Буинск": "Europe/Moscow",
  "Буй": "Europe/Moscow",
  "Буйнакск": "Europe/Moscow",
  "Бутурлиновка": "Europe/Moscow",
  "Валдай": "Europe/Moscow",
  "Валуйки": "Europe/Moscow",
  "Вардане": "Europe/Moscow",
  "Вардане-Верино": "Europe/Moscow",
  "Васюринская": "Europe/Moscow",
  "Велиж": "Europe/Moscow",
  "Великие Луки": "Europe/Moscow",
  "Великий Новгород": "Europe/Moscow",
  "Великий Устюг": "Europe/Moscow",
  "Вельск": "Europe/Moscow",
  "Венев": "Europe/Moscow",
  "Верзилово": "Europe/Moscow",
  "Весёлое": "Europe/Moscow",
  "Видное": "Europe/Moscow",
  "Витязево": "Europe/Moscow",
  "Вичуга": "Europe/Moscow",
  "Владикавказ": "Europe/Moscow",
  "Владимир": "Europe/Moscow",
  "Волгоград": "Europe/Moscow",
  "Волгодонск": "Europe/Moscow",
  "Волжский": "Europe/Moscow",
  "Вологда": "Europe/Moscow",
  "Володарск": "Europe/Moscow",
  "Волоколамск": "Europe/Moscow",
  "Волосово": "Europe/Moscow",
  "Волхов": "Europe/Moscow",
  "Вольск": "Europe/Moscow",
  "Воронеж": "Europe/Moscow",
  "Воскресенск": "Europe/Moscow",
  "Всеволожск": "Europe/Moscow",
  "Выборг": "Europe/Moscow",
  "Выкса": "Europe/Moscow",
  "Вырица": "Europe/Moscow",
  "Выселки": "Europe/Moscow",
  "Вытегра": "Europe/Moscow",
  "Вышний Волочёк": "Europe/Moscow",
  "Вязники": "Europe/Moscow",
  "Вязьма": "Europe/Moscow",
  "Гаврилов Посад": "Europe/Moscow",
  "Гаврилов-Ям": "Europe/Moscow",
  "Гагарин": "Europe/Moscow",
  "Гаджиево": "Europe/Moscow",
  "Галич": "Europe/Moscow",
  "Гатчина": "Europe/Moscow",
  "Геленджик": "Europe/Moscow",
  "Георгиевск": "Europe/Moscow",
  "Голубицкая": "Europe/Moscow",
  "Голубое": "Europe/Moscow",
  "Горное Лоо": "Europe/Moscow",
  "Городец": "Europe/Moscow",
  "Городище": "Europe/Moscow",
  "Городовиковск": "Europe/Moscow",
  "Горячий Ключ": "Europe/Moscow",
  "Грозный": "Europe/Moscow",
  "Грязи": "Europe/Moscow",
  "Грязовец": "Europe/Moscow",
  "Губкин": "Europe/Moscow",
  "Гудермес": "Europe/Moscow",
  "Гуково": "Europe/Moscow",
  "Гулькевичи": "Europe/Moscow",
  "Гусь-Хрустальный": "Europe/Moscow",
  "Дагестанские огни": "Europe/Moscow",
  "Дагомыс": "Europe/Moscow",
  "Данков": "Europe/Moscow",
  "Дедовск": "Europe/Moscow",
  "Дербент": "Europe/Moscow",
  "Десногорск": "Europe/Moscow",
  "Джубга": "Europe/Moscow",
  "Дзержинск": "Europe/Moscow",
  "Дзержинский": "Europe/Moscow",
  "Дивное": "Europe/Moscow",
  "Дивноморское": "Europe/Moscow",
  "Динская": "Europe/Moscow",
  "Дмитров": "Europe/Moscow",
  "Долгопрудный": "Europe/Moscow",
  "Долгоруково": "Europe/Moscow",
  "Должанская": "Europe/Moscow",
  "Домодедово": "Europe/Moscow",
  "Дондуковская": "Europe/Moscow",
  "Донецк": "Europe/Moscow",
  "Донской": "Europe/Moscow",
  "Дрезна": "Europe/Moscow",
  "Дубна": "Europe/Moscow",
  "Дубовка": "Europe/Moscow",
  "Дубовое": "Europe/Moscow",
  "Дубровка": "Europe/Moscow",
  "Дятьково": "Europe/Moscow",
  "Егорьевск": "Europe/Moscow",
  "Ейск": "Europe/Moscow",
  "Елабуга": "Europe/Moscow",
  "Елань": "Europe/Moscow",
  "Елец": "Europe/Moscow",
  "Елизаветинская": "Europe/Moscow",
  "Емва": "Europe/Moscow",
  "Ессентуки": "Europe/Moscow",
  "Ессентукская": "Europe/Moscow",
  "Ефремов": "Europe/Moscow",
  "Железноводск": "Europe/Moscow",
  "Железногорск": "Europe/Moscow",
  "Железнорожный": "Europe/Moscow",
  "Жердевка": "Europe/Moscow",
  "Жешарт": "Europe/Moscow",
  "Жирновск": "Europe/Moscow",
  "Жуковка": "Europe/Moscow",
  "Жуковский": "Europe/Moscow",
  "Заволжье": "Europe/Moscow",
  "Задонск": "Europe/Moscow",
  "Заинск": "Europe/Moscow",
  "Заозерск": "Europe/Moscow",
  "Заполярный": "Europe/Moscow",
  "Зарайск": "Europe/Moscow",
  "Засечное": "Europe/Moscow",
  "Звенигород": "Europe/Moscow",
  "Зверево": "Europe/Moscow",
  "Зеленоград": "Europe/Moscow",
  "Зеленодольск": "Europe/Moscow",
  "Зеленокумск": "Europe/Moscow",
  "Зеленчукская": "Europe/Moscow",
  "Зерноград": "Europe/Moscow",
  "Зуевка": "Europe/Moscow",
  "Ивангород": "Europe/Moscow",
  "Иваново": "Europe/Moscow",
  "Ивантеевка": "Europe/Moscow",
  "Избербаш": "Europe/Moscow",
  "Изобильный": "Europe/Moscow",
  "Иловля": "Europe/Moscow",
  "Ильинский": "Europe/Moscow",
  "Ильский": "Europe/Moscow",
  "Инжавино": "Europe/Moscow",
  "Иннополис": "Europe/Moscow",
  "Иноземцево": "Europe/Moscow",
  "Ипатово": "Europe/Moscow",
  "Истра": "Europe/Moscow",
  "Йошкар-Ола": "Europe/Moscow",
  "Кабардинка": "Europe/Moscow",
  "Казань": "Europe/Moscow",
  "Калач": "Europe/Moscow",
  "Калач-на-Дону": "Europe/Moscow",
  "Калининская": "Europe/Moscow",
  "Калуга": "Europe/Moscow",
  "Калязин": "Europe/Moscow",
  "Каменка": "Europe/Moscow",
  "Каменск-Шахтинский": "Europe/Moscow",
  "Камышин": "Europe/Moscow",
  "Канаш": "Europe/Moscow",
  "Кандалакша": "Europe/Moscow",
  "Каневская": "Europe/Moscow",
  "Карабаново": "Europe/Moscow",
  "Карачаевск": "Europe/Moscow",
  "Каргополь": "Europe/Moscow",
  "Касимов": "Europe/Moscow",
  "Каспийск": "Europe/Moscow",
  "Кашары": "Europe/Moscow",
  "Кашин": "Europe/Moscow",
  "Кашира": "Europe/Moscow",
  "Каширское": "Europe/Moscow",
  "Кемь": "Europe/Moscow",
  "Кизилюрт": "Europe/Moscow",
  "Кизляр": "Europe/Moscow",
  "Кимовск": "Europe/Moscow",
  "Кимры": "Europe/Moscow",
  "Кингисепп": "Europe/Moscow",
  "Кинешма": "Europe/Moscow",
  "Киреевск": "Europe/Moscow",
  "Киржач": "Europe/Moscow",
  "Кириллов": "Europe/Moscow",
  "Кириши": "Europe/Moscow",
  "Киров": "Europe/Moscow",
  "Кирово-Чепецк": "Europe/Moscow",
  "Кировск": "Europe/Moscow",
  "Кирсанов": "Europe/Moscow",
  "Кисловодск": "Europe/Moscow",
  "Климово": "Europe/Moscow",
  "Клин": "Europe/Moscow",
  "Клинцы": "Europe/Moscow",
  "Клязьма": "Europe/Moscow",
  "Ковдор": "Europe/Moscow",
  "Ковров": "Europe/Moscow",
  "Козельск": "Europe/Moscow",
  "Козловка": "Europe/Moscow",
  "Козьмодемьянск": "Europe/Moscow",
  "Кокошкино": "Europe/Moscow",
  "Кола": "Europe/Moscow",
  "Колодезный": "Europe/Moscow",
  "Коломна": "Europe/Moscow",
  "Колпино": "Europe/Moscow",
  "Кольчугино": "Europe/Moscow",
  "Комсомольский": "Europe/Moscow",
  "Конаково": "Europe/Moscow",
  "Кондопога": "Europe/Moscow",
  "Коноша": "Europe/Moscow",
  "Кореновск": "Europe/Moscow",
  "Королев": "Europe/Moscow",
  "Коряжма": "Europe/Moscow",
  "Костомукша": "Europe/Moscow",
  "Кострома": "Europe/Moscow",
  "Котельники": "Europe/Moscow",
  "Котельниково": "Europe/Moscow",
  "Котельнич": "Europe/Moscow",
  "Котлас": "Europe/Moscow",
  "Котово": "Europe/Moscow",
  "Котовск": "Europe/Moscow",
  "Кохма": "Europe/Moscow",
  "Кочетовка": "Europe/Moscow",
  "Кочубеевское": "Europe/Moscow",
  "Красавино": "Europe/Moscow",
  "Красково": "Europe/Moscow",
  "Красная Горка": "Europe/Moscow",
  "Красная Поляна": "Europe/Moscow",
  "Красноармейск": "Europe/Moscow",
  "Красногвардейское": "Europe/Moscow",
  "Красногорск": "Europe/Moscow",
  "Краснодар": "Europe/Moscow",
  "Красное": "Europe/Moscow",
  "Красное Село": "Europe/Moscow",
  "Красное-на-Волге": "Europe/Moscow",
  "Краснознаменск": "Europe/Moscow",
  "Кронштадт": "Europe/Moscow",
  "Кропоткин": "Europe/Moscow",
  "Крыловская": "Europe/Moscow",
  "Крымск": "Europe/Moscow",
  "Кстово": "Europe/Moscow",
  "Кубинка": "Europe/Moscow",
  "Кугеси": "Europe/Moscow",
  "Кудепста": "Europe/Moscow",
  "Кудрово": "Europe/Moscow",
  "Кузнецк": "Europe/Moscow",
  "Кукмор": "Europe/Moscow",
  "Кулаково": "Europe/Moscow",
  "Кулебаки": "Europe/Moscow",
  "Кулешовка": "Europe/Moscow",
  "Курганинск": "Europe/Moscow",
  "Куровское": "Europe/Moscow",
  "Курск": "Europe/Moscow",
  "Курчатов": "Europe/Moscow",
  "Кучугуры": "Europe/Moscow",
  "Кущевская": "Europe/Moscow",
  "Лабинск": "Europe/Moscow",
  "Лагань": "Europe/Moscow",
  "Ладожская": "Europe/Moscow",
  "Лазаревское": "Europe/Moscow",
  "Лахденпохья": "Europe/Moscow",
  "Лебедянь": "Europe/Moscow",
  "Левокумское": "Europe/Moscow",
  "Лежнево": "Europe/Moscow",
  "Ленинградская": "Europe/Moscow",
  "Ленинградская область": "Europe/Moscow",
  "Лениногорск": "Europe/Moscow",
  "Ленинск": "Europe/Moscow",
  "Лермонтов": "Europe/Moscow",
  "Ливны": "Europe/Moscow",
  "Ликино-Дулево": "Europe/Moscow",
  "Липецк": "Europe/Moscow",
  "Лиски": "Europe/Moscow",
  "Лихославль": "Europe/Moscow",
  "Лобня": "Europe/Moscow",
  "Лодейное Поле": "Europe/Moscow",
  "Ломоносов": "Europe/Moscow",
  "Лопатино": "Europe/Moscow",
  "Лосино-Петровский": "Europe/Moscow",
  "Луга": "Europe/Moscow",
  "Луза": "Europe/Moscow",
  "Лукоянов": "Europe/Moscow",
  "Луховицы": "Europe/Moscow",
  "Лысково": "Europe/Moscow",
  "Лысогорская": "Europe/Moscow",
  "Лыткарино": "Europe/Moscow",
  "Льгов": "Europe/Moscow",
  "Любань": "Europe/Moscow",
  "Люберцы": "Europe/Moscow",
  "Людиново": "Europe/Moscow",
  "Магас": "Europe/Moscow",
  "Майкоп": "Europe/Moscow",
  "Майский": "Europe/Moscow",
  "Макарьев": "Europe/Moscow",
  "Малаховка": "Europe/Moscow",
  "Малоярославец": "Europe/Moscow",
  "Мамадыш": "Europe/Moscow",
  "Мантурово": "Europe/Moscow",
  "Марьянская": "Europe/Moscow",
  "Матвеев Курган": "Europe/Moscow",
  "Матвеев-Курган": "Europe/Moscow",
  "Махачкала": "Europe/Moscow",
  "Мга": "Europe/Moscow",
  "Медведовская": "Europe/Moscow",
  "Медвежьегорск": "Europe/Moscow",
  "Мелитополь": "Europe/Moscow",
  "Менделеевск": "Europe/Moscow",
  "Мещерино": "Europe/Moscow",
  "Миллерово": "Europe/Moscow",
  "Минеральные Воды": "Europe/Moscow",
  "Мирный": "Europe/Moscow",
  "Мисайлово": "Europe/Moscow",
  "Митино": "Europe/Moscow",
  "Михайловка": "Europe/Moscow",
  "Михайловск": "Europe/Moscow",
  "Михайловская": "Europe/Moscow",
  "Михнево": "Europe/Moscow",
  "Мичуринск": "Europe/Moscow",
  "Моздок": "Europe/Moscow",
  "Монино": "Europe/Moscow",
  "Мончегорск": "Europe/Moscow",
  "Морозовск": "Europe/Moscow",
  "Моршанск": "Europe/Moscow",
  "Мостовской": "Europe/Moscow",
  "Мурино": "Europe/Moscow",
  "Мурманск": "Europe/Moscow",
  "Мурмаши": "Europe/Moscow",
  "Муром": "Europe/Moscow",
  "Мценск": "Europe/Moscow",
  "Мытищи": "Europe/Moscow",
  "Набережные Челны": "Europe/Moscow",
  "Навашино": "Europe/Moscow",
  "Наволоки": "Europe/Moscow",
  "Надвоицы": "Europe/Moscow",
  "Надежда": "Europe/Moscow",
  "Назрань": "Europe/Moscow",
  "Нальчик": "Europe/Moscow",
  "Наро-Фоминск": "Europe/Moscow",
  "Нарткала": "Europe/Moscow",
  "Нарышкино": "Europe/Moscow",
  "Нахабино": "Europe/Moscow",
  "Началово": "Europe/Samara",
  "Невель": "Europe/Moscow",
  "Невинномысск": "Europe/Moscow",
  "Некрасовский": "Europe/Moscow",
  "Нерехта": "Europe/Moscow",
  "Нижнекамск": "Europe/Moscow",
  "Нижний Ломов": "Europe/Moscow",
  "Нижний Новгород": "Europe/Moscow",
  "Нижний Одес": "Europe/Moscow",
  "Никель": "Europe/Moscow",
  "Николаевск": "Europe/Moscow",
  "Николо-Хованское": "Europe/Moscow",
  "Никольск": "Europe/Moscow",
  "Новая Ладога": "Europe/Moscow",
  "Новая Усмань": "Europe/Moscow",
  "Новоалександровск": "Europe/Moscow",
  "Новоаннинский": "Europe/Moscow",
  "Нововеличковская": "Europe/Moscow",
  "Нововоронеж": "Europe/Moscow",
  "Новодвинск": "Europe/Moscow",
  "Новое Девяткино": "Europe/Moscow",
  "Новозыбков": "Europe/Moscow",
  "Новокубанск": "Europe/Moscow",
  "Новоминская": "Europe/Moscow",
  "Новомихайловский": "Europe/Moscow",
  "Новомосковск": "Europe/Moscow",
  "Новониколаевский": "Europe/Moscow",
  "Новопавловск": "Europe/Moscow",
  "Новопокровская": "Europe/Moscow",
  "Новороссийск": "Europe/Moscow",
  "Новосадовый": "Europe/Moscow",
  "Новоселицкое": "Europe/Moscow",
  "Новотитаровская": "Europe/Moscow",
  "Новохоперск": "Europe/Moscow",
  "Новочебоксарск": "Europe/Moscow",
  "Новочебоксарсск": "Europe/Moscow",
  "Новочеркасск": "Europe/Moscow",
  "Новошахтинск": "Europe/Moscow",
  "Новые Бурасы": "Europe/Samara",
  "Новый Оскол": "Europe/Moscow",
  "Новый Рогачик": "Europe/Moscow",
  "Ногинск": "Europe/Moscow",
  "Нурлат": "Europe/Moscow",
  "Няндома": "Europe/Moscow",
  "Обнинск": "Europe/Moscow",
  "Обоянь": "Europe/Moscow",
  "Одинцово": "Europe/Moscow",
  "Ожерелье": "Europe/Moscow",
  "Озеры": "Europe/Moscow",
  "Октябрьская": "Europe/Moscow",
  "Окуловка": "Europe/Moscow",
  "Оленегорск": "Europe/Moscow",
  "Оленино": "Europe/Moscow",
  "Олонец": "Europe/Moscow",
  "Ольгинская": "Europe/Moscow",
  "Орел": "Europe/Moscow",
  "Орехово-Зуево": "Europe/Moscow",
  "Оричи": "Europe/Moscow",
  "Орлов": "Europe/Moscow",
  "Орловский": "Europe/Moscow",
  "Осташков": "Europe/Moscow",
  "Остров": "Europe/Moscow",
  "Островцы": "Europe/Moscow",
  "Острогожск": "Europe/Moscow",
  "Отрадная": "Europe/Moscow",
  "Отрадное": "Europe/Moscow",
  "Павловка": "Europe/Moscow",
  "Павлово": "Europe/Moscow",
  "Павловск": "Europe/Moscow",
  "Павловская": "Europe/Moscow",
  "Павловский Посад": "Europe/Moscow",
  "Палласовка": "Europe/Moscow",
  "Парголово": "Europe/Moscow",
  "Партенит": "Europe/Moscow",
  "Парфино": "Europe/Moscow",
  "Пенза": "Europe/Moscow",
  "Первомайск": "Europe/Moscow",
  "Первомайский": "Europe/Moscow",
  "Первомайское": "Europe/Moscow",
  "Перевоз": "Europe/Moscow",
  "Переславль-Залесский": "Europe/Moscow",
  "Переясловская": "Europe/Moscow",
  "Пестрецы": "Europe/Moscow",
  "Песчанокопское": "Europe/Moscow",
  "Петергоф": "Europe/Moscow",
  "Петрозаводск": "Europe/Moscow",
  "Печора": "Europe/Moscow",
  "Пешково": "Europe/Moscow",
  "Пикалёво": "Europe/Moscow",
  "Питкяранта": "Europe/Moscow",
  "Плавск": "Europe/Moscow",
  "Плесецк": "Europe/Moscow",
  "Поворино": "Europe/Moscow",
  "Подгоренский": "Europe/Moscow",
  "Подольск": "Europe/Moscow",
  "Подпорожье": "Europe/Moscow",
  "Покров": "Europe/Moscow",
  "Покровское": "Europe/Moscow",
  "Полтавская": "Europe/Moscow",
  "Полярные Зори": "Europe/Moscow",
  "Полярный": "Europe/Moscow",
  "Почеп": "Europe/Moscow",
  "Приволжск": "Europe/Moscow",
  "Приморск": "Europe/Moscow",
  "Приморско-Ахтарск": "Europe/Moscow",
  "Пролетарск": "Europe/Moscow",
  "Протвино": "Europe/Moscow",
  "Прохладный": "Europe/Moscow",
  "Псебай": "Europe/Moscow",
  "Псков": "Europe/Moscow",
  "Пудож": "Europe/Moscow",
  "Путилково": "Europe/Moscow",
  "Пушкин": "Europe/Moscow",
  "Пушкино": "Europe/Moscow",
  "Пыталово": "Europe/Moscow",
  "Пятигорск": "Europe/Moscow",
  "Разумное": "Europe/Moscow",
  "Ракитное": "Europe/Moscow",
  "Раменское": "Europe/Moscow",
  "Рамонь": "Europe/Moscow",
  "Рассказово": "Europe/Moscow",
  "Растуново": "Europe/Moscow",
  "Реутов": "Europe/Moscow",
  "Ржакса": "Europe/Moscow",
  "Роговская": "Europe/Moscow",
  "Родники": "Europe/Moscow",
  "Рославль": "Europe/Moscow",
  "Россошь": "Europe/Moscow",
  "Ростов": "Europe/Moscow",
  "Ростов-на-Дону": "Europe/Moscow",
  "Рошаль": "Europe/Moscow",
  "Рощино": "Europe/Moscow",
  "Рузаевка": "Europe/Moscow",
  "Рыбинск": "Europe/Moscow",
  "Рыбное": "Europe/Moscow",
  "Ряжск": "Europe/Moscow",
  "Рязань": "Europe/Moscow",
  "Савино": "Europe/Moscow",
  "Савинский": "Europe/Moscow",
  "Сальск": "Europe/Moscow",
  "Самарское": "Europe/Moscow",
  "Саранск": "Europe/Moscow",
  "Саров": "Europe/Moscow",
  "Сасово": "Europe/Moscow",
  "Сафоново": "Europe/Moscow",
  "Светлоград": "Europe/Moscow",
  "Светлый Яр": "Europe/Moscow",
  "Светогорск": "Europe/Moscow",
  "Севастополь": "Europe/Moscow",
  "Северный": "Europe/Moscow",
  "Северный-Первый": "Europe/Moscow",
  "Северодвинск": "Europe/Moscow",
  "Североморск": "Europe/Moscow",
  "Северская": "Europe/Moscow",
  "Сегежа": "Europe/Moscow",
  "Селижарово": "Europe/Moscow",
  "Семенов": "Europe/Moscow",
  "Семикаракорск": "Europe/Moscow",
  "Семилуки": "Europe/Moscow",
  "Сергач": "Europe/Moscow",
  "Сергиев Посад": "Europe/Moscow",
  "Сердобск": "Europe/Moscow",
  "Серпухов": "Europe/Moscow",
  "Сертолово": "Europe/Moscow",
  "Сестрорецк": "Europe/Moscow",
  "Симферополь": "Europe/Moscow",
  "Сириус": "Europe/Moscow",
  "Славянск-на-Кубани": "Europe/Moscow",
  "Сланцы": "Europe/Moscow",
  "Слободской": "Europe/Moscow",
  "Смоленск": "Europe/Moscow",
  "Смоленская": "Europe/Moscow",
  "Снежногорск": "Europe/Moscow",
  "Собинка": "Europe/Moscow",
  "Сокол": "Europe/Moscow",
  "Солнечногорск": "Europe/Moscow",
  "Соль-Илецк": "Asia/Yekaterinburg",
  "Соновый Бор": "Europe/Moscow",
  "Сортавала": "Europe/Moscow",
  "Сосенский": "Europe/Moscow",
  "Сосново": "Europe/Moscow",
  "Сосновый Бор": "Europe/Moscow",
  "Сосногорск": "Europe/Moscow",
  "Сочи": "Europe/Moscow",
  "Спас-Клепики": "Europe/Moscow",
  "Ставрополь": "Europe/Moscow",
  "Становое": "Europe/Moscow",
  "Старая Полтавка": "Europe/Moscow",
  "Старая Русса": "Europe/Moscow",
  "Старовеличковская": "Europe/Moscow",
  "Старое Михайловское": "Europe/Moscow",
  "Староминская": "Europe/Moscow",
  "Старомышастовская": "Europe/Moscow",
  "Старонижестеблиевская": "Europe/Moscow",
  "Старый Оскол": "Europe/Moscow",
  "Строитель": "Europe/Moscow",
  "Стрельна": "Europe/Moscow",
  "Струнино": "Europe/Moscow",
  "Ступино": "Europe/Moscow",
  "Суворовская": "Europe/Moscow",
  "Суздаль": "Europe/Moscow",
  "Суоярви": "Europe/Moscow",
  "Суровикино": "Europe/Moscow",
  "Сухиничи": "Europe/Moscow",
  "Сыктывкар": "Europe/Moscow",
  "Сычёво": "Europe/Moscow",
  "Сямжа": "Europe/Moscow",
  "Сясьстрой": "Europe/Moscow",
  "Таганрог": "Europe/Moscow",
  "Талдом": "Europe/Moscow",
  "Таловая": "Europe/Moscow",
  "Тамань": "Europe/Moscow",
  "Тамбов": "Europe/Moscow",
  "Тарасовский": "Europe/Moscow",
  "Таруса": "Europe/Moscow",
  "Тацинская": "Europe/Moscow",
  "Тбилисская": "Europe/Moscow",
  "Тверь": "Europe/Moscow",
  "Теберда": "Europe/Moscow",
  "Тейково": "Europe/Moscow",
  "Тельмана": "Europe/Moscow",
  "Темрюк": "Europe/Moscow",
  "Терек": "Europe/Moscow",
  "Тимашевск": "Europe/Moscow",
  "Тихвин": "Europe/Moscow",
  "Тихорецк": "Europe/Moscow",
  "Торбеево": "Europe/Moscow",
  "Торжок": "Europe/Moscow",
  "Тосно": "Europe/Moscow",
  "Троицко-Печорск": "Europe/Moscow",
  "Троицкое": "Europe/Moscow",
  "Туапсе": "Europe/Moscow",
  "Тула": "Europe/Moscow",
  "Тутаев": "Europe/Moscow",
  "Тырныауз": "Europe/Moscow",
  "Уварово": "Europe/Moscow",
  "Углич": "Europe/Moscow",
  "Удомля": "Europe/Moscow",
  "Узловая": "Europe/Moscow",
  "Унеча": "Europe/Moscow",
  "Уразово": "Europe/Moscow",
  "Урень": "Europe/Moscow",
  "Уржум": "Europe/Moscow",
  "Урус-Мартан": "Europe/Moscow",
  "Урюпинск": "Europe/Moscow",
  "Усинск": "Europe/Moscow",
  "Усмань": "Europe/Moscow",
  "Успенское": "Europe/Moscow",
  "Усть-Джегута": "Europe/Moscow",
  "Усть-Лабинск": "Europe/Moscow",
  "Устье": "Europe/Moscow",
  "Ухта": "Europe/Moscow",
  "Федоровка": "Europe/Moscow",
  "Ферзиково": "Europe/Moscow",
  "Фокино": "Europe/Moscow",
  "Форос": "Europe/Moscow",
  "Фролово": "Europe/Moscow",
  "Фрязино": "Europe/Moscow",
  "Фурманов": "Europe/Moscow",
  "Харабали": "Europe/Samara",
  "Хасавюрт": "Europe/Moscow",
  "Хатунь": "Europe/Moscow",
  "Хвалынск": "Europe/Samara",
  "Химки": "Europe/Moscow",
  "Холмогоры": "Europe/Moscow",
  "Холмская": "Europe/Moscow",
  "Хоста": "Europe/Moscow",
  "Цивильск": "Europe/Moscow",
  "Чебоксары": "Europe/Moscow",
  "Чегем": "Europe/Moscow",
  "Чердаклы": "Europe/Samara",
  "Черемшан": "Europe/Moscow",
  "Череповец": "Europe/Moscow",
  "Черкесск": "Europe/Moscow",
  "Чернянка": "Europe/Moscow",
  "Чертково": "Europe/Moscow",
  "Чехов": "Europe/Moscow",
  "Чистополь": "Europe/Moscow",
  "Чкаловск": "Europe/Moscow",
  "Чудово": "Europe/Moscow",
  "Шатура": "Europe/Moscow",
  "Шахты": "Europe/Moscow",
  "Шахунья": "Europe/Moscow",
  "Шебекино": "Europe/Moscow",
  "Шексна": "Europe/Moscow",
  "Шихазаны": "Europe/Moscow",
  "Шолоховский": "Europe/Moscow",
  "Шумерля": "Europe/Moscow",
  "Шушары": "Europe/Moscow",
  "Шуя": "Europe/Moscow",
  "Щекино": "Europe/Moscow",
  "Щёлково": "Europe/Moscow",
  "Щербинка": "Europe/Moscow",
  "Электрогорск": "Europe/Moscow",
  "Электросталь": "Europe/Moscow",
  "Элиста": "Europe/Moscow",
  "Энем": "Europe/Moscow",
  "Южный": "Europe/Moscow",
  "Юрьев-Польский": "Europe/Moscow",
  "Яблоновский": "Europe/Moscow",
  "Ялта": "Europe/Moscow",
  "Яранск": "Europe/Moscow",
  "Ярега": "Europe/Moscow",
  "Ярославль": "Europe/Moscow",
  "Ярцево": "Europe/Moscow",
  "Станица Воронежская (Краснодарский край)": "Europe/Moscow",
  "Чалтырь": "Europe/Moscow",

  // UTC+4
  "Самара": "Europe/Samara",
  "Тольятти": "Europe/Samara",
  "Ульяновск": "Europe/Samara",
  "Димитровград": "Europe/Samara",
  "Саратов": "Europe/Samara",
  "Энгельс": "Europe/Samara",
  "Астрахань": "Europe/Samara",
  "Балаково": "Europe/Samara",
  "Безенчук": "Europe/Samara",
  "Большая Черниговка": "Europe/Samara",
  "Жигулевск": "Europe/Samara",
  "Кинель-Черкассы": "Europe/Samara",
  "Красный Кут": "Europe/Samara",
  "Красный Яр": "Europe/Samara",
  "Маркс": "Europe/Samara",
  "Мокроус": "Europe/Samara",
  "Нариманов": "Europe/Samara",
  "Нефтегорск": "Europe/Samara",
  "Новоузенск": "Europe/Samara",
  "Озинки": "Europe/Samara",
  "Октябрьск": "Europe/Samara",
  "Пугачев": "Europe/Samara",
  "Сарапул": "Europe/Samara",
  "Старая Майна": "Europe/Samara",
  "Степное": "Europe/Samara",
  "Татищево": "Europe/Samara",
  "Чапаевск": "Europe/Samara",

  // UTC+5
  "Екатеринбург": "Asia/Yekaterinburg",
  "Тюмень": "Asia/Yekaterinburg",
  "Челябинск": "Asia/Yekaterinburg",
  "Курган": "Asia/Yekaterinburg",
  "Уфа": "Asia/Yekaterinburg",
  "Оренбург": "Asia/Yekaterinburg",
  "Орск": "Asia/Yekaterinburg",
  "Магнитогорск": "Asia/Yekaterinburg",
  "Нефтекамск": "Asia/Yekaterinburg",
  "Новый Уренгой": "Asia/Yekaterinburg",
  "Ноябрьск": "Asia/Yekaterinburg",
  "Надым": "Asia/Yekaterinburg",
  "Сургут": "Asia/Yekaterinburg",
  "Нижневартовск": "Asia/Yekaterinburg",
  "Ханты-Мансийск": "Asia/Yekaterinburg",
  "Лангепас": "Asia/Yekaterinburg",
  "Нягань": "Asia/Yekaterinburg",
  "Когалым": "Asia/Yekaterinburg",
  "Салехард": "Asia/Yekaterinburg",
  "Лабытнанги": "Asia/Yekaterinburg",
  "Тарко-Сале": "Asia/Yekaterinburg",
  "Аксарка": "Asia/Yekaterinburg",
  "Алапаевск": "Asia/Yekaterinburg",
  "Арамиль": "Asia/Yekaterinburg",
  "Артемовский": "Asia/Yekaterinburg",
  "Арти": "Asia/Yekaterinburg",
  "Асбест": "Asia/Yekaterinburg",
  "Бисерть": "Asia/Yekaterinburg",
  "Богданович": "Asia/Yekaterinburg",
  "Буланаш": "Asia/Yekaterinburg",
  "Верхний Тагил": "Asia/Yekaterinburg",
  "Верхняя Пышма": "Asia/Yekaterinburg",
  "Верхняя Салда": "Asia/Yekaterinburg",
  "Верхняя Тура": "Asia/Yekaterinburg",
  "Верхотурье": "Asia/Yekaterinburg",
  "Ивдель": "Asia/Yekaterinburg",
  "Ирбит": "Asia/Yekaterinburg",
  "Каменск-Уральский": "Asia/Yekaterinburg",
  "Карпинск": "Asia/Yekaterinburg",
  "Качканар": "Asia/Yekaterinburg",
  "Краснотурьинск": "Asia/Yekaterinburg",
  "Красноуральск": "Asia/Yekaterinburg",
  "Красноуфимск": "Asia/Yekaterinburg",
  "Кировград": "Asia/Yekaterinburg",
  "Лесной": "Asia/Yekaterinburg",
  "Мелеуз": "Asia/Yekaterinburg",
  "Нижний Тагил": "Asia/Yekaterinburg",
  "Нижняя Салда": "Asia/Yekaterinburg",
  "Нижняя Тавда": "Asia/Yekaterinburg",
  "Нижняя Тура": "Asia/Yekaterinburg",
  "Новая Ляля": "Asia/Yekaterinburg",
  "Новоуральск": "Asia/Yekaterinburg",
  "Первоуральск": "Asia/Yekaterinburg",
  "Полевской": "Asia/Yekaterinburg",
  "Ревда": "Asia/Yekaterinburg",
  "Реж": "Asia/Yekaterinburg",
  "Североуральск": "Asia/Yekaterinburg",
  "Серов": "Asia/Yekaterinburg",
  "Стерлитамак": "Asia/Yekaterinburg",
  "Сухой Лог": "Asia/Yekaterinburg",
  "Сысерть": "Asia/Yekaterinburg",
  "Тавда": "Asia/Yekaterinburg",
  "Троицк": "Asia/Yekaterinburg",
  "Туринск": "Asia/Yekaterinburg",
  "Туринская Слобода": "Asia/Yekaterinburg",
  "Учалы": "Asia/Yekaterinburg",
  "Чишмы": "Asia/Yekaterinburg",
  "Ясный": "Asia/Yekaterinburg",

  // UTC+6
  "Омск": "Asia/Omsk",
  "Азово": "Asia/Omsk",
  "Исилькуль": "Asia/Omsk",
  "Калачинск": "Asia/Omsk",
  "Тара": "Asia/Omsk",
  "Тюкалинск": "Asia/Omsk",

  // UTC+7
  "Новосибирск": "Asia/Novosibirsk",
  "Бердск": "Asia/Novosibirsk",
  "Искитим": "Asia/Novosibirsk",
  "Обь": "Asia/Novosibirsk",
  "Барабинск": "Asia/Novosibirsk",
  "Куйбышев": "Asia/Novosibirsk",
  "Татарск": "Asia/Novosibirsk",
  "Тогучин": "Asia/Novosibirsk",
  "Барнаул": "Asia/Barnaul",
  "Бийск": "Asia/Barnaul",
  "Рубцовск": "Asia/Barnaul",
  "Славгород": "Asia/Barnaul",
  "Яровое": "Asia/Barnaul",
  "Белокуриха": "Asia/Barnaul",
  "Горно-Алтайск": "Asia/Barnaul",
  "Томск": "Asia/Tomsk",
  "Северск": "Asia/Tomsk",
  "Кемерово": "Asia/Novokuznetsk",
  "Новокузнецк": "Asia/Novokuznetsk",
  "Прокопьевск": "Asia/Novokuznetsk",
  "Киселёвск": "Asia/Novokuznetsk",
  "Междуреченск": "Asia/Novokuznetsk",
  "Белово": "Asia/Novokuznetsk",
  "Ленинск-Кузнецкий": "Asia/Novokuznetsk",
  "Юрга": "Asia/Novokuznetsk",
  "Абакан": "Asia/Krasnoyarsk",
  "Черногорск": "Asia/Krasnoyarsk",
  "Красноярск": "Asia/Krasnoyarsk",
  "Ачинск": "Asia/Krasnoyarsk",
  "Канск": "Asia/Krasnoyarsk",
  "Назарово": "Asia/Krasnoyarsk",
  "Минусинск": "Asia/Krasnoyarsk",
  "Лесосибирск": "Asia/Krasnoyarsk",
  "Норильск": "Asia/Krasnoyarsk",
  "Дивногорск": "Asia/Krasnoyarsk",
  "Енисейск": "Asia/Krasnoyarsk",
  "Шарыпово": "Asia/Krasnoyarsk",
  "Заозерный": "Asia/Krasnoyarsk",
  "Ужур": "Asia/Krasnoyarsk",

  // UTC+8
  "Иркутск": "Asia/Irkutsk",
  "Ангарск": "Asia/Irkutsk",
  "Братск": "Asia/Irkutsk",
  "Усолье-Сибирское": "Asia/Irkutsk",
  "Шелехов": "Asia/Irkutsk",
  "Усть-Кут": "Asia/Irkutsk",
  "Тайшет": "Asia/Irkutsk",
  "Тулун": "Asia/Irkutsk",
  "Зима": "Asia/Irkutsk",
  "Улан-Удэ": "Asia/Irkutsk",

  // UTC+9
  "Чита": "Asia/Chita",
  "Якутск": "Asia/Yakutsk",
  "Благовещенск": "Asia/Yakutsk",

  // UTC+10
  "Владивосток": "Asia/Vladivostok",
  "Артём": "Asia/Vladivostok",
  "Уссурийск": "Asia/Vladivostok",
  "Находка": "Asia/Vladivostok",
  "Хабаровск": "Asia/Vladivostok",

  // UTC+11
  "Южно-Сахалинск": "Asia/Sakhalin",
  "Магадан": "Asia/Magadan",

  // UTC+12
  "Петропавловск-Камчатский": "Asia/Kamchatka",

  // Не Россия
  "Бишкек": "Asia/Bishkek",
  "Кызыл-Кия": "Asia/Bishkek",

  // Фоллбек-значение
  "Другой город": "Europe/Moscow"
};

function normalizeCity(city) {
  return String(city || '')
    .replace(/\s+/g, ' ')
    .replace(/[ёЁ]/g, 'е')
    .trim();
}

function getCachedTZ(city) {
  const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
  return cache[city];
}

function setCachedTZ(city, tz) {
  const cache = JSON.parse(localStorage.getItem('tz_cache') || '{}');
  cache[city] = tz;
  localStorage.setItem('tz_cache', JSON.stringify(cache));
}

async function getTimezone(city) {
  const normalized = normalizeCity(city);

  for (const key in MANUAL_TZ) {
    if (normalizeCity(key) === normalized) {
      return MANUAL_TZ[key];
    }
  }

  const cached = getCachedTZ(normalized);
  if (cached) return cached;

  try {
    const geoRes = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(city));
    const geoData = await geoRes.json();

    if (!geoData.length) return 'Europe/Moscow';

    const lat = geoData[0].lat;
    const lon = geoData[0].lon;

    const tzRes = await fetch('https://timeapi.io/api/Time/current/coordinate?latitude=' + lat + '&longitude=' + lon);
    const tzData = await tzRes.json();

    const tz = tzData.timeZone || 'Europe/Moscow';
    setCachedTZ(normalized, tz);
    return tz;
  } catch (e) {
    return 'Europe/Moscow';
  }
}

function renderStatus(hour) {
  let text = 'Можно звонить';
  let cls = 'ok';

  if (hour < 9) {
    text = 'Слишком рано';
    cls = 'bad';
  }

  if (hour >= 21) {
    text = 'Слишком поздно';
    cls = 'bad';
  }

  statusEl.textContent = text;
  statusEl.className = 'status ' + cls;
}

function startClock(tz) {
  function render() {
    const now = new Date();

    const time = new Intl.DateTimeFormat('ru-RU', {
      timeZone: tz,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }).format(now);

    timeEl.textContent = time;

    const hour = Number(new Intl.DateTimeFormat('en-GB', {
      timeZone: tz,
      hour: '2-digit',
      hour12: false
    }).format(now));

    renderStatus(hour);

    if (window.BX24 && BX24.fitWindow) {
      BX24.fitWindow();
    }
  }

  render();
  setInterval(render, 1000);
}

BX24.init(function() {
  const info = BX24.placement.info();
  const options = info && info.options ? info.options : {};

  const entityId =
    options.ENTITY_VALUE_ID ||
    options.ID ||
    options.id ||
    options.LEAD_ID ||
    options.DEAL_ID ||
    null;

  BX24.callMethod('app.option.get', {}, function(optRes) {
    const appOptions = optRes.data() || {};

    let entity = 'lead';
    if (
      options.ENTITY_ID === 'DEAL' ||
      options.DEAL_ID ||
      String(options.ENTITY_TYPE_ID || '') === '2'
    ) {
      entity = 'deal';
    }

    const field = entity === 'deal'
      ? appOptions.dealField
      : appOptions.leadField;

    if (!entityId || !field) {
      cityEl.textContent = 'Нет ID или не выбрано поле';
      return;
    }

    const method = entity === 'deal' ? 'crm.deal.get' : 'crm.lead.get';

    BX24.callMethod(method, { id: entityId }, function(res) {
      if (res.error()) {
        cityEl.textContent = 'Ошибка чтения карточки';
        return;
      }

      const item = res.data() || {};
      let city = item[field];

      if (Array.isArray(city)) {
        city = city[0] || '';
      } else if (typeof city === 'object' && city !== null) {
        city = city.VALUE || city.value || '';
      } else {
        city = city || '';
      }

      if (!city) {
        cityEl.textContent = 'Город не заполнен';
        return;
      }

      cityEl.textContent = city;
      getTimezone(city).then(startClock);
    });
  });
});
</script>

</body>
</html>
