# ProptIQ REST API

A comprehensive, institute-grade online quiz REST API backend built with Laravel. ProptIQ supports quiz authoring, distribution, auto-grading, analytics, and user management.

## Table of Contents

- [System Architecture](#system-architecture)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [API Flows](#api-flows)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing](#testing)

## System Architecture

```mermaid
graph TB
    Client["Client Application"]
    API["Laravel API<br/>Port 8000"]
    Auth["Sanctum Auth<br/>Middleware"]
    Controllers["Controllers Layer"]
    Services["Services Layer"]
    Models["Models/ORM"]
    DB["MySQL Database"]
    
    Client -->|HTTP/JSON| API
    API --> Auth
    Auth --> Controllers
    Controllers --> Services
    Services --> Models
    Models --> DB
    
    subgraph "Authentication"
        Auth
    end
    
    subgraph "Business Logic"
        Controllers
        Services
    end
    
    subgraph "Data Layer"
        Models
        DB
    end
```

## Database Schema

### Entity Relationship Diagram

```mermaid
erDiagram
    users ||--o{ quizzes : "creates (author)"
    users ||--o{ quiz_attempts : "takes"
    users ||--o{ certificates : "receives"
    users ||--o{ groups : "owns"
    users }o--o{ groups : "belongs to"
    
    quizzes ||--|| quiz_settings : "has"
    quizzes ||--o{ questions : "contains"
    quizzes ||--o{ quiz_attempts : "has"
    quizzes ||--o{ certificates : "issues"
    quizzes ||--o{ webhooks : "triggers"
    quizzes }o--o{ groups : "assigned to"
    
    questions ||--o{ question_options : "has"
    questions ||--o{ question_answers : "answered in"
    
    quiz_attempts ||--o{ question_answers : "contains"
    quiz_attempts ||--o| certificates : "generates"
    
    question_options ||--o{ question_answers : "selected in"
    
    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role "admin,teacher,student,guest"
        timestamp created_at
        timestamp updated_at
    }
    
    quizzes {
        bigint id PK
        string title
        text description
        string slug UK
        bigint author_id FK
        enum status "draft,published,archived"
        enum type "classic,exam,survey"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    
    quiz_settings {
        bigint id PK
        bigint quiz_id FK
        int time_limit "minutes"
        int passing_score
        boolean shuffle_questions
        boolean show_results
        enum access_mode "public,private,password"
        string access_code
        timestamp start_at
        timestamp end_at
        timestamp created_at
        timestamp updated_at
    }
    
    questions {
        bigint id PK
        bigint quiz_id FK
        enum type "mcq,true_false,open,fill_blank,matching,picture,file"
        text content
        string media_url
        int points
        int order
        timestamp created_at
        timestamp updated_at
    }
    
    question_options {
        bigint id PK
        bigint question_id FK
        text content
        boolean is_correct
        int order
        timestamp created_at
        timestamp updated_at
    }
    
    quiz_attempts {
        bigint id PK
        bigint quiz_id FK
        bigint user_id FK
        timestamp start_time
        timestamp end_time
        decimal score
        enum status "in_progress,completed,graded"
        timestamp created_at
        timestamp updated_at
    }
    
    question_answers {
        bigint id PK
        bigint attempt_id FK
        bigint question_id FK
        text answer_content
        bigint option_id FK
        boolean is_correct
        decimal points_awarded
        timestamp created_at
        timestamp updated_at
    }
    
    certificates {
        bigint id PK
        bigint attempt_id FK
        bigint user_id FK
        bigint quiz_id FK
        string certificate_code UK
        decimal score
        timestamp issued_at
        timestamp created_at
        timestamp updated_at
    }
    
    webhooks {
        bigint id PK
        bigint quiz_id FK
        string event "quiz.completed,quiz.started"
        string url
        string secret
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
    
    groups {
        bigint id PK
        string name
        bigint owner_id FK
        timestamp created_at
        timestamp updated_at
    }
```

### Table Descriptions

#### users
Stores all user accounts with role-based access control.
- **Roles**: admin (full access), teacher (create/manage quizzes), student (take quizzes), guest (limited access)
- **Authentication**: Email/password with bcrypt hashing
- **Relationships**: Creates quizzes, takes attempts, receives certificates, owns/belongs to groups

#### quizzes
Main quiz entity containing metadata and configuration.
- **Status**: draft (editing), published (available), archived (hidden)
- **Type**: classic (standard quiz), exam (formal assessment), survey (no grading)
- **Soft Deletes**: Preserves data when deleted

#### quiz_settings
Configuration for quiz behavior and access control.
- **time_limit**: Duration in minutes (null = unlimited)
- **passing_score**: Minimum score to pass
- **access_mode**: public (anyone), private (groups only), password (requires code)
- **Scheduling**: start_at/end_at for time-bound quizzes

#### questions
Individual questions within a quiz.
- **Types**: mcq, true_false, open, fill_blank, matching, picture, file
- **Points**: Weighted scoring per question
- **Order**: Display sequence (supports shuffling)

#### question_options
Answer choices for MCQ/True-False questions.
- **is_correct**: Marks the correct answer(s)
- **Order**: Display sequence for options

#### quiz_attempts
Tracks student quiz sessions.
- **Status**: in_progress (active), completed (finished), graded (scored)
- **Timing**: start_time and end_time for duration tracking
- **Score**: Calculated after completion

#### question_answers
Individual answers submitted during an attempt.
- **answer_content**: Text answer for open-ended questions
- **option_id**: Selected option for MCQ questions
- **is_correct**: Auto-graded result
- **points_awarded**: Partial credit support

#### certificates
Auto-generated certificates for passing students.
- **certificate_code**: Unique verification code
- **issued_at**: Generation timestamp
- **Verification**: Public endpoint to validate certificates

#### webhooks
Event-driven integrations for external systems.
- **Events**: quiz.started, quiz.completed, etc.
- **Secret**: HMAC signature for security
- **is_active**: Enable/disable without deletion

#### groups
User organization for access control.
- **owner_id**: Teacher/admin who manages the group
- **Many-to-many**: Users can belong to multiple groups
- **Usage**: Private quiz access, class management

## API Endpoints

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication Endpoints

#### 1. Register User
```http
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "student"
}
```

**Response (201 Created):**
```json
{
  "access_token": "1|abc123xyz...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "student",
    "created_at": "2025-11-25T14:00:00.000000Z",
    "updated_at": "2025-11-25T14:00:00.000000Z"
  }
}
```

#### 2. Login
```http
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "access_token": "2|def456uvw...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "student"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Invalid login details"
}
```

#### 3. Logout
```http
POST /api/v1/logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

#### 4. Get Current User
```http
GET /api/v1/me
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "student",
  "created_at": "2025-11-25T14:00:00.000000Z",
  "updated_at": "2025-11-25T14:00:00.000000Z"
}
```

### Quiz Endpoints

#### 5. List Quizzes
```http
GET /api/v1/quizzes
Authorization: Bearer {token}
```

**Access Control:**
- Admin: All quizzes
- Teacher: Own quizzes only
- Student/Guest: Published public quizzes only

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Mathematics Quiz",
      "description": "Basic algebra quiz",
      "slug": "mathematics-quiz",
      "author_id": 2,
      "status": "published",
      "type": "classic",
      "created_at": "2025-11-25T10:00:00.000000Z",
      "updated_at": "2025-11-25T10:00:00.000000Z",
      "author": {
        "id": 2,
        "name": "Teacher Name"
      }
    }
  ],
  "links": {},
  "meta": {}
}
```

