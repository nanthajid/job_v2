-- เพิ่มความสามารถ "แก้ไขเลขล่าสุด" ให้ผู้ดูแลระบบกำหนดเองได้เป็นรายรอบ
-- ManualPeriod เก็บส่วนหน้าของเลขในรอบที่ตั้งค่าไว้ (เช่น "2026-07-27/")
-- เมื่อขึ้นรอบใหม่ ส่วนหน้าจะไม่ตรงกัน ค่าที่ตั้งไว้จึงหมดอายุเองโดยอัตโนมัติ
ALTER TABLE doc_running
  ADD COLUMN ManualLastSeq   INT UNSIGNED NULL DEFAULT NULL COMMENT 'เลขล่าสุดที่ผู้ดูแลกำหนดเอง',
  ADD COLUMN ManualPeriod    VARCHAR(40)  NULL DEFAULT NULL COMMENT 'ส่วนหน้าของเลขในรอบที่ค่านี้ใช้ได้',
  ADD COLUMN ManualUpdatedAt DATETIME     NULL DEFAULT NULL,
  ADD COLUMN ManualBy        VARCHAR(15)  NULL DEFAULT NULL COMMENT 'StID ผู้ตั้งค่า';
