<?php

namespace RebelCode\Migrations\TestStub;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Store\DbPdoDriver;
use ByJG\Util\Uri;
use PDO;

/**
 * An extension of ByJG's PDO driver that can be constructed without a URI.
 *
 * Used in conjunction with SQLite in-memory databases, that are accessed via a DBN that can't be parsed correctly by
 * the URI class.
 *
 * @since [*next-version*]
 */
class PdoSqliteDriverStub extends DbPdoDriver implements DbDriverInterface
{
    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param PDO    $pdo    The PDO instance.
     * @param string $dbName Thee name of the database to connect to.
     */
    public function __construct(PDO $pdo, $dbName)
    {
        $this->instance = $pdo;
        $this->connectionUri = new Uri('mysql://root@memory/'.$dbName);
    }
}
