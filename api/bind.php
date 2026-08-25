<?php

header('Content-Type: application/json; charset=utf-8');

/*
 * ============================================================
 * ВРЕМЯ КАНДИДАТА
 *
 * Этот файл:
 * 1. Обновляет HANDLER существующего типа candidate_time
 *    на ТЕКУЩИЙ домен Vercel.
 *
 * 2. Если тип почему-то ещё не существует — создаёт его.
 *
 * 3. Проверяет наличие поля "Время кандидата"
 *    в лидах и сделках.
 *
 * 4. Существующие поля НЕ удаляет и НЕ пересоздаёт.
 * ============================================================
 */

const USER_TYPE_ID = 'candidate_time';
const FIELD_NAME   = 'UF_CRM_CAND_TIME';

const FIELD_LABEL = 'Время кандидата';


/*
 * ============================================================
 * JSON RESPONSE
 * ============================================================
 */

function jsonResponse($data)
{
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}


/*
 * ============================================================
 * ТЕКУЩИЙ ДОМЕН
 *
 * На новом Vercel автоматически получится:
 * https://candidate-time.vercel.app
 * ============================================================
 */

function getBaseUrl()
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    $host = preg_replace(
        '/[^a-zA-Z0-9.\-]/',
        '',
        $host
    );

    return 'https://' . $host;
}


/*
 * ============================================================
 * AUTH
 * ============================================================
 */

function normalizeAuth($input)
{
    $auth = [];

    if (
        isset($input['auth']) &&
        is_array($input['auth'])
    ) {
        $auth = $input['auth'];
    }

    if (
        isset($input['AUTH']) &&
        is_array($input['AUTH'])
    ) {
        $auth = array_merge(
            $auth,
            $input['AUTH']
        );
    }


    /*
     * ACCESS TOKEN
     */

    if (empty($auth['access_token'])) {

        if (!empty($input['access_token'])) {

            $auth['access_token'] =
                $input['access_token'];

        } elseif (!empty($input['AUTH_ID'])) {

            $auth['access_token'] =
                $input['AUTH_ID'];

        } elseif (
            !empty(
                $input['AUTH']['ACCESS_TOKEN']
            )
        ) {

            $auth['access_token'] =
                $input['AUTH']['ACCESS_TOKEN'];
        }
    }


    /*
     * DOMAIN
     */

    if (empty($auth['domain'])) {

        if (!empty($input['domain'])) {

            $auth['domain'] =
                $input['domain'];

        } elseif (!empty($input['DOMAIN'])) {

            $auth['domain'] =
                $input['DOMAIN'];

        } elseif (
            !empty(
                $input['AUTH']['DOMAIN']
            )
        ) {

            $auth['domain'] =
                $input['AUTH']['DOMAIN'];
        }
    }


    return $auth;
}


/*
 * ============================================================
 * BITRIX REST
 * ============================================================
 */

function callBitrix(
    $method,
    $params,
    $auth
) {

    if (
        empty($auth['domain']) ||
        empty($auth['access_token'])
    ) {

        return [
            'error' => 'NO_AUTH',
            'error_description' =>
                'Нет domain или access_token'
        ];
    }


    $domain = preg_replace(
        '/[^a-zA-Z0-9.\-]/',
        '',
        $auth['domain']
    );


    $url =
        'https://' .
        $domain .
        '/rest/' .
        $method .
        '.json';


    $params['auth'] =
        $auth['access_token'];


    $context =
        stream_context_create([
            'http' => [

                'method' =>
                    'POST',

                'header' =>
                    "Content-Type: application/x-www-form-urlencoded\r\n",

                'content' =>
                    http_build_query(
                        $params
                    ),

                'timeout' =>
                    20,

                'ignore_errors' =>
                    true
            ]
        ]);


    $raw =
        @file_get_contents(
            $url,
            false,
            $context
        );


    if ($raw === false) {

        return [
            'error' =>
                'HTTP_REQUEST_FAILED',

            'error_description' =>
                'Не удалось обратиться к Bitrix24',

            'method' =>
                $method
        ];
    }


    $decoded =
        json_decode(
            $raw,
            true
        );


    if (!is_array($decoded)) {

        return [
            'error' =>
                'BAD_JSON',

            'method' =>
                $method,

            'json_error' =>
                json_last_error_msg(),

            'raw_start' =>
                substr(
                    $raw,
                    0,
                    1000
                )
        ];
    }


    return $decoded;
}


