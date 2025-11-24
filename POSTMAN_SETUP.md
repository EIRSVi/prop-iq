# Postman Collection Setup Guide

## 📦 Import Collection

### Step 1: Import into Postman
1. Open Postman Desktop or Web
2. Click **Import** button (top left)
3. Select **File** tab
4. Choose `postman-collection.json`
5. Click **Import**

### Step 2: Create Environment (Optional)
The collection includes built-in variables, but you can also create an environment:

1. Click **Environments** (left sidebar)
2. Click **+** to create new environment
3. Name it "ProptIQ Local"
4. Add variables:
   - `token` (leave empty)
   - `quiz_id` (leave empty)
   - `question_id` (leave empty)
   - `attempt_id` (leave empty)
   - `certificate_code` (leave empty)
5. Click **Save**
6. Select the environment from dropdown (top right)

---

## 🚀 Quick Start Testing

### Complete Workflow

#### 1. Register & Login
```
1. Run: Authentication > Register Teacher
2. Run: Authentication > Login
   ✅ Token automatically saved to {{token}} variable
3. Run: Authentication > Get Current User (verify token works)
```

#### 2. Create Quiz
```
4. Run: Quizzes > Create Quiz - Public
   ✅ Quiz ID automatically saved to {{quiz_id}} variable
5. Run: Quizzes > Get Quiz (verify quiz created)
```

#### 3. Add Questions
```
6. Run: Questions > Add MCQ Question
   ✅ Question ID automatically saved to {{question_id}} variable
7. Run: Questions > Add True/False Question
8. Run: Questions > Add Open Question
```

#### 4. Publish Quiz
```
9. Run: Quizzes > Update Quiz
   (Body already set to publish: "status": "published")
```

#### 5. Take Quiz (as Student)
```
10. Run: Authentication > Register Student
11. Run: Authentication > Login (with student credentials)
    ✅ New token saved
12. Run: Quiz Attempts > Start Quiz (Public)
    ✅ Attempt ID automatically saved to {{attempt_id}} variable
13. Run: Quiz Attempts > Submit Answer (MCQ)
14. Run: Quiz Attempts > Submit Answer (Open)
15. Run: Quiz Attempts > Finish Attempt
```

#### 6. View Results
```
16. Run: Analytics & Reports > Get Leaderboard
17. Run: Analytics & Reports > Get Quiz Statistics
18. Run: Certificates > Generate Certificate
    ✅ Certificate code automatically saved
19. Run: Certificates > Verify Certificate
```

---

## 🎯 Features

### Auto-Save Variables
The collection includes **Test Scripts** that automatically save important IDs:

- **Login** → Saves `token`
- **Create Quiz** → Saves `quiz_id`
- **Add Question** → Saves `question_id`
- **Start Attempt** → Saves `attempt_id`
- **Generate Certificate** → Saves `certificate_code`

### Pre-configured Headers
All requests include:
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

### Environment Variables
Collection uses these variables:
- `{{token}}` - Authentication token
- `{{quiz_id}}` - Current quiz ID
- `{{question_id}}` - Current question ID
- `{{attempt_id}}` - Current attempt ID
- `{{certificate_code}}` - Certificate verification code

---

## 📋 Collection Structure

### 1. Authentication (6 requests)
- Register Student
- Register Teacher
- Register Admin
- Login (auto-saves token)
- Get Current User
- Logout

### 2. Quizzes (6 requests)
- List Quizzes
- Create Quiz - Public (auto-saves quiz_id)
- Create Quiz - Password Protected
- Get Quiz
- Update Quiz
- Delete Quiz

### 3. Questions (5 requests)
- Add MCQ Question (auto-saves question_id)
- Add True/False Question
- Add Open Question
- Update Question
- Delete Question

### 4. Quiz Attempts (5 requests)
- Start Quiz (Public) (auto-saves attempt_id)
- Start Quiz (With Password)
- Submit Answer (MCQ)
- Submit Answer (Open)
- Finish Attempt

### 5. Analytics & Reports (2 requests)
- Get Leaderboard
- Get Quiz Statistics

### 6. Certificates (2 requests)
- Generate Certificate (auto-saves certificate_code)
- Verify Certificate

