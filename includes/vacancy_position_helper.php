<?php

function resolvePositionMasterId(PDO $pdo, string $positionName): int
{
    $positionName = trim($positionName);
    $find = $pdo->prepare('SELECT PositionMasterID FROM vacancy_position_master WHERE PositionName = :name');
    $find->execute(['name' => $positionName]);
    $id = $find->fetchColumn();
    if ($id) {
        return (int)$id;
    }
    $ins = $pdo->prepare('INSERT INTO vacancy_position_master (PositionName) VALUES (:name)');
    $ins->execute(['name' => $positionName]);
    return (int)$pdo->lastInsertId();
}
