<?php
class DB
{
    private static ?PDO $pdo = null;

    /**
     * Establishes a connection to the database.
     *
     * @throws PDOException If connection fails.
     * @return PDO
     */
    private static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Load configuration from environment variables
        $config = [
            'host'    => getenv('DB_HOST') ?: '127.0.0.1',
            'port'    => (int) (getenv('DB_PORT') ?: 3306),
            'dbname'  => getenv('DB_NAME') ?: 'db_name',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4'
        ];

        $password = getenv('DB_PASS');
        if ($password === false) {
            throw new RuntimeException('Database password is not set in environment variables.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        try {
            self::$pdo = new PDO($dsn, getenv('DB_USER') ?: 'db_user_login', $password);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new PDOException('Failed to connect to database: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    /**
     * Executes a SQL query with optional parameters.
     *
     * @param string $query SQL query.
     * @param array $params Parameters for prepared statement.
     * @return PDOStatement
     * @throws PDOException If query preparation or execution fails.
     */
    public static function query(string $query, array $params = []): PDOStatement
    {
        $statement = self::connect()->prepare($query);
        $statement->execute($params);
        return $statement;
    }

    /**
     * Executes a query and fetches one row.
     *
     * @param string $query SQL query.
     * @param array $params Parameters for prepared statement.
     * @return array|false Fetched row or false if no results.
     */
    public static function fetchOne(string $query, array $params = []): array|false
    {
        $statement = self::query($query, $params);
        $result = $statement->fetch();
        $statement->closeCursor();
        return $result;
    }

    /**
     * Executes a query and fetches all rows.
     *
     * @param string $query SQL query.
     * @param array $params Parameters for prepared statement.
     * @return array Fetched rows.
     */
    public static function fetchAll(string $query, array $params = []): array
    {
        $statement = self::query($query, $params);
        $result = $statement->fetchAll();
        $statement->closeCursor();
        return $result;
    }

    /**
     * Executes a query that does not return data (e.g., INSERT, UPDATE, DELETE).
     *
     * @param string $query SQL query.
     * @param array $params Parameters for prepared statement.
     * @return int Number of affected rows.
     * @throws PDOException If query preparation or execution fails.
     */
    public static function execute(string $query, array $params = []): int
    {
        $statement = self::query($query, $params);
        $rowCount = $statement->rowCount();
        $statement->closeCursor();
        return $rowCount;
    }
}
