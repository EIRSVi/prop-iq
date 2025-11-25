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
graph TB
    subgraph "User Registration"
        RegStart["POST /api/v1/register"] --> RegValidate["Validate Input<br/>name, email, password"]
        RegValidate --> RegCreate["Create User<br/>Hash Password"]
        RegCreate --> RegDB["Save to Database<br/>users table"]
        RegDB --> RegToken["Generate Token<br/>Sanctum"]
        RegToken --> RegResponse["201 Created<br/>{access_token, user}"]
    end
    
    subgraph "User Login"
        LoginStart["POST /api/v1/login"] --> LoginAuth["Verify Credentials<br/>email + password"]
        LoginAuth -->|Valid| LoginToken["Generate Token<br/>Sanctum"]
        LoginAuth -->|Invalid| LoginError["401 Unauthorized<br/>Invalid login details"]
        LoginToken --> LoginResponse["200 OK<br/>{access_token, user}"]
    end
    
    subgraph "Authenticated Request"
        AuthReq["GET /api/v1/me<br/>Bearer token"] --> VerifyToken["Verify Token<br/>Sanctum Middleware"]
        VerifyToken -->|Valid| GetUser["Fetch User Data"]
        VerifyToken -->|Invalid| AuthError["401 Unauthorized"]
        GetUser --> AuthResponse["200 OK<br/>{user}"]
    end
    
    subgraph "Logout"
        LogoutReq["POST /api/v1/logout<br/>Bearer token"] --> LogoutVerify["Verify Token"]
        LogoutVerify --> LogoutDelete["Delete Token<br/>personal_access_tokens"]
        LogoutDelete --> LogoutResponse["200 OK<br/>Logged out successfully"]
    end
    
    style RegResponse fill:#90EE90
    style LoginResponse fill:#90EE90
    style AuthResponse fill:#90EE90
    style LogoutResponse fill:#90EE90
    style LoginError fill:#FFB6C1
    style AuthError fill:#FFB6C1
```

### Quiz Creation Flow

```mermaid
graph TB
    Start["Teacher: POST /api/v1/quizzes"] --> Auth["Verify Auth Token<br/>Sanctum"]
    Auth --> Validate["Validate Quiz Data<br/>title, type, settings"]
    Validate --> CreateQuiz["QuizService<br/>Create Quiz"]
    CreateQuiz --> SaveQuiz["Database<br/>INSERT INTO quizzes"]
    SaveQuiz --> GenSlug["Generate Unique Slug"]
    GenSlug --> CreateSettings["Create Quiz Settings<br/>time_limit, passing_score"]
    CreateSettings --> SaveSettings["Database<br/>INSERT INTO quiz_settings"]
    SaveSettings --> QuizResponse["201 Created<br/>{quiz, settings}"]
    
    QuizResponse --> AddQ1["Teacher: POST /api/v1/quizzes/1/questions"]
    AddQ1 --> ValidateQ1["Validate Question Data<br/>type, content, points"]
    ValidateQ1 --> CreateQ1["Create Question"]
    CreateQ1 --> SaveQ1["Database<br/>INSERT INTO questions"]
    SaveQ1 --> CreateOpts["Create Options<br/>Loop through options"]
    CreateOpts --> SaveOpts["Database<br/>INSERT INTO question_options"]
    SaveOpts --> Q1Response["201 Created<br/>{question, options}"]
    
    Q1Response --> AddQ2["Teacher: Add More Questions"]
    AddQ2 --> Q2Response["201 Created<br/>{question, options}"]
    
    Q2Response --> Publish["Teacher: PUT /api/v1/quizzes/1<br/>status=published"]
    Publish --> AuthCheck["Authorize<br/>Owner or Admin"]
    AuthCheck -->|Authorized| UpdateStatus["Database<br/>UPDATE quizzes<br/>SET status='published'"]
    AuthCheck -->|Unauthorized| Error403["403 Forbidden"]
    UpdateStatus --> PublishResponse["200 OK<br/>{quiz}"]
    
    style QuizResponse fill:#90EE90
    style Q1Response fill:#90EE90
    style Q2Response fill:#90EE90
    style PublishResponse fill:#90EE90
    style Error403 fill:#FFB6C1