/*
 * ============================================================
 * ЧИТАЕМ ВХОД
 * ============================================================
 */

$rawInput =
    file_get_contents(
        'php://input'
    );


$json =
    json_decode(
        $rawInput,
        true
    );


if (
    is_array($json) &&
    !empty($json)
) {

    $input =
        array_replace_recursive(
            $_REQUEST,
            $json
        );

} else {

    $input =
        $_REQUEST;
}


$auth =
    normalizeAuth(
        $input
    );


if (
    empty($auth['domain']) ||
    empty($auth['access_token'])
) {

    jsonResponse([
        'success' => false,
        'reason' => 'no_auth',
        'message' =>
            'Не удалось получить авторизацию Bitrix24.'
    ]);
}


/*
 * ============================================================
 * НОВЫЙ HANDLER
 * ============================================================
 */

$baseUrl =
    getBaseUrl();


$fieldHandler =
    $baseUrl .
    '/field';


/*
 * ============================================================
 * 1. ОБНОВЛЯЕМ СУЩЕСТВУЮЩИЙ ТИП
 *
 * КЛЮЧЕВОЙ МОМЕНТ МИГРАЦИИ.
 *
 * Было:
 * https://project-jbfzb.vercel.app/field
 *
 * Станет:
 * https://candidate-time.vercel.app/field
 * ============================================================
 */

$typeUpdate =
    callBitrix(
        'userfieldtype.update',
        [

            'USER_TYPE_ID' =>
                USER_TYPE_ID,

            'HANDLER' =>
                $fieldHandler,

            'TITLE' =>
                FIELD_LABEL,

            'DESCRIPTION' =>
                'Показывает текущее время кандидата по его городу и региону',

            'OPTIONS' => [
                'height' => 180
            ]
        ],
        $auth
    );


$typeAction =
    'updated';


/*
 * ============================================================
 * ЕСЛИ ТИПА НЕТ — СОЗДАЁМ
 * ============================================================
 */

if (
    !empty(
        $typeUpdate['error']
    )
) {

    $typeAdd =
        callBitrix(
            'userfieldtype.add',
            [

                'USER_TYPE_ID' =>
                    USER_TYPE_ID,

                'HANDLER' =>
                    $fieldHandler,

                'TITLE' =>
                    FIELD_LABEL,

                'DESCRIPTION' =>
                    'Показывает текущее время кандидата по его городу и региону',

                'OPTIONS' => [
                    'height' => 180
                ]
            ],
            $auth
        );


    if (
        empty(
            $typeAdd['error']
        )
    ) {

        $typeUpdate =
            $typeAdd;

        $typeAction =
            'created';

    } else {

        /*
         * Если update упал не потому,
         * что типа нет, а add тоже не сработал —
         * возвращаем обе ошибки.
         */

        jsonResponse([
            'success' => false,

            'reason' =>
                'userfieldtype_error',

            'new_handler' =>
                $fieldHandler,

            'update_error' =>
                $typeUpdate,

            'add_error' =>
                $typeAdd
        ]);
    }
}


/*
 * ============================================================
 * 2. ПОЛУЧАЕМ APP ID
 *
 * Полный тип будет:
 * rest_<APP_ID>_candidate_time
 * ============================================================
 */

$appInfo =
    callBitrix(
        'app.info',
        [],
        $auth
    );


if (
    !empty(
        $appInfo['error']
    )
) {

    jsonResponse([
        'success' => false,

        'reason' =>
            'app_info_error',

        'error' =>
            $appInfo
    ]);
}


$appId =
    $appInfo['result']['ID'] ??
    $appInfo['result']['APP_ID'] ??
    null;


if (!$appId) {

    jsonResponse([
        'success' => false,

        'reason' =>
            'no_app_id',

        'app_info' =>
            $appInfo
    ]);
}


$fullUserTypeId =
    'rest_' .
    $appId .
    '_' .
    USER_TYPE_ID;


/*
 * ============================================================
 * ПОИСК ПОЛЯ
 * ============================================================
 */

