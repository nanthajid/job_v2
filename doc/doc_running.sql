-- ตารางกำหนดรูปแบบเลขรันเอกสาร (แยกออกจากตารางข้อมูลจริง)
-- เลขที่ออกจริงคิดจาก MAX ของเลขที่มีอยู่ในตารางต้นทาง + 1 เสมอ
-- ตารางนี้จึงเก็บ "รูปแบบ/กติกา" ไม่ใช่ตัวนับที่เดินหน้าอย่างเดียว
-- (charset utf8mb3 ให้ตรงกับตารางหลัก register/selft_rep/users เลี่ยง collation mix)
CREATE TABLE IF NOT EXISTS doc_running (
  RunID        INT AUTO_INCREMENT PRIMARY KEY,
  DocType      VARCHAR(30)  NOT NULL COMMENT 'คีย์ที่โค้ดใช้อ้างอิง เช่น register',
  DocName      VARCHAR(100) NOT NULL COMMENT 'ชื่อที่แสดงในหน้าจัดการ',
  SourceTable  VARCHAR(64)  NOT NULL COMMENT 'ตารางข้อมูลจริงที่ใช้หาเลขล่าสุด',
  SourceColumn VARCHAR(64)  NOT NULL DEFAULT 'DocID' COMMENT 'คอลัมน์ที่เก็บเลขเอกสาร',
  Prefix       VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'ข้อความนำหน้า ใช้ผ่าน token {PREFIX}',
  Format       VARCHAR(50)  NOT NULL DEFAULT '{DATE}/{SEQ}' COMMENT 'รูปแบบเลข ต้องลงท้ายด้วย {SEQ}',
  ResetCycle   ENUM('daily','monthly','yearly','never') NOT NULL DEFAULT 'daily' COMMENT 'รอบการรีเซ็ตเลข',
  Padding      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'จำนวนหลักของ {SEQ} (0 = ไม่เติมศูนย์)',
  StartSeq     INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'เลขเริ่มต้นของแต่ละรอบ',
  Active       TINYINT(1)   NOT NULL DEFAULT 1,
  Note         VARCHAR(255) NULL,
  UpdatedAt    DATETIME     NULL,
  UNIQUE KEY uq_doc_running_doctype (DocType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ค่าเริ่มต้นให้ตรงกับรูปแบบที่ใช้อยู่เดิม "YYYY-MM-DD/n"
INSERT INTO doc_running (DocType, DocName, SourceTable, SourceColumn, Prefix, Format, ResetCycle, Padding, StartSeq, Active, UpdatedAt)
VALUES
  ('register',  'ขึ้นทะเบียนว่างงาน', 'register',  'DocID', '', '{DATE}/{SEQ}', 'daily', 0, 1, 1, NOW()),
  ('selft_rep', 'รายงานตัวว่างงาน',   'selft_rep', 'DocID', '', '{DATE}/{SEQ}', 'daily', 0, 1, 1, NOW())
ON DUPLICATE KEY UPDATE DocName = VALUES(DocName);

-- index บนคอลัมน์เลขเอกสาร เพื่อให้การหาเลขล่าสุดเป็น range scan และล็อกเฉพาะช่วงของรอบนั้น
-- (รันครั้งเดียว หากมีอยู่แล้วจะ error ข้ามได้)
-- ALTER TABLE register  ADD INDEX idx_register_docid (DocID);
-- ALTER TABLE selft_rep ADD INDEX idx_selft_rep_docid (DocID);
