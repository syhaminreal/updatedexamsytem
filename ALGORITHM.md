# Exam System Algorithms Documentation

This document explains the three key algorithms used in the Online Exam System:

1. **Result Calculation Algorithm** - How exam scores and grades are computed
2. **Location Tracking Algorithm** - How student location is tracked during exams
3. **Password Hashing Algorithm** - How passwords are securely stored and verified

---

## 1. Result Calculation Algorithm

### Overview
The result calculation algorithm processes student answers and computes:
- Total marks obtained
- Percentage score
- Letter grade
- Pass/Fail status

### Process Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    RESULT CALCULATION                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Initialize Variables                                 │
│   - total_questions = count(all_questions)                  │
│   - attempted_questions = 0                                 │
│   - correct_answers = 0                                     │
│   - wrong_answers = 0                                        │
│   - total_marks = 0                                         │
│   - marks_obtained = 0                                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 2: Loop Through Each Question                          │
│                                                             │
│   FOR each question in exam:                                │
│     ├─ Get user's answer from POST                          │
│     ├─ Add question marks to total_marks                    │
│     │                                                        │
│     └─ IF user answered:                                    │
│         ├─ increment attempted_questions                   │
│         ├─ IF answer == correct_answer:                   │
│         │   ├─ increment correct_answers                    │
│         │   ├─ marks_obtained += question.marks            │
│         │   └─ is_correct = 1                              │
│         └─ ELSE:                                            │
│             ├─ increment wrong_answers                     │
│             └─ is_correct = 0                               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 3: Calculate Percentage                                │
│                                                             │
│   percentage = (marks_obtained / total_marks) × 100        │
│                                                             │
│   Example:                                                  │
│   - marks_obtained = 75                                     │
│   - total_marks = 100                                       │
│   - percentage = (75/100) × 100 = 75%                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 4: Calculate Grade                                     │
│                                                             │
│   IF percentage >= 90 → Grade = 'A+'                        │
│   ELSE IF percentage >= 80 → Grade = 'A'                    │
│   ELSE IF percentage >= 70 → Grade = 'B'                    │
│   ELSE IF percentage >= 60 → Grade = 'C'                   │
│   ELSE IF percentage >= 50 → Grade = 'D'                    │
│   ELSE → Grade = 'F'                                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 5: Determine Pass/Fail Status                          │
│                                                             │
│   IF marks_obtained >= passing_marks:                      │
│       status = 'PASS'                                       │
│   ELSE:                                                    │
│       status = 'FAIL'                                       │
│                                                             │
│   Note: passing_marks is set per exam (default: 40)        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 6: Save Results to Database                            │
│                                                             │
│   INSERT INTO exam_results (                                │
│       attempt_id, exam_id, student_name,                   │
│       total_questions, attempted_questions,                  │
│       correct_answers, wrong_answers,                       │
│       total_marks, marks_obtained, percentage,             │
│       grade, status                                        │
│   ) VALUES (...)                                           │
└─────────────────────────────────────────────────────────────┘
```

### Implementation Details

**File:** [`submit_exam.php`](updatedexamsytem/submit_exam.php:45-130)

```php
// Step 2: Loop through questions
foreach ($questions as $question) {
    $user_answer = $_POST['answer_' . $question['question_id']] ?? null;
    $total_marks += $question['marks'];
    
    if ($user_answer !== null) {
        $attempted_questions++;
        
        if (strtoupper($user_answer) == strtoupper($question['correct_answer'])) {
            $correct_answers++;
            $marks_obtained += $question['marks'];
            $is_correct = 1;
        } else {
            $wrong_answers++;
        }
    }
}

// Step 3: Calculate percentage
$percentage = round(($marks_obtained / $total_marks) * 100, 2);

// Step 4: Calculate grade
if ($percentage >= 90) $grade = 'A+';
elseif ($percentage >= 80) $grade = 'A';
elseif ($percentage >= 70) $grade = 'B';
elseif ($percentage >= 60) $grade = 'C';
elseif ($percentage >= 50) $grade = 'D';
else $grade = 'F';

