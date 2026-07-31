<?php
// รายชื่อตารางที่รองรับการนำเข้าข้อมูลจากฐานข้อมูลต้นทาง (IMPORT_SOURCE_DB)
// pk = คอลัมน์ที่ใช้ตรวจสอบข้อมูลซ้ำ, columns = คอลัมน์ทั้งหมดที่จะคัดลอก (ต้องรวม pk ด้วย)
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
        'columns' => ['DocID', 'DocDate', 'OfficeName', 'SenderName', 'SenderPosition', 'ReceiverOffice', 'ReceiverName', 'Remark', 'StID', 'CreatedAt', 'UpdatedAt'],
    ],
    'alien_insured_person' => [
        'label'   => 'ผู้ประกันตนแรงงานต่างด้าว (alien_insured_person)',
        'table'   => 'alien_insured_person',
        'pk'      => 'PersonID',
        'columns' => ['PersonID', 'DocID', 'SeqNo', 'TitleName', 'FullName', 'SsoCardNo', 'IsTerminated', 'IsResigned', 'Remark'],
    ],
];
