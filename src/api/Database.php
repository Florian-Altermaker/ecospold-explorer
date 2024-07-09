<?php

namespace EcospoldExplorer;

/**
 * Database
 * This class manages MySQL database actions
 */
class Database
{

    // PDO connection (use method ::connect() to initiate)
    private static \PDO $PDO;
    private static \PDOStatement $statement;
    private static ?string $query;
    private static array $parameters = [];

    /**
     * This method initiate a connection to a MySQL/MariaDB database using PDO.
     * The PDO object is stored in static property $PDO
     *
     * @param string $dsn as a properly formatted DSN (see https://en.wikipedia.org/wiki/Data_source_name)
     * @param string $charset as the charset used to open the connection ('utf8' by default)
     *
     * @return void
     */
    public static function connect(string $dsn, $charset = 'utf8'): void
    {

        // Parse DSN declared in environment variable DB_CONNECTION
        $dsn = parse_url($dsn);

        // Check if DSN is complete
        if (!isset($dsn['user'], $dsn['pass'], $dsn['path'], $dsn['host'], $dsn['port'])) {
            throw new \Exception('Declared DSN is not a correctly formatted.');
        }

        // Initiate PDO connection
        self::$PDO = new \PDO(
            'mysql:host='. $dsn['host'].
            ';charset='.$charset.
            ';port='.$dsn['port'].';dbname='.ltrim($dsn['path'], '/'),
            $dsn['user'],
            $dsn['pass']
        );

    }

    /**
     * This method allows to execute a prepared query
     *
     * @param string $query as a correctly formatted query (e.g. "SELECT * FROM table WHERE id = ?")
     * @param array $parameters as the list of prepared parameters corresponding to the passed query string (e.g. ["id" => 1])
     *
     * @return static
     */
    public static function request(string $query, array $parameters = []): string
    {

        // Check if parameters are passed when a WHERE clause is declared (to prevent injection)
        if (preg_match('# WHERE #i', $query) && count($parameters) === 0) {
            throw new \Exception('Parameters must be used for a query containing a WHERE clause');
        }

        // Prepare and execute query
        static::$statement = static::$PDO->prepare($query);

        if (!static::$statement->execute($parameters)) {
            throw new \Exception('Database query execution returned false');
        }

        // Keep request information to allow a direct fetch
        static::$query = $query;
        static::$parameters = $parameters;

        return static::class;

    }

    /**
     * This method prepares a fetch statement
     * In case it is called after a SELECT or DELETE query, it only returns the current static statement.
     * In case it is called after an UPDATE or INSERT query, it returns a statement selecting the affected rows.
     *
     * @return \PDOStatement
     */
    private static function fetchPrepare(): \PDOStatement
    {

        // Prepare an additional SELECT query in case of INSERT or UPDATE
        if (preg_match('/^(?:INSERT\s+INTO|UPDATE)\s+(?:`([^`]+)`|\'([^\']+)\'|"([^"]+)"|([a-zA-Z0-9_]+))/i', static::$query, $matches)) {

            // Get target table
            $matches = array_filter($matches);
            $table = end($matches);

            // Prepare the query in case a table was found
            if ($table) {

                // Define a fetch WHERE clause to execute if the query is an INSERT
                if (preg_match('/^INSERT/i', static::$query)) {
                    $id = (int) static::$PDO->lastInsertId();
                    $where = $id > 0 ? ' id = '.$id : null; // The primary key must be named 'id'
                    $parameters = [];

                // Define a fetch WHERE clause to execute if the query is an UPDATE whith a WHERE clause
                } else if (preg_match('/^UPDATE/i', static::$query) && preg_match('/ WHERE /i', static::$query)) {
                    $fragments = preg_split('/WHERE/i', static::$query);
                    $where = end($fragments);
                    $aliases = substr_count($where, '?');
                    $parameters = array_slice(static::$parameters, -$aliases);
                }

                // Prepare and execute the additional request
                if (isset($where)) {

                    static::$statement = static::$PDO->prepare("SELECT * FROM $table WHERE $where");

                    if (!static::$statement->execute($parameters)) {
                        throw new \Exception('Database query execution returned false');
                    }

                }

            }

        }

        static::$query = null;

        return static::$statement;

    }

    /**
     * This method allows to execute a prepared query
     *
     * @param int $fetchMode as flag(s) to specify the expected query output format
     *
     * @return array|false as an array with requested data or false in case of fail or empty result
     */
    public static function fetch(int $fetchMode = \PDO::FETCH_ASSOC): array|false
    {
        return !is_null(static::$query) ? static::fetchPrepare()->fetch($fetchMode) : [];
    }

    /**
     * This method allows to execute a prepared query
     *
     * @param int $fetchMode as flag(s) to specify the expected query output format
     *
     * @return array|false as an array with requested data or false in case of fail or empty result
     */
    public static function fetchAll(int $fetchMode = \PDO::FETCH_ASSOC): array|false
    {
        return !is_null(static::$query) ? static::fetchPrepare()->fetchAll($fetchMode) : [];
    }

}