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

-- 課堂狀態種類
CREATE TABLE IF NOT EXISTS course_status_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='課堂狀態種類';

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

-- 哪裡知道我們的來源種類
CREATE TABLE IF NOT EXISTS referral_source_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='哪裡知道我們的來源種類';

-- 職業種類
CREATE TABLE IF NOT EXISTS occupation_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='職業種類';

-- 目標種類
CREATE TABLE IF NOT EXISTS goal_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='目標種類';

-- 目的種類
CREATE TABLE IF NOT EXISTS purpose_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='目的種類';

-- 學歷種類
CREATE TABLE IF NOT EXISTS education_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='學歷種類';

-- 學校種類
CREATE TABLE IF NOT EXISTS school_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='學校種類';

-- 系所種類
CREATE TABLE IF NOT EXISTS department_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系所種類';

-- 證照種類
CREATE TABLE IF NOT EXISTS certificate_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='證照種類';

-- 輪播圖種類
CREATE TABLE IF NOT EXISTS slideshow_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique name for each type',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique URL slug',
    note TEXT COMMENT 'Additional notes or description',
    sort INT DEFAULT 0 COMMENT 'Sort order',
    status BOOLEAN DEFAULT TRUE COMMENT 'Status: active or inactive',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='輪播圖種類';