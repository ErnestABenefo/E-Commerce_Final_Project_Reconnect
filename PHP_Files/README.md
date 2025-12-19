# ReConnect - Alumni Social Network & Career Platform

## Table of Contents
- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Core Features](#core-features)
- [Database Design](#database-design)
- [User Roles & Permissions](#user-roles--permissions)
- [Key Architectural Patterns](#key-architectural-patterns)
- [Main User Workflows](#main-user-workflows)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Project Structure](#project-structure)

---

## Overview

**ReConnect** is a comprehensive social networking and career development platform designed specifically for university alumni. It bridges the gap between educational institutions and their graduates by providing tools for networking, job searching, e-commerce, event management, and community engagement.

### Key Objectives
- Foster lifelong connections between alumni and their alma mater
- Facilitate professional networking and career advancement
- Enable universities to engage with and support their alumni communities
- Provide a marketplace for alumni entrepreneurship
- Create opportunities for mentorship and knowledge sharing

---

## Systems Analysis and Design

### 1. System Overview

ReConnect is a **web-based social networking and career development platform** designed specifically for university alumni. The platform connects graduates with their alma mater and fellow alumni, facilitating professional networking, career opportunities, and community engagement.

**Core Design Principles:**
- **User-Centric**: Intuitive workflows optimized for alumni engagement
- **Secure**: Multi-layered security with role-based access control
- **Scalable**: Modular architecture supporting future growth
- **Maintainable**: Clear separation of concerns following MVC pattern

**System Architecture:**
```
┌─────────────────────────────────────────────────────────┐
│              PRESENTATION LAYER                          │
│         (HTML, CSS, JavaScript, PHP Views)              │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│             APPLICATION LAYER                            │
│        (Controllers, Actions, Business Logic)           │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│              DATA ACCESS LAYER                           │
│            (Model Classes, PHP Objects)                 │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│               DATABASE LAYER                             │
│            (MySQL - reconnectdb2)                       │
└─────────────────────────────────────────────────────────┘
```

---

### 2. System Requirements

#### 2.1 Functional Requirements

**User Management**
- FR1: System shall allow users to register with email and password
- FR2: System shall verify alumni status through document upload
- FR3: System shall support profile creation with academic information
- FR4: System shall enable profile photo uploads
- FR5: System shall allow users to update personal information

**Social Networking**
- FR6: System shall enable users to create text and image posts
- FR7: System shall allow users to like and comment on posts
- FR8: System shall support following/unfollowing other users
- FR9: System shall provide direct messaging between users
- FR10: System shall enable global search across users and content

**University Administration**
- FR11: System shall allow university admins to create official posts
- FR12: System shall enable admins to approve/reject alumni verifications
- FR13: System shall support context switching for multi-university admins
- FR14: System shall allow admin role assignments by global administrators

**Events Management**
- FR15: System shall enable verified alumni to create events
- FR16: System shall support event RSVP and attendance tracking
- FR17: System shall allow event editing and cancellation
- FR18: System shall send event reminders to attendees

**Job Board**
- FR19: System shall allow verified alumni to post job listings
- FR20: System shall enable users to apply for jobs with CV upload
- FR21: System shall provide applicant tracking for employers
- FR22: System shall support job filtering and search

**Marketplace**
- FR23: System shall enable users to list items for sale
- FR24: System shall provide shopping cart functionality
- FR25: System shall process secure payments via payment gateway
- FR26: System shall track orders and transaction history

**Groups & Communities**
- FR27: System shall allow users to create interest-based groups
- FR28: System shall support group chat functionality
- FR29: System shall enable group member management
- FR30: System shall provide university-affiliated official groups

#### 2.2 Non-Functional Requirements

**Performance**
- NFR1: Page load time shall not exceed 3 seconds on standard connection
- NFR2: Database queries shall be optimized with proper indexing
- NFR3: System shall support at least 1000 concurrent users

**Security**
- NFR4: All passwords shall be hashed using bcrypt algorithm
- NFR5: SQL injection shall be prevented through prepared statements
- NFR6: XSS attacks shall be prevented through input sanitization
- NFR7: Sessions shall timeout after 30 minutes of inactivity

**Usability**
- NFR8: Interface shall be responsive across desktop, tablet, and mobile
- NFR9: System shall provide clear error messages to users
- NFR10: Navigation shall be intuitive with maximum 3 clicks to any feature

**Reliability**
- NFR11: System shall have 99% uptime
- NFR12: Database backups shall be performed daily
- NFR13: Failed transactions shall not corrupt database state

**Maintainability**
- NFR14: Code shall follow MVC architectural pattern
- NFR15: Database shall be normalized to 3rd normal form
- NFR16: All code shall include comments for complex logic

---

### 3. User Access Hierarchy

The platform implements a **four-tier role-based access control system** where permissions cascade from top to bottom.

#### 3.1 Role Hierarchy Diagram

```
                    ┌──────────────────────────────┐
                    │    GLOBAL ADMINISTRATOR      │
                    │                              │
                    │  • Manage all universities   │
                    │  • Assign university admins  │
                    │  • System-wide access        │
                    │  • All lower-tier permissions│
                    └──────────────┬───────────────┘
                                   │
                                   │ Assigns
                                   │
            ┌──────────────────────▼────────────────────────┐
            │       UNIVERSITY ADMINISTRATOR                 │
            │                                                │
            │  • Manage specific university content          │
            │  • Approve alumni verifications                │
            │  • Create official university posts            │
            │  • Manage university events & groups           │
            │  • All verified alumni permissions             │
            └──────────────────────┬─────────────────────────┘
                                   │
                                   │ Approves verification
                                   │
                    ┌──────────────▼───────────────┐
                    │      VERIFIED ALUMNI         │
                    │                              │
                    │  • Verified badge displayed  │
                    │  • Post job listings         │
                    │  • Create events             │
                    │  • Sell on marketplace       │
                    │  • All regular user perms    │
                    └──────────────┬───────────────┘
                                   │
                                   │ Registers & verifies
                                   │
                    ┌──────────────▼───────────────┐
                    │       REGULAR USER           │
                    │                              │
                    │  • View all content          │
                    │  • Create posts              │
                    │  • Like & comment            │
                    │  • Join groups               │
                    │  • Apply for jobs            │
                    │  • Buy from marketplace      │
                    └──────────────────────────────┘
```

#### 3.2 Permission Matrix

| Feature / Action | Regular User | Verified Alumni | University Admin | Global Admin |
|------------------|:------------:|:---------------:|:----------------:|:------------:|
| **Content Viewing** |
| View posts & profiles | ✅ | ✅ | ✅ | ✅ |
| Search users | ✅ | ✅ | ✅ | ✅ |
| Browse marketplace | ✅ | ✅ | ✅ | ✅ |
| View job listings | ✅ | ✅ | ✅ | ✅ |
| **Content Creation** |
| Create personal posts | ✅ | ✅ | ✅ | ✅ |
| Upload post images | ✅ | ✅ | ✅ | ✅ |
| Like & comment | ✅ | ✅ | ✅ | ✅ |
| **Advanced Features** |
| Post job listings | ❌ | ✅ | ✅ | ✅ |
| Create events | ❌ | ✅ | ✅ | ✅ |
| Sell marketplace items | ✅ | ✅ | ✅ | ✅ |
| Apply for verification | ✅ | N/A | N/A | N/A |
| **Administrative** |
| Official university posts | ❌ | ❌ | ✅ | ✅ |
| Approve verifications | ❌ | ❌ | ✅ | ✅ |
| Manage university groups | ❌ | ❌ | ✅ | ✅ |
| Assign admins | ❌ | ❌ | ❌ | ✅ |
| Manage universities | ❌ | ❌ | ✅ (scoped) | ✅ |
| System configuration | ❌ | ❌ | ❌ | ✅ |

#### 3.3 User Progression Flow

```
New User Registration
         │
         ▼
┌─────────────────────┐
│   REGULAR USER      │──────┐
│                     │      │ Can immediately:
│  Limited Access     │      │ • Browse content
└──────────┬──────────┘      │ • Create posts
           │                 │ • Join groups
           │ Submits         └─────────────────
           │ verification
           │ documents
           ▼
┌─────────────────────┐
│ PENDING APPROVAL    │
│                     │
│  Same as Regular    │
└──────────┬──────────┘
           │
           │ University Admin
           │ reviews & approves
           ▼
┌─────────────────────┐
│  VERIFIED ALUMNI    │──────┐
│                     │      │ Gains ability to:
│  Enhanced Access    │      │ • Post jobs
└──────────┬──────────┘      │ • Create events
           │                 │ • Get verified badge
           │ Global/Univ     └─────────────────
           │ Admin assigns
           │ admin role
           ▼
┌─────────────────────┐
│ UNIVERSITY ADMIN    │──────┐
│                     │      │ Can now:
│  Management Access  │      │ • Approve verifications
└──────────┬──────────┘      │ • Official posts
           │                 │ • Manage university
           │ Global Admin    └─────────────────
           │ grants global
           │ privileges
           ▼
┌─────────────────────┐
│  GLOBAL ADMIN       │──────┐
│                     │      │ Full control:
│  Full System Access │      │ • All universities
└─────────────────────┘      │ • Assign admins
                             │ • System settings
                             └─────────────────
```

---

### 4. Core Feature Workflows

#### 4.1 User Registration & Verification Workflow

```
START: User visits platform
         │
         ▼
┌──────────────────────────────┐
│  1. REGISTRATION             │
│  • Fill registration form    │
│  • Enter: name, email,       │
│    password, phone           │
│  • Select university         │
└────────────┬─────────────────┘
             │
             ▼
┌──────────────────────────────┐
│  2. ACCOUNT CREATION         │
│  • Validate email format     │
│  • Check email uniqueness    │
│  • Hash password (bcrypt)    │
│  • INSERT into Users table   │
│  • Create session            │
└────────────┬─────────────────┘
             │
             ▼
┌──────────────────────────────┐
│  3. PROFILE SETUP            │
│  • Upload profile photo      │
│  • Select department/program │
│  • Add graduation year       │
│  • UPDATE academic profile   │
└────────────┬─────────────────┘
             │
             ▼
┌──────────────────────────────┐
│  4. ALUMNI VERIFICATION      │
│  • Upload documents          │
│    - Student ID              │
│    - Degree certificate      │
│  • INSERT verification       │
│    request (status: pending) │
└────────────┬─────────────────┘
             │
             ▼
┌──────────────────────────────┐
│  5. ADMIN REVIEW             │
│  • University admin notified │
│  • Admin views documents     │
│  • Admin approves/rejects    │
│  • UPDATE verification status│
└────────────┬─────────────────┘
             │
             ▼
        [Approved?]
         /        \
      Yes          No
       │            │
       ▼            ▼
┌────────────┐  ┌──────────────┐
│ VERIFIED   │  │ REJECTED     │
│ Get badge  │  │ Can reapply  │
└────────────┘  └──────────────┘
```

#### 4.2 Social Engagement Workflow

**Creating & Interacting with Posts**

```
User on Homepage
       │
       ▼
┌────────────────────────────────┐
│  CREATE POST                   │
│  • Write content in text box   │
│  • Optional: Upload image      │
│  • Click "Post" button         │
└──────────┬─────────────────────┘
           │
           ▼
┌────────────────────────────────┐
│  SUBMIT POST                   │
│  • AJAX → create_post_action   │
│  • Validate session            │
│  • Sanitize input              │
│  • Store image (if provided)   │
│  • INSERT into Posts table     │
└──────────┬─────────────────────┘
           │
           ▼
┌────────────────────────────────┐
│  DISPLAY IN FEED               │
│  • Post appears at top         │
│  • Shows author info           │
│  • Displays verified badge     │
│  • Like/comment buttons active │
└────────────────────────────────┘
           │
    ┌──────┴──────┐
    │             │
    ▼             ▼
┌─────────┐  ┌──────────┐
│  LIKE   │  │ COMMENT  │
└────┬────┘  └─────┬────┘
     │             │
     ▼             ▼
┌─────────────┐ ┌──────────────────┐
│ Toggle Like │ │ Write & Submit   │
│ Update count│ │ Show in thread   │
└─────────────┘ └──────────────────┘
```

#### 4.3 E-Commerce (Marketplace) Workflow

```
SELLER FLOW                         BUYER FLOW

┌──────────────────┐               ┌──────────────────┐
│ List Item        │               │ Browse Items     │
│ • Title, price   │               │ • Search/filter  │
│ • Upload image   │               │ • View details   │
└────────┬─────────┘               └────────┬─────────┘
         │                                  │
         ▼                                  ▼
┌──────────────────┐               ┌──────────────────┐
│ Item Published   │               │ Add to Cart      │
│ • Visible to all │               │ • Update quantity│
└──────────────────┘               └────────┬─────────┘
                                            │
                                            ▼
                                   ┌──────────────────┐
                                   │ Checkout         │
                                   │ • Review cart    │
                                   │ • Enter payment  │
                                   └────────┬─────────┘
                                            │
                                            ▼
         ┌──────────────────────────────────┴─────────┐
         │         PAYMENT PROCESSING                  │
         │  • Validate payment details                 │
         │  • Call payment gateway API                 │
         │  • BEGIN TRANSACTION                        │
         └────────┬───────────────────────┬────────────┘
                  │                       │
            [Success?]                [Failure]
                  │                       │
                  ▼                       ▼
         ┌──────────────────┐    ┌──────────────────┐
         │ Create Order     │    │ Return to Cart   │
         │ • PaymentOrders  │    │ • Show error     │
         │ • OrderItems     │    └──────────────────┘
         │ • Clear cart     │
         │ • Update item    │
         │   status: sold   │
         │ • COMMIT         │
         └────────┬─────────┘
                  │
                  ▼
         ┌──────────────────┐
         │ Confirmation     │
         │ • Order summary  │
         │ • Email sent     │
         └──────────────────┘
```

#### 4.4 Job Application Workflow

```
EMPLOYER FLOW                       JOB SEEKER FLOW

┌──────────────────┐               ┌──────────────────┐
│ Post Job         │               │ Browse Jobs      │
│ • Title, company │               │ • Filter by:     │
│ • Description    │               │   - Location     │
│ • Requirements   │               │   - Type         │
│ • Salary         │               │   - Company      │
└────────┬─────────┘               └────────┬─────────┘
         │                                  │
         ▼                                  ▼
┌──────────────────┐               ┌──────────────────┐
│ Job Published    │               │ View Job Details │
│ • Appears in     │               │ • Full           │
│   listings       │               │   description    │
└────────┬─────────┘               │ • Requirements   │
         │                         └────────┬─────────┘
         │                                  │
         │                                  ▼
         │                         ┌──────────────────┐
         │                         │ Apply            │
         │                         │ • Upload CV      │
         │                         │ • Cover letter   │
         │                         │ • Submit         │
         │                         └────────┬─────────┘
         │                                  │
         │                                  ▼
         │                         ┌──────────────────┐
         │                         │ Application Sent │
         │                         │ • Status: Pending│
         │                         └────────┬─────────┘
         │                                  │
         └──────────────────┬───────────────┘
                            │
                            ▼
                   ┌──────────────────┐
                   │ Employer Reviews │
                   │ • View applicants│
                   │ • Download CVs   │
                   │ • Update status: │
                   │   - Shortlisted  │
                   │   - Rejected     │
                   └──────────────────┘
```

#### 4.5 Event Management Workflow

```
EVENT CREATOR (Verified Alumni/Admin)     ATTENDEE

┌──────────────────┐                    ┌──────────────────┐
│ Create Event     │                    │ Browse Events    │
│ • Title, date    │                    │ • Filter by date │
│ • Location       │                    │ • Filter by      │
│ • Description    │                    │   location       │
└────────┬─────────┘                    └────────┬─────────┘
         │                                       │
         ▼                                       ▼
┌──────────────────┐                    ┌──────────────────┐
│ Event Published  │                    │ View Details     │
│ • Visible to all │                    │ • Full info      │
└────────┬─────────┘                    │ • Attendee count │
         │                              └────────┬─────────┘
         │                                       │
         │                                       ▼
         │                              ┌──────────────────┐
         │                              │ RSVP             │
         │                              │ • Click "Attend" │
         │                              │ • Confirmation   │
         │                              └────────┬─────────┘
         │                                       │
         └───────────────┬───────────────────────┘
                         │
                         ▼
                ┌──────────────────┐
                │ Event Dashboard  │
                │ • Attendee list  │
                │ • Edit details   │
                │ • Send updates   │
                └──────────────────┘
                         │
                         ▼
                ┌──────────────────┐
                │ Reminder Emails  │
                │ • 1 day before   │
                │ • Day of event   │
                └──────────────────┘
```

#### 4.6 University Administration Workflow

```
GLOBAL ADMIN                    UNIVERSITY ADMIN

┌──────────────────┐
│ Assign Admin     │
│ • Search user    │
│ • Select univ    │
│ • Grant role     │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Admin Notified   │
│ • Email sent     │
│ • Role active    │
└────────┬─────────┘
         │
         └──────────────┐
                        │
                        ▼
              ┌──────────────────┐
              │ Login as Admin   │
              │ • Context switcher│
              │   appears        │
              └────────┬─────────┘
                       │
                       ▼
              ┌──────────────────┐
              │ Select University│
              │ • Set context    │
              │ • Load dashboard │
              └────────┬─────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Official     │ │ Approve      │ │ Manage       │
│ Posts        │ │ Verifications│ │ Events       │
└──────────────┘ └──────────────┘ └──────────────┘
```

---

This systems analysis provides a comprehensive overview of ReConnect's architecture, requirements, user roles, and core workflows. The platform is designed to be intuitive for end-users while maintaining robust security and scalability for institutional needs.

---

## Core Features

### 1. 👤 User Management
- **User Registration**: New alumni can create accounts with email verification
- **Profile Management**: Upload profile photos, update bio, add skills and experience
- **Alumni Verification**: Document-based verification system for alumni status
- **Multi-University Support**: Users can be associated with multiple institutions

### 2. 🌐 Social Networking
- **Community Feed**: Real-time posts from users and universities
- **Post Interactions**: Like, comment, and share functionality
- **Connections**: Follow other alumni to stay updated
- **Direct Messaging**: Private one-on-one conversations
- **Search**: Find alumni by name, university, skills, or location

### 3. 🎓 University Administration
- **University Profiles**: Comprehensive university information management
- **Admin Roles**: Assign and manage university administrators
- **Official Posts**: Universities can share announcements and news
- **Alumni Verification Approval**: Review and approve alumni verification requests
- **Context Switching**: Admins can manage multiple universities

### 4. 📅 Events Management
- **Create Events**: Universities and verified alumni can organize events
- **RSVP System**: Track attendees and manage registrations
- **Event Discovery**: Browse upcoming events by date, location, or type
- **Event Updates**: Notify attendees of changes or cancellations

### 5. 💼 Job Board
- **Job Listings**: Post and browse career opportunities
- **Application System**: Apply with CV and cover letter
- **Applicant Tracking**: Employers can review and manage applications
- **Job Filters**: Search by location, type, company, or salary range

### 6. 🛒 Marketplace
- **Item Listings**: Buy and sell products within the alumni community
- **Shopping Cart**: Add multiple items before checkout
- **Payment Processing**: Secure payment gateway integration
- **Order Management**: Track purchases and sales history
- **Seller Profiles**: View items from specific sellers

### 7. 👥 Groups & Communities
- **Create Groups**: Form communities around shared interests
- **Group Chat**: Real-time messaging within groups
- **University Groups**: Official university-affiliated communities
- **Member Management**: Admin controls for group moderation

### 8. 🤝 Mentorship & Professional Development
- **Mentorship Programs**: Connect mentors with mentees
- **Skill Sharing**: Showcase expertise and find learning opportunities
- **Project Collaboration**: Crowdfunding and partnership opportunities
- **Business Ventures**: Support alumni entrepreneurship

---

## Database Design

### Core Tables

**Users & Authentication**
- `Users` - User accounts and basic information
- `AlumniVerification` - Alumni status verification records
- `UniversityAdmins` - University administrator assignments

**Academic Information**
- `University` - University profiles and details
- `AcademicDepartment` - Schools/faculties and programs
- `UserAcademicProfile` - User's educational background

**Social Features**
- `Posts` - User and university content (supports images)
- `PostLikes` - Like tracking for posts
- `PostComments` - Comment threads on posts
- `Messages` - Direct user-to-user messaging
- `Connections` - User follow relationships

**Groups & Communities**
- `Groups` - Community groups and official university groups
- `GroupMembers` - Group membership records
- `GroupChats` - Group messaging

**Events**
- `Events` - Event information and scheduling
- `EventAttendees` - RSVP and attendance tracking

**Career & Jobs**
- `JobListings` - Job postings
- `JobApplications` - Application submissions

**Marketplace & Commerce**
- `MarketplaceItems` - Items for sale
- `Cart` - Shopping cart items
- `PaymentOrders` - Payment transaction records
- `PaymentOrderItems` - Order line items
- `Orders` - Order history

**Professional Development**
- `UserSkills` - User skill profiles
- `UserExperience` - Work history
- `UserAchievements` - Awards and accomplishments
- `MentorshipPrograms` - Mentor-mentee relationships
- `Projects` - Collaborative projects
- `Businesses` - Alumni business ventures

### Database Relationships

**Polymorphic Relationships**
- Posts can be created by either users or universities
- Implemented using `creator_type` ENUM and nullable foreign keys

**Cascade Behaviors**
- User deletion cascades to posts, likes, comments (data cleanup)
- University deletion restricted if academic profiles exist (data integrity)
- Order items reference prevention (transaction history preservation)

---

## User Roles & Permissions

### Role Hierarchy

```
┌──────────────────────────────────────┐
│         Global Administrator          │
│  • Full system access                 │
│  • Manage all universities            │
│  • Approve university admins          │
│  • System-wide moderation             │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│      University Administrator         │
│  • University-scoped management       │
│  • Create official posts              │
│  • Approve alumni verifications       │
│  • Manage university events           │
│  • Assign university roles            │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│         Verified Alumni               │
│  • All regular user features          │
│  • Verified badge display             │
│  • Post job listings                  │
│  • Create events                      │
│  • Sell on marketplace                │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│          Regular User                 │
│  • Create posts                       │
│  • Comment and like                   │
│  • Join groups                        │
│  • Apply for jobs                     │
│  • Purchase from marketplace          │
└──────────────────────────────────────┘
```

### Permission Matrix

| Feature | Regular User | Verified Alumni | University Admin | Global Admin |
|---------|--------------|-----------------|------------------|--------------|
| View Content | ✅ | ✅ | ✅ | ✅ |
| Create Posts | ✅ | ✅ | ✅ | ✅ |
| Post Jobs | ❌ | ✅ | ✅ | ✅ |
| Create Events | ❌ | ✅ | ✅ | ✅ |
| Sell Items | ✅ | ✅ | ✅ | ✅ |
| Official University Posts | ❌ | ❌ | ✅ | ✅ |
| Approve Verifications | ❌ | ❌ | ✅ | ✅ |
| Assign Admins | ❌ | ❌ | ❌ | ✅ |
| Manage Universities | ❌ | ❌ | ✅ (scoped) | ✅ |

---

## Key Architectural Patterns

### 1. Model-View-Controller (MVC)

**Separation of Concerns**
- **Views**: User interface presentation (`view/` directory)
- **Controllers**: Business logic orchestration (`controllers/` and `actions/`)
- **Models**: Data operations and validation (`classes/`)

**Benefits**
- Maintainable and testable code
- Reusable components
- Clear responsibility boundaries

### 2. Role-Based Access Control (RBAC)

**Implementation**
- Session-based authentication
- Permission checks at action level
- Database-driven role assignment
- Context-aware authorization

**Security Features**
- Prepared SQL statements (SQL injection prevention)
- Password hashing (bcrypt)
- Session validation on all protected pages
- CSRF token validation for forms

### 3. Multi-Tenancy Architecture

**University Context System**
- Admins can manage multiple universities
- Context switching without re-authentication
- Scoped data access based on active context
- University-tagged content and operations

**Session Management**
```
$_SESSION['user_id']           → Current user identifier
$_SESSION['university_context'] → Active university scope
$_SESSION['logged_in']         → Authentication status
$_SESSION['admin_role']        → Administrative privileges
```

### 4. Service-Oriented Actions

**RESTful-Style Endpoints**
- Action files process specific operations
- JSON response format for AJAX calls
- Stateless request handling
- Clear naming conventions

**Example Actions**
- `post_like_action.php` - Toggle post likes
- `connection_action.php` - Manage user connections
- `cart_add.php` - Add items to shopping cart
- `verify_payment.php` - Process payments

### 5. Component-Based UI

**Reusable Components**
- `includes/post_item.php` - Post card display
- `includes/search_component.php` - Search functionality
- Consistent styling across pages
- Single source of truth for UI elements

### 6. Database Design Patterns

**Normalization**
- Third normal form (3NF) compliance
- Minimal data redundancy
- Foreign key relationships

**Soft Deletes & Constraints**
- `ON DELETE CASCADE` for dependent data
- `ON DELETE RESTRICT` for protected records
- `ON DELETE SET NULL` for optional relationships

**Audit Trails**
- `created_at` timestamps on all tables
- `created_by` fields for tracking
- `updated_at` for modification history

---

## Main User Workflows

### Workflow 1: New User Onboarding

```
1. Registration
   → User visits index.php
   → Fills registration form
   → Email validation
   → Account created in Users table
   → Welcome email sent

2. Profile Setup
   → Redirected to dashboard
   → Complete academic profile
   → Select university and department
   → Add graduation year
   → Upload profile photo (optional)

3. Alumni Verification
   → Navigate to alumni_verification.php
   → Upload verification documents
   → Verification request submitted (status: pending)
   → University admin reviews request
   → Admin approves/rejects verification
   → User receives verified badge if approved

4. Engage with Platform
   → Browse community feed
   → Connect with fellow alumni
   → Join groups and events
```

### Workflow 2: Social Engagement

```
Creating Content
   → Write post on homepage
   → Optional: Upload image
   → Submit post
   → Post appears in community feed
   → Other users can see and interact

Engaging with Posts
   → View post in feed
   → Click like → Heart fills, count increments
   → Click comment → Comment section expands
   → Write comment → Posted under post
   → Share → Post shared to profile

Building Network
   → Search for alumni (by name, university, skills)
   → View user profiles
   → Click "Follow" → Connection established
   → Followed user's posts appear in feed
   → Send direct message
```

### Workflow 3: E-Commerce Transaction

```
Seller Workflow
   → Navigate to marketplace
   → Click "Sell Item"
   → Fill item details (title, price, description)
   → Upload product images
   → Submit listing
   → Item appears on marketplace

Buyer Workflow
   → Browse marketplace
   → Filter by category/price
   → Click item → View details
   → Add to cart → Cart icon updates
   → Continue shopping or checkout
   → Review cart items
   → Proceed to checkout
   → Enter payment information
   → Submit payment
   → Payment processed
   → Order confirmation sent
   → Cart cleared
   → Order history updated
```

### Workflow 4: Job Application Process

```
Employer Posts Job
   → Navigate to jobs page
   → Click "Post Job"
   → Fill job details (title, company, description, salary)
   → Submit listing
   → Job appears on job board

Job Seeker Applies
   → Browse job listings
   → Filter jobs by criteria
   → Click job → View full description
   → Click "Apply"
   → Upload CV/resume
   → Write cover letter
   → Submit application
   → Application recorded (status: pending)

Employer Reviews
   → View posted jobs
   → Click job → View applicants
   → Review applicant profiles
   → Download CVs
   → Update application status (shortlisted/rejected)
   → Contact selected candidates
```

### Workflow 5: Event Management

```
Event Creation
   → Verified alumni or admin navigates to events
   → Click "Create Event"
   → Fill event details (title, date, location, description)
   → Set event type (conference, reunion, networking)
   → Submit event
   → Event published

Attendee Registration
   → Browse upcoming events
   → Filter by date/location
   → View event details
   → Click "RSVP" or "I'm Attending"
   → Registration confirmed
   → Event added to user's calendar
   → Reminder notifications sent

Event Management
   → Creator views manage_events page
   → See attendee list
   → Edit event details
   → Update notifications sent to attendees
   → Cancel event (if needed)
```

### Workflow 6: University Administration

```
Admin Assignment
   → Global admin logs in
   → Navigate to global_admin_panel
   → Search user by email
   → Select university
   → Assign as university admin
   → User notified of admin role

Admin Activation
   → User logs in
   → Context switcher appears
   → Select university to manage
   → University context activated
   → Access to admin panel

Admin Operations
   → Create official university posts
   → Review pending alumni verifications
   → Approve/reject verification requests
   → Create and manage university events
   → Post job opportunities
   → Manage university groups
   → Assign additional admins
```

### Workflow 7: Group & Community Engagement

```
Creating a Group
   → Navigate to groups page
   → Click "Create Group"
   → Enter group name and description
   → Set group type (study, networking, hobby)
   → Submit group
   → Group created, creator becomes admin

Joining a Group
   → Browse available groups
   → Search by interest or university
   → Click group → View details
   → Click "Join Group"
   → Membership confirmed
   → Group chat access granted

Group Interaction
   → Open group chat
   → View message history
   → Send messages to group
   → Real-time updates
   → Admin can manage members
   → Admin can moderate content
```

---

## Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 8.0+** - Relational database
- **PHPMailer** - Email functionality
- **Session Management** - User authentication

### Frontend
- **HTML5** - Page structure
- **CSS3** - Styling and responsive design
- **JavaScript (Vanilla)** - Dynamic interactions
- **AJAX** - Asynchronous data operations
- **FontAwesome** - Icon library

### Development Tools
- **XAMPP** - Local development environment
- **Apache** - Web server
- **phpMyAdmin** - Database management

### Architecture
- **MVC Pattern** - Code organization
- **RESTful Actions** - API-like endpoints
- **Component-Based UI** - Reusable templates

---

## Installation & Setup

### Prerequisites
- XAMPP (or LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Installation Steps

1. **Clone/Download Project**
   ```
   Place project folder in: C:\xampp\htdocs\ReConnectFinal Project\
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database: `reconnectdb2`
   - Import database schema:
     - Navigate to Import tab
     - Select file: `reconnectdb2.sql`
     - Click "Go" to execute
   - Verify all tables created successfully

3. **Configure Database Connection**
   - Open: `settings/db_cred.php`
   - Verify database credentials:
     ```php
     define("SERVER", "localhost");
     define("USERNAME", "root");
     define("PASSWORD", "");
     define("DATABASE", "reconnectdb2");
     ```

4. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

5. **Access Application**
   - Open browser
   - Navigate to: `http://localhost/ReConnectFinal%20Project/index.php`
   - Register new account or use test credentials

### Default Admin Account (Optional)
After database import, create an admin user:
```sql
-- Insert test user
INSERT INTO Users (first_name, last_name, email, password, phone_number) 
VALUES ('Admin', 'User', 'admin@reconnect.com', '$2y$10$hash_here', '1234567890');

-- Get the user_id from above insert
-- Assign as global admin (requires custom implementation or manual flag)
```

---

## Project Structure

```
ReConnectFinal Project/
│
├── actions/                     # Backend action handlers
│   ├── login_user_action.php   # User authentication
│   ├── register_user_action.php # User registration
│   ├── post_like_action.php    # Post interactions
│   ├── cart_add.php            # Shopping cart operations
│   └── ...
│
├── classes/                     # Data access layer
│   ├── user_class.php          # User data operations
│   ├── general_class.php       # General database methods
│   └── ...
│
├── controllers/                 # Business logic layer
│   ├── user_controller.php     # User management logic
│   ├── general_controller.php  # General operations
│   └── ...
│
├── view/                        # Presentation layer
│   ├── homepage.php            # Community feed
│   ├── dashboard.php           # User dashboard
│   ├── marketplace.php         # E-commerce platform
│   ├── jobs.php                # Job board
│   ├── events.php              # Events listing
│   ├── connections.php         # Network page
│   ├── groups.php              # Groups & communities
│   ├── profile.php             # User profiles
│   ├── includes/               # Reusable components
│   │   ├── post_item.php      # Post card template
│   │   └── search_component.php # Search bar
│   └── css/                    # Stylesheets
│
├── settings/                    # Configuration files
│   ├── db_class.php            # Database connection
│   ├── db_cred.php             # Database credentials
│   └── core.php                # Core settings
│
├── uploads/                     # User-uploaded files
│   ├── posts/                  # Post images
│   ├── profiles/               # Profile photos
│   └── documents/              # Verification documents
│
├── fontawesome/                 # Icon library
│   ├── css/
│   └── webfonts/
│
├── PHPMailer/                   # Email library
│   ├── PHPMailer.php
│   ├── SMTP.php
│   └── Exception.php
│
├── index.php                    # Landing/login page
├── reconnectdb2.sql            # Complete database schema
└── README.md                    # This file
```

---

## Key Features Breakdown

### Session Management
- Secure session handling across all pages
- Context preservation for admin users
- Auto-logout on inactivity
- Session validation on protected routes

### Search Functionality
- Global search across users, posts, groups
- Filter by university, department, graduation year
- Real-time search suggestions
- Search history tracking

### Notification System
- Email notifications for key events
- In-app notification indicators
- Event reminders
- Job application updates
- Message alerts

### File Upload Management
- Secure file upload validation
- Image optimization and resizing
- File type restrictions
- Storage quota management
- Path sanitization

### Responsive Design
- Mobile-friendly interfaces
- Tablet optimization
- Desktop-first design approach
- Touch-friendly controls
- Adaptive layouts

---

## Security Features

### Authentication & Authorization
- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control
- Permission validation on every action

### Data Protection
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF token validation
- Secure file uploads
- Output encoding

### Database Security
- Foreign key constraints
- Transaction support for critical operations
- Backup and recovery procedures
- Audit trail logging

---

## Future Enhancements

### Planned Features
- Real-time notifications (WebSocket integration)
- Video conferencing for mentorship sessions
- Advanced analytics dashboard
- Mobile application (iOS/Android)
- Alumni donation platform
- Certificate verification blockchain
- AI-powered job recommendations
- Social media integration
- Calendar synchronization
- Email newsletter system

### Scalability Considerations
- Caching layer implementation (Redis)
- CDN for static assets
- Database query optimization
- Load balancing support
- Microservices architecture migration

---

## Contributing

### Development Guidelines
- Follow MVC pattern
- Use prepared statements for all database queries
- Validate all user inputs
- Comment complex logic
- Test thoroughly before deployment

### Code Style
- Consistent indentation (4 spaces)
- Descriptive variable names
- Function documentation
- Error handling best practices

---

## Support & Documentation

### Additional Resources
- `UNIVERSITY_CONTEXT_GUIDE.md` - University admin context switching
- `IMAGE_UPLOAD_README.md` - Image upload implementation
- `GROUP_CHAT_README.md` - Group chat features

### Contact
For questions, issues, or contributions, please contact the development team.

---

## License

This project is developed as an educational platform for university alumni networking and career development.

---

**ReConnect** - Bridging Education and Career, One Connection at a Time.
