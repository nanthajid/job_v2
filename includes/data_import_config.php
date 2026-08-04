<?php
// รายชื่อตารางที่รองรับการนำเข้าข้อมูลจากฐานข้อมูลต้นทาง (IMPORT_SOURCE_DB)
// pk = คอลัมน์ที่ใช้ตรวจสอบข้อมูลซ้ำ, columns = คอลัมน์ทั้งหมดที่จะคัดลอก (ต้องรวม pk ด้วย)
// source_expr = (ไม่บังคับ) นิพจน์ SQL ฝั่งต้นทางสำหรับคอลัมน์ที่โครงสร้างสองฝั่งไม่ตรงกัน — อ้างตารางต้นทางด้วย alias s
return [
    'employee' => [
        'label'   => 'ข้อมูลพนักงาน (employee)',
        'table'   => 'employee',
        'pk'      => 'EmpID',
        'columns' => ['EmpID', 'EmpName', 'Titles', 'SexNo', 'image', 'KNo', 'Phone', 'lineID', 'Address', 'SDate'],
    ],
    'register' => [
        'label'   => 'ขึ้นทะเบียนว่างงาน (register)',
        'table'   => 'register',
        'pk'      => 'DocNo',
        'columns' => ['DocNo', 'DocID', 'RDate', 'SDate', 'QNo', 'EqNo', 'PotNo', 'KNo', 'SexNo', 'EmpID', 'StID'],
    ],
    'selft_rep' => [
        'label'   => 'รายงานตัวว่างงาน (selft_rep)',
        'table'   => 'selft_rep',
        'pk'      => 'DocNo',
        'columns' => ['DocNo', 'DocID', 'RDate', 'SDate', 'QNo', 'EqNo', 'JNo', 'PotNo', 'KNo', 'SexNo', 'EmpID', 'StID'],
    ],
    // alien_insured_person.DocID อ้างถึง alien_insured_doc.DocID (fk_alien_person_doc)
    // จึงต้องนำเข้าตัวเอกสารก่อนรายชื่อเสมอ — เรียงลำดับการ์ดบนหน้าจอไว้ตามนี้แล้ว
    'alien_insured_doc' => [
        'label'   => 'ขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว (alien_insured_doc)',
        'table'   => 'alien_insured_doc',
        'pk'      => 'DocID',
        // ไม่คัดลอก CreatedAt/UpdatedAt — ต้นทางเป็น NULL ทั้งหมด แต่ปลายทางเป็น NOT NULL
        // ปล่อยให้ DB เติมเองจาก DEFAULT CURRENT_TIMESTAMP / ON UPDATE CURRENT_TIMESTAMP
        'columns' => ['DocID', 'DocDate', 'OfficeName', 'SenderName', 'SenderPosition', 'ReceiverOffice', 'ReceiverName', 'Remark', 'StID'],
        // ต้นทางเก็บตำแหน่งผู้ส่งมอบเป็นรหัส position.StPost (varchar '01'-'21') จึงต้องเติม 0 นำหน้าก่อนเทียบ
        'source_expr' => [
            'SenderPosition' => "(SELECT p.StPostName FROM position p WHERE p.StPost = LPAD(s.SenderPosition, 2, '0'))",
        ],
    ],
    'alien_insured_person' => [
        'label'   => 'ผู้ประกันตนแรงงานต่างด้าว (alien_insured_person)',
        'table'   => 'alien_insured_person',
        'pk'      => 'PersonID',
        'columns' => ['PersonID', 'DocID', 'SeqNo', 'TitleName', 'FullName', 'SsoCardNo', 'IsTerminated', 'IsResigned', 'Remark'],
        // ต้นทางไม่มี SeqNo (ไล่ลำดับตาม PersonID ในเอกสารเดียวกัน)
        // และเก็บคำนำหน้าเป็นรหัส TitleNo จึงต้องแปลงเป็นข้อความจากตาราง titles
        'source_expr' => [
            'SeqNo'     => 'ROW_NUMBER() OVER (PARTITION BY s.DocID ORDER BY s.PersonID)',
            'TitleName' => '(SELECT t.Title FROM titles t WHERE t.TitleNo = s.TitleNo)',
        ],
    ],
];
