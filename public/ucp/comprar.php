<?php
session_start();

require_once 'ind/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'], $_POST['itemid'], $_POST['char'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
    exit;
}

$username = $_SESSION['username'];
$itemId = (int)$_POST['itemid'];
$charName = trim($_POST['char']);

$conn = getDbConnection();

/* =========================
   GERAR OBJECT ID ÚNICO
========================= */
function generateUniqueObjectId(mysqli $conn): int {
    do {
        $objectId = random_int(100000000, 999999999);

        $stmt = $conn->prepare("SELECT 1 FROM items WHERE object_id = ? LIMIT 1");
        $stmt->bind_param("i", $objectId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

    } while ($exists);

    return $objectId;
}

/* =========================
   SALDO
========================= */
$stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($balance);

if (!$stmt->fetch()) {
    $stmt->close();
    exit(json_encode(['success' => false, 'message' => 'Conta não encontrada.']));
}
$stmt->close();

/* =========================
   ITEM SHOP
========================= */
$stmt = $conn->prepare("
    SELECT item_id, price, quantity, stackable 
    FROM shop_items 
    WHERE id = ? AND available = 1
");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    exit(json_encode(['success' => false, 'message' => 'Item inválido.']));
}

/* =========================
   CHAR
========================= */
$stmt = $conn->prepare("
    SELECT obj_Id, online 
    FROM characters 
    WHERE account_name = ? AND char_name = ?
");
$stmt->bind_param("ss", $username, $charName);
$stmt->execute();
$stmt->bind_result($charId, $online);

if (!$stmt->fetch()) {
    $stmt->close();
    exit(json_encode(['success' => false, 'message' => 'Personagem não encontrado.']));
}
$stmt->close();

if ($online) {
    exit(json_encode(['success' => false, 'message' => 'Personagem está online.']));
}

/* =========================
   VALIDA SALDO
========================= */
if ($balance < $item['price']) {
    exit(json_encode(['success' => false, 'message' => 'Saldo insuficiente.']));
}

/* =========================
   TRANSAÇÃO (ANTI-DUPE)
========================= */
$conn->begin_transaction();

try {

    $gameItemId = (int)$item['item_id'];
    $quantity = (int)$item['quantity'];
    $stackable = (int)$item['stackable'] === 1;
    $now = -1;

    /* =========================
       STACKABLE
    ========================= */
    if ($stackable) {

        $stmt = $conn->prepare("
            SELECT object_id, count 
            FROM items 
            WHERE owner_id = ? 
            AND item_id = ? 
            AND loc = 'INVENTORY'
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("ii", $charId, $gameItemId);
        $stmt->execute();
        $stmt->bind_result($objectId, $currentCount);

        if ($stmt->fetch()) {
            $stmt->close();

            $newCount = $currentCount + $quantity;

            $stmt = $conn->prepare("UPDATE items SET count = ? WHERE object_id = ?");
            $stmt->bind_param("ii", $newCount, $objectId);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar item.");
            }

            $stmt->close();

        } else {
            $stmt->close();

            $objectId = generateUniqueObjectId($conn);

            $stmt = $conn->prepare("
                INSERT INTO items 
                (owner_id, object_id, item_id, count, enchant_level, loc, loc_data, time_of_use, custom_type1, custom_type2, mana_left, time)
                VALUES (?, ?, ?, ?, 0, 'INVENTORY', 0, 0, 0, 0, -1, ?)
            ");
            $stmt->bind_param("iiiii", $charId, $objectId, $gameItemId, $quantity, $now);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir item.");
            }

            $stmt->close();
        }

    } 
    /* =========================
       NÃO STACKABLE
    ========================= */
    else {

        for ($i = 0; $i < $quantity; $i++) {

            $objectId = generateUniqueObjectId($conn);

            $stmt = $conn->prepare("
                INSERT INTO items 
                (owner_id, object_id, item_id, count, enchant_level, loc, loc_data, time_of_use, custom_type1, custom_type2, mana_left, time)
                VALUES (?, ?, ?, 1, 0, 'INVENTORY', 0, 0, 0, 0, -1, ?)
            ");
            $stmt->bind_param("iiii", $charId, $objectId, $gameItemId, $now);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir item não stackable.");
            }

            $stmt->close();
        }
    }

    /* =========================
       DESCONTAR SALDO
    ========================= */
    $stmt = $conn->prepare("
        UPDATE account_balance 
        SET balance = balance - ? 
        WHERE login = ?
    ");
    $stmt->bind_param("ds", $item['price'], $username);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao debitar saldo.");
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item entregue com sucesso.'
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}