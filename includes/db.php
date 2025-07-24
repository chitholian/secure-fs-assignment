<?php
require_once __DIR__ . '/config.php';


/**
 * An extended singleton {@see PDO} class with custom functions for convenient query execution.
 */
class MyDB extends PDO
{
    private static MyDB|null $instance = null;

    /**
     * Make the construction private for making it singleton.
     */
    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        parent::__construct($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * @return MyDB Returns a singleton instance of {@see MyDB} class.
     */
    public static function getInstance(): MyDB
    {
        if (!self::$instance) {
            self::$instance = new MyDB();
        }
        return self::$instance;
    }

    /**
     * @param string $sql SQL query or statement to execute.
     * @param array $binds Bindings for the SQL statement or query.
     * @return false|PDOStatement Returns <b>FALSE</b> if prepare or execution fails. Otherwise, returns {@see PDOStatement}.
     */
    public function execute(string $sql, array $binds = []): false|PDOStatement
    {
        $stmt = $this->prepare($sql);
        if (!$stmt || !$stmt->execute($binds)) {
            return false;
        }
        return $stmt;
    }
}