#### 6. Create Quiz
```http
POST /api/v1/quizzes
Authorization: Bearer {token}
Content-Type: application/json

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

**Response (201 Created):**
```json
{
  "id": 1,
  "title": "Mathematics Quiz",
  "description": "Basic algebra quiz",
  "slug": "mathematics-quiz",
  "author_id": 2,
  "status": "draft",
  "type": "classic",
  "settings": {
    "id": 1,
    "quiz_id": 1,
    "time_limit": 30,
    "passing_score": 70,
    "shuffle_questions": true,
    "show_results": true,
    "access_mode": "public",
    "access_code": null,
    "start_at": null,
    "end_at": null
  }
}
```

#### 7. Get Quiz Details
```http
GET /api/v1/quizzes/{quiz_id}
Authorization: Bearer {token}
```

**Response (200 OK) - For Author/Admin:**
```json
{
  "id": 1,
  "title": "Mathematics Quiz",
  "description": "Basic algebra quiz",
  "status": "published",
  "type": "classic",
  "questions": [
    {
      "id": 1,
      "type": "mcq",
      "content": "What is 2 + 2?",
      "points": 10,
      "order": 1,
      "options": [
        {
          "id": 1,
          "content": "3",
          "is_correct": false,
          "order": 1
        },
        {
          "id": 2,
          "content": "4",
          "is_correct": true,
          "order": 2
        }
      ]
    }
  ],
  "settings": {}
}
```

**Response (200 OK) - For Students:**
```json
{
  "id": 1,
  "title": "Mathematics Quiz",
  "questions": [
    {
      "id": 1,
      "type": "mcq",
      "content": "What is 2 + 2?",
      "options": [
        {
          "id": 1,
          "content": "3",
          "order": 1
        },
        {
          "id": 2,
          "content": "4",
          "order": 2
        }
      ]
    }
  ]
}
```

#### 8. Update Quiz
```http
PUT /api/v1/quizzes/{quiz_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Advanced Mathematics Quiz",
  "status": "published",
  "settings": {
    "passing_score": 80
  }
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "title": "Advanced Mathematics Quiz",
  "status": "published",
  "settings": {
    "passing_score": 80
  }
}
```

#### 9. Delete Quiz
```http
DELETE /api/v1/quizzes/{quiz_id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

### Question Endpoints

#### 10. Add Question to Quiz
```http
POST /api/v1/quizzes/{quiz_id}/questions
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "mcq",
  "content": "What is 2 + 2?",
  "points": 10,
  "options": [
    {
      "content": "3",
      "is_correct": false
    },
    {
      "content": "4",
      "is_correct": true
    },
    {
      "content": "5",
      "is_correct": false
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "quiz_id": 1,
  "type": "mcq",
  "content": "What is 2 + 2?",
  "points": 10,
  "order": 1,
  "options": [
    {
      "id": 1,
      "question_id": 1,
      "content": "3",
      "is_correct": false,
      "order": 1
    },
    {
      "id": 2,
      "question_id": 1,
      "content": "4",
      "is_correct": true,
      "order": 2
    }
  ]
}
```

#### 11. Update Question
```http
PUT /api/v1/questions/{question_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "What is 3 + 3?",
  "points": 15
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "content": "What is 3 + 3?",
  "points": 15
}
```

#### 12. Delete Question
```http
DELETE /api/v1/questions/{question_id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

### Quiz Attempt Endpoints

#### 13. Start Quiz Attempt
```http
POST /api/v1/quizzes/{quiz_id}/start
Authorization: Bearer {token}
Content-Type: application/json

{
  "access_code": "optional-password"
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "quiz_id": 1,
  "user_id": 3,
  "start_time": "2025-11-25T14:00:00.000000Z",
  "end_time": null,
  "score": null,
  "status": "in_progress"
}
```

**Error Responses:**
```json
{
  "message": "Quiz not available"
}
```
```json
{
  "message": "Quiz has not started yet"
}
```
```json
{
  "message": "Quiz has ended"
}
```
```json
{
  "message": "Invalid access code"
}
```

#### 14. Submit Answer
```http
POST /api/v1/attempts/{attempt_id}/submit
Authorization: Bearer {token}
Content-Type: application/json

{
  "question_id": 1,
  "option_id": 2
}
```

**For Open-Ended Questions:**
```json
{
  "question_id": 2,
  "answer_content": "The answer is 42"
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "attempt_id": 1,
  "question_id": 1,
  "option_id": 2,
  "answer_content": null,
  "is_correct": null,
  "points_awarded": 0
}
```

#### 15. Finish Quiz Attempt
```http
POST /api/v1/attempts/{attempt_id}/finish
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "quiz_id": 1,
  "user_id": 3,
  "start_time": "2025-11-25T14:00:00.000000Z",
  "end_time": "2025-11-25T14:30:00.000000Z",
  "score": 85.50,
  "status": "completed"
}
```

### Analytics Endpoints

#### 16. Get Leaderboard
```http
GET /api/v1/quizzes/{quiz_id}/leaderboard
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
  {
    "rank": 1,
    "user": "Alice Johnson",
    "score": 95.00,
    "completed_at": "2025-11-25T14:30:00.000000Z"
  },
  {
    "rank": 2,
    "user": "Bob Smith",
    "score": 85.50,
    "completed_at": "2025-11-25T14:35:00.000000Z"
  }
]
```

#### 17. Get Quiz Statistics
```http
GET /api/v1/quizzes/{quiz_id}/stats
Authorization: Bearer {token}
```

**Access**: Author or Admin only

**Response (200 OK):**
```json
{
  "total_attempts": 50,
  "average_score": 75.50,
  "highest_score": 100.00,
  "lowest_score": 45.00,
  "pass_rate": "80.00%",
  "passing_score": 70
}
```

### Certificate Endpoints

#### 18. Generate Certificate
```http
POST /api/v1/attempts/{attempt_id}/certificate
Authorization: Bearer {token}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "attempt_id": 1,
  "user_id": 3,
  "quiz_id": 1,
  "certificate_code": "CERT-ABC123XYZ",
  "score": 85.50,
  "issued_at": "2025-11-25T14:30:00.000000Z"
}
```

**Error Responses:**
```json
{
  "message": "Quiz not completed"
}
```
```json
{
  "message": "Did not meet passing criteria"
}
```

#### 19. Verify Certificate
```http
GET /api/v1/certificates/verify/{certificate_code}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "certificate_code": "CERT-ABC123XYZ",
  "score": 85.50,
  "issued_at": "2025-11-25T14:30:00.000000Z",
  "user": {
    "id": 3,
    "name": "John Doe"
  },
  "quiz": {
    "id": 1,
    "title": "Mathematics Quiz"
  }
}
```

### Webhook Endpoints

#### 20. List Webhooks
```http
GET /api/v1/quizzes/{quiz_id}/webhooks
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "quiz_id": 1,
    "event": "quiz.completed",
    "url": "https://example.com/webhook",
    "is_active": true
  }
]
```

#### 21. Create Webhook
```http
POST /api/v1/quizzes/{quiz_id}/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "event": "quiz.completed",
  "url": "https://example.com/webhook",
  "secret": "your-secret-key"
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "quiz_id": 1,
  "event": "quiz.completed",
  "url": "https://example.com/webhook",
  "secret": "your-secret-key",
  "is_active": true
}
```

#### 22. Delete Webhook
```http
DELETE /api/v1/webhooks/{webhook_id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

### User Management Endpoints (Admin Only)

#### 23. List Users
```http
GET /api/v1/users
Authorization: Bearer {admin_token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin",
      "created_at": "2025-11-25T10:00:00.000000Z"
    }
  ]
}
```

#### 24. Create User
```http
POST /api/v1/users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "New Teacher",
  "email": "teacher@example.com",
  "password": "password123",
  "role": "teacher"
}
```

**Response (201 Created)**

#### 25. Get User Details
```http
GET /api/v1/users/{user_id}
Authorization: Bearer {admin_token}
```

**Response (200 OK)**

#### 26. Update User
```http
PUT /api/v1/users/{user_id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "role": "admin"
}
```

**Response (200 OK)**

#### 27. Delete User
```http
DELETE /api/v1/users/{user_id}
Authorization: Bearer {admin_token}
```

**Response (204 No Content)**

## API Flows

### Authentication Flow

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant AuthController
    participant User Model
    participant Database
    participant Sanctum
    
    Note over Client,Sanctum: User Registration
    Client->>API: POST /api/v1/register
    API->>AuthController: register(request)
    AuthController->>AuthController: Validate input
    AuthController->>User Model: Create user
    User Model->>Database: INSERT INTO users
    Database-->>User Model: User created
    User Model-->>AuthController: User object
    AuthController->>Sanctum: createToken('auth_token')
    Sanctum-->>AuthController: Token
    AuthController-->>Client: 201 {access_token, user}
    
    Note over Client,Sanctum: User Login
    Client->>API: POST /api/v1/login
    API->>AuthController: login(request)
    AuthController->>AuthController: Attempt authentication
    AuthController->>Database: SELECT * FROM users WHERE email
    Database-->>AuthController: User record
    AuthController->>AuthController: Verify password (bcrypt)
    alt Valid credentials
        AuthController->>Sanctum: createToken('auth_token')
        Sanctum-->>AuthController: Token
        AuthController-->>Client: 200 {access_token, user}
    else Invalid credentials
        AuthController-->>Client: 401 {message: "Invalid login details"}
    end
    
    Note over Client,Sanctum: Authenticated Request
    Client->>API: GET /api/v1/me (Bearer token)
    API->>Sanctum: Verify token
    Sanctum->>Database: SELECT * FROM personal_access_tokens
    Database-->>Sanctum: Token valid
    Sanctum-->>API: User authenticated
    API->>AuthController: me(request)
    AuthController-->>Client: 200 {user}
    
    Note over Client,Sanctum: Logout
    Client->>API: POST /api/v1/logout (Bearer token)
    API->>Sanctum: Verify token
    Sanctum-->>API: User authenticated
    API->>AuthController: logout(request)
    AuthController->>Database: DELETE FROM personal_access_tokens
    Database-->>AuthController: Token deleted
    AuthController-->>Client: 200 {message: "Logged out successfully"}
```

### Quiz Creation Flow

```mermaid
sequenceDiagram
    participant Teacher
    participant API
    participant QuizController
    participant QuizService
    participant Quiz Model
    participant QuizSettings Model
    participant Question Model
    participant QuestionOption Model
    participant Database
    
    Note over Teacher,Database: Create Quiz
    Teacher->>API: POST /api/v1/quizzes
    API->>API: Verify auth token
    API->>QuizController: store(request)
    QuizController->>QuizController: Validate input
    QuizController->>QuizService: createQuiz(data, user_id)
    QuizService->>Quiz Model: Create quiz
    Quiz Model->>Database: INSERT INTO quizzes
    Database-->>Quiz Model: Quiz created (id=1)
    QuizService->>QuizService: Generate slug
    QuizService->>QuizSettings Model: Create settings
    QuizSettings Model->>Database: INSERT INTO quiz_settings
    Database-->>QuizSettings Model: Settings created
    QuizService-->>QuizController: Quiz with settings
    QuizController-->>Teacher: 201 {quiz, settings}
    
    Note over Teacher,Database: Add Question 1
    Teacher->>API: POST /api/v1/quizzes/1/questions
    API->>QuizController: QuestionController.store(quiz_id)
    QuizController->>QuizController: Validate question data
    QuizController->>Question Model: Create question
    Question Model->>Database: INSERT INTO questions
    Database-->>Question Model: Question created (id=1)
    
    loop For each option
        QuizController->>QuestionOption Model: Create option
        QuestionOption Model->>Database: INSERT INTO question_options
        Database-->>QuestionOption Model: Option created
    end
    
    QuizController-->>Teacher: 201 {question, options}
    
    Note over Teacher,Database: Add Question 2
    Teacher->>API: POST /api/v1/quizzes/1/questions
    API->>QuizController: QuestionController.store(quiz_id)
    QuizController->>Question Model: Create question
    Question Model->>Database: INSERT INTO questions
    Database-->>Question Model: Question created (id=2)
    QuizController-->>Teacher: 201 {question, options}
    
    Note over Teacher,Database: Publish Quiz
    Teacher->>API: PUT /api/v1/quizzes/1
    API->>QuizController: update(quiz_id)
    QuizController->>QuizController: Authorize (owner/admin)
    QuizController->>Quiz Model: Update status='published'
    Quiz Model->>Database: UPDATE quizzes SET status='published'
    Database-->>Quiz Model: Updated
    QuizController-->>Teacher: 200 {quiz}
```

### Quiz Taking Flow

```mermaid
sequenceDiagram
    participant Student
    participant API
    participant AttemptController
    participant GradingService
    participant QuizAttempt Model
    participant QuestionAnswer Model
    participant Database
    
    Note over Student,Database: Start Quiz
    Student->>API: POST /api/v1/quizzes/1/start
    API->>API: Verify auth token
    API->>AttemptController: start(quiz_id)
    AttemptController->>Database: SELECT * FROM quizzes WHERE id=1
    Database-->>AttemptController: Quiz data
    AttemptController->>Database: SELECT * FROM quiz_settings WHERE quiz_id=1
    Database-->>AttemptController: Settings data
    
    AttemptController->>AttemptController: Check quiz status='published'
    AttemptController->>AttemptController: Check start_at/end_at dates
    AttemptController->>AttemptController: Check access_mode
    
    alt Access denied
        AttemptController-->>Student: 403/404 {message}
    else Access granted
        AttemptController->>QuizAttempt Model: Create attempt
        QuizAttempt Model->>Database: INSERT INTO quiz_attempts
        Database-->>QuizAttempt Model: Attempt created (id=1)
        AttemptController-->>Student: 201 {attempt_id, start_time, status}
    end
    
    Note over Student,Database: Submit Answer 1
    Student->>API: POST /api/v1/attempts/1/submit
    API->>AttemptController: submitAnswer(attempt_id)
    AttemptController->>AttemptController: Verify user owns attempt
    AttemptController->>AttemptController: Verify status='in_progress'
    AttemptController->>AttemptController: Validate question belongs to quiz
    AttemptController->>QuestionAnswer Model: updateOrCreate answer
    QuestionAnswer Model->>Database: INSERT/UPDATE question_answers
    Database-->>QuestionAnswer Model: Answer saved
    AttemptController-->>Student: 200 {answer}
    
    Note over Student,Database: Submit Answer 2
    Student->>API: POST /api/v1/attempts/1/submit
    API->>AttemptController: submitAnswer(attempt_id)
    AttemptController->>QuestionAnswer Model: updateOrCreate answer
    QuestionAnswer Model->>Database: INSERT/UPDATE question_answers
    Database-->>QuestionAnswer Model: Answer saved
    AttemptController-->>Student: 200 {answer}
    
    Note over Student,Database: Finish Quiz
    Student->>API: POST /api/v1/attempts/1/finish
    API->>AttemptController: finish(attempt_id)
    AttemptController->>AttemptController: Verify user owns attempt
    AttemptController->>QuizAttempt Model: Set end_time=now()
    QuizAttempt Model->>Database: UPDATE quiz_attempts SET end_time
    Database-->>QuizAttempt Model: Updated
    
    AttemptController->>GradingService: calculateScore(attempt)
    GradingService->>Database: SELECT answers with questions
    Database-->>GradingService: All answers
    
    loop For each answer
        GradingService->>GradingService: Check if option.is_correct
        GradingService->>GradingService: Calculate points_awarded
        GradingService->>Database: UPDATE question_answers
        Database-->>GradingService: Updated
    end
    
    GradingService->>GradingService: Sum total score
    GradingService->>Database: UPDATE quiz_attempts SET score, status='completed'
    Database-->>GradingService: Updated
    GradingService-->>AttemptController: Graded attempt
    AttemptController-->>Student: 200 {attempt_id, score, status='completed'}
```

### Certificate Generation Flow

```mermaid
sequenceDiagram
    participant Student
    participant API
    participant CertificateController
    participant CertificateService
    participant Certificate Model
    participant Database
    
    Note over Student,Database: Request Certificate
    Student->>API: POST /api/v1/attempts/1/certificate
    API->>API: Verify auth token
    API->>CertificateController: generate(attempt_id)
    CertificateController->>Database: SELECT * FROM quiz_attempts WHERE id=1
    Database-->>CertificateController: Attempt data
    
    CertificateController->>CertificateController: Verify user owns attempt
    CertificateController->>CertificateController: Check status='completed'
    
    alt Not completed
        CertificateController-->>Student: 400 {message: "Quiz not completed"}
    else Completed
        CertificateController->>Database: SELECT * FROM certificates WHERE attempt_id=1
        Database-->>CertificateController: Check existing
        
        alt Certificate exists
            CertificateController-->>Student: 200 {existing certificate}
        else No certificate
            CertificateController->>CertificateService: generateCertificate(attempt)
            CertificateService->>Database: SELECT quiz_settings
            Database-->>CertificateService: Settings (passing_score=70)
            
            CertificateService->>CertificateService: Check score >= passing_score
            
            alt Score too low
                CertificateService-->>CertificateController: null
                CertificateController-->>Student: 400 {message: "Did not meet passing criteria"}
            else Score sufficient
                CertificateService->>CertificateService: Generate unique code
                CertificateService->>Certificate Model: Create certificate
                Certificate Model->>Database: INSERT INTO certificates
                Database-->>Certificate Model: Certificate created
                CertificateService-->>CertificateController: Certificate
                CertificateController-->>Student: 201 {certificate_code, score, issued_at}
            end
        end
    end
    
    Note over Student,Database: Verify Certificate
    Student->>API: GET /api/v1/certificates/verify/CERT-ABC123
    API->>CertificateController: verify(code)
    CertificateController->>Database: SELECT * FROM certificates WHERE certificate_code
    Database-->>CertificateController: Certificate with user, quiz
    
    alt Not found
        CertificateController-->>Student: 404 {message: "Certificate not found"}
    else Found
        CertificateController-->>Student: 200 {certificate, user, quiz}
    end
```

### Leaderboard & Analytics Flow

```mermaid
sequenceDiagram
    participant User
    participant API
    participant LeaderboardController
    participant ReportController
    participant Database
    
    Note over User,Database: Get Leaderboard
    User->>API: GET /api/v1/quizzes/1/leaderboard
    API->>API: Verify auth token
    API->>LeaderboardController: index(quiz_id)
    LeaderboardController->>Database: SELECT quiz with settings
    Database-->>LeaderboardController: Quiz data
    
    LeaderboardController->>LeaderboardController: Check show_results permission
    
    alt Results hidden
        LeaderboardController-->>User: 403 {message: "Leaderboard hidden"}
    else Results visible
        LeaderboardController->>Database: SELECT * FROM quiz_attempts<br/>WHERE quiz_id=1 AND status='completed'<br/>ORDER BY score DESC, end_time ASC
        Database-->>LeaderboardController: Attempts with users
        
        loop For each attempt
            LeaderboardController->>LeaderboardController: Map to {user, score, completed_at}
            LeaderboardController->>LeaderboardController: Assign rank
        end
        
        LeaderboardController-->>User: 200 [{rank, user, score, completed_at}]
    end
    
    Note over User,Database: Get Quiz Statistics
    User->>API: GET /api/v1/quizzes/1/stats
    API->>ReportController: quizStatistics(quiz_id)
    ReportController->>ReportController: Authorize (author/admin only)
    
    alt Unauthorized
        ReportController-->>User: 403 {message: "Unauthorized"}
    else Authorized
        ReportController->>Database: SELECT * FROM quiz_attempts<br/>WHERE quiz_id=1 AND status='completed'
        Database-->>ReportController: All completed attempts
        
        ReportController->>ReportController: Calculate total_attempts
        ReportController->>ReportController: Calculate avg(score)
        ReportController->>ReportController: Calculate max(score)
        ReportController->>ReportController: Calculate min(score)
        
        ReportController->>Database: SELECT passing_score FROM quiz_settings
        Database-->>ReportController: passing_score=70
        
        ReportController->>ReportController: Count attempts WHERE score >= 70
        ReportController->>ReportController: Calculate pass_rate percentage
        
        ReportController-->>User: 200 {total_attempts, average_score,<br/>highest_score, lowest_score, pass_rate}
    end
```

### Webhook Flow

```mermaid
sequenceDiagram
    participant Student
    participant AttemptController
    participant GradingService
    participant Database
    participant WebhookService
    participant External System
    
    Note over Student,External System: Quiz Completion with Webhook
    Student->>AttemptController: POST /api/v1/attempts/1/finish
    AttemptController->>GradingService: calculateScore(attempt)
    GradingService->>Database: Grade answers
    Database-->>GradingService: Graded
    GradingService->>Database: UPDATE quiz_attempts SET status='completed'
    Database-->>GradingService: Updated
    GradingService-->>AttemptController: Attempt completed
    
    AttemptController->>Database: SELECT * FROM webhooks<br/>WHERE quiz_id=1 AND event='quiz.completed'<br/>AND is_active=true
    Database-->>AttemptController: Webhook configurations
    
    loop For each webhook
        AttemptController->>WebhookService: Trigger webhook
        WebhookService->>WebhookService: Prepare payload<br/>{user, quiz, attempt, score}
        WebhookService->>WebhookService: Generate HMAC signature<br/>using webhook.secret
        WebhookService->>External System: POST webhook.url<br/>Headers: X-Signature<br/>Body: {event, data}
        
        alt Webhook success
            External System-->>WebhookService: 200 OK
            WebhookService->>Database: Log success
        else Webhook failure
            External System-->>WebhookService: 4xx/5xx Error
            WebhookService->>Database: Log failure
            WebhookService->>WebhookService: Queue retry
        end
    end
    
    AttemptController-->>Student: 200 {attempt with score}
```

### Role-Based Access Control Flow

```mermaid
graph TB
    Request["Incoming API Request"]
    Auth["Sanctum Authentication"]
    RoleCheck["Role Middleware Check"]
    
    Admin["Admin Actions"]
    Teacher["Teacher Actions"]
    Student["Student Actions"]
    Guest["Guest Actions"]
    
    Request --> Auth
    Auth -->|Valid Token| RoleCheck
    Auth -->|Invalid Token| Unauthorized["401 Unauthorized"]
    
    RoleCheck -->|role=admin| Admin
    RoleCheck -->|role=teacher| Teacher
    RoleCheck -->|role=student| Student
    RoleCheck -->|role=guest| Guest
    
    Admin --> AdminActions["- Manage all users<br/>- View all quizzes<br/>- Access all statistics<br/>- Delete any content"]
    
    Teacher --> TeacherActions["- Create quizzes<br/>- Manage own quizzes<br/>- View own statistics<br/>- Manage webhooks"]
    
    Student --> StudentActions["- Take public quizzes<br/>- View own attempts<br/>- Generate certificates<br/>- View leaderboards"]
    
    Guest --> GuestActions["- View public quizzes<br/>- Limited access<br/>- No quiz creation"]
    
    style Admin fill:#ff6b6b
    style Teacher fill:#4ecdc4
    style Student fill:#45b7d1
    style Guest fill:#96ceb4
```

### Quiz Status Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Draft: Create Quiz
    Draft --> Published: Publish
    Draft --> Archived: Archive
    Published --> Archived: Archive
    Archived --> Published: Restore
    Published --> Draft: Unpublish
    Archived --> [*]: Soft Delete
    
    note right of Draft
        - Editable
        - Not visible to students
        - Can add/remove questions
    end note
    
    note right of Published
        - Visible to students
        - Can be taken
        - Limited editing
    end note
    
    note right of Archived
        - Hidden from students
        - Read-only
        - Preserves data
    end note
```

### Quiz Attempt Status Lifecycle

```mermaid
stateDiagram-v2
    [*] --> in_progress: Start Quiz
    in_progress --> in_progress: Submit Answer
    in_progress --> completed: Finish Quiz
    completed --> graded: Auto-Grade
    graded --> [*]: View Results
    
    note right of in_progress
        - Student can submit answers
        - Timer running (if time_limit set)
        - Can update answers
    end note
    
    note right of completed
        - All answers submitted
        - End time recorded
        - No more changes allowed
    end note
    
    note right of graded
        - Score calculated
        - Correct answers marked
        - Certificate eligible
    end note
```

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- XAMPP (for local development)

### Step 1: Clone Repository
```bash
git clone https://github.com/EIRSVi/prop-iq.git
cd prop-iq
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proptiq_base
DB_USERNAME=root
DB_PASSWORD=
```

### Step 5: Run Migrations
```bash
php artisan migrate
```

### Step 6: Start Development Server
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api/v1`

## Configuration

### Authentication
ProptIQ uses Laravel Sanctum for API token authentication.

```bash
php artisan install:api
```

### Roles
- **Admin** - Full system access
- **Teacher** - Create and manage own quizzes
- **Student** - Take quizzes and view results
- **Guest** - Limited access to public quizzes

### Environment Variables
```env
APP_NAME=ProptIQ
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proptiq_base
DB_USERNAME=root
DB_PASSWORD=
```

## Testing

### Using Postman
1. Import `postman-collection.json`
2. The collection includes auto-save scripts for tokens and IDs
3. Follow the testing workflow in `POSTMAN_SETUP.md`

### Manual Testing with cURL
```bash
# Register a teacher
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teacher",
    "email": "teacher@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "teacher"
  }'
```

### Run Tests
```bash
php artisan test
```

## Error Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 204 | No Content - Resource deleted successfully |
| 400 | Bad Request - Invalid input data |
| 401 | Unauthorized - Invalid or missing authentication |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource does not exist |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error - Server-side error |

## Security

- Token-based authentication (Laravel Sanctum)
- Role-based access control (RBAC)
- Password hashing (bcrypt)
- SQL injection protection (Eloquent ORM)
- CSRF protection
- Rate limiting
- Input validation

## Performance

- Database indexing on foreign keys
- Eager loading relationships
- Pagination on list endpoints
- Query optimization
- Caching opportunities (Redis/Memcached)

## Project Statistics

- **27 API Endpoints**
- **13 Database Tables**
- **9 Controllers**
- **4 Services**
- **11 Models**
- **7 Question Types**

## License

This project is licensed under the MIT License.

## Authors

- **EIRSVi** - [GitHub](https://github.com/EIRSVi)

## Support

For issues and questions:
- Create an issue on GitHub
- Review Laravel logs: `storage/logs/laravel.log`

---

Built with Laravel 11.x