function findUserField(
    $entity,
    $fieldName,
    $auth
) {

    $method =
        $entity === 'lead'
            ? 'crm.lead.userfield.list'
            : 'crm.deal.userfield.list';


    $response =
        callBitrix(
            $method,
            [
                'filter' => [
                    'FIELD_NAME' =>
                        $fieldName
                ]
            ],
            $auth
        );


    if (
        !empty(
            $response['error']
        )
    ) {

        return [
            'found' => false,
            'error' => $response
        ];
    }


    foreach (
        $response['result'] ?? []
        as $field
    ) {

        if (
            ($field['FIELD_NAME'] ?? '') ===
            $fieldName
        ) {

            return [
                'found' => true,
                'field' => $field
            ];
        }
    }


    return [
        'found' => false
    ];
}


/*
 * ============================================================
 * СОЗДАНИЕ ПОЛЯ, ТОЛЬКО ЕСЛИ ЕГО НЕТ
 * ============================================================
 */

function ensureCandidateTimeField(
    $entity,
    $fieldName,
    $fullUserTypeId,
    $auth
) {

    $existing =
        findUserField(
            $entity,
            $fieldName,
            $auth
        );


    if (
        !empty(
            $existing['error']
        )
    ) {

        return [
            'success' => false,
            'reason' =>
                'field_search_error',
            'error' =>
                $existing['error']
        ];
    }


    /*
     * ВАЖНО:
     * существующее поле НЕ ТРОГАЕМ.
     *
     * Оно уже использует тип
     * rest_<APP_ID>_candidate_time.
     *
     * Мы выше обновили сам HANDLER типа,
     * поэтому поле автоматически начнёт
     * открывать новый URL.
     */

    if (
        !empty(
            $existing['found']
        )
    ) {

        return [
            'success' => true,

            'already_exists' =>
                true,

            'field_name' =>
                $fieldName,

            'current_type' =>
                $existing['field']['USER_TYPE_ID'] ??
                null
        ];
    }


    $method =
        $entity === 'lead'
            ? 'crm.lead.userfield.add'
            : 'crm.deal.userfield.add';


    $result =
        callBitrix(
            $method,
            [
                'fields' => [

                    'FIELD_NAME' =>
                        $fieldName,

                    'EDIT_FORM_LABEL' =>
                        FIELD_LABEL,

                    'LIST_COLUMN_LABEL' =>
                        FIELD_LABEL,

                    'LIST_FILTER_LABEL' =>
                        FIELD_LABEL,

                    'USER_TYPE_ID' =>
                        $fullUserTypeId,

                    'MULTIPLE' =>
                        'N',

                    'MANDATORY' =>
                        'N',

                    'SHOW_FILTER' =>
                        'N',

                    'EDIT_IN_LIST' =>
                        'N',

                    'SORT' =>
                        100
                ]
            ],
            $auth
        );


    if (
        !empty(
            $result['error']
        )
    ) {

        return [
            'success' => false,
            'reason' =>
                'field_create_error',
            'error' =>
                $result
        ];
    }


    return [
        'success' => true,

        'already_exists' =>
            false,

        'created' =>
            true,

        'field_name' =>
            $fieldName,

        'user_type_id' =>
            $fullUserTypeId,

        'result' =>
            $result
    ];
}


/*
 * ============================================================
 * 3. ПРОВЕРЯЕМ ЛИД
 * ============================================================
 */

$leadField =
    ensureCandidateTimeField(
        'lead',
        FIELD_NAME,
        $fullUserTypeId,
        $auth
    );


/*
 * ============================================================
 * 4. ПРОВЕРЯЕМ СДЕЛКУ
 * ============================================================
 */

$dealField =
    ensureCandidateTimeField(
        'deal',
        FIELD_NAME,
        $fullUserTypeId,
        $auth
    );


/*
 * ============================================================
 * ГОТОВО
 * ============================================================
 */

jsonResponse([
    'success' => true,

    'message' =>
        'Обработчик поля "Время кандидата" обновлён.',

    'domain' =>
        $baseUrl,

    'handler' =>
        $fieldHandler,

    'user_type' => [

        'short' =>
            USER_TYPE_ID,

        'full' =>
            $fullUserTypeId,

        'action' =>
            $typeAction,

        'result' =>
            $typeUpdate
    ],

    'fields' => [

        'lead' =>
            $leadField,

        'deal' =>
            $dealField
    ]
]);
