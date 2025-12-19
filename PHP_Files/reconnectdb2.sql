SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ==============================================
-- Table: University
-- ==============================================
CREATE TABLE IF NOT EXISTS University (
  university_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(255) NOT NULL,
  location      VARCHAR(255),
  website       VARCHAR(255),
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  address       TEXT,
  established_year YEAR,
  university_type ENUM('public','private','religious','technical','other'),
  description   TEXT,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (university_id),
  INDEX idx_university_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Users
-- ==============================================
CREATE TABLE IF NOT EXISTS Users (
  user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  phone         VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  profile_photo VARCHAR(255),
  bio           TEXT,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY unique_email (email),
  INDEX idx_users_name (first_name, last_name),
  INDEX idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: AlumniVerification
-- ==============================================
CREATE TABLE IF NOT EXISTS AlumniVerification (
  verification_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED NOT NULL,
  university_id      INT UNSIGNED NOT NULL,
  graduation_year    YEAR,
  student_id_number  VARCHAR(100),
  verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  verified_at        TIMESTAMP NULL DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (verification_id),
  INDEX idx_verification_user (user_id),
  INDEX idx_verification_status (verification_status),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: AcademicDepartment
-- ==============================================
CREATE TABLE IF NOT EXISTS AcademicDepartment (
  department_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  university_id   INT UNSIGNED NOT NULL,
  faculty         VARCHAR(255),
  department_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (department_id),
  INDEX idx_department_university (university_id),
  INDEX idx_department_name (department_name),
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UserAcademicProfile
-- ==============================================
CREATE TABLE IF NOT EXISTS UserAcademicProfile (
  profile_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  department_id   INT UNSIGNED NOT NULL,
  graduation_year YEAR,
  degree          VARCHAR(100),
  PRIMARY KEY (profile_id),
  INDEX idx_academic_user (user_id),
  INDEX idx_academic_department (department_id),
  INDEX idx_academic_year (graduation_year),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES AcademicDepartment(department_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UserSkills
-- ==============================================
CREATE TABLE IF NOT EXISTS UserSkills (
  skill_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  skill_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (skill_id),
  INDEX idx_skills_user (user_id),
  INDEX idx_skills_name (skill_name),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UserExperience
-- ==============================================
CREATE TABLE IF NOT EXISTS UserExperience (
  experience_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  company_name  VARCHAR(255) NOT NULL,
  role          VARCHAR(255),
  start_date    DATE,
  end_date      DATE,
  description   TEXT,
  PRIMARY KEY (experience_id),
  INDEX idx_experience_user (user_id),
  INDEX idx_experience_company (company_name),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UserAchievements
-- ==============================================
CREATE TABLE IF NOT EXISTS UserAchievements (
  achievement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT,
  date_achieved  DATE,
  PRIMARY KEY (achievement_id),
  INDEX idx_achievements_user (user_id),
  INDEX idx_achievements_date (date_achieved),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: MentorshipPrograms
-- ==============================================
CREATE TABLE IF NOT EXISTS MentorshipPrograms (
  mentorship_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mentor_id     INT UNSIGNED NOT NULL,
  mentee_id     INT UNSIGNED NOT NULL,
  status        ENUM('pending','active','completed') NOT NULL DEFAULT 'pending',
  matched_on    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (mentorship_id),
  INDEX idx_mentorship_mentor (mentor_id),
  INDEX idx_mentorship_mentee (mentee_id),
  INDEX idx_mentorship_status (status),
  FOREIGN KEY (mentor_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (mentee_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_different_users CHECK (mentor_id != mentee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: MentorSkills
-- ==============================================
CREATE TABLE IF NOT EXISTS MentorSkills (
  mentor_skill_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  skill_name      VARCHAR(100) NOT NULL,
  PRIMARY KEY (mentor_skill_id),
  INDEX idx_mentor_skills_user (user_id),
  INDEX idx_mentor_skills_name (skill_name),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Businesses
-- ==============================================
CREATE TABLE IF NOT EXISTS Businesses (
  business_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id INT UNSIGNED NOT NULL,
  name          VARCHAR(255) NOT NULL,
  description   TEXT,
  category      VARCHAR(100),
  verified      TINYINT(1) NOT NULL DEFAULT 0,
  website       VARCHAR(255),
  contact_email VARCHAR(255),
  PRIMARY KEY (business_id),
  INDEX idx_business_owner (owner_user_id),
  INDEX idx_business_category (category),
  INDEX idx_business_verified (verified),
  FOREIGN KEY (owner_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: MarketplaceItems
-- ==============================================
CREATE TABLE IF NOT EXISTS MarketplaceItems (
  item_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  seller_id  INT UNSIGNED NOT NULL,
  title      VARCHAR(255) NOT NULL,
  description TEXT,
  price      DECIMAL(12,2) NOT NULL,
  category   VARCHAR(100),
  image_url  VARCHAR(255),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status     ENUM('available','sold','removed') NOT NULL DEFAULT 'available',
  PRIMARY KEY (item_id),
  INDEX idx_marketplace_seller (seller_id),
  INDEX idx_marketplace_status (status),
  INDEX idx_marketplace_category (category),
  INDEX idx_marketplace_created (created_at),
  FOREIGN KEY (seller_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_price_positive CHECK (price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Orders
-- ==============================================
CREATE TABLE IF NOT EXISTS Orders (
  order_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  buyer_id  INT UNSIGNED NOT NULL,
  item_id   INT UNSIGNED NOT NULL,
  order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status    ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (order_id),
  INDEX idx_orders_buyer (buyer_id),
  INDEX idx_orders_item (item_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_date (order_date),
  FOREIGN KEY (buyer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES MarketplaceItems(item_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Cart (consolidated from create_cart_table.sql)
-- ==============================================
CREATE TABLE IF NOT EXISTS Cart (
  cart_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  UNIQUE KEY unique_user_item (user_id, item_id),
  INDEX idx_cart_user (user_id),
  INDEX idx_cart_item (item_id),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES MarketplaceItems(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: PaymentOrders (consolidated from create_orders_tables.sql)
-- ==============================================
CREATE TABLE IF NOT EXISTS PaymentOrders (
  order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_reference VARCHAR(255) UNIQUE NOT NULL,
  payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: PaymentOrderItems (consolidated from create_orders_tables.sql)
-- ==============================================
CREATE TABLE IF NOT EXISTS PaymentOrderItems (
  order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  seller_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES PaymentOrders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES MarketplaceItems(item_id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Groups
-- ==============================================
CREATE TABLE IF NOT EXISTS `Groups` (
  group_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id INT UNSIGNED,
  university_id INT UNSIGNED,
  name       VARCHAR(255) NOT NULL,
  description TEXT,
  group_type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id),
  INDEX idx_groups_type (group_type),
  INDEX idx_groups_name (name),
  INDEX idx_groups_owner (owner_user_id),
  INDEX idx_groups_university (university_id),
  FOREIGN KEY (owner_user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: GroupMembers
-- ==============================================
CREATE TABLE IF NOT EXISTS GroupMembers (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id  INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NOT NULL,
  is_admin  TINYINT(1) NOT NULL DEFAULT 0,
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_group_member (group_id, user_id),
  INDEX idx_group_members_group (group_id),
  INDEX idx_group_members_user (user_id),
  INDEX idx_group_members_admin (group_id, is_admin),
  FOREIGN KEY (group_id) REFERENCES `Groups`(group_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Projects (Investment Hub)
-- ==============================================
CREATE TABLE IF NOT EXISTS Projects (
  project_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id     INT UNSIGNED NOT NULL,
  title        VARCHAR(255) NOT NULL,
  description  TEXT,
  category     VARCHAR(100),
  funding_goal DECIMAL(14,2),
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id),
  INDEX idx_projects_owner (owner_id),
  INDEX idx_projects_category (category),
  INDEX idx_projects_created (created_at),
  FOREIGN KEY (owner_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: ProjectInvestments
-- ==============================================
CREATE TABLE IF NOT EXISTS ProjectInvestments (
  investment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id    INT UNSIGNED NOT NULL,
  investor_id   INT UNSIGNED NOT NULL,
  amount        DECIMAL(14,2) NOT NULL,
  invested_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (investment_id),
  INDEX idx_investments_project (project_id),
  INDEX idx_investments_investor (investor_id),
  INDEX idx_investments_date (invested_at),
  FOREIGN KEY (project_id) REFERENCES Projects(project_id) ON DELETE CASCADE,
  FOREIGN KEY (investor_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_investment_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Events
-- ==============================================
CREATE TABLE IF NOT EXISTS Events (
  event_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  host_user_id   INT UNSIGNED NOT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT,
  event_type     VARCHAR(50) NOT NULL,
  start_datetime DATETIME NOT NULL,
  location       VARCHAR(255),
  event_image    VARCHAR(255),
  PRIMARY KEY (event_id),
  INDEX idx_events_host (host_user_id),
  INDEX idx_events_type (event_type),
  INDEX idx_events_datetime (start_datetime),
  FOREIGN KEY (host_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: EventAttendees
-- ==============================================
CREATE TABLE IF NOT EXISTS EventAttendees (
  attendee_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id    INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  PRIMARY KEY (attendee_id),
  UNIQUE KEY unique_event_attendee (event_id, user_id),
  INDEX idx_attendees_event (event_id),
  INDEX idx_attendees_user (user_id),
  FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Posts (Community Feed)
-- ==============================================
CREATE TABLE IF NOT EXISTS Posts (
  post_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id   INT UNSIGNED,
  university_id INT UNSIGNED,
  content   TEXT NOT NULL,
  image_url VARCHAR(500),
  post_type VARCHAR(50) NOT NULL,
  creator_type ENUM('user', 'university') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id),
  INDEX idx_posts_user (user_id),
  INDEX idx_posts_university (university_id),
  INDEX idx_posts_type (post_type),
  INDEX idx_posts_creator (creator_type),
  INDEX idx_posts_created (created_at),
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE CASCADE,
  CONSTRAINT chk_posts_creator CHECK ((creator_type = 'user' AND user_id IS NOT NULL AND university_id IS NULL) OR (creator_type = 'university' AND university_id IS NOT NULL AND user_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: PostLikes (consolidated from migrations_post_likes.sql)
-- ==============================================
CREATE TABLE IF NOT EXISTS PostLikes (
  like_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (like_id),
  UNIQUE KEY unique_post_like (post_id, user_id),
  INDEX idx_likes_post (post_id),
  INDEX idx_likes_user (user_id),
  INDEX idx_likes_created (created_at),
  FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: PostComments
-- ==============================================
CREATE TABLE IF NOT EXISTS PostComments (
  comment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  comment    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (comment_id),
  INDEX idx_comments_post (post_id),
  INDEX idx_comments_user (user_id),
  INDEX idx_comments_created (created_at),
  FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: Messages (Direct user-to-user messaging)
-- ==============================================
CREATE TABLE IF NOT EXISTS Messages (
  message_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_id  INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  content    TEXT NOT NULL,
  sent_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  INDEX idx_messages_sender (sender_id),
  INDEX idx_messages_receiver (receiver_id),
  INDEX idx_messages_conversation (sender_id, receiver_id),
  INDEX idx_messages_sent (sent_at),
  FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: GroupChats
-- ==============================================
CREATE TABLE IF NOT EXISTS GroupChats (
  chat_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id  INT UNSIGNED NOT NULL,
  created_by INT UNSIGNED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (chat_id),
  INDEX idx_group_chats_group (group_id),
  INDEX idx_group_chats_creator (created_by),
  FOREIGN KEY (group_id) REFERENCES `Groups`(group_id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: GroupChatMessages
-- ==============================================
CREATE TABLE IF NOT EXISTS GroupChatMessages (
  msg_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  chat_id   INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  content   TEXT NOT NULL,
  sent_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (msg_id),
  INDEX idx_group_messages_chat (chat_id),
  INDEX idx_group_messages_sender (sender_id),
  INDEX idx_group_messages_sent (sent_at),
  FOREIGN KEY (chat_id) REFERENCES GroupChats(chat_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: JobListings
-- ==============================================
CREATE TABLE IF NOT EXISTS JobListings (
  job_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  posted_by INT UNSIGNED NOT NULL,
  title     VARCHAR(255) NOT NULL,
  company   VARCHAR(255),
  description TEXT,
  location  VARCHAR(255),
  job_type  VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  INDEX idx_jobs_poster (posted_by),
  INDEX idx_jobs_type (job_type),
  INDEX idx_jobs_location (location),
  INDEX idx_jobs_created (created_at),
  FOREIGN KEY (posted_by) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: JobApplications
-- ==============================================
CREATE TABLE IF NOT EXISTS JobApplications (
  application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id         INT UNSIGNED NOT NULL,
  user_id        INT UNSIGNED NOT NULL,
  cover_letter   TEXT,
  submitted_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  UNIQUE KEY unique_job_application (job_id, user_id),
  INDEX idx_applications_job (job_id),
  INDEX idx_applications_user (user_id),
  INDEX idx_applications_submitted (submitted_at),
  FOREIGN KEY (job_id) REFERENCES JobListings(job_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UniversityAdmins
-- ==============================================
CREATE TABLE IF NOT EXISTS UniversityAdmins (
  ua_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  university_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ua_id),
  UNIQUE KEY unique_university_admin (university_id, user_id),
  INDEX idx_university_admin_univ (university_id),
  INDEX idx_university_admin_user (user_id),
  INDEX idx_university_admin_status (status),
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- Table: UserConnections
-- ==============================================
CREATE TABLE IF NOT EXISTS UserConnections (
  connection_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id_1 INT UNSIGNED NOT NULL,
  user_id_2 INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (connection_id),
  UNIQUE KEY unique_connection (user_id_1, user_id_2),
  INDEX idx_connections_user1 (user_id_1),
  INDEX idx_connections_user2 (user_id_2),
  INDEX idx_connections_status (status),
  FOREIGN KEY (user_id_1) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id_2) REFERENCES Users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_different_connection_users CHECK (user_id_1 != user_id_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;