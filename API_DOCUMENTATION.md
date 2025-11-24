# ProptIQ REST API Documentation

## Overview
ProptIQ is a comprehensive, institute-grade online quiz REST API backend built with Laravel. It supports quiz authoring, distribution, auto-grading, analytics, and user management.

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All protected endpoints require Bearer token authentication using Laravel Sanctum.

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## API Endpoints

### Authentication

#### Register
```http
POST /register
```
**Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "student"
}
```

#### Login
```http
POST /login
```
**Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Logout
```http
POST /logout
```
*Requires authentication*

#### Get Current User
```http
GET /me
```
*Requires authentication*

---

### Quizzes

#### List Quizzes
```http
GET /quizzes
```
*Requires authentication*

**Response:**
- Admin: All quizzes
- Teacher: Own quizzes
- Student: Published public quizzes

#### Create Quiz
```http
POST /quizzes
```
*Requires authentication (Teacher/Admin)*

**Body:**
```json
{
  "title": "Mathematics Quiz",
  "description": "Basic algebra quiz",
  "type": "classic",
  "settings": {
    "time_limit": 30,
    "passing_score": 70,
    "access_mode": "public",
    "shuffle_questions": true,
    "show_results": true
  }
}
```

#### Get Quiz
```http
GET /quizzes/{id}
```
*Requires authentication*

#### Update Quiz
```http
PUT /quizzes/{id}
```
*Requires authentication (Author/Admin)*

#### Delete Quiz
```http
DELETE /quizzes/{id}
```
*Requires authentication (Author/Admin)*

---

### Questions

#### Add Question to Quiz
```http
POST /quizzes/{quiz_id}/questions
```
*Requires authentication (Author/Admin)*

**Body:**
```json
{
  "type": "mcq",
  "content": "What is 2 + 2?",
  "points": 10,
  "options": [
    {"content": "3", "is_correct": false},
    {"content": "4", "is_correct": true},
    {"content": "5", "is_correct": false}
  ]
}
```

#### Update Question
```http
PUT /questions/{id}
```
*Requires authentication (Author/Admin)*

#### Delete Question
```http
DELETE /questions/{id}
```
*Requires authentication (Author/Admin)*

---

### Quiz Attempts

#### Start Quiz Attempt
```http
POST /quizzes/{quiz_id}/start
```
*Requires authentication*

**Optional Body (for password-protected quizzes):**
```json
{
  "access_code": "ABC123"
}
```

#### Submit Answer
```http
POST /attempts/{attempt_id}/submit
```
*Requires authentication*

**Body:**
```json
{
  "question_id": 1,
  "option_id": 2,
  "answer_content": "Optional text answer"
}
```

#### Finish Attempt
```http
POST /attempts/{attempt_id}/finish
```
*Requires authentication*

---

### Analytics & Reporting

#### Get Leaderboard
```http
GET /quizzes/{quiz_id}/leaderboard
```
*Requires authentication*

**Response:**
```json
[
  {
    "rank": 1,
    "user": "John Doe",
    "score": 95.5,
    "completed_at": "2025-11-24T12:00:00Z"
  }
]
```

#### Get Quiz Statistics
```http
GET /quizzes/{quiz_id}/stats
```
*Requires authentication (Author/Admin)*

**Response:**
```json
{
  "total_attempts": 50,
  "average_score": 75.5,
  "highest_score": 100,
  "lowest_score": 45,
  "pass_rate": "80.00%",
  "passing_score": 70
}
```

---

### Certificates

#### Generate Certificate
```http
POST /attempts/{attempt_id}/certificate
```
*Requires authentication*

**Response:**
```json
{
  "id": 1,
  "certificate_code": "CERT-ABC123XYZ456",
  "score": 85.5,
  "issued_at": "2025-11-24T12:00:00Z"
}
```

#### Verify Certificate
```http
GET /certificates/verify/{code}
```
*Requires authentication*

---

### Webhooks

#### List Webhooks
```http
GET /quizzes/{quiz_id}/webhooks
```
*Requires authentication (Author/Admin)*

#### Create Webhook
```http
POST /quizzes/{quiz_id}/webhooks
```
*Requires authentication (Author/Admin)*

**Body:**
```json
{
  "event": "quiz.completed",
  "url": "https://example.com/webhook",
  "secret": "optional_secret"
}
```

**Supported Events:**
- `quiz.started`
- `quiz.completed`
- `quiz.graded`

#### Delete Webhook
```http
DELETE /webhooks/{id}
```
*Requires authentication (Author/Admin)*

---

### User Management (Admin Only)

#### List Users
```http
GET /users
```
*Requires authentication (Admin)*

#### Create User
```http
POST /users
```
*Requires authentication (Admin)*

#### Update User
```http
PUT /users/{id}
```
*Requires authentication (Admin)*

#### Delete User
```http
DELETE /users/{id}
```
*Requires authentication (Admin)*

---

## Question Types

### Supported Types
1. **mcq** - Multiple Choice (multiple correct answers)
2. **true_false** - True/False questions
3. **open** - Open-ended text response
4. **fill_blank** - Fill in the blanks
5. **matching** - Match items
6. **picture** - Picture-based questions
7. **file_upload** - File upload questions

---

## Access Modes

### Public
Anyone with the link can access the quiz.

### Private
Only users in specified groups can access.

### Password
Requires an access code to start the quiz.

---

## Roles & Permissions

### Admin
- Full system access
- Manage all users and quizzes
- View all analytics

### Teacher
- Create and manage own quizzes
- View analytics for own quizzes
- Manage students in their groups

### Student
- Take published quizzes
- View own results
- Generate certificates

### Guest
- Limited access to public quizzes

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "Unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Rate Limiting
API requests are rate-limited to prevent abuse. Default limits:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

---

## Database Schema

### Core Tables
- `users` - User accounts
- `groups` - User groups/classes
- `quizzes` - Quiz definitions
- `quiz_settings` - Quiz configuration
- `questions` - Question bank
- `question_options` - Answer options
- `quiz_attempts` - Student attempts
- `question_answers` - Individual answers
- `certificates` - Generated certificates
- `webhooks` - Webhook configurations

---

## Setup Instructions

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer
- XAMPP (for local development)

### Installation
```bash
# Clone repository
git clone <repository-url>
cd ProptIQ

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_DATABASE=proptiq_base
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Start server
php artisan serve
```

---

## Testing

### Using Hoppscotch/Postman
1. Import the API collection (see `docs/hoppscotch-collection.json`)
2. Set base URL to `http://localhost:8000/api/v1`
3. Register a new user
4. Use the returned token for authenticated requests

---

## Support
For issues and questions, please contact the development team.
