<?php declare(strict_types=1);

use App\Database\Connection;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function testGetReturnsPdoInstance(): void
    {
        $pdo = Connection::get();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testGetReturnsSameInstance(): void
    {
        $pdo1 = Connection::get();
        $pdo2 = Connection::get();
        
        $this->assertSame($pdo1, $pdo2);
    }

    public function testConnectionCanExecuteQuery(): void
    {
        $pdo = Connection::get();
        $result = $pdo->query('SELECT 1 AS test');
        
        $this->assertNotFalse($result);
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(1, (int)$row['test']);
    }

    public function testConnectionHasCorrectErrorMode(): void
    {
        $pdo = Connection::get();
        $errorMode = $pdo->getAttribute(\PDO::ATTR_ERRMODE);
        
        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    public function testConnectionHasCorrectFetchMode(): void
    {
        $pdo = Connection::get();
        $fetchMode = $pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
        
        $this->assertSame(\PDO::FETCH_ASSOC, $fetchMode);
    }

    public function testConnectionCanAccessDatabase(): void
    {
        $pdo = Connection::get();
        
        // Test that we can access the todos table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM todos");
        $result = $stmt->fetch();
        
        $this->assertArrayHasKey('count', $result);
        $this->assertGreaterThanOrEqual(0, (int)$result['count']);
    }

    public function testPreparedStatementWorks(): void
    {
        $pdo = Connection::get();
        
        $stmt = $pdo->prepare('SELECT :value AS result');
        $stmt->execute([':value' => 'test']);
        $row = $stmt->fetch();
        
        $this->assertSame('test', $row['result']);
    }
}

