<?php
/*
 * CTF LAB - Local File Inclusion (LFI) deliberadamente vulnerable
 * NO usar este código en un entorno real. Es material didáctico para CTF.
 */

$page = isset($_GET['page']) ? $_GET['page'] : 'welcome.php';

// Vulnerabilidad: no hay whitelist ni sanitización del parámetro "page"
include($page);
