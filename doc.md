le: draw.md - System Overview for Diagram Generation
# 📝 Online Exam System - Comprehensive System Overview

> **Use this document as a prompt to generate various architectural diagrams.**
> Each section below contains structured information for specific diagram types.

---

## 📋 System Identity & Purpose

| Attribute | Description |
|-----------|-------------|
| **System Name** | Online Exam System |
| **Type** | Web-based Examination Management Platform |
| **Primary Purpose** | Enable educational institutions to conduct secure online examinations with automatic grading, location tracking, and result management |
| **Target Users** | Students, Teachers, Administrators |
| **Deployment** | PHP/MySQL web application |

---

## 🏗️ System Architecture Overview

### Technology Stack
┌─────────────────────────────────────────────────────────────┐ │ TECHNOLOGY STACK │ ├─────────────────────────────────────────────────────────────┤ │ Frontend Layer │ │ ├── HTML5 + CSS3 (Bootstrap 5.1.3) │ │ ├── JavaScript (ES6+) │ │ ├── Font Awesome Icons │ │ └── Responsive Design │ ├─────────────────────────────────────────────────────────────┤ │ Backend Layer │ │ ├── PHP 7.4+ (Session-based authentication) │ │ ├── PDO (Database abstraction) │ │ └── REST-like endpoints (AJAX-based) │ ├─────────────────────────────────────────────────────────────┤ │ Database Layer │ │ ├── MySQL/MariaDB │ │ ├── Database: exaam │ │ └── UTF8MB4 character set │ ├─────────────────────────────────────────────────────────────┤ │ External APIs/Services │ │ ├── Browser Geolocation API (Location tracking) │ │ └── Leaflet/OpenStreetMap (Maps visualization) │ └─────────────────────────────────────────────────────────────┘


---

## 👥 User Roles & Hierarchy

