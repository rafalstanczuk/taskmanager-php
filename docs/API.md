# API Reference

Complete REST API documentation for the Task Manager application.

## Base URL

```
http://localhost:8000
```

## Endpoints

### Health Check

Check if the service is running.

**Request:**
```http
GET /health
```

**Response:** `200 OK`
```json
{
  "status": "ok"
}
```

---

### List Tasks

Retrieve all tasks.

**Request:**
```http
GET /todos
Accept: application/json
```

**Response:** `200 OK`
```json
[
  {
    "id": 1,
    "title": "Implement user authentication",
    "description": "Add JWT-based authentication to the API",
    "completed": false,
    "start_date": "2025-10-06T09:00:00+00:00",
    "due_date": "2025-10-15T17:00:00+00:00",
    "priority": 1,
    "created_at": "2025-10-06 12:00:00+00:00",
    "updated_at": "2025-10-06 12:00:00+00:00"
  }
]
```

**HTML List View:**
```http
GET /todos
Accept: text/html
```

Returns an interactive HTML interface with:
- Task list table with checkboxes
- Add task form
- Bulk operations toolbar
- Filtering by priority and completion status
- Inline editing of tasks
- Gantt chart preview

---

### Gantt Chart View

Display interactive Gantt chart for visual project management.

**Request:**
```http
GET /gantt
```

**Response:** `200 OK` (HTML)

Returns a full-page interactive Gantt chart interface with:
- Visual timeline spanning all task dates
- Draggable task bars (move to reschedule)
- Resizable bars (adjust start/end dates)
- Priority color coding (red/amber/blue)
- Weekend highlighting
- Today marker line
- Task detail modal on click
- Pixel-perfect 80px-per-day positioning

**Features:**
- Click task bar to view details
- Drag task bar to move (preserves duration)
- Drag left edge to change start date
- Drag right edge to change due date
- Minimum 5px drag threshold prevents accidental moves

---

### Get Task

Retrieve a specific task by ID.

**Request:**
```http
GET /todos/{id}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "title": "Implement user authentication",
  "description": "Add JWT-based authentication to the API",
  "completed": false,
  "start_date": "2025-10-06T09:00:00+00:00",
  "due_date": "2025-10-15T17:00:00+00:00",
  "priority": 1,
  "created_at": "2025-10-06 12:00:00+00:00",
  "updated_at": "2025-10-06 12:00:00+00:00"
}
```

**Error Response:** `404 Not Found`
```json
{
  "error": "Not found"
}
```

---

### Create Task

Create a new task.

**Request:**
```http
POST /todos
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Deploy to production",
  "description": "Deploy the new version to production servers",
  "completed": false,
  "start_date": "2025-10-10T09:00:00Z",
  "due_date": "2025-10-20T17:00:00Z",
  "priority": 1
}
```

**Minimal Example:**
```json
{
  "title": "Buy groceries"
}
```

**Response:** `201 Created`
```json
{
  "id": 2,
  "title": "Deploy to production",
  "description": "Deploy the new version to production servers",
  "completed": false,
  "start_date": "2025-10-10T09:00:00+00:00",
  "due_date": "2025-10-20T17:00:00+00:00",
  "priority": 1,
  "created_at": "2025-10-06 14:30:00+00:00",
  "updated_at": "2025-10-06 14:30:00+00:00"
}
```

**Error Response:** `400 Bad Request`
```json
{
  "error": "title is required"
}
```

---

### Update Task

Update an existing task. Supports partial updates.

**Request:**
```http
PUT /todos/{id}
Content-Type: application/json
```

**Body (partial update):**
```json
{
  "title": "Updated title",
  "completed": true,
  "priority": 2
}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "title": "Updated title",
  "description": "Add JWT-based authentication to the API",
  "completed": true,
  "start_date": "2025-10-06T09:00:00+00:00",
  "due_date": "2025-10-15T17:00:00+00:00",
  "priority": 2,
  "created_at": "2025-10-06 12:00:00+00:00",
  "updated_at": "2025-10-06 15:45:00+00:00"
}
```

**Error Responses:**

`400 Bad Request` - Validation error
```json
{
  "error": "title cannot be empty"
}
```

`404 Not Found` - Task doesn't exist
```json
{
  "error": "Not found"
}
```

---

### Delete Task

Delete a task.

**Request:**
```http
DELETE /todos/{id}
```

**Response:** `204 No Content`

No response body.

**Error Response:** `404 Not Found`
```json
{
  "error": "Not found"
}
```

---

## Data Schema

