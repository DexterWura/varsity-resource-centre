<?php
declare(strict_types=1);

namespace Database;

use PDO;
use PDOException;

class DB {
	private static ?PDO $pdo = null;

	public static function pdo(): PDO {
		if (self::$pdo instanceof PDO) {
			return self::$pdo;
		}
		$host = getenv('DB_HOST') ?: '127.0.0.1';
		$name = getenv('DB_NAME') ?: 'varsity_resource_centre';
		$user = getenv('DB_USER') ?: 'root';
		$pass = getenv('DB_PASS') ?: '';
		$dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
		try {
			self::$pdo = new PDO($dsn, $user, $pass, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]);
			return self::$pdo;
		} catch (PDOException $e) {
			throw $e;
		}
	}
}
