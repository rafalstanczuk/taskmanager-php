<?php declare(strict_types=1);

namespace App {
    // Store original functions if they exist
    $originalJsonResponse = function_exists('App\json_response') ? 'App\json_response' : null;
    $originalReadJsonInput = function_exists('App\read_json_input') ? 'App\read_json_input' : null;
    
    /**
     * Mock for the json_response function
     */
    function json_response(array $data, int $code = 200): array
    {
        http_response_code($code);
        return $data;
    }

    /**
     * Mock for the read_json_input function
     */
    function read_json_input(): array
    {
        static $testData = [];
        
        // Allow tests to set the mock data
        if (func_num_args() > 0) {
            $testData = func_get_arg(0);
            return $testData;
        }
        
        return $testData;
    }
    
    /**
     * Helper to set mock input data for tests
     */
    function set_test_input(array $data): void
    {
        read_json_input($data);
    }
}

// Override PHP's http_response_code function in the global namespace
namespace {
    if (!function_exists('http_response_code')) {
        function http_response_code($code = null) {
            static $response_code = 200;
            
            if ($code !== null) {
                $response_code = $code;
            }
            
            return $response_code;
        }
    }
}
