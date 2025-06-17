<?php

function getDbConnection(): \mysqli {
    global $config;
    
    $conn = new mysqli(
        $config['DB_HOST'],
        $config['DB_USER'],
        $config['DB_PASSWORD'],
        $config['DB_NAME'],
        (int)$config['DB_PORT']
    );

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Ensure we're using read-only mode
    $conn->query("SET SESSION TRANSACTION READ ONLY");
    $conn->query("SET SESSION SQL_SAFE_UPDATES = 1");
    
    return $conn;
}

function validateQuery(string $query): bool {
    // Convert to uppercase for easier matching
    $upperQuery = strtoupper($query);
    
    // List of forbidden keywords
    $forbiddenKeywords = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE',
        'TRUNCATE', 'REPLACE', 'GRANT', 'REVOKE', 'SET'
    ];
    
    // Check if query starts with SELECT
    if (!str_starts_with(trim($upperQuery), 'SELECT')) {
        return false;
    }
    
    // Check for forbidden keywords
    foreach ($forbiddenKeywords as $keyword) {
        if (str_contains($upperQuery, $keyword)) {
            return false;
        }
    }
    
    return true;
}
