<?php
header('Content-Type: text/plain; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

$entity = $input['entity'] ?? 'lead';
$field = $input['field'] ?? '';
$auth = $input['auth'] ?? [];

$domain = $auth['domain'] ?? '';
$accessToken = $auth['access_token'] ?? '';

if (!$domain || !$accessToken) {
    echo "Нет авторизации от Б24";
    exit;
}

function bxCall(string $domain, string $token, string $method, array $params = []): array
{
    $url = "https://{$domain}/rest/{$method}.json";

    $params['auth'] = $token;

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($params),
            'timeout' => 30
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        return ['error' => 'HTTP request failed'];
    }

    $decoded = json_decode($result, true);
    return is_array($decoded) ? $decoded : ['raw' => $result];
}

$userTypeId = 'candidate_time';
$handlerUrl = 'https://project-jbfzb.vercel.app/field';

// 1. Пытаемся зарегистрировать тип поля
$typeRes = bxCall($domain, $accessToken, 'userfieldtype.add', [
    'USER_TYPE_ID' => $userTypeId,
    'HANDLER' => $handlerUrl,
    'TITLE[ru]' => 'Время кандидата',
    'DESCRIPTION[ru]' => 'Показывает текущее время кандидата'
]);

// если тип уже существует — не считаем это фатальной ошибкой
$typeInfo = "Регистрация типа поля:\n" . json_encode($typeRes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// 2. Проверяем, есть ли уже поле
$listMethod = $entity === 'deal' ? 'crm.deal.userfield.list' : 'crm.lead.userfield.list';
$listRes = bxCall($domain, $accessToken, $listMethod, []);

$existingField = null;
if (!empty($listRes['result']) && is_array($listRes['result'])) {
    foreach ($listRes['result'] as $item) {
        if (($item['FIELD_NAME'] ?? '') === 'UF_CRM_CANDIDATE_TIME') {
            $existingField = $item;
            break;
        }
    }
}

if ($existingField) {
    echo $typeInfo . "\n\nПоле уже существует:\n" .
         json_encode($existingField, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 3. Создаём поле
$addMethod = $entity === 'deal' ? 'crm.deal.userfield.add' : 'crm.lead.userfield.add';

$fieldRes = bxCall($domain, $accessToken, $addMethod, [
    'fields[FIELD_NAME]' => 'UF_CRM_CANDIDATE_TIME',
    'fields[EDIT_FORM_LABEL][ru]' => 'Время кандидата',
    'fields[LIST_COLUMN_LABEL][ru]' => 'Время кандидата',
    'fields[LIST_FILTER_LABEL][ru]' => 'Время кандидата',
    'fields[XML_ID]' => 'CANDIDATE_TIME',
    'fields[USER_TYPE_ID]' => $userTypeId
]);

echo $typeInfo . "\n\nСоздание поля:\n" .
     json_encode($fieldRes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
