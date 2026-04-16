<?php
header('Content-Type: text/plain; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$entity = $input['entity'] ?? 'lead';

echo "Следующий шаг: сюда добавим REST-вызовы userfieldtype.add и создание поля для сущности {$entity}.";
