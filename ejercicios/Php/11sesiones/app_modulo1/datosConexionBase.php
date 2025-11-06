<?php
/*
 * datosConexionBase.php
 *
 * Este archivo actúa como proxy hacia el archivo de conexión
 * ubicado en la raíz del proyecto. Se incluye para mantener la
 * compatibilidad con código que incluya app_modulo1/datosConexionBase.php
 * como en la aplicación original. Todas las funciones (conectar,
 * checkSession y checkSessionApp) se importan desde el archivo superior.
 */

require_once __DIR__ . '/../datosConexionBase.php';
?>