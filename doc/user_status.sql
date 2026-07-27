-- =====================================================================
-- Module: จัดการสถานะผู้ใช้งาน (User Status)
-- ตาราง lookup สถานะ + คอลัมน์ StatusNo ในตาราง users
-- ออกแบบตาม pattern lookup เดิม (titles/sex/position) แบบ soft reference
-- (users เป็น MyISAM จึงไม่ผูก FK constraint เช่นเดียวกับตารางเดิม)
-- =====================================================================

-- 1) ตาราง lookup สถานะผู้ใช้งาน
CREATE TABLE IF NOT EXISTS `user_status` (
  `StatusNo`   TINYINT      NOT NULL,
  `StatusName` VARCHAR(50)  NOT NULL,
  `BadgeClass` VARCHAR(30)  NOT NULL DEFAULT 'badge-secondary',
  PRIMARY KEY (`StatusNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- 2) ข้อมูลสถานะเริ่มต้น
INSERT INTO `user_status` (`StatusNo`, `StatusName`, `BadgeClass`) VALUES
  (1, 'เปิดใช้งาน', 'badge-success'),
  (0, 'ปิดใช้งาน',  'badge-secondary')
ON DUPLICATE KEY UPDATE
  `StatusName` = VALUES(`StatusName`),
  `BadgeClass` = VALUES(`BadgeClass`);

-- 3) เพิ่มคอลัมน์สถานะในตาราง users (ค่าเริ่มต้น = เปิดใช้งาน)
--    หมายเหตุ: หากคอลัมน์มีอยู่แล้ว บรรทัดนี้จะแจ้ง error ให้ข้ามได้
ALTER TABLE `users` ADD COLUMN `StatusNo` TINYINT NOT NULL DEFAULT 1 AFTER `level`;

-- 4) บัญชีที่มีอยู่เดิมทั้งหมดตั้งเป็นเปิดใช้งาน
UPDATE `users` SET `StatusNo` = 1;
