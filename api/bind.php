<?php
header('Content-Type: text/plain; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$entity = $input['entity'] ?? 'lead';

// Получаем данные из Bitrix (через POST)
$domain = $_GET['DOMAIN'] ?? '';
$auth = $_GET['AUTH_ID'] ?? '';

if (!$domain || !$auth) {
    echo "Нет авторизации от Б24";
    exit;
}

// REST URL
$restUrl = "https://$domain/rest/$auth/";

// 1. Регистрируем тип поля
$userFieldType = [
    "USER_TYPE_ID" => "candidate_time",
    "HANDLER" => "https://project-jbfzb.vercel.app/field",
    "TITLE" => "Время кандидата",
    "DESCRIPTION" => "Показывает текущее время кандидата"
];

$res1 = file_get_contents($restUrl . "userfieldtype.add?" . http_build_query($userFieldType));

// 2. Создаём поле
$fieldData = [
    "fields" => [
        "ENTITY_ID" => strtoupper($entity),
        "FIELD_NAME" => "UF_CRM_CANDIDATE_TIME",
        "USER_TYPE_ID" => "candidate_time",
        "XML_ID" => "CANDIDATE_TIME",
        "SORT" => 100,
        "MULTIPLE" => "N",
        "MANDATORY" => "N",
        "SHOW_FILTER" => "N",
        "SHOW_IN_LIST" => "Y",
        "EDIT_IN_LIST" => "N",
        "IS_SEARCHABLE" => "N",
        "SETTINGS" => []
    ]
];

$res2 = file_get_contents($restUrl . "crm." . $entity . ".userfield.add?" . http_build_query($fieldData));

// Вывод
echo "Тип поля: \n$res1\n\n";
echo "Поле создано: \n$res2\n";
