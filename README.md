# MySQL Model Context Protocol (MCP) Server

This is a PHP-based implementation of a Model Context Protocol server that provides read-only access to MySQL databases. It serves as a middleware interface between MySQL databases and AI models (LLMs) that need to access structured data via HTTP API endpoints.

## Features

- ✅ API Key Authentication
- ✅ Read-only Database Access
- ✅ Query Validation and Sanitization
- ✅ CORS Support
- ✅ Standard MCP Endpoints
- ✅ Pagination Support
- ✅ Query Execution Time Tracking

## Requirements

- PHP 8.1 or higher
- MySQL/MariaDB
- Composer

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure your settings:
   ```bash
   cp .env.example .env
   ```
4. Configure your MySQL credentials and API key in `.env`

## Configuration

Edit the `.env` file with your settings:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=readonly_user
DB_PASSWORD=your_password
DB_NAME=your_database

# API Authentication
API_KEY=your_api_key_here
```

## API Endpoints

### GET /databases
Lists all accessible databases

### GET /tables?database={db_name}
Lists all tables in the specified database

### GET /schema?database={db_name}&table={table_name}
Returns the schema information for the specified table

### GET /data?database={db_name}&table={table_name}&limit=20&offset=0
Returns paginated data from the specified table

### POST /query
Executes a read-only SQL query
```json
{
  "database": "your_database",
  "query": "SELECT * FROM users LIMIT 10"
}
```

## Security Features

- Read-only MySQL user required
- API key authentication
- Query validation and sanitization
- Prepared statements for SQL injection prevention
- CORS headers configuration

## Usage Example

```bash
# Get list of databases
curl -X GET http://localhost:8080/databases \
  -H "Authorization: Bearer your_api_key_here"

# Execute a custom query
curl -X POST http://localhost:8080/query \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "database": "your_database",
    "query": "SELECT name, email FROM users WHERE status = '\''active'\''"
  }'
```

## Development

To start the development server:

```bash
php -S localhost:8080 -t public
```

## License

GNU GENERAL PUBLIC LICENSE  Version 3, 29 June 2007

## Security

For security concerns, please submit an issue or contact the maintainers directly.
