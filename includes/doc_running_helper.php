<?php
/**
 * ระบบเลขรันเอกสาร — อ่านรูปแบบจากตาราง doc_running (จัดการผ่านหน้า doc_running_management.php)
 *
 * หลักการ: ตาราง doc_running เก็บ "กติกา" ของเลข (รูปแบบ/รอบรีเซ็ต/จำนวนหลัก/เลขเริ่มต้น)
 * ส่วนเลขที่ออกจริงคิดจากเลขสูงสุดที่มีอยู่จริงในตารางต้นทางของรอบนั้น + 1 เสมอ
 * จึงไม่มีปัญหาเลขข้ามเมื่อลบเอกสารท้ายสุดของรอบออก
 *
 * nextDocRunning() ต้องเรียกภายใน transaction ที่จะ INSERT แถวใหม่ต่อทันที เพราะใช้
 * SELECT ... FOR UPDATE ล็อกช่วงเลขของรอบนั้นไว้จนกว่าจะ commit กันสองคนบันทึกพร้อมกันแล้วได้เลขซ้ำ
 */

/** token ที่ใช้ได้ในรูปแบบเลข => คำอธิบายสำหรับแสดงในหน้าจัดการ */
function docRunningTokens(): array
{
    return [
        '{PREFIX}' => 'ข้อความนำหน้าที่ตั้งไว้ในช่อง Prefix',
        '{DATE}'   => 'วันที่แบบ ค.ศ. YYYY-MM-DD (เช่น 2026-07-27)',
        '{YYYY}'   => 'ปี ค.ศ. 4 หลัก',
        '{YY}'     => 'ปี ค.ศ. 2 หลัก',
        '{BYYYY}'  => 'ปี พ.ศ. 4 หลัก',
        '{BYY}'    => 'ปี พ.ศ. 2 หลัก',
        '{MM}'     => 'เดือน 2 หลัก',
        '{DD}'     => 'วันที่ 2 หลัก',
        '{SEQ}'    => 'เลขลำดับ (ต้องอยู่ท้ายสุดของรูปแบบ)',
    ];
}

/**
 * รหัสประเภทเอกสารที่โค้ดเรียกใช้อยู่ — ห้ามลบและห้ามเปลี่ยนรหัส
 * มิฉะนั้นหน้าบันทึกจะออกเลขไม่ได้ (แก้รูปแบบ/ปิดใช้งานยังทำได้)
 */
function docRunningLockedTypes(): array
{
    return [
        'register'  => 'api/register_save.php',
        'selft_rep' => 'api/selfrep_save.php',
    ];
}

/** ชื่อรอบรีเซ็ตเป็นภาษาไทย */
function docRunningCycleName(string $cycle): string
{
    $names = [
        'daily'   => 'รายวัน',
        'monthly' => 'รายเดือน',
        'yearly'  => 'รายปี',
        'never'   => 'ไม่รีเซ็ต (นับต่อเนื่อง)',
    ];
    return $names[$cycle] ?? $cycle;
}

/** อ่านรูปแบบทั้งหมด (ใช้ในหน้าจัดการ) */
function docRunningAll(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM doc_running ORDER BY RunID")->fetchAll();
}

/** อ่านรูปแบบของเอกสารประเภทหนึ่ง — โยน exception หากไม่พบหรือถูกปิดใช้งาน */
function docRunningConfig(PDO $pdo, string $docType): array
{
    $stmt = $pdo->prepare("SELECT * FROM doc_running WHERE DocType = :t LIMIT 1");
    $stmt->execute([':t' => $docType]);
    $cfg = $stmt->fetch();

    if (!$cfg) {
        throw new RuntimeException('ยังไม่ได้ตั้งค่าเลขรันของเอกสาร "' . $docType . '" กรุณาตั้งค่าที่หน้าจัดการเลขที่เอกสาร');
    }
    if ((int) $cfg['Active'] !== 1) {
        throw new RuntimeException('รูปแบบเลขรันของเอกสาร "' . $cfg['DocName'] . '" ถูกปิดใช้งานอยู่');
    }
    return $cfg;
}

