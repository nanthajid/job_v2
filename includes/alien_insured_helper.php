<?php
// ตัวช่วยสำหรับแบบขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว (alien_insured_doc / alien_insured_person)

/** ค่าเริ่มต้นของหัวเอกสารตามแบบฟอร์มของสำนักงาน */
const ALIEN_INSURED_DEFAULT_OFFICE   = 'สำนักงานจัดหางานกรุงเทพมหานครเขตพื้นที่ 2';
const ALIEN_INSURED_DEFAULT_RECEIVER = 'สำนักงานประกันสังคมเขตพื้นที่ 7';

/** ผู้ส่งมอบเอกสารประจำตามแบบฟอร์ม — ตั้งเป็นค่าเริ่มต้นให้ทุกคนที่เข้ามากรอก (เปลี่ยนในฟอร์มได้) */
const ALIEN_INSURED_DEFAULT_SENDER     = 'กิติยากรณ์ วงศ์ทอง';
const ALIEN_INSURED_DEFAULT_SENDER_POS = 'เจ้าพนักงานแรงงาน';

/**
 * ตารางเทียบ "ชื่อ-สกุลในตาราง staff" => "คำนำหน้า + ชื่อ-สกุล"
 * ใช้เติมคำนำหน้าให้เอกสารที่บันทึกไว้ก่อนระบบจะเก็บคำนำหน้ามาด้วย
 * ข้ามระเบียนที่พิมพ์คำนำหน้าไว้ในช่องชื่ออยู่แล้ว จะได้ไม่ซ้ำซ้อน
 */
function alienInsuredTitleMap(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT s.StName, t.Title FROM staff s LEFT JOIN titles t ON t.TitleNo = s.TitleNo"
    )->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) {
        $name  = trim((string)$r['StName']);
        $title = trim((string)($r['Title'] ?? ''));
        if ($name === '' || $title === '' || mb_strpos($name, $title) === 0) continue;
        $map[$name] = $title . $name;
    }
    return $map;
}

/** ชื่อที่ยังไม่มีคำนำหน้าให้เติมให้ ส่วนชื่อที่มีอยู่แล้ว/ไม่รู้จักคืนค่าเดิม */
function alienInsuredWithTitle(?string $name, array $titleMap): string
{
    $name = trim((string)$name);
    return $titleMap[$name] ?? $name;
}

/**
 * ตัวเลือกผู้ส่งมอบเอกสาร: ชื่อพร้อมคำนำหน้า (DisplayName) และตำแหน่งของแต่ละคน
 * ใช้ร่วมกันระหว่างหน้ากรอกฟอร์มและหน้าพิมพ์รายงาน จะได้ไม่หลุดจากกัน
 */
function alienInsuredStaffOptions(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT s.StID, s.StName, t.Title, p.StPostName
         FROM staff s
         LEFT JOIN titles t ON t.TitleNo = s.TitleNo
         LEFT JOIN position p ON p.StPost = s.StPost
         ORDER BY s.StName"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $name  = trim((string)$r['StName']);
        $title = trim((string)($r['Title'] ?? ''));
        // บางระเบียนพิมพ์คำนำหน้าไว้ในช่องชื่อแล้ว จึงต้องกันไม่ให้ซ้ำซ้อน
        $r['DisplayName'] = ($title === '' || mb_strpos($name, $title) === 0) ? $name : $title . $name;
    }
    unset($r);

    // ค่าเริ่มต้นต้องมีให้เลือกเสมอ แม้ระเบียนจะถูกลบไปแล้ว
    if (!in_array(ALIEN_INSURED_DEFAULT_SENDER, array_column($rows, 'StName'), true)) {
        array_unshift($rows, [
            'StID'        => '',
            'StName'      => ALIEN_INSURED_DEFAULT_SENDER,
            'DisplayName' => ALIEN_INSURED_DEFAULT_SENDER,
            'StPostName'  => ALIEN_INSURED_DEFAULT_SENDER_POS,
        ]);
    }
    return $rows;
}

