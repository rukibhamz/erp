<?php
/**
 * Included at the top of every install/*.php entry point.
 * Allows fresh deployments to run the installer; blocks re-runs after install.
 */
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/application/');
}
require_once BASEPATH . 'helpers/install_helper.php';
install_require_not_installed();