// Step 5: Pass/Fail status
$status = ($marks_obtained >= $exam['passing_marks']) ? 'PASS' : 'FAIL';
```

### Grade Thresholds Table

| Percentage Range | Grade | Description |
|-----------------|-------|-------------|
| 90 - 100% | A+ | Excellent |
| 80 - 89% | A | Very Good |
| 70 - 79% | B | Good |
| 60 - 69% | C | Satisfactory |
| 50 - 59% | D | Pass |
| 0 - 49% | F | Fail |

---

## 2. Location Tracking Algorithm

### Overview
The location tracking algorithm uses browser Geolocation API to:
- Track student location during exam
- Calculate distance to exam center
- Detect if student is within allowed area

### Process Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  LOCATION TRACKING                          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Request Location Permission                         │
│                                                             │
│   IF navigator.geolocation exists:                         │
│       navigator.geolocation.getCurrentPosition()           │
│   ELSE:                                                     │
│       Show error "Geolocation not supported"               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 2: Get Current Position                               │
│                                                             │
│   navigator.geolocation.watchPosition(                    │
│     successCallback,                                       │
│     errorCallback,                                        │
│     { enableHighAccuracy: true,                           │
│       timeout: 10000,                                      │
│       maximumAge: 0 }                                      │
│   )                                                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 3: Calculate Distance                                 │
│                                                             │
│   Using Haversine Formula:                                  │
│                                                             │
│   a = sin²(Δlat/2) + cos(lat1) × cos(lat2) × sin²(Δlon/2) │
│   c = 2 × atan2(√a, √(1-a))                               │
│   distance = R × c                                         │
│                                                             │
│   Where R = Earth's radius (6371 km)                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 4: Update Display                                      │
│                                                             │
│   - Update map with new position                           │
│   - Draw line to exam center                               │
│   - Update distance display                                │
│   - Store location history                                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ Step 5: Continuous Monitoring                              │
│                                                             │
│   REPEAT every 5 seconds:                                  │
│     - Get new position                                     │
│     - Calculate new distance                               │
│     - Check if within allowed radius                       │
│     - IF outside → ALERT & LOG                             │
└─────────────────────────────────────────────────────────────┘
```

### Implementation Details

**File:** [`exam_center.php`](updatedexamsytem/exam_center.php:614-670)

```javascript
// Step 2: Start watching position
locationWatcher = navigator.geolocation.watchPosition(
    position => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        // Calculate distance to exam center
        const distance = calculateDistance(lat, lng, examCenterLat, examCenterLng);
        
        // Update map
        updateMapPosition(lat, lng);
        
        // Check if outside allowed area
        if (distance > allowedRadius) {
            logViolation(lat, lng, distance);
            showAlert('Student outside allowed area!');
        }
    },
    error => handleLocationError(error),
    { enableHighAccuracy: true, timeout: 10000 }
);

// Step 3: Haversine formula for distance
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}
```

### Distance Thresholds

| Distance from Center | Action |
|---------------------|--------|
| 0 - 100m | Normal - Green indicator |
| 100m - 500m | Warning - Yellow indicator |
| > 500m | Violation - Red alert logged |

---

## 3. Password Hashing Algorithm

### Overview
The system uses PHP's built-in `password_hash()` function with bcrypt algorithm (PASSWORD_BCRYPT) to:
- Securely hash passwords for storage
- Verify passwords during login
- Support future password migration

