# File Storage Service

A Laravel-based microservice for handling file uploads with multiple storage providers (R2 and GCP) and user management.

## Setup & Installation

### Prerequisites
- Docker and Docker Compose
- Git
- Composer (for local development)

### Getting Started

1. Clone the repository:
```bash
git clone <repository-url>
cd bites
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Start the Docker containers:
```bash
docker-compose up -d
```

4. Install dependencies and setup the project:
```bash
docker-compose exec bites_app composer install
docker-compose exec bites_app php artisan key:generate
docker-compose exec bites_app php artisan migrate
```

5. Seed the database with sample users:
```bash
docker-compose exec bites_app php artisan db:seed
```
This command will create sample users in the database that you can use for testing the API.

## API Documentation

### File Management

#### Upload File
- **POST** `/api/v1/files`
- **Headers:**
  - Content-Type: multipart/form-data
- **Body:**
  - file: (file) Required
- **Response:** 201 Created
```json
{
    "id": "1"
}
```

#### Get File Details
- **GET** `/api/v1/files/{id}`
- **Response:** 200 OK
```json
{
    "data": {
        "filename": "example.txt",
        "upload_status": "uploaded",
        "origin_url": "https://storage.url/example.txt",
        "download_url": "http://api.url/files/1/download",
        "created_at": "2025-09-14 00:00:00",
        "updated_at": "2025-09-14 00:00:00"
    }
}
```

### User Management

#### List/Search Users
- **GET** `/api/v1/users`
- **Query Parameters:**
  - name: (string) Optional - Filter by name
  - dob: (date) Optional - Filter by date of birth (format: YYYY-MM-DD)
- **Response:** 200 OK
```json
{
    "status": 200,
    "message": "Users retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "dob": "1990-01-01",
            "dob_formated": "01 Jan, 1990"
        }
    ]
}
```

## Architecture

### Storage Providers
The service supports multiple storage providers:
- R2 (Cloudflare)
- GCP (Google Cloud Platform)

If one provider fails, the system automatically tries the next available provider.

### Queue System
File uploads are processed asynchronously with:
- Retry mechanism (3 attempts)
- Exponential backoff (10s, 30s, 60s)
- Queue monitoring

## Testing

### Running Tests

Run all tests:
```bash
docker-compose exec bites_app php artisan test
```

Run specific test suite:
```bash
docker-compose exec bites_app php artisan test tests/Feature/FileTest.php
docker-compose exec bites_app php artisan test tests/Feature/UserTest.php
docker-compose exec bites_app php artisan test tests/Unit/Services/Storage/StorageFactoryServiceTest.php
```

### Test Coverage
- Feature Tests:
  - File upload and management
  - User listing and filtering
- Unit Tests:
  - Storage Factory Service
  - Storage Provider implementations

## Environment Variables

Key environment variables:
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

R2_ACCESS_KEY=your_r2_key
R2_SECRET_KEY=your_r2_secret
R2_BUCKET=your_bucket
R2_ENDPOINT=your_endpoint

GCP_PROJECT_ID=your_project_id
GCP_STORAGE_BUCKET=your_bucket
```

## Monitoring and Maintenance

### Queue Worker
Start the queue worker:
```bash
docker-compose exec bites_app php artisan queue:work
```

### Logs
Access logs:
```bash
docker-compose exec bites_app tail -f storage/logs/laravel.log
```