```mermaid
graph TD
    A[System Users] --> B[Students]
    A --> C[Staff/Teachers]
    A --> D[Administrators]
    
    B --> B1[View Available Exams]
    B --> B2[Take Exams]
    B --> B3[View Results]
    B --> B4[View Leaderboard]
    B --> B5[Manage Profile]
    
    C --> C1[Create Exams]
    C --> C2[Manage Questions]
    C --> C3[View Student Results]
    C --> C4[View Leaderboards]
    C --> C5[Exam Analytics]
    
    D --> D1[Full System Access]
    D --> D2[User Management]
    D --> D3[System Configuration]
    D --> D4[All Staff Privileges]
    
    style A fill:#667eea,color:#fff
    style B fill:#28a745,color:#fff
    style C fill:#ffc107,color:#000
    style D fill:#dc3545,color:#fff
🗄️ Database Schema (Entity Relationship)
erDiagram
    USERS {
        int user_id PK
        string username UK
        string email UK
        string password_hash
        string full_name
        enum user_type
        string phone
        string profile_image
        boolean is_active
        datetime last_login
        timestamp created_at
        timestamp updated_at
    }
    
    ADMIN_USERS {
        int admin_id PK
        string username UK
        string email UK
        string password_hash
        string full_name
        string role
        boolean is_active
        datetime last_login
        timestamp created_at
    }
    
    EXAMS {
        int exam_id PK
        string exam_title
        text exam_description
        int exam_duration
        int total_marks
        int passing_marks
        enum exam_status
        int created_by FK
        boolean is_deleted
        timestamp created_at
    }
    
    QUESTIONS {
        int question_id PK
        int exam_id FK
        text question_text
        text option_a
        text option_b
        text option_c
        text option_d
        json options
        enum correct_answer
        int marks
        timestamp created_at
    }
    
    EXAM_ATTEMPTS {
        int attempt_id PK
        int exam_id FK
        string student_name
        string student_email
        string student_roll
        timestamp started_at
        datetime completed_at
        string ip_address
    }
    
    USER_ANSWERS {
        int answer_id PK
        int attempt_id FK
        int question_id FK
        string user_answer
        boolean is_correct
        int marks_obtained
        timestamp created_at
    }
    
    EXAM_RESULTS {
        int result_id PK
        int attempt_id FK
        int exam_id FK
        string student_name
        int total_questions
        int attempted_questions
        int correct_answers
        int wrong_answers
        int total_marks
        int marks_obtained
        decimal percentage
        string grade
        enum status
        timestamp completed_at
    }
    
    USERS ||--o{ EXAMS : "creates"
    EXAMS ||--o{ QUESTIONS : "contains"
    EXAMS ||--o{ EXAM_ATTEMPTS : "has"
    EXAM_ATTEMPTS ||--o{ USER_ANSWERS : "records"
    EXAM_ATTEMPTS ||--o{ EXAM_RESULTS : "produces"
    QUESTIONS ||--o{ USER_ANSWERS : "answered_in"
📁 File Structure & Module Organization
updatedexamsytem/
│
├── 🌐 PUBLIC PAGES (Student-Facing)
│   ├── index.php                 # Landing page - List available exams
│   ├── start_exam.php            # Exam initiation & student info collection
│   ├── take_exam.php             # Main exam interface with timer
│   ├── submit_exam.php           # Exam submission & auto-grading
│   ├── results.php               # Individual result display
│   ├── leaderboard.php           # Public leaderboard view
│   ├── home.php                  # Student dashboard
│   ├── profile.php               # Student profile view
│   ├── edit_profile.php          # Profile editing
│   ├── change_password.php       # Password management
│   ├── login.php                 # Student login
│   ├── register.php              # Student registration
│   └── logout.php                # Session termination
│
├── 🛡️ ADMIN MODULE
│   ├── login.php                 # Admin authentication
│   ├── index.php                 # Admin dashboard
│   ├── create_exam.php           # Exam creation
│   ├── edit_exam.php             # Exam modification
│   ├── exam_crud.php             # Exam CRUD operations
│   ├── add_question.php          # Question addition
│   ├── edit_question.php         # Question editing
│   ├── mange_question.php        # Question management
│   ├── view_exams.php            # Exam listing
│   ├── db_connection.php         # Database connection
│   └── config.php                # Admin configuration
│
├── 👨‍🏫 STAFF MODULE
│   ├── login.php                 # Staff authentication
│   ├── index.php                 # Staff dashboard
│   ├── create_exam.php           # Create exams
│   ├── edit_exam.php             # Modify exams
│   ├── manage_exams.php          # Exam management
│   ├── delete_exam.php           # Exam deletion
│   ├── add_question.php          # Add questions
│   ├── edit_question.php         # Edit questions
│   ├── manage_questions.php      # Question management
│   ├── delete_question.php       # Question deletion
│   ├── view_results.php          # View all results
│   ├── leaderboard.php           # Staff leaderboard view
│   ├── create_admin.php          # Admin creation utility
│   ├── admin_reg.php             # Admin registration
│   ├── logout.php                # Staff logout
│   └── db_connection.php         # Staff DB connection
│
├── 🔧 CORE COMPONENTS
│   ├── db_connection.php         # Main database connection
│   ├── style.css                 # Global styles
│   └── exam_center.php           # Location tracking module
│
├── 🗄️ DATABASE
│   └── database.sql              # Complete schema & seed data
│
└── 📚 DOCUMENTATION
    ├── ALGORITHM.md              # Algorithm documentation
    ├── README.md                 # Project readme
    └── draw.md                   # This file
🔄 Data Flow Diagrams
1. Student Exam Taking Flow
sequenceDiagram
    participant S as Student
    participant I as index.php
    participant St as start_exam.php
    participant T as take_exam.php
    participant Sub as submit_exam.php
    participant R as results.php
    participant DB as Database
    
    S->>I: Browse available exams
    I->>DB: SELECT * FROM exams
    DB-->>I: Return exam list
    I-->>S: Display exams
    
    S->>St: Click "Start Exam"
    S->>St: Enter name, email, roll
    
    St->>DB: INSERT INTO exam_attempts
    DB-->>St: Return attempt_id
    St->>St: Create session variables
    St-->>S: Redirect to exam
    
    S->>T: Load exam interface
    T->>DB: SELECT * FROM questions
    DB-->>T: Return questions
    T-->>S: Display questions with timer
    
    loop Every Answer Selection
        S->>T: Select option
        T->>DB: INSERT/UPDATE user_answers
    end
    
    S->>Sub: Submit exam / Time expires
    Sub->>DB: SELECT correct answers
    DB-->>Sub: Return answers
    
    Sub->>Sub: Calculate score, percentage, grade
    
    Sub->>DB: INSERT INTO exam_results
    DB-->>Sub: Confirm save
    
    Sub-->>S: Redirect to results
    
    S->>R: View results
    R->>DB: SELECT exam_results
    DB-->>R: Return result data
    R-->>S: Display certificate & analytics
2. Admin/Staff Exam Management Flow
sequenceDiagram
    participant A as Admin/Staff
    participant L as login.php
    participant D as Dashboard
    participant M as Management Pages
    participant DB as Database
    
    A->>L: Enter credentials
    L->>DB: SELECT * FROM admin_users
    DB-->>L: Return user data
    L->>L: password_verify()
    L->>L: Create session
    L-->>A: Redirect to dashboard
    
    A->>D: Access dashboard
    D-->>A: Show statistics & options
    
    alt Create Exam
        A->>M: create_exam.php
        M-->>A: Display form
        A->>M: Submit exam details
        M->>DB: INSERT INTO exams
        DB-->>M: Confirm
        M-->>A: Success message
    end
    
    alt Add Questions
        A->>M: add_question.php
        M-->>A: Display question form
        A->>M: Submit question data
        M->>DB: INSERT INTO questions
        DB-->>M: Confirm
        M-->>A: Success message
    end
    
    alt View Results
        A->>M: view_results.php
        M->>DB: SELECT exam_results
        DB-->>M: Return results
        M-->>A: Display results table
    end
🎨 UI/UX Component Architecture
graph TB
    subgraph "Frontend Components"
        NAV[Navigation Bar]
        CARD[Exam Cards]
        TIMER[Exam Timer]
        FORM[Question Forms]
        MODAL[Modal Dialogs]
        MAP[Location Map]
        CHART[Result Charts]
    end
    
    subgraph "Styling System"
        BS[Bootstrap 5.1.3]
        FA[Font Awesome]
        CSS[Custom CSS]
        
        BS -->|Grids & Layout| NAV
        BS -->|Cards & Modals| CARD
        BS -->|Progress Bars| TIMER
        BS -->|Forms| FORM
        FA -->|Icons| NAV
        FA -->|Icons| CARD
        CSS -->|Custom Styles| TIMER
        CSS -->|Animations| CARD
    end
    
    subgraph "JavaScript Modules"
        AUTH[Auth Validation]
        TIMER_JS[Timer Logic]
        NAV_JS[Question Navigation]
        SAVE[Auto-save Answers]
        LOC[Location Tracker]
        
        TIMER_JS -->|Update Display| TIMER
        NAV_JS -->|Switch Questions| FORM
        SAVE -->|Background Sync| DB[(Database)]
        LOC -->|Geolocation API| MAP
    end
🔐 Security Architecture
flowchart TD
    A[User Request] --> B{Authentication Check}
    
    B -->|Not Logged In| C[Redirect to Login]
    B -->|Session Valid| D{Authorization Check}
    
    D -->|Insufficient Privileges| E[Access Denied]
    D -->|Authorized| F[Process Request]
    
    F --> G[Input Validation]
    G -->|Invalid| H[Error Response]
    G -->|Valid| I[Database Operation]
    
    I --> J{SQL Injection Protection}
    J -->|PDO Prepared Statements| K[Execute Query]
    
    K --> L[Output Encoding]
    L -->|htmlspecialchars| M[Safe Response]
    
    C --> N[End]
    E --> N
    H --> N
    M --> N
    
    style A fill:#667eea,color:#fff
    style B fill:#ffc107,color:#000
    style D fill:#ffc107,color:#000
    style F fill:#28a745,color:#fff
    style E fill:#dc3545,color:#fff
    style H fill:#dc3545,color:#fff
⚡ Core Algorithms
1. Result Calculation Algorithm
flowchart TD
    A[Start Exam Submission] --> B[Initialize Variables]
    B --> C[Loop Through Each Question]
    
    C --> D{User Answered?}
    D -->|Yes| E[Increment Attempted]
    D -->|No| C
    
    E --> F{Answer Correct?}
    F -->|Yes| G[Add Marks & Increment Correct]
    F -->|No| H[Increment Wrong]
    
    G --> I{More Questions?}
    H --> I
    
    I -->|Yes| C
    I -->|No| J[Calculate Percentage]
    
    J --> K[Calculate Grade]
    K --> L{Pass or Fail?}
    L -->|Marks >= Passing| M[Status: PASS]
    L -->|Marks < Passing| N[Status: FAIL]
    
    M --> O[Save to Database]
    N --> O
    O --> P[Display Results]
    
    style A fill:#667eea,color:#fff
    style P fill:#28a745,color:#fff
    style M fill:#28a745,color:#fff
    style N fill:#dc3545,color:#fff
2. Location Tracking Algorithm
flowchart TD
    A[Request Geolocation Permission] --> B{Browser Support?}
    B -->|No| C[Show Error]
    B -->|Yes| D[Get Current Position]
    
    D --> E[Calculate Distance<br/>Haversine Formula]
    E --> F{Within Allowed Radius?}
    
    F -->|Yes < 100m| G[Green Status]
    F -->|Warning 100-500m| H[Yellow Status]
    F -->|Violation > 500m| I[Red Alert & Log]
    
    G --> J[Update Map Display]
    H --> J
    I --> J
    
    J --> K{Exam Still Active?}
    K -->|Yes| D
    K -->|No| L[Stop Tracking]
    
    C --> M[End]
    L --> M
    
    style G fill:#28a745,color:#fff
    style H fill:#ffc107,color:#000
    style I fill:#dc3545,color:#fff
3. Password Security Flow
flowchart LR
    subgraph Registration
        R1[User Input Password] --> R2[password_hash<br/>bcrypt]
        R2 --> R3[Store Hash in DB]
    end
    
    subgraph Login
        L1[User Input Password] --> L2[Fetch Hash from DB]
        L2 --> L3[password_verify]
        L3 -->|Valid| L4[Create Session]
        L3 -->|Invalid| L5[Error Message]
    end
    
    R3 -.->|Stored Hash| L2
    
    style R2 fill:#667eea,color:#fff
    style L3 fill:#28a745,color:#fff
    style L5 fill:#dc3545,color:#fff
🌐 API Endpoints & Interactions
Endpoint	Method	Purpose	Access
index.php	GET	List active exams	Public
start_exam.php	GET/POST	Initiate exam attempt	Public
take_exam.php	GET	Load exam questions	Session
take_exam.php	POST	Save answer (AJAX)	Session
submit_exam.php	POST	Submit & grade exam	Session
results.php	GET	View results	Public/Session
leaderboard.php	GET	View rankings	Public
staff/login.php	POST	Staff authentication	Public
staff/create_exam.php	POST	Create new exam	Staff/Admin
staff/add_question.php	POST	Add exam questions	Staff/Admin
📊 System Metrics & KPIs
graph LR
    subgraph "Performance Metrics"
        A1[Page Load Time<br/>< 2s]
        A2[Database Query Time<br/>< 100ms]
        A3[Timer Accuracy<br/>±1s]
    end
    
    subgraph "Security Metrics"
        B1[Password Hashing<br/>bcrypt]
        B2[SQL Injection Prevention<br/>100%]
        B3[XSS Prevention<br/>htmlspecialchars]
    end
    
    subgraph "Business Metrics"
        C1[Concurrent Users<br/>50+]
        C2[Exam Completion Rate<br/>Trackable]
        C3[Average Score<br/>Analytics]
    end
    
    A1 --> D[System Health]
    A2 --> D
    A3 --> D
    B1 --> D
    B2 --> D
    B3 --> D
    C1 --> D
    C2 --> D
    C3 --> D
    
    style D fill:#667eea,color:#fff
🎯 Use Case Diagrams
Student Use Cases
graph LR
    S((Student)) --> UC1[Browse Exams]
    S --> UC2[Start Exam]
    S --> UC3[Answer Questions]
    S --> UC4[Submit Exam]
    S --> UC5[View Results]
    S --> UC6[View Leaderboard]
    S --> UC7[Manage Profile]
    
    UC2 --> UC3
    UC3 --> UC4
    UC4 --> UC5
    
    style S fill:#667eea,color:#fff
Admin Use Cases
graph LR
    A((Admin)) --> UC1[Create Exams]
    A --> UC2[Manage Questions]
    A --> UC3[View All Results]
    A --> UC4[Manage Users]
    A --> UC5[System Configuration]
    A --> UC6[View Analytics]
    A --> UC7[Export Data]
    
    UC1 --> UC2
    
    style A fill:#dc3545,color:#fff
🚀 Deployment Architecture
graph TB
    subgraph "Client Layer"
        C1[Web Browser<br/>Chrome/Firefox/Safari]
        C2[Mobile Browser<br/>iOS/Android]
    end
    
    subgraph "Web Server Layer"
        WS[Apache/Nginx<br/>PHP 7.4+]
    end
    
    subgraph "Application Layer"
        APP[Online Exam System<br/>PHP Application]
    end
    
    subgraph "Data Layer"
        DB[(MySQL/MariaDB<br/>exaam Database)]
        CACHE[Session Storage<br/>$_SESSION]
    end
    
    C1 -->|HTTP/HTTPS| WS
    C2 -->|HTTP/HTTPS| WS
    WS -->|PHP Processing| APP
    APP -->|PDO| DB
    APP -->|Read/Write| CACHE
    
    style WS fill:#667eea,color:#fff
    style APP fill:#28a745,color:#fff
    style DB fill:#ffc107,color:#000
📖 Prompt Templates for AI Diagram Generation
Generate High-Level Architecture Diagram
"Using the system overview above, create a high-level system architecture diagram showing the three-tier architecture (Presentation, Application, Data) with all major components and their interactions."

Generate Database ER Diagram
"Generate a detailed Entity-Relationship diagram based on the database schema provided, showing all tables, primary keys, foreign keys, and relationships with cardinality."

Generate User Flow Diagram
"Create a user flow diagram for a student taking an exam from start to finish, including decision points and alternative flows (timeout, early submission, etc.)."

Generate Sequence Diagram
"Generate a sequence diagram showing the interaction between student, web server, and database during exam submission and grading."

Generate Component Diagram
"Create a component diagram showing all PHP files/modules as components and their dependencies/connections."

Generate Security Flow Diagram
"Generate a security flow diagram showing authentication, authorization, and data protection mechanisms throughout the system."

Generate Deployment Diagram
"Create a deployment diagram showing how the system components are deployed across web server, application server, and database server."

🔗 Quick Reference: Key Files for Diagram Context
Diagram Type	Key Files to Reference
System Architecture	db_connection.php, index.php, folder structure
Database Schema	database.sql
User Flow	index.php → start_exam.php → take_exam.php → submit_exam.php → results.php
Admin Flow	staff/login.php → staff/index.php → staff/create_exam.php
Security	login.php, register.php, password_hash() usage
Algorithms	ALGORITHM.md, submit_exam.php (grading), exam_center.php (location)
📝 Notes for Diagram Generation
Color Coding Suggestions:
Primary: #667eea (Purple - Main brand)
Success: #28a745 (Green - Pass/Active)
Warning: #ffc107 (Yellow - Caution)
Danger: #dc3545 (Red - Fail/Error)
Info: #17a2b8 (Blue - Information)
Icons to Use:
Students: 👨‍🎓 or 🎓
Exams: 📝 or 📋
Database: 🗄️ or 💾
Security: 🔐 or 🛡️
Admin: 👨‍💼 or ⚙️
Layout Preferences:
Top-to-bottom flow for process diagrams
Left-to-right flow for data flow
Hierarchical for architecture diagrams
Centralized hub-and-spoke for ER diagrams
Last Updated: March 2026 System Version: Online Exam System v1.0


---

## How to Use This Document

1. **Copy the content above** into a file named `draw.md` in your project root directory.

2. **Use as AI Prompt**: Feed this document to AI tools (ChatGPT, Claude, etc.) along with a specific request like:
   - "Generate a system architecture diagram based on this overview"
   - "Create a detailed ER diagram from the database schema section"
   - "Draw the student exam-taking user flow"

3. **Generate Mermaid Diagrams**: The document includes Mermaid syntax that can be rendered in:
   - GitHub/GitLab Markdown
   - VS Code with Mermaid extension
   - Online Mermaid Live Editor (mermaid.live)

4. **Create Visual Diagrams**: Use tools like:
   - **draw.io/diagrams.net**: Import Mermaid or create manually
   - **Lucidchart**: Professional architecture diagrams
   - **Excalidraw**: Hand-drawn style diagrams

This comprehensive overview provides all necessary context to generate any level of diagram from high-level architecture to detailed sequence flows!
make me.md file not docx.
I'll create the draw.md file with the comprehensive system overview for diagram generation.

I cannot directly create files, but I can provide you with the complete content for draw.md. Here's the comprehensive markdown file you can save in your project root:

Save this content as draw.md in your project directory:
# 📝 Online Exam System - Comprehensive System Overview

> **Use this document as a prompt to generate various architectural diagrams.**
> Each section below contains structured information for specific diagram types.

---

## 📋 System Identity & Purpose

| Attribute | Description |
|-----------|-------------|
| **System Name** | Online Exam System |
| **Type** | Web-based Examination Management Platform |
| **Primary Purpose** | Enable educational institutions to conduct secure online examinations with automatic grading, location tracking, and result management |
| **Target Users** | Students, Teachers, Administrators |
| **Deployment** | PHP/MySQL web application |
| **Database** | `exaam` |

---

## 🏗️ System Architecture Overview

### Technology Stack
┌─────────────────────────────────────────────────────────────┐ │ TECHNOLOGY STACK │ ├─────────────────────────────────────────────────────────────┤ │ Frontend Layer │ │ ├── HTML5 + CSS3 (Bootstrap 5.1.3) │ │ ├── JavaScript (ES6+) │ │ ├── Font Awesome Icons │ │ └── Responsive Design │ ├─────────────────────────────────────────────────────────────┤ │ Backend Layer │ │ ├── PHP 7.4+ (Session-based authentication) │ │ ├── PDO (Database abstraction) │ │ └── REST-like endpoints (AJAX-based) │ ├─────────────────────────────────────────────────────────────┤ │ Database Layer │ │ ├── MySQL/MariaDB │ │ ├── Database: exaam │ │ └── UTF8MB4 character set │ ├─────────────────────────────────────────────────────────────┤ │ External APIs/Services │ │ ├── Browser Geolocation API (Location tracking) │ │ └── Leaflet/OpenStreetMap (Maps visualization) │ └─────────────────────────────────────────────────────────────┘


---

## 👥 User Roles & Hierarchy

```mermaid
graph TD
    A[System Users] --> B[Students]
    A --> C[Staff/Teachers]
    A --> D[Administrators]
    
    B --> B1[View Available Exams]
    B --> B2[Take Exams]
    B --> B3[View Results]
    B --> B4[View Leaderboard]
    B --> B5[Manage Profile]
    
    C --> C1[Create Exams]
    C --> C2[Manage Questions]
    C --> C3[View Student Results]
    C --> C4[View Leaderboards]
    C --> C5[Exam Analytics]
    
    D --> D1[Full System Access]
    D --> D2[User Management]
    D --> D3[System Configuration]
    D --> D4[All Staff Privileges]
    
    style A fill:#667eea,color:#fff
    style B fill:#28a745,color:#fff
    style C fill:#ffc107,color:#000
    style D fill:#dc3545,color:#fff