### Process Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  PASSWORD HASHING                           │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│   REGISTRATION         │     │   LOGIN                │
│   (Hash Password)      │     │   (Verify Password)    │
└─────────────────────────┘     └─────────────────────────┘
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│ Step 1: Get Password    │     │ Step 1: Get Input       │
│                         │     │                         │
│ $password = $_POST[     │     │ $password = $_POST[    │
│   'password']          │     │   'password']           │
└─────────────────────────┘     └─────────────────────────┘
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│ Step 2: Hash Password  │     │ Step 2: Fetch Hash      │
│                         │     │                         │
│ $hash = password_hash( │     │ $stmt = $pdo->prepare( │
│   $password,           │     │   "SELECT * FROM       │
│   PASSWORD_DEFAULT     │     │    users WHERE email   │
│ );                     │     │    = ?");              │
│                         │     │ $user = $stmt->fetch() │
└─────────────────────────┘     └─────────────────────────┘
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│ Step 3: Store Hash      │     │ Step 3: Verify          │
│                         │     │                         │
│ $stmt = $pdo->prepare( │     │ if (password_verify(    │
│   "INSERT INTO users   │     │   $password,            │
│    (password_hash)     │     │   $user['password_hash'])│
│    VALUES (?)");       │     │ ) { // login success   │
│ $stmt->execute([$hash])│     │                        │
└─────────────────────────┘     └─────────────────────────┘
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│ Hashed password stored  │     │ Re-hash if needed       │
│ in database            │     │                         │
│                         │     │ if (password_needs_     │
│ Example hash:           │     │   rehash($user['hash']))│
│ $2y$10$92IXUNpkjO0r    │     │ {                       │
│ OQ5byMi.Ye4oKoEa3R     │     │   $newHash = password_ │
│ o9llC/.og/at2.uheWG/   │     │   hash($password);      │
│ igi                    │     │   update($newHash);     │
└─────────────────────────┘     └─────────────────────────┘
```

### Implementation Details

**Registration (Hash):** [`register.php`](updatedexamsytem/register.php:54-60)

```php
// Step 2: Hash password using bcrypt
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Step 3: Store in database
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, ...) VALUES (?, ?, ?, ...)");
$stmt->execute([$username, $email, $password_hash, ...]);
```

**Login (Verify):** [`login.php`](updatedexamsytem/login.php:61-63)

```php
// Fetch user and verify password
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify using password_verify()
if (!password_verify($password, $user['password_hash'])) {
    $error = "Invalid password!";
}
```

### Hash Format Explained

The bcrypt hash format is: `$2y$10$...`

| Part | Meaning |
|------|---------|
| `$2y$` | Algorithm identifier (bcrypt) |
| `10$` | Cost factor (2^10 = 1024 iterations) |
| `...` | 22-character salt + 31-character hash |

### Security Features

1. **Salt**: Automatically generated for each password
2. **Cost Factor**: 10 iterations (configurable)
3. **Backward Compatibility**: PASSWORD_DEFAULT auto-upgrades
4. **Rehash Check**: Automatically rehashes when needed

---

## Database Schema

### Relevant Tables

```sql
-- Users table (contains password_hash)
CREATE TABLE users (
    user_id INT PRIMARY KEY,
    username VARCHAR(50),
    email VARCHAR(100),
    password_hash VARCHAR(255),  -- bcrypt hash
    full_name VARCHAR(100),
    user_type ENUM('student', 'teacher', 'admin'),
    ...
);

-- Exam results table
CREATE TABLE exam_results (
    result_id INT PRIMARY KEY,
    attempt_id INT,
    exam_id INT,
    total_questions INT,
    attempted_questions INT,
    correct_answers INT,
    wrong_answers INT,
    total_marks INT,
    marks_obtained INT,
    percentage DECIMAL(5,2),
    grade VARCHAR(2),
    status ENUM('PASS', 'FAIL'),
    completed_at TIMESTAMP
);

-- Exam attempts (contains ip_address for location tracking)
CREATE TABLE exam_attempts (
    attempt_id INT PRIMARY KEY,
    exam_id INT,
    student_name VARCHAR(100),
    ip_address VARCHAR(45),  -- IPv4/IPv6 address
    started_at TIMESTAMP,
    completed_at DATETIME
);
```

---

## Summary

| Algorithm | Purpose | Key Function |
|-----------|---------|--------------|
| Result Calculation | Compute scores & grades | percentage = (marks / total) × 100 |
| Location Tracking | Monitor student location | Haversine distance formula |
| Password Hashing | Secure password storage | bcrypt via password_hash() |

All three algorithms work together to create a secure, feature-rich examination system.
