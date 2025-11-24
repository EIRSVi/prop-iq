# ProptIQ REST API

A comprehensive, institute-grade online quiz REST API backend built with Laravel. ProptIQ supports quiz authoring, distribution, auto-grading, analytics, and user management - similar to professional quiz platforms like FlexiQuiz.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🚀 Features

### Core Functionality
- ✅ **User Management** - Role-based access control (Admin, Teacher, Student, Guest)
- ✅ **Quiz Authoring** - Create quizzes with multiple question types
- ✅ **Access Control** - Public, Private, and Password-protected quizzes
- ✅ **Quiz Taking** - Real-time answer submission with auto-grading
- ✅ **Analytics** - Leaderboards, statistics, and performance reports
- ✅ **Certificates** - Auto-generate certificates for passing students
- ✅ **Webhooks** - Event-driven integrations

### Question Types Supported
- Multiple Choice (MCQ)
- True/False
- Open-ended
- Fill in the blanks
- Matching
- Picture choice
- File upload

### Advanced Features
- Scheduled quizzes (start/end dates)
- Time limits
- Passing score requirements
- Question shuffling
- Group-based access
- Soft deletes for data preservation

## 📋 Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## 🛠️ Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- XAMPP (for local development)

### Step 1: Clone Repository
```bash
git clone https://github.com/EIRSVi/ProptIQ.git
cd ProptIQ
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

## ⚙️ Configuration

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

## 📚 API Documentation

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication Endpoints

#### Register
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

#### Login
```http
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "access_token": "1|abc123...",
  "token_type": "Bearer",
  "user": { ... }
}
```

### Quiz Endpoints

#### Create Quiz
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

#### Add Question
```http
POST /api/v1/quizzes/{quiz_id}/questions
Authorization: Bearer {token}
Content-Type: application/json

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

#### Start Quiz Attempt
```http
POST /api/v1/quizzes/{quiz_id}/start
Authorization: Bearer {token}
```

#### Submit Answer
```http
POST /api/v1/attempts/{attempt_id}/submit
Authorization: Bearer {token}
Content-Type: application/json

{
  "question_id": 1,
  "option_id": 2
}
```

#### Finish Attempt
```http
POST /api/v1/attempts/{attempt_id}/finish
Authorization: Bearer {token}
```

### Analytics Endpoints

#### Get Leaderboard
```http
GET /api/v1/quizzes/{quiz_id}/leaderboard
Authorization: Bearer {token}
```

#### Get Quiz Statistics
```http
GET /api/v1/quizzes/{quiz_id}/stats
Authorization: Bearer {token}
```

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

### Complete API Documentation
For complete API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

## 🗄️ Database Schema

### Core Tables
- `users` - User accounts with roles
- `quizzes` - Quiz definitions
- `quiz_settings` - Quiz configuration
- `questions` - Question bank
- `question_options` - Answer options
- `quiz_attempts` - Student attempts
- `question_answers` - Individual answers
- `certificates` - Generated certificates
- `webhooks` - Webhook configurations
- `groups` - User groups/classes

For detailed schema documentation, see [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)

## 🧪 Testing

### Using Postman
1. Import `postman-collection.json`
2. Set environment variables
3. Run the collection

### Using Hoppscotch
1. Import `hoppscotch-collection.json`
2. Follow setup guide in `HOPPSCOTCH_SETUP.md`

### Manual Testing
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

## 📊 Project Structure

```
ProptIQ/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── QuizController.php
│   │   │       ├── QuestionController.php
│   │   │       ├── AttemptController.php
│   │   │       ├── LeaderboardController.php
│   │   │       ├── ReportController.php
│   │   │       ├── CertificateController.php
│   │   │       ├── WebhookController.php
│   │   │       └── UserController.php
│   │   └── Middleware/
│   │       └── CheckRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Quiz.php
│   │   ├── Question.php
│   │   ├── QuizAttempt.php
│   │   ├── Certificate.php
│   │   └── ...
│   ├── Services/
│   │   ├── QuizService.php
│   │   ├── GradingService.php
│   │   └── CertificateService.php
│   └── Repositories/
│       └── BaseRepository.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── API_DOCUMENTATION.md
├── DATABASE_SCHEMA.md
├── POSTMAN_SETUP.md
├── postman-collection.json
└── README.md
```

## 🚢 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up HTTPS/SSL
- [ ] Configure CORS settings
- [ ] Set up queue workers for webhooks
- [ ] Configure email service
- [ ] Set up backup strategy
- [ ] Configure monitoring and logging
- [ ] Set up rate limiting

### Environment Variables
```env
APP_NAME=ProptIQ
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=proptiq_production
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

## 🔒 Security

- Token-based authentication (Laravel Sanctum)
- Role-based access control (RBAC)
- Password hashing (bcrypt)
- SQL injection protection (Eloquent ORM)
- CSRF protection
- Rate limiting
- Input validation

## 📈 Performance

- Database indexing on foreign keys
- Eager loading relationships
- Pagination on list endpoints
- Query optimization
- Caching opportunities (Redis/Memcached)

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **EIRSVi** - [GitHub](https://github.com/EIRSVi)

## 🙏 Acknowledgments

- Laravel Framework
- Laravel Sanctum
- MySQL
- Postman/Hoppscotch

## 📞 Support

For issues and questions:
- Create an issue on GitHub
- Check the documentation files
- Review Laravel logs: `storage/logs/laravel.log`

## 🗺️ Roadmap

- [ ] Email notifications (SMTP integration)
- [ ] PDF/Excel export for reports
- [ ] Media file uploads for questions
- [ ] Real-time quiz sessions (WebSockets)
- [ ] Advanced question types (drawing, audio)
- [ ] Team quiz mode
- [ ] LMS integration (Moodle, Canvas)
- [ ] Mobile app support

## 📊 Statistics

- **27 API Endpoints**
- **13 Database Tables**
- **9 Controllers**
- **4 Services**
- **11 Models**
- **Multiple Question Types**

---

**Built with ❤️ using Laravel**
