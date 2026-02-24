CREATE TABLE project (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_by_user_id INTEGER NOT NULL,
    github_repo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