```

### Quiz Taking Flow

```mermaid
graph TB
    Start["Student: POST /api/v1/quizzes/1/start"] --> Auth["Verify Auth Token"]
    Auth --> LoadQuiz["Load Quiz Data<br/>quizzes + quiz_settings"]
    LoadQuiz --> CheckStatus["Check Quiz Status<br/>published?"]
    CheckStatus --> CheckDates["Check Dates<br/>start_at, end_at"]
    CheckDates --> CheckAccess["Check Access Mode<br/>public/private/password"]
    
    CheckAccess -->|Access Denied| Error403["403/404<br/>Access Denied"]
    CheckAccess -->|Access Granted| CreateAttempt["Create Quiz Attempt<br/>status=in_progress"]
    CreateAttempt --> SaveAttempt["Database<br/>INSERT INTO quiz_attempts"]
    SaveAttempt --> StartResponse["201 Created<br/>{attempt_id, start_time}"]
    
    StartResponse --> SubmitA1["Student: POST /api/v1/attempts/1/submit<br/>question_id, option_id"]
    SubmitA1 --> VerifyAttempt["Verify Ownership<br/>& Status"]
    VerifyAttempt --> ValidateQ["Validate Question<br/>belongs to quiz"]
    ValidateQ --> SaveAnswer1["Database<br/>INSERT/UPDATE question_answers"]
    SaveAnswer1 --> Answer1Response["200 OK<br/>{answer}"]
    
    Answer1Response --> SubmitA2["Student: Submit More Answers"]
    SubmitA2 --> SaveAnswer2["Database<br/>UPDATE question_answers"]
    SaveAnswer2 --> Answer2Response["200 OK"]
    
    Answer2Response --> Finish["Student: POST /api/v1/attempts/1/finish"]
    Finish --> SetEndTime["Set end_time=now()"]
    SetEndTime --> StartGrading["GradingService<br/>calculateScore()"]
    StartGrading --> LoadAnswers["Load All Answers<br/>with Questions"]
    LoadAnswers --> CheckCorrect["Check Each Answer<br/>is_correct?"]
    CheckCorrect --> CalcPoints["Calculate Points<br/>points_awarded"]
    CalcPoints --> UpdateAnswers["Database<br/>UPDATE question_answers"]
    UpdateAnswers --> SumScore["Sum Total Score"]
    SumScore --> UpdateAttempt["Database<br/>UPDATE quiz_attempts<br/>score, status=completed"]
    UpdateAttempt --> FinishResponse["200 OK<br/>{score, status=completed}"]
    
    style StartResponse fill:#90EE90
    style Answer1Response fill:#90EE90
    style Answer2Response fill:#90EE90
    style FinishResponse fill:#90EE90
    style Error403 fill:#FFB6C1
```

### Certificate Generation Flow

```mermaid
graph TB
    Start["Student: POST /api/v1/attempts/1/certificate"] --> VerifyAuth["Verify Auth Token"]
    VerifyAuth --> LoadAttempt["Load Quiz Attempt<br/>from Database"]
    LoadAttempt --> CheckOwner["Verify User<br/>Owns Attempt"]
    CheckOwner --> CheckComplete["Check Status<br/>completed?"]
    
    CheckComplete -->|Not Completed| Error400A["400 Bad Request<br/>Quiz not completed"]
    CheckComplete -->|Completed| CheckExisting["Check Existing<br/>Certificate"]
    
    CheckExisting -->|Exists| ReturnExisting["200 OK<br/>{existing certificate}"]
    CheckExisting -->|Not Exists| LoadSettings["Load Quiz Settings<br/>passing_score"]
    
    LoadSettings --> CompareScore["Compare Score<br/>score >= passing_score?"]
    CompareScore -->|Failed| Error400B["400 Bad Request<br/>Did not meet passing criteria"]
    CompareScore -->|Passed| GenCode["Generate Unique Code<br/>CERT-XXXXXX"]
    
    GenCode --> CreateCert["Create Certificate<br/>CertificateService"]
    CreateCert --> SaveCert["Database<br/>INSERT INTO certificates"]
    SaveCert --> CertResponse["201 Created<br/>{certificate_code, score, issued_at}"]
    
    CertResponse --> Verify["Anyone: GET /api/v1/certificates/verify/CODE"]
    Verify --> SearchCert["Database<br/>SELECT certificate<br/>WHERE certificate_code"]
    SearchCert -->|Not Found| Error404["404 Not Found<br/>Certificate not found"]
    SearchCert -->|Found| VerifyResponse["200 OK<br/>{certificate, user, quiz}"]
    
    style ReturnExisting fill:#87CEEB
    style CertResponse fill:#90EE90
    style VerifyResponse fill:#90EE90
    style Error400A fill:#FFB6C1
    style Error400B fill:#FFB6C1
    style Error404 fill:#FFB6C1
