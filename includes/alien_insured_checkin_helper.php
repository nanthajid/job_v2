<?php
// ตัวช่วยสำหรับแบบรายงานตัวผู้ประกันตนแรงงานต่างด้าว
// ใช้ฟอร์ม/ตัวช่วยชุดเดียวกับแบบขึ้นทะเบียน ต่างกันแค่ตารางที่เก็บและชื่อเอกสาร
require_once __DIR__ . '/alien_insured_helper.php';

const ALIEN_CHECKIN_DOC_TABLE    = 'alien_insured_checkin_doc';
const ALIEN_CHECKIN_PERSON_TABLE = 'alien_insured_checkin_person';
const ALIEN_CHECKIN_DOC_TITLE    = 'แบบรายงานตัวผู้ประกันตนแรงงานต่างด้าว';
