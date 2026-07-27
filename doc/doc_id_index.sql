-- index บนคอลัมน์เลขเอกสาร เพื่อให้การหาเลขล่าสุดของรอบเป็น range scan
-- แทนการอ่านทั้งตาราง และให้ SELECT ... FOR UPDATE ล็อกเฉพาะช่วงเลขของวันนั้น
ALTER TABLE register   ADD INDEX idx_register_docid (DocID);
ALTER TABLE selft_rep  ADD INDEX idx_selft_rep_docid (DocID);