```

### Leaderboard & Analytics Flow

```mermaid
graph TB
    subgraph "Leaderboard"
        LB1["User: GET /api/v1/quizzes/1/leaderboard"] --> LB2["Verify Auth Token"]
        LB2 --> LB3["Load Quiz Settings<br/>show_results"]
        LB3 -->|Hidden| LB4["403 Forbidden<br/>Leaderboard hidden"]
        LB3 -->|Visible| LB5["Database<br/>SELECT quiz_attempts<br/>WHERE status=completed<br/>ORDER BY score DESC"]
        LB5 --> LB6["Map Each Attempt<br/>user, score, completed_at"]
        LB6 --> LB7["Assign Ranks<br/>1, 2, 3..."]
        LB7 --> LB8["200 OK<br/>{rank, user, score, completed_at}"]
    end
    
    subgraph "Quiz Statistics"
        ST1["Teacher/Admin: GET /api/v1/quizzes/1/stats"] --> ST2["Verify Auth Token"]
        ST2 --> ST3["Authorize<br/>Author or Admin?"]
        ST3 -->|Unauthorized| ST4["403 Forbidden<br/>Unauthorized"]
        ST3 -->|Authorized| ST5["Database<br/>SELECT quiz_attempts<br/>WHERE status=completed"]
        ST5 --> ST6["Calculate Statistics<br/>total, avg, max, min"]
        ST6 --> ST7["Load passing_score<br/>from quiz_settings"]
        ST7 --> ST8["Count Passed Attempts<br/>score >= passing_score"]
        ST8 --> ST9["Calculate Pass Rate<br/>percentage"]
        ST9 --> ST10["200 OK<br/>{total_attempts, average_score,<br/>highest_score, lowest_score, pass_rate}"]
    end
    
    style LB8 fill:#90EE90
    style ST10 fill:#90EE90
    style LB4 fill:#FFB6C1
    style ST4 fill:#FFB6C1
```

### Webhook Flow

```mermaid
graph TB
    Start["Student: POST /api/v1/attempts/1/finish"] --> Grade["GradingService<br/>calculateScore()"]
    Grade --> UpdateDB["Database<br/>UPDATE quiz_attempts<br/>SET status=completed, score"]
    UpdateDB --> LoadWebhooks["Database<br/>SELECT webhooks<br/>WHERE quiz_id AND event=quiz.completed<br/>AND is_active=true"]
    
    LoadWebhooks -->|No Webhooks| Response["200 OK<br/>{attempt with score}"]
    LoadWebhooks -->|Has Webhooks| PreparePayload["WebhookService<br/>Prepare Payload<br/>{user, quiz, attempt, score}"]
    
    PreparePayload --> GenSignature["Generate HMAC Signature<br/>using webhook.secret"]
    GenSignature --> SendWebhook["POST to External URL<br/>Headers: X-Signature<br/>Body: {event, data}"]
    
    SendWebhook -->|Success 200| LogSuccess["Database<br/>Log webhook success"]
    SendWebhook -->|Error 4xx/5xx| LogFailure["Database<br/>Log webhook failure"]
    LogFailure --> QueueRetry["Queue Retry<br/>for failed webhook"]
    
    LogSuccess --> Response
    QueueRetry --> Response
    
    style Response fill:#90EE90
    style LogSuccess fill:#90EE90
    style LogFailure fill:#FFB6C1
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