### Task Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | read-only | Auto-generated task ID |
| `title` | string | yes | Task title (max 255 chars) |
| `description` | string | no | Detailed task description |
| `completed` | boolean | no | Task completion status (default: false) |
| `start_date` | string (ISO 8601) | no | Task start date |
| `due_date` | string (ISO 8601) | no | Task due date |
| `priority` | integer | no | Priority: 0 (low), 1 (medium), 2 (high) |
| `created_at` | string (ISO 8601) | read-only | Creation timestamp |
| `updated_at` | string (ISO 8601) | read-only | Last update timestamp |

### Date Format

All dates use ISO 8601 format with timezone:
```
2025-10-15T17:00:00+00:00
```

When creating/updating dates, you can use:
```json
{
  "due_date": "2025-10-15T17:00:00Z"
}
```

### Priority Levels

- `0` - Low priority (blue)
- `1` - Medium priority (amber)
- `2` - High priority (red)

---

## Usage Examples

### Create High-Priority Task

```bash
curl -X POST http://localhost:8000/todos \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Fix critical bug in payment system",
    "description": "Users report failed transactions",
    "priority": 2,
    "due_date": "2025-10-07T23:59:59Z"
  }'
```

### Mark Task as Completed

```bash
curl -X PUT http://localhost:8000/todos/1 \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'
```

### Update Task Dates

```bash
curl -X PUT http://localhost:8000/todos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-10-10T09:00:00Z",
    "due_date": "2025-10-15T17:00:00Z"
  }'
```

### Reschedule Task (Move)

```bash
curl -X PUT http://localhost:8000/todos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-10-12T09:00:00Z",
    "due_date": "2025-10-17T17:00:00Z"
  }'
```

### Change Start Date Only (Resize)

```bash
curl -X PUT http://localhost:8000/todos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-10-08T09:00:00Z"
  }'
```

### List All Tasks

```bash
curl http://localhost:8000/todos \
  -H "Accept: application/json"
```

### Delete Task

```bash
curl -X DELETE http://localhost:8000/todos/1
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| `200` | Success - Resource returned/updated |
| `201` | Created - New resource created |
| `204` | No Content - Resource deleted |
| `400` | Bad Request - Validation error |
| `404` | Not Found - Resource doesn't exist |
| `500` | Internal Server Error - Server error |

---

## Error Handling

All errors return JSON with an `error` field:

```json
{
  "error": "Descriptive error message"
}
```

### Common Errors

**Missing required field:**
```json
{
  "error": "title is required"
}
```

**Empty title:**
```json
{
  "error": "title cannot be empty"
}
```

**Resource not found:**
```json
{
  "error": "Not found"
}
```

**Invalid JSON:**
```json
{
  "error": "Invalid JSON in request body"
}
```

---

## Response Headers

All API responses include:

```http
Content-Type: application/json; charset=utf-8
```

For successful creation:
```http
HTTP/1.1 201 Created
Content-Type: application/json
```

For successful deletion:
```http
HTTP/1.1 204 No Content
```

---

## CORS Support

The API includes CORS headers for cross-origin requests:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

---

## Rate Limiting

Currently, no rate limiting is implemented. For production use, consider adding rate limiting at the reverse proxy level.

---

## Authentication

The current implementation does not include authentication. For production use, consider adding:

- JWT tokens
- API keys
- OAuth 2.0

---

## Pagination

The `/todos` endpoint returns all tasks. For large datasets, consider implementing pagination:

```
GET /todos?page=1&limit=20
```

*(Not currently implemented)*

---

## Filtering & Sorting

Future enhancements may include:

```
GET /todos?completed=false&priority=2&sort=due_date
```

*(Not currently implemented)*

---

## Related Documentation

- [README.md](../README.md) - Project overview
- [Testing Guide](TESTING.md) - API testing examples
- [Gantt Features](GANTT_FEATURES.md) - UI interactions

---

## Testing the API

### Using cURL

```bash
# Health check
curl http://localhost:8000/health

# List tasks
curl http://localhost:8000/todos

# Create task
curl -X POST http://localhost:8000/todos \
  -H "Content-Type: application/json" \
  -d '{"title": "Test Task"}'

# Update task
curl -X PUT http://localhost:8000/todos/1 \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'

# Delete task
curl -X DELETE http://localhost:8000/todos/1
```

### Using HTTPie

```bash
# List tasks
http GET http://localhost:8000/todos

# Create task
http POST http://localhost:8000/todos \
  title="Test Task" priority:=1

# Update task
http PUT http://localhost:8000/todos/1 \
  completed:=true
```

### Using Postman

1. Import the base URL: `http://localhost:8000`
2. Create requests for each endpoint
3. Set `Content-Type: application/json` header
4. Use raw JSON body for POST/PUT requests

---

**API Version**: 1.0  
**Last Updated**: October 2025
