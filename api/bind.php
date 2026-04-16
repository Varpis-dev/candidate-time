<?php
header('Content-Type: text/plain; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

$entity = $input['entity'] ?? 'lead';
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

    $payload = $params;
    $payload['auth'] = $token;

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $json,
            'ignore_errors' => true,
            'timeout' => 30,
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    $decoded = json_decode($result ?: '', true);

    return [
        'raw' => $result,
        'decoded' => is_array($decoded) ? $decoded : null,
    ];
}

// 1. Узнаём APP_ID
$appInfoRes = bxCall($domain, $accessToken, 'app.info', []);
$appInfo = $appInfoRes['decoded']['result'] ?? null;

if (!$appInfo || empty($appInfo['ID'])) {
    echo "Не удалось получить app.info\n\n";
    echo $appInfoRes['raw'] ?? '';
    exit;
}

$appId = $appInfo['ID'];
$baseUserTypeId = 'candidate_time';
$finalUserTypeId = 'rest_' . $appId . '_' . $baseUserTypeId;
$handlerUrl = 'https://project-jbfzb.vercel.app/field';

// 2. Регистрируем тип поля
$typeRes = bxCall($domain, $accessToken, 'userfieldtype.add', [
    'USER_TYPE_ID' => $baseUserTypeId,
    'HANDLER' => $handlerUrl,
    'TITLE' => 'Время кандидата',
    'DESCRIPTION' => 'Показывает текущее время кандидата',
    'OPTIONS' => [
        'height' => 90
    ]
]);

// Если тип уже существует — это не критично
$typeDecoded = $typeRes['decoded'] ?? [];
$typeError = $typeDecoded['error'] ?? '';

// 3. Проверяем, есть ли уже поле
$listMethod = $entity === 'deal' ? 'crm.deal.userfield.list' : 'crm.lead.userfield.list';
$listRes = bxCall($domain, $accessToken, $listMethod, []);
$listDecoded = $listRes['decoded'] ?? [];

$fieldName = 'CAND_TIME'; // БЕЗ UF_CRM_

$existingField = null;
if (!empty($listDecoded['result']) && is_array($listDecoded['result'])) {
    foreach ($listDecoded['result'] as $item) {
        if (($item['FIELD_NAME'] ?? '') === 'UF_CRM_' . $fieldName) {
            $existingField = $item;
            break;
        }
    }
}

if ($existingField) {
    echo "Тип поля:\n";
    echo json_encode($typeDecoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "\n\nПоле уже существует:\n";
    echo json_encode($existingField, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 4. Создаём поле
$addMethod = $entity === 'deal' ? 'crm.deal.userfield.add' : 'crm.lead.userfield.add';

$fieldRes = bxCall($domain, $accessToken, $addMethod, [
    'fields' => [
        'FIELD_NAME' => $fieldName,
        'USER_TYPE_ID' => $finalUserTypeId,
        'LABEL' => 'Время кандидата',
        'EDIT_FORM_LABEL' => ['ru' => 'Время кандидата'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Время кандидата'],
        'LIST_FILTER_LABEL' => ['ru' => 'Время кандидата'],
        'XML_ID' => 'CANDIDATE_TIME',
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'N',
        'SETTINGS' => []
    ]
]);

echo "app.info:\n";
echo json_encode($appInfoRes['decoded'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

echo "\n\nРегистрация типа поля:\n";
echo json_encode($typeDecoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

echo "\n\nСоздание поля:\n";
echo json_encode($fieldRes['decoded'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