/** แทนค่า token ทุกตัวยกเว้น {SEQ} — ได้ส่วนหน้าของเลขเอกสารสำหรับรอบ (วัน/เดือน/ปี) ที่ระบุ */
function docRunningPrefix(array $cfg, string $date): string
{
    $ts = strtotime($date);
    if ($ts === false) {
        throw new InvalidArgumentException('รูปแบบวันที่ไม่ถูกต้อง: ' . $date);
    }

    $format = (string) $cfg['Format'];
    if (!str_ends_with($format, '{SEQ}')) {
        throw new RuntimeException('รูปแบบเลขต้องลงท้ายด้วย {SEQ} (ปัจจุบัน: ' . $format . ')');
    }

    $head = substr($format, 0, -strlen('{SEQ}'));
    $year = (int) date('Y', $ts);

    return strtr($head, [
        '{PREFIX}' => (string) ($cfg['Prefix'] ?? ''),
        '{DATE}'   => date('Y-m-d', $ts),
        '{YYYY}'   => (string) $year,
        '{YY}'     => date('y', $ts),
        '{BYYYY}'  => (string) ($year + 543),
        '{BYY}'    => substr((string) ($year + 543), -2),
        '{MM}'     => date('m', $ts),
        '{DD}'     => date('d', $ts),
    ]);
}

/** ประกอบเลขเอกสารเต็มรูปแบบจากเลขลำดับ */
function docRunningFormat(array $cfg, int $seq, string $date): string
{
    $pad = (int) ($cfg['Padding'] ?? 0);
    $num = $pad > 0 ? str_pad((string) $seq, $pad, '0', STR_PAD_LEFT) : (string) $seq;

    return docRunningPrefix($cfg, $date) . $num;
}

/**
 * ตรวจว่ารูปแบบทำให้เลขรีเซ็ตตามรอบไหนจริงๆ — ดูจากว่าส่วนหน้าเปลี่ยนค่าเมื่อข้ามวัน/เดือน/ปีหรือไม่
 * ใช้ตรวจว่ารูปแบบที่ผู้ใช้กรอกสอดคล้องกับรอบรีเซ็ตที่เลือก
 */
function docRunningDetectCycle(array $cfg): string
{
    $base = '2000-01-15';
    $head = docRunningPrefix($cfg, $base);

    if ($head !== docRunningPrefix($cfg, '2000-01-16')) return 'daily';
    if ($head !== docRunningPrefix($cfg, '2000-02-15')) return 'monthly';
    if ($head !== docRunningPrefix($cfg, '2001-01-15')) return 'yearly';
    return 'never';
}

/** SQL ส่วนที่ใช้ร่วมกันในการหาเลขสูงสุดของรอบ — คืน [sql, params] */
function docRunningMaxQuery(array $cfg, string $date, bool $forUpdate): array
{
    $table  = docRunningSafeIdent((string) $cfg['SourceTable']);
    $column = docRunningSafeIdent((string) $cfg['SourceColumn']);
    $head   = docRunningPrefix($cfg, $date);

    // escape %, _ และ \ ในส่วนหน้า เพื่อไม่ให้กลายเป็น wildcard ของ LIKE (MySQL ใช้ \ เป็น escape char ตามค่าเริ่มต้น)
    $like = addcslashes($head, '%_\\') . '%';

    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(`{$column}`, :hlen + 1) AS UNSIGNED)), 0)
            FROM `{$table}` WHERE `{$column}` LIKE :like";
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    return [$sql, [':hlen' => strlen($head), ':like' => $like]];
}