/** ชื่อตำแหน่งทั้งหมดแบบไม่ซ้ำ (ตาราง position มีชื่อเดียวกันหลายรหัสตามสังกัด) */
function alienInsuredPositionOptions(PDO $pdo): array
{
    $names = $pdo->query("SELECT DISTINCT StPostName FROM position WHERE StPostName <> '' ORDER BY StPostName")
                 ->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array(ALIEN_INSURED_DEFAULT_SENDER_POS, $names, true)) {
        array_unshift($names, ALIEN_INSURED_DEFAULT_SENDER_POS);
    }
    return $names;
}

/**
 * ตรวจสอบและจัดรูปแบบรายชื่อผู้ประกันตนจาก $_POST['persons']
 * @throws InvalidArgumentException เมื่อข้อมูลไม่ครบถ้วน
 */
function alienInsuredNormalizePersons($persons): array
{
    if (!is_array($persons) || count($persons) === 0) {
        throw new InvalidArgumentException('กรุณาเพิ่มรายชื่อผู้ประกันตนอย่างน้อย 1 รายการ');
    }

    $result = [];
    $seq    = 0;
    foreach ($persons as $p) {
        if (!is_array($p)) {
            continue;
        }
        $fullName = trim((string)($p['FullName'] ?? ''));
        $cardNo   = preg_replace('/\s+/', '', (string)($p['SsoCardNo'] ?? ''));

        // แถวว่างทั้งแถว → ข้ามไป (ผู้ใช้กดเพิ่มแถวแล้วไม่ได้กรอก)
        if ($fullName === '' && $cardNo === '') {
            continue;
        }
        if ($fullName === '') {
            throw new InvalidArgumentException('กรุณากรอกชื่อ-สกุลผู้ประกันตนให้ครบทุกรายการ');
        }
        if ($cardNo !== '' && !preg_match('/^\d{10,20}$/', $cardNo)) {
            throw new InvalidArgumentException('เลขที่บัตร (ปกส.) ของ "' . $fullName . '" ต้องเป็นตัวเลข 10-20 หลัก');
        }

        $result[] = [
            'SeqNo'        => ++$seq,
            'TitleName'    => trim((string)($p['TitleName'] ?? '')) ?: null,
            'FullName'     => $fullName,
            'SsoCardNo'    => $cardNo ?: null,
            'IsTerminated' => !empty($p['IsTerminated']) ? 1 : 0,
            'IsResigned'   => !empty($p['IsResigned']) ? 1 : 0,
            'Remark'       => trim((string)($p['Remark'] ?? '')) ?: null,
        ];
    }

    if (count($result) === 0) {
        throw new InvalidArgumentException('กรุณาเพิ่มรายชื่อผู้ประกันตนอย่างน้อย 1 รายการ');
    }

    return $result;
}

/**
 * บันทึกรายชื่อผู้ประกันตนของเอกสารหนึ่งฉบับ (ต้องอยู่ใน transaction)
 * $table รับค่าจากค่าคงที่ของแต่ละโมดูลเท่านั้น (ขึ้นทะเบียน / รายงานตัว) ไม่รับจากผู้ใช้
 */
function alienInsuredInsertPersons(PDO $pdo, int $docId, array $persons, string $table = 'alien_insured_person'): void
{
    $stmt = $pdo->prepare('INSERT INTO ' . $table . '
        (DocID, SeqNo, TitleName, FullName, SsoCardNo, IsTerminated, IsResigned, Remark)
        VALUES (:DocID,:SeqNo,:TitleName,:FullName,:SsoCardNo,:IsTerminated,:IsResigned,:Remark)');

    foreach ($persons as $p) {
        $stmt->execute([
            'DocID'        => $docId,
            'SeqNo'        => $p['SeqNo'],
            'TitleName'    => $p['TitleName'],
            'FullName'     => $p['FullName'],
            'SsoCardNo'    => $p['SsoCardNo'],
            'IsTerminated' => $p['IsTerminated'],
            'IsResigned'   => $p['IsResigned'],
            'Remark'       => $p['Remark'],
        ]);
    }
}

/** แปลงวันที่จากฟอร์มเป็นรูปแบบ Y-m-d (คืน null เมื่อรูปแบบไม่ถูกต้อง) */
function alienInsuredParseDate(?string $value): ?string
{
    $value = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}
