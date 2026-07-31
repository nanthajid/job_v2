<?php
// ตัวช่วยสำหรับแบบขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว (alien_insured_doc / alien_insured_person)

/** ค่าเริ่มต้นของหัวเอกสารตามแบบฟอร์มของสำนักงาน */
const ALIEN_INSURED_DEFAULT_OFFICE   = 'สำนักงานจัดหางานกรุงเทพมหานครเขตพื้นที่ 2';
const ALIEN_INSURED_DEFAULT_RECEIVER = 'สำนักงานประกันสังคมเขตพื้นที่ 7';

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

/** บันทึกรายชื่อผู้ประกันตนของเอกสารหนึ่งฉบับ (ต้องอยู่ใน transaction) */
function alienInsuredInsertPersons(PDO $pdo, int $docId, array $persons): void
{
    $stmt = $pdo->prepare('INSERT INTO alien_insured_person
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
