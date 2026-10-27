-- PostgreSQL Schema for dailyTakip v2
-- Target: PostgreSQL 9.2+ (compatible with MariaDB/MySQL 5.5+ concepts)

-- ============================================
-- 1. PROJECTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

-- ============================================
-- 2. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    global_role VARCHAR(20) DEFAULT NULL,  -- SUPER_ADMIN veya NULL (proje rolleri var)
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

-- ============================================
-- 3. PROJECT_MEMBERS TABLE (User-Project-Role relationship)
-- ============================================
CREATE TABLE IF NOT EXISTS project_members (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(20) NOT NULL CHECK (role IN ('PROJECT_MANAGER', 'COORDINATOR', 'MEMBER', 'VIEWER')),
    viewer_scope VARCHAR(10) DEFAULT NULL CHECK (viewer_scope IS NULL OR viewer_scope IN ('MANAGER', 'USER')),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(project_id, user_id)
);

-- Index for faster lookups
CREATE INDEX IF NOT EXISTS idx_project_members_user ON project_members(user_id);
CREATE INDEX IF NOT EXISTS idx_project_members_project ON project_members(project_id);

-- ============================================
-- 4. COORDINATOR_MANAGER TABLE (Hierarchy: Coordinator -> Manager)
-- ============================================
CREATE TABLE IF NOT EXISTS coordinator_manager (
    coordinator_user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    manager_user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    PRIMARY KEY (coordinator_user_id, manager_user_id)
);

-- ============================================
-- 5. DAILY NOTES (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS daily_notes (
    id VARCHAR(30) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    content TEXT NOT NULL,
    tags TEXT DEFAULT '',
    jira_link TEXT DEFAULT '',
    files TEXT DEFAULT '[]',
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_daily_notes_project ON daily_notes(project_id);
CREATE INDEX IF NOT EXISTS idx_daily_notes_user ON daily_notes(user_id);
CREATE INDEX IF NOT EXISTS idx_daily_notes_date ON daily_notes(date);

-- ============================================
-- 6. DAILY STATUSES (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS daily_statuses (
    id VARCHAR(30) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('OFFICE', 'REMOTE', 'HOLIDAY', 'SICK')),
    note TEXT DEFAULT '',
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_daily_statuses_project ON daily_statuses(project_id);
CREATE INDEX IF NOT EXISTS idx_daily_statuses_user ON daily_statuses(user_id);
CREATE INDEX IF NOT EXISTS idx_daily_statuses_date ON daily_statuses(date);

-- ============================================
-- 7. ATTENDANCES (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS attendances (
    id VARCHAR(30) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    present BOOLEAN DEFAULT TRUE,
    note TEXT DEFAULT '',
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_attendances_project ON attendances(project_id);
CREATE INDEX IF NOT EXISTS idx_attendances_user ON attendances(user_id);
CREATE INDEX IF NOT EXISTS idx_attendances_date ON attendances(date);

-- ============================================
-- 8. TODOS (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS todos (
    id VARCHAR(36) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    parent_id VARCHAR(36) DEFAULT NULL REFERENCES todos(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    creator_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    assigned_to VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'TODO' CHECK (status IN ('TODO', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED')),
    priority VARCHAR(10) NOT NULL DEFAULT 'NORMAL' CHECK (priority IN ('LOW', 'NORMAL', 'HIGH')),
    due_date DATE DEFAULT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    completed_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_todos_project ON todos(project_id);
CREATE INDEX IF NOT EXISTS idx_todos_creator ON todos(creator_id);
CREATE INDEX IF NOT EXISTS idx_todos_assigned ON todos(assigned_to);
CREATE INDEX IF NOT EXISTS idx_todos_status ON todos(status);
CREATE INDEX IF NOT EXISTS idx_todos_due_date ON todos(due_date);

-- ============================================
-- 9. ON-CALL (on_call, project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS on_call (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(project_id, date)
);

CREATE INDEX IF NOT EXISTS idx_on_call_project ON on_call(project_id);
CREATE INDEX IF NOT EXISTS idx_on_call_date ON on_call(date);

-- ============================================
-- 10. TEAM MEMBERS (on_call.team array karşılığı)
-- ============================================
CREATE TABLE IF NOT EXISTS on_call_team (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(project_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_on_call_team_project ON on_call_team(project_id);

-- ============================================
-- 11. HOLIDAYS (project_id eklenmiş veya GLOBAL)
-- ============================================
CREATE TABLE IF NOT EXISTS holidays (
    id SERIAL PRIMARY KEY,
    project_id INTEGER DEFAULT NULL REFERENCES projects(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_global BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(project_id, date)
);

CREATE INDEX IF NOT EXISTS idx_holidays_date ON holidays(date);

-- ============================================
-- 12. JIRA TASKS (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS jira_tasks (
    id VARCHAR(36) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    jira_link TEXT NOT NULL,
    title TEXT NOT NULL,
    priority VARCHAR(10) NOT NULL DEFAULT 'NORMAL' CHECK (priority IN ('LOW', 'NORMAL', 'HIGH')),
    assigned_to VARCHAR(30) DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    assigned_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED')),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    created_by VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_jira_tasks_project ON jira_tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_jira_tasks_assigned ON jira_tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_jira_tasks_status ON jira_tasks(status);

-- ============================================
-- 13. EVALUATION PERIODS (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS evaluation_periods (
    id VARCHAR(36) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_evaluation_periods_project ON evaluation_periods(project_id);
CREATE INDEX IF NOT EXISTS idx_evaluation_periods_date ON evaluation_periods(start_date, end_date);

-- ============================================
-- 14. EVALUATION CRITERIA (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS evaluation_criteria (
    id VARCHAR(50) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    weight INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_evaluation_criteria_project ON evaluation_criteria(project_id);

-- ============================================
-- 15. EVALUATIONS (project_id eklenmiş)
-- ============================================
CREATE TABLE IF NOT EXISTS evaluations (
    id VARCHAR(36) PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id VARCHAR(30) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    period_id VARCHAR(36) NOT NULL REFERENCES evaluation_periods(id) ON DELETE CASCADE,
    scores TEXT NOT NULL,  -- JSON string olarak saklanır
    notes TEXT DEFAULT '',
    evaluated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_evaluations_project ON evaluations(project_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_user ON evaluations(user_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_period ON evaluations(period_id);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_global_role ON users(global_role);
