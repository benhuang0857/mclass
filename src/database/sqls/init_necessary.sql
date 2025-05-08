-- 角色
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色';

-- 授課形式
CREATE TABLE IF NOT EXISTS teach_method_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='授課形式';

-- 課堂種類
CREATE TABLE IF NOT EXISTS course_info_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='課堂種類';

-- 通知種類
CREATE TABLE IF NOT EXISTS notice_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知種類';

-- 語言種類
CREATE TABLE IF NOT EXISTS lang_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='語言種類';

-- 等級種類
CREATE TABLE IF NOT EXISTS level_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='等級種類';