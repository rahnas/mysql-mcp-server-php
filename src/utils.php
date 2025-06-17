<?php
use Psr\Http\Message\ResponseInterface as Response;

function createJsonResponse(Response $response, $data, int $status = 200): Response {
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function sanitizeTableName(string $table): string {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $table);
}

function sanitizeDatabaseName(string $database): string {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $database);
}
