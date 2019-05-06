<?php

namespace Luan\ORM;

use PDO;
use PDOException;

abstract Class Conexao {

    private static $instance;

    private static $externalDb;

    private static function connect($host, $user, $pass, $database) {

        try {

            $queryDb = "mysql:host={$host};dbname={$database};";

            $conn = new PDO($queryDb, $user, $pass, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {

            $text = utf8_encode($e->getMessage());

            die($queryDb);

        }

        return $conn;

    }

    public static function getConn() {

        if (!isset(self::$instance)) {

            self::$instance = self::connect(ENV["DB_HOST"], ENV["DB_USER"], ENV["DB_PASSWORD"], ENV["DB_DB"]);

        }
        return self::$instance;

    }

    public static function getExternalConn($file = "config.php") {

        if (!isset(self::$externalDb)) {

            define("EXTERNAL", include $file);

            self::$externalDb = self::connect(EXTERNAL['ip'], EXTERNAL['user'], EXTERNAL['senha'], EXTERNAL['db']);

        }

        return self::$externalDb;

    }

}

?>