🗄️ Database Schema (Entity Relationship)
erDiagram
    USERS {
        int user_id PK
        string username UK
        string email UK
        string password_hash
        string full_name
        enum user_type
        string phone
        string profile_image
        boolean is_active
        datetime last_login
        timestamp created_at
        timestamp updated_at
        string remember_token
        datetime remember_expires
    }
    
    ADMIN_USERS {
        int admin_id PK
        string username UK
        string email UK
        string password_hash
        string full_name
        string role
        boolean is_active
        datetime last_login
        timestamp created_at
        timestamp updated_at
    }
    
    EXAMS {
        int exam_id PK
        string exam_title
        text exam_description
        int exam_duration
        int total_marks
        int passing_marks
        enum exam_status
        int created_by FK
        boolean is_deleted
        timestamp created_at
        timestamp updated_at
    }
    
    QUESTIONS {
        int question_id PK
        int exam_id FK
        text question_text
        text option_a
        text option_b
        text option_c
        text option_d
        json options
        enum correct_answer
        int marks
        timestamp created_at
    }
    
    EXAM_ATTEMPTS {
        int attempt_id PK
        int exam_id FK
        string student_name
        string student_email
        string student_roll
        timestamp started_at
        datetime completed_at
        string ip_address
    }
    
    USER_ANSWERS {
        int answer_id PK
        int attempt_id FK
        int question_id FK
        string user_answer
        boolean is_correct
        int marks_obtained
        timestamp created_at
        timestamp updated_at
    }
    
    EXAM_RESULTS {
        int result_id PK
        int attempt_id FK
        int exam_id FK
        string student_name
        int total_questions
        int attempted_questions
        int correct_answers
        int wrong_answers
        int total_marks
        int marks_obtained
        decimal percentage
        string grade
        enum status
        timestamp completed_at
    }
    
    USERS ||--o{ EXAMS : "creates"
    EXAMS ||--o{ QUESTIONS : "contains"
    EXAMS ||--o{ EXAM_ATTEMPTS : "has"
    EXAM_ATTEMPTS ||--o{ USER_ANSWERS : "records"
    EXAM_ATTEMPTS ||--o{ EXAM_RESULTS : "produces"
    QUESTIONS ||--o{ USER_ANSWERS : "answered_in"
    EXAMS ||--o{ EXAM_RESULTS : "generates"
📁 File Structure & Module Organization
updatedexamsytem/
│
├── 🌐 PUBLIC PAGES (Student-Facing)
│   ├── index.php                 # Landing page - List available exams
│   ├── start_exam.php            # Exam initiation & student info collection
│   ├── take_exam.php             # Main exam interface with timer
│   ├── submit_exam.php           # Exam submission & auto-grading
│   ├── results.php               # Individual result display
│   ├── leaderboard.php           # Public leaderboard view
│   ├── home.php                  # Student dashboard
│   ├── profile.php               # Student profile view
│   ├── edit_profile.php          # Profile editing
│   ├── change_password.php       # Password management
│   ├── login.php                 # Student login
│   ├── register.php              # Student registration
│   ├── logout.php                # Session termination
│   ├── dashboard.php             # User dashboard
│   └── exam_center.php           # Location tracking
│
├── 🛡️ ADMIN MODULE
│   ├── login.php                 # Admin authentication
│   ├── index.php                 # Admin dashboard
│   ├── create_exam.php           # Exam creation
│   ├── edit_exam.php             # Exam modification
│   ├── exam_crud.php             # Exam CRUD operations
│   ├── add_question.php          # Question addition
│   ├── edit_question.php         # Question editing
│   ├── mange_question.php        # Question management
│   ├── view_exams.php            # Exam listing
│   ├── db_connection.php         # Database connection
│   └── config.php                # Admin configuration
│
├── 👨‍🏫 STAFF MODULE
│   ├── login.php                 # Staff authentication
│   ├── index.php                 # Staff dashboard
│   ├── create_exam.php           # Create exams
│   ├── edit_exam.php             # Modify exams
│   ├── manage_exams.php          # Exam management
│   ├── delete_exam.php           # Exam deletion
│   ├── add_question.php          # Add questions
│   ├── edit_question.php         # Edit questions
│   ├── manage_questions.php      # Question management
│   ├── delete_question.php       # Question deletion
│   ├── view_results.php          # View all results
│   ├── leaderboard.php           # Staff leaderboard view
│   ├── create_admin.php          # Admin creation utility
│   ├── admin_reg.php             # Admin registration
│   ├── logout.php                # Staff logout
│   └── db_connection.php         # Staff DB connection
│
├── 🔧 CORE COMPONENTS
│   ├── db_connection.php         # Main database connection
│   ├── style.css                 # Global styles
│   └── exam_center.php           # Location tracking module
│
├── 🗄️ DATABASE
│   └── database.sql              # Complete schema & seed data
│
└── 📚 DOCUMENTATION
    ├── ALGORITHM.md              # Algorithm documentation
    ├── README.md                 # Project readme
    └── draw.md                   # This file
🔄 Data Flow Diagrams
1. Student Exam Taking Flow
sequenceDiagram
    participant S as Student
    participant I as index.php
    participant St as start_exam.php
    participant T as take_exam.php
    participant Sub as submit_exam.php
    participant R as results.php
    participant DB as Database
    
    S->>I: Browse available exams
    I->>DB: SELECT * FROM exams
    DB-->>I: Return exam list
    I-->>S: Display exams
    
    S->>St: Click "Start Exam"
    S->>St: Enter name, email, roll
    
    St->>DB: INSERT INTO exam_attempts
    DB-->>St: Return attempt_id
    St->>St: Create session variables
    St-->>S: Redirect to exam
    
    S->>T: Load exam interface
    T->>DB: SELECT * FROM questions
    DB-->>T: Return questions
    T-->>S: Display questions with timer
    
    loop Every Answer Selection
        S->>T: Select option
        T->>DB: INSERT/UPDATE user_answers
    end
    
    S->>Sub: Submit exam / Time expires
    Sub->>DB: SELECT correct answers
    DB-->>Sub: Return answers
    
    Sub->>Sub: Calculate score, percentage, grade
    
    Sub->>DB: INSERT INTO exam_results
    DB-->>Sub: Confirm save
    
    Sub-->>S: Redirect to results
    
    S->>R: View results
    R->>DB: SELECT exam_results
    DB-->>R: Return result data
    R-->>S: Display certificate & analytics
2. Admin/Staff Exam Management Flow
sequenceDiagram
    participant A as Admin/Staff
    participant L as login.php
    participant D as Dashboard
    participant M as Management Pages
    participant DB as Database
    
    A->>L: Enter credentials
    L->>DB: SELECT * FROM admin_users
    DB-->>L: Return user data
    L->>L: password_verify()
    L->>L: Create session
    L-->>A: Redirect to dashboard
    
    A->>D: Access dashboard
    D-->>A: Show statistics & options
    
    alt Create Exam
        A->>M: create_exam.php
        M-->>A: Display form
        A->>M: Submit exam details
        M->>DB: INSERT INTO exams
        DB-->>M: Confirm
        M-->>A: Success message
    end
    
    alt Add Questions
        A->>M: add_question.php
        M-->>A: Display question form
        A->>M: Submit question data
        M->>DB: INSERT INTO questions
        DB-->>M: Confirm
        M-->>A: Success message
    end
    
    alt View Results
        A->>M: view_results.php
        M->>DB: SELECT exam_results
        DB-->>M: Return results
        M-->>A: Display results table
    end
🎨 UI/UX Component Architecture
graph TB
    subgraph "Frontend Components"
        NAV[Navigation Bar]
        CARD[Exam Cards]
        TIMER[Exam Timer]
        FORM[Question Forms]
        MODAL[Modal Dialogs]
        MAP[Location Map]
        CHART[Result Charts]
    end
    
    subgraph "Styling System"
        BS[Bootstrap 5.1.3]
        FA[Font Awesome]
        CSS[Custom CSS]
        
        BS -->|Grids & Layout| NAV
        BS -->|Cards & Modals| CARD
        BS -->|Progress Bars| TIMER
        BS -->|Forms| FORM
        FA -->|Icons| NAV
        FA -->|Icons| CARD
        CSS -->|Custom Styles| TIMER
        CSS -->|Animations| CARD
    end
    
    subgraph "JavaScript Modules"
        AUTH[Auth Validation]
        TIMER_JS[Timer Logic]
        NAV_JS[Question Navigation]
        SAVE[Auto-save Answers]
        LOC[Location Tracker]
        
        TIMER_JS -->|Update Display| TIMER
        NAV_JS -->|Switch Questions| FORM
        SAVE -->|Background Sync| DB[(Database)]
        LOC -->|Geolocation API| MAP
    end
🔐 Security Architecture
flowchart TD
    A[User Request] --> B{Authentication Check}
    
    B -->|Not Logged In| C[Redirect to Login]
    B -->|Session Valid| D{Authorization Check}
    
    D -->|Insufficient Privileges| E[Access Denied]
    D -->|Authorized| F[Process Request]
    
    F --> G[Input Validation]
    G -->|Invalid| H[Error Response]
    G -->|Valid| I[Database Operation]
    
    I --> J{SQL Injection Protection}
    J -->|PDO Prepared Statements| K[Execute Query]
    
    K --> L[Output Encoding]
    L -->|htmlspecialchars| M[Safe Response]
    
    C --> N[End]
    E --> N
    H --> N
    M --> N
    
    style A fill:#667eea,color:#fff
    style B fill:#ffc107,color:#000
    style D fill:#ffc107,color:#000
    style F fill:#28a745,color:#fff
    style E fill:#dc3545,color:#fff
    style H fill:#dc3545,color:#fff
⚡ Core Algorithms
1. Result Calculation Algorithm
flowchart TD
    A[Start Exam Submission] --> B[Initialize Variables]
    B --> C[Loop Through Each Question]
    
    C --> D{User Answered?}
    D -->|Yes| E[Increment Attempted]
    D -->|No| C
    
    E --> F{Answer Correct?}
    F -->|Yes| G[Add Marks & Increment Correct]
    F -->|No| H[Increment Wrong]
    
    G --> I{More Questions?}
    H --> I
    
    I -->|Yes| C
    I -->|No| J[Calculate Percentage]
    
    J --> K[Calculate Grade]
    K --> L{Pass or Fail?}
    L -->|Marks >= Passing| M[Status: PASS]
    L -->|Marks < Passing| N[Status: FAIL]
    
    M --> O[Save to Database]
    N --> O
    O --> P[Display Results]
    
    style A fill:#667eea,color:#fff
    style P fill:#28a745,color:#fff
    style M fill:#28a745,color:#fff
    style N fill:#dc3545,color:#fff
2. Location Tracking Algorithm
flowchart TD
    A[Request Geolocation Permission] --> B{Browser Support?}
    B -->|No| C[Show Error]
    B -->|Yes| D[Get Current Position]
    
    D --> E[Calculate Distance<br/>Haversine Formula]
    E --> F{Within Allowed Radius?}
    
    F -->|Yes < 100m| G[Green Status]
    F -->|Warning 100-500m| H[Yellow Status]
    F -->|Violation > 500m| I[Red Alert & Log]
    
    G --> J[Update Map Display]
    H --> J
    I --> J
    
    J --> K{Exam Still Active?}
    K -->|Yes| D
    K -->|No| L[Stop Tracking]
    
    C --> M[End]
    L --> M
    
    style G fill:#28a745,color:#fff
    style H fill:#ffc107,color:#000
    style I fill:#dc3545,color:#fff
3. Password Security Flow
flowchart LR
    subgraph Registration
        R1[User Input Password] --> R2[password_hash<br/>bcrypt]
        R2 --> R3[Store Hash in DB]
    end
    
    subgraph Login
        L1[User Input Password] --> L2[Fetch Hash from DB]
        L2 --> L3[password_verify]
        L3 -->|Valid| L4[Create Session]
        L3 -->|Invalid| L5[Error Message]
    end
    
    R3 -.->|Stored Hash| L2
    
    style R2 fill:#667eea,color:#fff
    style L3 fill:#28a745,color:#fff
    style L5 fill:#dc3545,color:#fff
🌐 API Endpoints & Interactions
Endpoint	Method	Purpose	Access
index.php	GET	List active exams	Public
start_exam.php	GET/POST	Initiate exam attempt	Public
take_exam.php	GET	Load exam questions	Session
take_exam.php	POST	Save answer (AJAX)	Session
submit_exam.php	POST	Submit & grade exam	Session
results.php	GET	View results	Public/Session
leaderboard.php	GET	View rankings	Public
staff/login.php	POST	Staff authentication	Public
staff/create_exam.php	POST	Create new exam	Staff/Admin
staff/add_question.php	POST	Add exam questions	Staff/Admin
📊 System Metrics & KPIs
graph LR
    subgraph "Performance Metrics"
        A1[Page Load Time<br/>< 2s]
        A2[Database Query Time<br/>< 100ms]
        A3[Timer Accuracy<br/>±1s]
    end
    
    subgraph "Security Metrics"
        B1[Password Hashing<br/>bcrypt]
        B2[SQL Injection Prevention<br/>100%]
        B3[XSS Prevention<br/>htmlspecialchars]
    end
    
    subgraph "Business Metrics"
        C1[Concurrent Users<br/>50+]
        C2[Exam Completion Rate<br/>Trackable]
        C3[Average Score<br/>Analytics]
    end
    
    A1 --> D[System Health]
    A2 --> D
    A3 --> D
    B1 --> D
    B2 --> D
    B3 --> D
    C1 --> D
    C2 --> D
    C3 --> D
    
    style D fill:#667eea,color:#fff
🎯 Use Case Diagrams
Student Use Cases
graph LR
    S((Student)) --> UC1[Browse Exams]
    S --> UC2[Start Exam]
    S --> UC3[Answer Questions]
    S --> UC4[Submit Exam]
    S --> UC5[View Results]
    S --> UC6[View Leaderboard]
    S --> UC7[Manage Profile]
    
    UC2 --> UC3
    UC3 --> UC4
    UC4 --> UC5
    
    style S fill:#667eea,color:#fff
Admin Use Cases
graph LR
    A((Admin)) --> UC1[Create Exams]
    A --> UC2[Manage Questions]
    A --> UC3[View All Results]
    A --> UC4[Manage Users]
    A --> UC5[System Configuration]
    A --> UC6[View Analytics]
    A --> UC7[Export Data]
    
    UC1 --> UC2
    
    style A fill:#dc3545,color:#fff
🚀 Deployment Architecture
graph TB
    subgraph "Client Layer"
        C1[Web Browser<br/>Chrome/Firefox/Safari]
        C2[Mobile Browser<br/>iOS/Android]
    end
    
    subgraph "Web Server Layer"
        WS[Apache/Nginx<br/>PHP 7.4+]
    end
    
    subgraph "Application Layer"
        APP[Online Exam System<br/>PHP Application]
    end
    
    subgraph "Data Layer"
        DB[(MySQL/MariaDB<br/>exaam Database)]
        CACHE[Session Storage<br/>$_SESSION]
    end
    
    C1 -->|HTTP/HTTPS| WS
    C2 -->|HTTP/HTTPS| WS
    WS -->|PHP Processing| APP
    APP -->|PDO| DB
    APP -->|Read/Write| CACHE
    
    style WS fill:#667eea,color:#fff
    style APP fill:#28a745,color:#fff
    style DB fill:#ffc107,color:#000
📖 Prompt Templates for AI Diagram Generation
Generate High-Level Architecture Diagram
"Using the system overview above, create a high-level system architecture diagram showing the three-tier architecture (Presentation, Application, Data) with all major components and their interactions."

Generate Database ER Diagram
"Generate a detailed Entity-Relationship diagram based on the database schema provided, showing all tables, primary keys, foreign keys, and relationships with cardinality."

Generate User Flow Diagram
"Create a user flow diagram for a student taking an exam from start to finish, including decision points and alternative flows (timeout, early submission, etc.)."

Generate Sequence Diagram
"Generate a sequence diagram showing the interaction between student, web server, and database during exam submission and grading."

Generate Component Diagram
"Create a component diagram showing all PHP files/modules as components and their dependencies/connections."

Generate Security Flow Diagram
"Generate a security flow diagram showing authentication, authorization, and data protection mechanisms throughout the system."

Generate Deployment Diagram
"Create a deployment diagram showing how the system components are deployed across web server, application server, and database server."

🔗 Quick Reference: Key Files for Diagram Context
Diagram Type	Key Files to Reference
System Architecture	db_connection.php, index.php, folder structure
Database Schema	database.sql
User Flow	index.php → start_exam.php → take_exam.php → submit_exam.php → results.php
Admin Flow	staff/login.php → staff/index.php → staff/create_exam.php
Security	login.php, register.php, password_hash() usage
Algorithms	ALGORITHM.md, submit_exam.php (grading), exam_center.php (location)
📝 Notes for Diagram Generation
Color Coding Suggestions:
Primary: #667eea (Purple - Main brand)
Success: #28a745 (Green - Pass/Active)
Warning: #ffc107 (Yellow - Caution)
Danger: #dc3545 (Red - Fail/Error)
Info: #17a2b8 (Blue - Information)
Icons to Use:
Students: 👨‍🎓 or 🎓
Exams: 📝 or 📋
Database: 🗄️ or 💾
Security: 🔐 or 🛡️
Admin: 👨‍💼 or ⚙️
Layout Preferences:
Top-to-bottom flow for process diagrams
Left-to-right flow for data flow
Hierarchical for architecture diagrams
Centralized hub-and-spoke for ER diagrams
Last Updated: March 2026 System Version: Online Exam System v1.0


---

## How to Create the File

1. **Open your project folder** in VS Code or any text editor
2. **Create a new file** named `draw.md`
3. **Copy and paste** the entire content above into the file
4. **Save the file**

The file contains comprehensive system documentation with Mermaid diagram syntax that can be used with various diagram generation tools, including:
- GitHub/GitLab Markdown preview
- VS Code with Mermaid extension
- Online Mermaid Live Editor (mermaid.live)
- draw.io/diagrams.net
- Lucidchart