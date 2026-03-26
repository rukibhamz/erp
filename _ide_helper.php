<?php
/**
 * IDE Helper for CodeIgniter 3
 * This file is purely for IDE autocompletion and warning suppression.
 * It is not included or executed anywhere in the actual application.
 */

if (!defined('FCPATH')) define('FCPATH', __DIR__ . '/');

if (!class_exists('CI_Model')) {
    class CI_Model {}
}

if (!class_exists('CI_Migration')) {
    class CI_Migration extends CI_Model {
        /** @var CI_DB_query_builder */
        public $db;
        /** @var CI_DB_forge */
        public $dbforge;
    }
}

if (!class_exists('Migration')) {
    class Migration extends CI_Migration {}
}

/**
 * @property CI_Controller $this
 */
