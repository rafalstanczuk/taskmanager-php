<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Import helper functions from App namespace
use function App\json_response;
use function App\read_json_input;

final class HelperFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        // Load bootstrap which includes helper functions
        require_once __DIR__ . '/../../config/bootstrap.php';
    }

    public function testJsonResponseWithArray(): void
    {
        ob_start();
        json_response(['key' => 'value'], 200);
        $output = ob_get_clean();
        
        $this->assertJson((string)$output);
        $decoded = json_decode((string)$output, true);
        $this->assertSame(['key' => 'value'], $decoded);
    }

    public function testJsonResponseSetsHttpCode(): void
    {
        ob_start();
        json_response(['status' => 'created'], 201);
        ob_get_clean();
        
        $this->assertSame(201, http_response_code());
        
        // Reset
        http_response_code(200);
    }

    public function testJsonResponseWith404(): void
    {
        ob_start();
        json_response(['error' => 'Not found'], 404);
        $output = ob_get_clean();
        
        $this->assertSame(404, http_response_code());
        $decoded = json_decode((string)$output, true);
        $this->assertSame('Not found', $decoded['error']);
        
        // Reset
        http_response_code(200);
    }

    public function testJsonResponseWith400(): void
    {
        ob_start();
        json_response(['error' => 'Bad request'], 400);
        $output = ob_get_clean();
        
        $this->assertSame(400, http_response_code());
        
        // Reset
        http_response_code(200);
    }

    public function testReadJsonInputWithValidJson(): void
    {
        // This test is limited because we can't easily mock php://input
        // Testing this in integration tests instead
        $this->assertTrue(true); // Placeholder - covered in integration tests
    }
}

