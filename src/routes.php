<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

function registerRoutes(App $app): void {
    // GET /databases - List accessible databases
    $app->get('/databases', function (Request $request, Response $response) {
        $conn = getDbConnection();
        $result = $conn->query('SHOW DATABASES');
        $databases = [];
        
        while ($row = $result->fetch_row()) {
            $databases[] = $row[0];
        }
        
        $conn->close();
        return createJsonResponse($response, ['databases' => $databases]);
    });

    // GET /tables - List tables in a database
    $app->get('/tables', function (Request $request, Response $response) {
        $params = $request->getQueryParams();
        $database = sanitizeDatabaseName($params['database'] ?? '');
        
        if (empty($database)) {
            return createJsonResponse($response, ['error' => 'Database parameter is required'], 400);
        }

        $conn = getDbConnection();
        $conn->select_db($database);
        $result = $conn->query('SHOW TABLES');
        $tables = [];
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $conn->close();
        return createJsonResponse($response, ['tables' => $tables]);
    });

    // GET /schema - Get table schema
    $app->get('/schema', function (Request $request, Response $response) {
        $params = $request->getQueryParams();
        $database = sanitizeDatabaseName($params['database'] ?? '');
        $table = sanitizeTableName($params['table'] ?? '');
        
        if (empty($database) || empty($table)) {
            return createJsonResponse($response, ['error' => 'Database and table parameters are required'], 400);
        }

        $conn = getDbConnection();
        $conn->select_db($database);
        
        // Get table columns
        $query = "SELECT 
            COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION";
            
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $database, $table);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        $conn->close();
        return createJsonResponse($response, ['schema' => $columns]);
    });

    // GET /data - Get table data with pagination
    $app->get('/data', function (Request $request, Response $response) {
        $params = $request->getQueryParams();
        $database = sanitizeDatabaseName($params['database'] ?? '');
        $table = sanitizeTableName($params['table'] ?? '');
        $limit = min((int)($params['limit'] ?? 20), 1000);
        $offset = max((int)($params['offset'] ?? 0), 0);
        
        if (empty($database) || empty($table)) {
            return createJsonResponse($response, ['error' => 'Database and table parameters are required'], 400);
        }

        $conn = getDbConnection();
        $conn->select_db($database);
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM `$table`");
        $countStmt->execute();
        $totalCount = $countStmt->get_result()->fetch_row()[0];
        
        // Get data with pagination
        $query = "SELECT * FROM `$table` LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $conn->close();
        return createJsonResponse($response, [
            'data' => $rows,
            'pagination' => [
                'total' => $totalCount,
                'offset' => $offset,
                'limit' => $limit
            ]
        ]);
    });

    // POST /query - Execute custom read-only query
    $app->post('/query', function (Request $request, Response $response) {
        $body = $request->getParsedBody();
        
        if (!isset($body['database']) || !isset($body['query'])) {
            return createJsonResponse($response, ['error' => 'Database and query parameters are required'], 400);
        }

        $database = sanitizeDatabaseName($body['database']);
        $query = $body['query'];

        if (!validateQuery($query)) {
            return createJsonResponse($response, ['error' => 'Invalid or unsafe query. Only SELECT queries are allowed.'], 400);
        }

        $conn = getDbConnection();
        $conn->select_db($database);
        
        $startTime = microtime(true);
        $result = $conn->query($query);
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        if (!$result) {
            return createJsonResponse($response, ['error' => $conn->error], 400);
        }

        $columns = [];
        $rows = [];
        
        if ($result->num_rows > 0) {
            $fieldsInfo = $result->fetch_fields();
            foreach ($fieldsInfo as $field) {
                $columns[] = $field->name;
            }
            
            while ($row = $result->fetch_row()) {
                $rows[] = $row;
            }
        }
        
        $conn->close();
        return createJsonResponse($response, [
            'columns' => $columns,
            'rows' => $rows,
            'rowCount' => count($rows),
            'executionTimeMs' => round($executionTime, 2)
        ]);
    });
}