/** เลขสูงสุดที่ใช้ไปแล้วในรอบนั้น (0 = ยังไม่มี) — ใช้แสดงผล ไม่ล็อกแถว */
function docRunningLastSeq(PDO $pdo, array $cfg, string $date): int
{
    [$sql, $params] = docRunningMaxQuery($cfg, $date, false);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

/**
 * เลขล่าสุดที่ผู้ดูแลกำหนดเองไว้สำหรับรอบนี้ (null = ไม่ได้ตั้งไว้ หรือค่าที่ตั้งไว้เป็นของรอบก่อน)
 * เก็บคู่กับส่วนหน้าของเลข ค่าที่ตั้งไว้จึงหมดอายุเองเมื่อขึ้นรอบใหม่
 */
function docRunningManualSeq(array $cfg, string $date): ?int
{
    if (($cfg['ManualLastSeq'] ?? null) === null || ($cfg['ManualPeriod'] ?? null) === null) {
        return null;
    }

    return $cfg['ManualPeriod'] === docRunningPrefix($cfg, $date) ? (int) $cfg['ManualLastSeq'] : null;
}

/**
 * เลขว่างตัวแรกตั้งแต่ $from ขึ้นไปในรอบนั้น
 * ใช้เมื่อผู้ดูแลตั้งเลขล่าสุดย้อนหลัง เพื่อไม่ให้ออกเลขที่ถูกใช้ไปแล้วซ้ำ
 */
function docRunningFirstFreeSeq(PDO $pdo, array $cfg, string $date, int $from): int
{
    $table  = docRunningSafeIdent((string) $cfg['SourceTable']);
    $column = docRunningSafeIdent((string) $cfg['SourceColumn']);
    $head   = docRunningPrefix($cfg, $date);
    $like   = addcslashes($head, '%_\\') . '%';

    $stmt = $pdo->prepare(
        "SELECT DISTINCT CAST(SUBSTRING(`{$column}`, :hlen + 1) AS UNSIGNED) AS s
         FROM `{$table}`
         WHERE `{$column}` LIKE :like AND CAST(SUBSTRING(`{$column}`, :hlen2 + 1) AS UNSIGNED) >= :from
         ORDER BY s"
    );
    $stmt->execute([':hlen' => strlen($head), ':hlen2' => strlen($head), ':like' => $like, ':from' => $from]);

    // เลขที่ใช้ไปแล้วเรียงจากน้อยไปมาก — ไล่ขึ้นทีละขั้นจนเจอช่องว่างแรก
    $candidate = $from;
    while (($used = $stmt->fetchColumn()) !== false) {
        $used = (int) $used;
        if ($used > $candidate) {
            break;
        }
        if ($used === $candidate) {
            $candidate++;
        }
    }

    return $candidate;
}

/**
 * คำนวณเลขลำดับถัดไปของรอบนั้น
 * $lock = true ให้ล็อกช่วงเลขไว้จนจบ transaction (ใช้ตอนออกเลขจริง)
 */
function docRunningResolveNextSeq(PDO $pdo, array $cfg, string $date, bool $lock): int
{
    [$sql, $params] = docRunningMaxQuery($cfg, $date, $lock);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $max = (int) $stmt->fetchColumn();

    $start  = max(1, (int) $cfg['StartSeq']);
    $manual = docRunningManualSeq($cfg, $date);

    // ผู้ดูแลกำหนดเลขล่าสุดไว้เอง → นับต่อจากค่านั้น
    if ($manual !== null) {
        return docRunningFirstFreeSeq($pdo, $cfg, $date, max($manual + 1, $start));
    }

    // เติมช่องว่าง: เลขที่ว่างจากการลบเอกสารจะถูกนำกลับมาใช้ก่อนออกเลขใหม่ท้ายแถว
    if ((int) ($cfg['FillGap'] ?? 1) === 1) {
        return docRunningFirstFreeSeq($pdo, $cfg, $date, $start);
    }

    return max($max + 1, $start);
}

/**
 * ออกเลขเอกสารถัดไป — ต้องเรียกภายใน transaction ที่จะ INSERT ต่อทันที
 * คืน ['seq' => int, 'docid' => string, 'config' => array]
 */
function nextDocRunning(PDO $pdo, string $docType, ?string $date = null): array
{
    $cfg  = docRunningConfig($pdo, $docType);
    $date = $date ?: date('Y-m-d');

    $seq   = docRunningResolveNextSeq($pdo, $cfg, $date, true);
    $docID = docRunningFormat($cfg, $seq, $date);

    // กันเลขยาวเกินคอลัมน์ปลายทางแล้วถูก MySQL ตัดทิ้งจนเลขเพี้ยน
    $limit = docRunningColumnLength($pdo, (string) $cfg['SourceTable'], (string) $cfg['SourceColumn']);
    if ($limit > 0 && strlen($docID) > $limit) {
        throw new RuntimeException(
            'เลขที่เอกสาร "' . $docID . '" ยาว ' . strlen($docID) . ' ตัวอักษร เกินความยาวคอลัมน์ '
            . $cfg['SourceTable'] . '.' . $cfg['SourceColumn'] . ' (' . $limit . ') กรุณาปรับรูปแบบเลข'
        );
    }

    return ['seq' => $seq, 'docid' => $docID, 'config' => $cfg];
}

/**
 * ข้อมูลเจ้าของเอกสารเลขหนึ่ง (ชื่อ + เลขบัตรประชาชน) — คืน null เมื่อตารางต้นทาง
 * ไม่ได้ผูกกับทะเบียนผู้ว่างงาน (ไม่มีคอลัมน์ EmpID) หรือหาไม่พบ
 */
function docRunningDocOwner(PDO $pdo, array $cfg, string $docID): ?array
{
    $table  = docRunningSafeIdent((string) $cfg['SourceTable']);
    $column = docRunningSafeIdent((string) $cfg['SourceColumn']);

    if (!docRunningColumnExists($pdo, $table, 'EmpID')) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT s.EmpID, t.Title, e.EmpName
         FROM `{$table}` s
         LEFT JOIN employee e ON s.EmpID = e.EmpID
         LEFT JOIN titles   t ON e.Titles = t.TitleNo
         WHERE s.`{$column}` = :doc
         ORDER BY s.DocNo DESC LIMIT 1"
    );
    $stmt->execute([':doc' => $docID]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/** ความยาวสูงสุดของคอลัมน์ปลายทาง (0 = ไม่ใช่ชนิดที่จำกัดความยาว) */
function docRunningColumnLength(PDO $pdo, string $table, string $column): int
{
    $stmt = $pdo->prepare(
        "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c"
    );
    $stmt->execute([':t' => $table, ':c' => $column]);

    return (int) $stmt->fetchColumn();
}

/** ชื่อตาราง/คอลัมน์ต้องเป็นตัวอักษร ตัวเลข หรือ _ เท่านั้น เพราะต้องต่อลงใน SQL ตรงๆ (bind ไม่ได้) */
function docRunningSafeIdent(string $ident): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $ident)) {
        throw new InvalidArgumentException('ชื่อตาราง/คอลัมน์ไม่ถูกต้อง: ' . $ident);
    }
    return $ident;
}

