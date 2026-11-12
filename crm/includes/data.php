<?php

declare(strict_types=1);

function crm_install(): void
{
    $pdo = db();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS crm_clienti (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            companie VARCHAR(160) NOT NULL,
            contact VARCHAR(120) NOT NULL DEFAULT \'\',
            telefon VARCHAR(80) NOT NULL DEFAULT \'\',
            pret_nota VARCHAR(120) NOT NULL DEFAULT \'\',
            status VARCHAR(20) NOT NULL DEFAULT \'activ\',
            note TEXT NOT NULL,
            creat DATETIME NOT NULL,
            actualizat DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY companie_idx (companie),
            KEY status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS crm_aparate (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT UNSIGNED NOT NULL,
            model VARCHAR(160) NOT NULL,
            tip VARCHAR(40) NOT NULL DEFAULT \'cialde\',
            cantitate SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            stare VARCHAR(20) NOT NULL DEFAULT \'comodat\',
            note VARCHAR(255) NOT NULL DEFAULT \'\',
            la DATE NOT NULL,
            PRIMARY KEY (id),
            KEY client_idx (client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS crm_vanzari (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT UNSIGNED NULL DEFAULT NULL,
            companie VARCHAR(160) NOT NULL DEFAULT \'\',
            telefon VARCHAR(80) NOT NULL DEFAULT \'\',
            contact VARCHAR(120) NOT NULL DEFAULT \'\',
            produs VARCHAR(80) NOT NULL,
            cutii DECIMAL(10,2) NOT NULL DEFAULT 0,
            plata VARCHAR(80) NOT NULL DEFAULT \'\',
            total DECIMAL(10,2) NULL DEFAULT NULL,
            pret_special DECIMAL(10,2) NULL DEFAULT NULL,
            cash DECIMAL(10,2) NULL DEFAULT NULL,
            note TEXT NOT NULL,
            la DATE NOT NULL,
            creat DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY client_idx (client_id),
            KEY la_idx (la),
            KEY produs_idx (produs)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_add_column($pdo, 'crm_vanzari', 'tip_plata', "VARCHAR(20) NOT NULL DEFAULT 'oficiala'");
    try {
        $pdo->exec('ALTER TABLE crm_vanzari ADD KEY tip_plata_idx (tip_plata)');
    } catch (PDOException $e) {
        // index may already exist
    }
}

function crm_stats(): array
{
    $pdo = db();
    $clients = (int) $pdo->query('SELECT COUNT(*) FROM crm_clienti')->fetchColumn();
    $machines = (int) $pdo->query("SELECT COALESCE(SUM(cantitate),0) FROM crm_aparate WHERE stare = 'comodat'")->fetchColumn();
    $monthStart = date('Y-m-01');
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(total),0), COALESCE(SUM(cash),0), COUNT(*) FROM crm_vanzari WHERE la >= ?');
    $stmt->execute([$monthStart]);
    $row = $stmt->fetch(PDO::FETCH_NUM) ?: [0, 0, 0];

    $oficial = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM crm_vanzari WHERE la >= ? AND tip_plata = 'oficiala'");
    $oficial->execute([$monthStart]);
    $neoficial = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM crm_vanzari WHERE la >= ? AND tip_plata = 'neoficiala'");
    $neoficial->execute([$monthStart]);

    return [
        'clienti' => $clients,
        'aparate_comodat' => $machines,
        'vanzari_luna' => (float) $row[0],
        'cash_luna' => (float) $row[1],
        'livrari_luna' => (int) $row[2],
        'oficial_luna' => (float) $oficial->fetchColumn(),
        'neoficial_luna' => (float) $neoficial->fetchColumn(),
    ];
}

function crm_clients_list(string $q = '', string $status = ''): array
{
    $pdo = db();
    $sql = 'SELECT c.*,
            (SELECT COALESCE(SUM(a.cantitate),0) FROM crm_aparate a WHERE a.client_id = c.id AND a.stare = \'comodat\') AS aparate_nr,
            (SELECT MAX(v.la) FROM crm_vanzari v WHERE v.client_id = c.id) AS ultima_vanzare
            FROM crm_clienti c WHERE 1=1';
    $params = [];
    if ($status !== '' && isset(crm_status_labels()[$status])) {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $sql .= ' AND (c.companie LIKE ? OR c.contact LIKE ? OR c.telefon LIKE ? OR c.pret_nota LIKE ? OR c.note LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY c.companie ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function crm_client_get(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM crm_clienti WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function crm_client_save(array $data, int $id = 0): int
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $companie = crm_text((string) ($data['companie'] ?? ''), 160);
    $contact = crm_text((string) ($data['contact'] ?? ''), 120);
    $telefon = crm_text((string) ($data['telefon'] ?? ''), 80);
    $pret = crm_text((string) ($data['pret_nota'] ?? ''), 120);
    $status = crm_text((string) ($data['status'] ?? 'activ'), 20);
    if (!isset(crm_status_labels()[$status])) {
        $status = 'activ';
    }
    $note = trim((string) ($data['note'] ?? ''));
    if (strlen($note) > 5000) {
        $note = substr($note, 0, 5000);
    }
    if ($companie === '') {
        throw new InvalidArgumentException('Completează numele clientului / compania.');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE crm_clienti SET companie=?, contact=?, telefon=?, pret_nota=?, status=?, note=?, actualizat=? WHERE id=?'
        );
        $stmt->execute([$companie, $contact, $telefon, $pret, $status, $note, $now, $id]);

        return $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO crm_clienti (companie, contact, telefon, pret_nota, status, note, creat, actualizat)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([$companie, $contact, $telefon, $pret, $status, $note, $now, $now]);

    return (int) $pdo->lastInsertId();
}

function crm_client_delete(int $id): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM crm_aparate WHERE client_id = ?')->execute([$id]);
    $pdo->prepare('UPDATE crm_vanzari SET client_id = NULL WHERE client_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM crm_clienti WHERE id = ?')->execute([$id]);
}

function crm_machines_for(int $clientId): array
{
    $stmt = db()->prepare('SELECT * FROM crm_aparate WHERE client_id = ? ORDER BY la DESC, id DESC');
    $stmt->execute([$clientId]);

    return $stmt->fetchAll() ?: [];
}

function crm_machine_add(int $clientId, array $data): void
{
    $model = crm_text((string) ($data['model'] ?? ''), 160);
    if ($model === '') {
        throw new InvalidArgumentException('Completează modelul aparatului.');
    }
    $tip = crm_text((string) ($data['tip'] ?? 'cialde'), 40);
    if (!isset(crm_aparat_tipuri()[$tip])) {
        $tip = 'cialde';
    }
    $qty = (int) ($data['cantitate'] ?? 1);
    if ($qty < 1) {
        $qty = 1;
    }
    if ($qty > 99) {
        $qty = 99;
    }
    $stare = crm_text((string) ($data['stare'] ?? 'comodat'), 20);
    if (!in_array($stare, ['comodat', 'vandut', 'returnat'], true)) {
        $stare = 'comodat';
    }
    $note = crm_text((string) ($data['note'] ?? ''), 255);
    $la = crm_text((string) ($data['la'] ?? date('Y-m-d')), 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $la)) {
        $la = date('Y-m-d');
    }
    $stmt = db()->prepare(
        'INSERT INTO crm_aparate (client_id, model, tip, cantitate, stare, note, la) VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([$clientId, $model, $tip, $qty, $stare, $note, $la]);
}

function crm_machine_delete(int $id, int $clientId): void
{
    db()->prepare('DELETE FROM crm_aparate WHERE id = ? AND client_id = ?')->execute([$id, $clientId]);
}

function crm_sales_list(string $from = '', string $to = '', string $q = '', string $tipPlata = ''): array
{
    $pdo = db();
    $sql = 'SELECT v.*, c.companie AS client_companie
            FROM crm_vanzari v
            LEFT JOIN crm_clienti c ON c.id = v.client_id
            WHERE 1=1';
    $params = [];
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $sql .= ' AND v.la >= ?';
        $params[] = $from;
    }
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $sql .= ' AND v.la <= ?';
        $params[] = $to;
    }
    if ($tipPlata !== '' && isset(crm_tip_plata_options()[$tipPlata])) {
        $sql .= ' AND v.tip_plata = ?';
        $params[] = $tipPlata;
    }
    if ($q !== '') {
        $sql .= ' AND (v.companie LIKE ? OR v.contact LIKE ? OR v.telefon LIKE ? OR v.produs LIKE ? OR v.note LIKE ? OR c.companie LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY v.la DESC, v.id DESC LIMIT 500';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function crm_sales_for_client(int $clientId): array
{
    $stmt = db()->prepare('SELECT * FROM crm_vanzari WHERE client_id = ? ORDER BY la DESC, id DESC LIMIT 200');
    $stmt->execute([$clientId]);

    return $stmt->fetchAll() ?: [];
}

function crm_sale_add(array $data): int
{
    $pdo = db();
    $clientId = (int) ($data['client_id'] ?? 0);
    if ($clientId < 1) {
        $clientId = null;
    }
    $companie = crm_text((string) ($data['companie'] ?? ''), 160);
    $telefon = crm_text((string) ($data['telefon'] ?? ''), 80);
    $contact = crm_text((string) ($data['contact'] ?? ''), 120);
    if ($clientId !== null) {
        $client = crm_client_get($clientId);
        if ($client === null) {
            $clientId = null;
        } else {
            if ($companie === '') {
                $companie = (string) $client['companie'];
            }
            if ($telefon === '') {
                $telefon = (string) $client['telefon'];
            }
            if ($contact === '') {
                $contact = (string) $client['contact'];
            }
        }
    }
    $produs = crm_text((string) ($data['produs'] ?? ''), 80);
    if ($produs === '') {
        throw new InvalidArgumentException('Alege produsul.');
    }
    $cutii = crm_money_input((string) ($data['cutii'] ?? '0'));
    if ($cutii === null) {
        $cutii = 0.0;
    }
    $plata = crm_text((string) ($data['plata'] ?? ''), 80);
    $tipPlata = crm_text((string) ($data['tip_plata'] ?? 'oficiala'), 20);
    if (!isset(crm_tip_plata_options()[$tipPlata])) {
        $tipPlata = 'oficiala';
    }
    $total = crm_money_input((string) ($data['total'] ?? ''));
    $pretSpecial = crm_money_input((string) ($data['pret_special'] ?? ''));
    $cash = crm_money_input((string) ($data['cash'] ?? ''));
    $note = trim((string) ($data['note'] ?? ''));
    if (strlen($note) > 3000) {
        $note = substr($note, 0, 3000);
    }
    $la = crm_text((string) ($data['la'] ?? date('Y-m-d')), 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $la)) {
        $la = date('Y-m-d');
    }
    if ($companie === '' && $clientId === null) {
        throw new InvalidArgumentException('Completează compania sau alege un client.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO crm_vanzari
        (client_id, companie, telefon, contact, produs, cutii, plata, tip_plata, total, pret_special, cash, note, la, creat)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $clientId,
        $companie,
        $telefon,
        $contact,
        $produs,
        $cutii,
        $plata,
        $tipPlata,
        $total,
        $pretSpecial,
        $cash,
        $note,
        $la,
        date('Y-m-d H:i:s'),
    ]);

    return (int) $pdo->lastInsertId();
}

function crm_sale_delete(int $id): void
{
    db()->prepare('DELETE FROM crm_vanzari WHERE id = ?')->execute([$id]);
}

function crm_find_client_id_by_company(string $companie): ?int
{
    $companie = crm_text($companie, 160);
    if ($companie === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM crm_clienti WHERE companie = ? LIMIT 1');
    $stmt->execute([$companie]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}
