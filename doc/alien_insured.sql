-- แบบขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว (อ้างอิงแบบฟอร์มใน al_data.md)
-- แยกตารางออกจาก register และ employee โดยสิ้นเชิง

CREATE TABLE IF NOT EXISTS alien_insured_doc (
  DocID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  DocDate DATE NOT NULL,
  OfficeName VARCHAR(255) NOT NULL DEFAULT 'สำนักงานจัดหางานกรุงเทพมหานครเขตพื้นที่ 2',
  SenderName VARCHAR(150) NULL,
  SenderPosition VARCHAR(150) NULL,
  ReceiverOffice VARCHAR(255) NULL,
  ReceiverName VARCHAR(150) NULL,
  Remark TEXT NULL,
  StID VARCHAR(15) NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (DocID),
  KEY idx_alien_doc_date (DocDate),
  KEY idx_alien_doc_created (CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alien_insured_person (
  PersonID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  DocID INT UNSIGNED NOT NULL,
  SeqNo INT NOT NULL DEFAULT 1,          -- ลำดับในเอกสาร
  TitleName VARCHAR(30) NULL,            -- คำนำหน้า เช่น นาย / นาง / นางสาว
  FullName VARCHAR(255) NOT NULL,        -- ชื่อ-สกุลผู้ประกันตน
  SsoCardNo VARCHAR(20) NULL,            -- เลขที่บัตร (ปกส.)
  IsTerminated TINYINT(1) NOT NULL DEFAULT 0, -- เลิกจ้าง
  IsResigned TINYINT(1) NOT NULL DEFAULT 0,   -- ลาออก
  Remark VARCHAR(255) NULL,              -- หมายเหตุ
  PRIMARY KEY (PersonID),
  KEY idx_alien_person_doc (DocID),
  KEY idx_alien_person_sso (SsoCardNo),
  KEY idx_alien_person_name (FullName),
  CONSTRAINT fk_alien_person_doc FOREIGN KEY (DocID) REFERENCES alien_insured_doc (DocID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