/** ตารางและคอลัมน์นั้นมีอยู่จริงในฐานข้อมูลหรือไม่ */
function docRunningColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c"
    );
    $stmt->execute([':t' => $table, ':c' => $column]);

    return (bool) $stmt->fetchColumn();
}

/**
 * ตรวจความถูกต้องของข้อมูลที่กรอกในหน้าจัดการ — คืน array ข้อความผิดพลาด (ว่าง = ผ่าน)
 * $input ใช้คีย์เดียวกับคอลัมน์ในตาราง doc_running
 */
function docRunningValidate(PDO $pdo, array $input): array
{
    $errors = [];

    $docType = trim((string) ($input['DocType'] ?? ''));
    if ($docType === '') {
        $errors[] = 'กรุณากรอกรหัสประเภทเอกสาร';
    } elseif (!preg_match('/^[A-Za-z0-9_]{1,30}$/', $docType)) {
        $errors[] = 'รหัสประเภทเอกสารใช้ได้เฉพาะ a-z A-Z 0-9 และ _ ไม่เกิน 30 ตัว';
    }

    if (trim((string) ($input['DocName'] ?? '')) === '') {
        $errors[] = 'กรุณากรอกชื่อเอกสาร';
    }

    $table  = trim((string) ($input['SourceTable'] ?? ''));
    $column = trim((string) ($input['SourceColumn'] ?? ''));
    if ($table === '' || $column === '') {
        $errors[] = 'กรุณาระบุตารางและคอลัมน์ที่เก็บเลขเอกสาร';
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        $errors[] = 'ชื่อตาราง/คอลัมน์ใช้ได้เฉพาะ a-z A-Z 0-9 และ _';
    } elseif (!docRunningColumnExists($pdo, $table, $column)) {
        $errors[] = 'ไม่พบคอลัมน์ ' . $table . '.' . $column . ' ในฐานข้อมูล';
    }

    $format = trim((string) ($input['Format'] ?? ''));
    if ($format === '') {
        $errors[] = 'กรุณากรอกรูปแบบเลข';
    } elseif (!str_ends_with($format, '{SEQ}')) {
        $errors[] = 'รูปแบบเลขต้องลงท้ายด้วย {SEQ} เพราะระบบอ่านเลขลำดับจากส่วนท้าย';
    } elseif (substr_count($format, '{SEQ}') > 1) {
        $errors[] = 'ใช้ {SEQ} ได้เพียงครั้งเดียว';
    } else {
        // token แปลกปลอมที่ระบบไม่รู้จักจะถูกใส่ลงเลขเอกสารตรงๆ โดยไม่ถูกแทนค่า
        preg_match_all('/\{[A-Za-z0-9_]*\}/', $format, $found);
        $unknown = array_diff(array_unique($found[0]), array_keys(docRunningTokens()));
        if ($unknown) {
            $errors[] = 'ไม่รู้จัก token: ' . implode(', ', $unknown);
        }
    }

    $cycle = (string) ($input['ResetCycle'] ?? '');
    if (!in_array($cycle, ['daily', 'monthly', 'yearly', 'never'], true)) {
        $errors[] = 'รอบการรีเซ็ตไม่ถูกต้อง';
    }

    $padding = (int) ($input['Padding'] ?? 0);
    if ($padding < 0 || $padding > 10) {
        $errors[] = 'จำนวนหลักของเลขลำดับต้องอยู่ระหว่าง 0-10';
    }

    $start = (int) ($input['StartSeq'] ?? 1);
    if ($start < 1) {
        $errors[] = 'เลขเริ่มต้นต้องมากกว่า 0';
    }

    // ตรวจความสอดคล้องของรูปแบบกับรอบรีเซ็ต + ความยาวเลข เมื่อข้อมูลพื้นฐานผ่านแล้วเท่านั้น
    if (!$errors) {
        $cfg = [
            'Prefix'  => (string) ($input['Prefix'] ?? ''),
            'Format'  => $format,
            'Padding' => $padding,
        ];

        $detected = docRunningDetectCycle($cfg);
        if ($detected !== $cycle) {
            $errors[] = 'รูปแบบนี้ทำให้เลขรีเซ็ต' . docRunningCycleName($detected)
                . ' แต่เลือกรอบเป็น' . docRunningCycleName($cycle)
                . ' — ต้องมี token วันที่ให้ครบตามรอบที่เลือก';
        }

        $sample = docRunningFormat($cfg, max(1, $start), date('Y-m-d'));
        $limit  = docRunningColumnLength($pdo, $table, $column);
        if ($limit > 0 && strlen($sample) > $limit) {
            $errors[] = 'ตัวอย่างเลข "' . $sample . '" ยาว ' . strlen($sample)
                . ' ตัวอักษร เกินความยาวคอลัมน์ ' . $table . '.' . $column . ' (' . $limit . ')';
        }
    }

    return $errors;
}
