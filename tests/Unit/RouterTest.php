<?php declare(strict_types=1);

use App\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesPathParam(): void
    {
        $router = new Router();
        $called = false;
        $router->add('GET', '/items/{id}', function (array $params) use (&$called) {
            $called = $params['id'] === '42';
            return ['ok' => true];
        });

        ob_start();
        $router->dispatch('GET', '/items/42');
        $out = ob_get_clean();

        $this->assertTrue($called);
        $this->assertStringContainsString('ok', (string)$out);
    }

    public function testDispatchesRouteWithoutParams(): void
    {
        $router = new Router();
        $called = false;
        $router->add('GET', '/health', function () use (&$called) {
            $called = true;
            return ['status' => 'ok'];
        });

        ob_start();
        $router->dispatch('GET', '/health');
        $out = ob_get_clean();

        $this->assertTrue($called);
        $this->assertStringContainsString('status', (string)$out);
    }

    public function testReturns404ForUnmatchedRoute(): void
    {
        $router = new Router();
        $router->add('GET', '/items', function () {
            return ['data' => []];
        });

        ob_start();
        $router->dispatch('GET', '/unknown');
        $out = ob_get_clean();

        // Check that the response contains 404 error
        $this->assertStringContainsString('Not found', (string)$out);
        $data = json_decode((string)$out, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
    }

    public function testDistinguishesDifferentHttpMethods(): void
    {
        $router = new Router();
        
        $router->add('GET', '/resource', function () {
            return ['method' => 'GET'];
        });
        
        $router->add('POST', '/resource', function () {
            return ['method' => 'POST'];
        });

        ob_start();
        $router->dispatch('GET', '/resource');
        $getOut = ob_get_clean();

        ob_start();
        $router->dispatch('POST', '/resource');
        $postOut = ob_get_clean();

        $this->assertStringContainsString('GET', $getOut);
        $this->assertStringContainsString('POST', $postOut);
    }

    public function testMultiplePathParameters(): void
    {
        $router = new Router();
        $params = [];
        
        $router->add('GET', '/users/{userId}/posts/{postId}', function (array $p) use (&$params) {
            $params = $p;
            return ['user' => $p['userId'], 'post' => $p['postId']];
        });

        ob_start();
        $router->dispatch('GET', '/users/123/posts/456');
        $out = ob_get_clean();

        $this->assertSame('123', $params['userId']);
        $this->assertSame('456', $params['postId']);
        $this->assertStringContainsString('123', (string)$out);
        $this->assertStringContainsString('456', (string)$out);
    }

    public function testHandlesNullReturn(): void
    {
        $router = new Router();
        $router->add('DELETE', '/items/{id}', function () {
            http_response_code(204);
            return null;
        });

        ob_start();
        $router->dispatch('DELETE', '/items/1');
        $out = ob_get_clean();

        $this->assertEmpty($out);
    }

    public function testHandlesStringReturn(): void
    {
        $router = new Router();
        $router->add('GET', '/text', function () {
            return 'Plain text response';
        });

        ob_start();
        $router->dispatch('GET', '/text');
        $out = ob_get_clean();

        $this->assertStringContainsString('Plain text response', (string)$out);
    }

    public function testRouteWithSpecialCharactersInParam(): void
    {
        $router = new Router();
        $capturedId = '';
        
        $router->add('GET', '/items/{id}', function (array $params) use (&$capturedId) {
            $capturedId = $params['id'];
            return ['id' => $params['id']];
        });

        ob_start();
        $router->dispatch('GET', '/items/abc-123_def');
        ob_get_clean();

        $this->assertSame('abc-123_def', $capturedId);
    }
}


