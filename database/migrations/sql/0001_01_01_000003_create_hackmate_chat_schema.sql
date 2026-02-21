-- 1. tech_stacks
CREATE TABLE tech_stacks (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(255)
);

-- 2. user_tech (Users <-> Tech)
CREATE TABLE user_tech (
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tech_id BIGINT NOT NULL REFERENCES tech_stacks(id) ON DELETE CASCADE,
    type VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, tech_id)
);

-- 3. groups
CREATE TABLE groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tech_id BIGINT NOT NULL REFERENCES tech_stacks(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. group_user (Users <-> Groups)
CREATE TABLE group_user (
    group_id BIGINT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id)
);

-- 5. group_chat
CREATE TABLE group_chat (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    sender_id BIGINT NOT NULL REFERENCES users(id),
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. chat_read_status
CREATE TABLE chat_read_status (
    message_id BIGINT NOT NULL REFERENCES group_chat(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    is_read BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (message_id, user_id)
);

