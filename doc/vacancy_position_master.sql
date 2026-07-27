CREATE TABLE IF NOT EXISTS vacancy_position_master (
  PositionMasterID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PositionName VARCHAR(255) NOT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (PositionMasterID),
  UNIQUE KEY uq_vacancy_position_master_name (PositionName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vacancy_notice_position becomes the N:M junction between vacancy_notice and
-- vacancy_position_master, carrying the per-notice attributes (Headcount, Wage, ...).
ALTER TABLE vacancy_notice_position
  ADD COLUMN PositionMasterID INT UNSIGNED NULL AFTER NoticeID;

-- Backfill: create one master row per distinct PositionName already in use.
INSERT INTO vacancy_position_master (PositionName)
SELECT DISTINCT PositionName FROM vacancy_notice_position
WHERE PositionName IS NOT NULL AND PositionName <> ''
ON DUPLICATE KEY UPDATE PositionName = VALUES(PositionName);

UPDATE vacancy_notice_position p
JOIN vacancy_position_master m ON m.PositionName = p.PositionName
SET p.PositionMasterID = m.PositionMasterID
WHERE p.PositionMasterID IS NULL;

ALTER TABLE vacancy_notice_position
  MODIFY PositionMasterID INT UNSIGNED NOT NULL,
  ADD CONSTRAINT fk_vnp_position_master FOREIGN KEY (PositionMasterID) REFERENCES vacancy_position_master (PositionMasterID),
  DROP COLUMN PositionName;