### 7. Webhooks (3 requests)
- List Webhooks
- Create Webhook
- Delete Webhook

### 8. User Management - Admin (4 requests)
- List Users
- Create User
- Update User
- Delete User

**Total: 33 API Endpoints**

---

## 💡 Tips & Tricks

### 1. View Saved Variables
- Click **Collections** tab
- Select "ProptIQ REST API"
- Click **Variables** tab
- See all auto-saved values

### 2. Manual Variable Update
If auto-save doesn't work:
1. Copy value from response
2. Go to **Collections** > **Variables**
3. Paste into **Current Value** column
4. Click **Save**

### 3. Test Multiple Users
Create multiple environments:
- "Teacher Account" (with teacher token)
- "Student Account" (with student token)
- "Admin Account" (with admin token)

Switch between them using the environment dropdown.

### 4. Run Collection
Test all endpoints at once:
1. Click **...** next to collection name
2. Select **Run collection**
3. Choose requests to run
4. Click **Run ProptIQ REST API**

### 5. View Console
See auto-save logs:
1. Click **Console** (bottom left)
2. View "Token saved:", "Quiz ID saved:", etc.

---

## 🔧 Customization

### Add Custom Test Scripts
Edit any request → **Tests** tab:

```javascript
// Example: Verify response status
pm.test("Status is 200", function () {
    pm.response.to.have.status(200);
});

// Example: Verify response has data
pm.test("Response has data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('id');
});
```

### Add Pre-request Scripts
Edit any request → **Pre-request Script** tab:

```javascript
// Example: Log current timestamp
console.log("Request sent at:", new Date().toISOString());

// Example: Generate random email
pm.environment.set("random_email", "user" + Math.random() + "@example.com");
```

---

## 🐛 Troubleshooting

### Issue: Token not saved
**Solution:**
1. Check **Console** for errors
2. Manually copy token from Login response
3. Paste into **Variables** tab
4. Click **Save**

### Issue: 401 Unauthorized
**Solution:**
1. Check if `{{token}}` variable is set
2. Re-run **Login** request
3. Verify token in **Variables** tab

### Issue: 404 Not Found
**Solution:**
1. Verify resource ID exists
2. Check `{{quiz_id}}`, `{{question_id}}`, etc. are set
3. Create resource first if needed

### Issue: 422 Validation Error
**Solution:**
1. Check request **Body** tab
2. Verify all required fields
3. Check field types match API requirements

---

## 📊 Testing Scenarios

### Scenario 1: Password Protected Quiz
```
1. Create Quiz - Password Protected
2. Try Start Quiz (Public) → Should fail
3. Start Quiz (With Password) → Should succeed
```

### Scenario 2: Leaderboard
```
1. Create quiz with 3 students
2. Each student takes quiz
3. View Leaderboard → See rankings
```

### Scenario 3: Certificate Generation
```
1. Complete quiz with passing score
2. Generate Certificate → Success
3. Complete quiz with failing score
4. Generate Certificate → Fail
```

---

## 🎓 Best Practices

1. **Use Folders**: Organize requests by feature
2. **Name Clearly**: Use descriptive request names
3. **Add Descriptions**: Document what each request does
4. **Use Variables**: Avoid hardcoding IDs
5. **Test Scripts**: Validate responses automatically
6. **Share Collection**: Export and share with team

---

## 📖 Additional Resources

- **API Documentation**: `API_DOCUMENTATION.md`
- **Database Schema**: `DATABASE_SCHEMA.md`
- **Walkthrough**: `walkthrough.md`
- **Laravel Logs**: `storage/logs/laravel.log`

---

## ✅ Checklist

- [ ] Import collection
- [ ] Create environment (optional)
- [ ] Register teacher account
- [ ] Login and verify token saved
- [ ] Create quiz and verify quiz_id saved
- [ ] Add questions
- [ ] Publish quiz
- [ ] Register student account
- [ ] Take quiz as student
- [ ] View leaderboard
- [ ] Generate certificate

---

## 🎉 Ready to Test!

Your Postman collection is ready with:
- ✅ 33 pre-configured endpoints
- ✅ Auto-save variables
- ✅ Full authentication flow
- ✅ Complete quiz workflow
- ✅ Analytics and certificates

Happy testing! 🚀
