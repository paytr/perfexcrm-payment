<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'paytr_installments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "paytr_installments` (`group_id` int NOT NULL, `installment_count` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'paytr_installments` ADD PRIMARY KEY (`group_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'paytr_installments` ADD UNIQUE KEY `group_id` (`group_id`);');

}

