# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Environment

This is a **Laravel 11 API** project with Docker containerization. The main application code is in the `/src` directory.

### Docker Setup Commands

```bash
# Initial setup with database
make run-app-with-setup-db

# Initial setup without database seeding
make run-app-with-setup

# Run existing app
make run-app

# Stop containers
make kill-app
```

### Container Access

```bash
# Enter PHP container (for artisan commands, composer, etc.)
make enter-php-container

# Enter MySQL container
make enter-mysql-container

# Enter Nginx container
make enter-nginx-container
```

### Database Commands

```bash
# Fresh migration
make flush-db

# Fresh migration with seeding
make flush-db-with-seeding

# Initialize database with necessary data
docker exec php /bin/sh -c "php artisan db:init-combo"
```

### Development Commands

```bash
# Format code
make code-format

# Check code formatting
make code-format-check

# Run tests
make code-test

# Inside container: Run migrations
php artisan migrate

# Inside container: Generate app key
php artisan key:generate
```

## Application Architecture

### Core Domain Models

This is a **course management system** with the following main entities:

- **ClubCourse**: Main course entity
- **ClubCourseInfo**: Detailed course information with many-to-many relationships
- **Member**: User members with roles and profiles
- **Order**: Order system for course purchases
- **Product**: Course products

### Key Model Relationships

- **ClubCourseInfo** has many-to-many relationships with:
  - `CourseInfoType` (course types)
  - `CourseStatusType` (course statuses)  
  - `LangType` (languages)
  - `LevelType` (difficulty levels)
  - `TeachMethodType` (teaching methods)
  - `FollowerClubCourseInfo` (course followers)
  - `VisiblerClubCourseInfo` (course visibility)

### API Structure

All API routes are in `/src/routes/api.php` with RESTful endpoints:

- `invitation-codes/` - Invitation code management
- `members/` - Member management  
- `notices/` - Notice system
- `products/` - Product catalog with follower/visibility features
- `orders/` - Order management
- `club-course-info/` - Course information
- `club-course/` - Course management
- Various type endpoints (`roles/`, `notice-types/`, `level-types/`, etc.)

### Database Schema

The database uses MySQL with comprehensive type tables for categorization. Key tables include:

- Core entities: `members`, `club_courses`, `club_course_infos`, `orders`, `products`
- Type tables: `roles`, `course_info_types`, `course_status_types`, `lang_types`, `level_types`, `teach_method_types`, `notice_types`
- Junction tables for many-to-many relationships

### Working Directory Structure

- `/src/` - Main Laravel application
- `/src/app/Models/` - Eloquent models
- `/src/app/Http/Controllers/` - API controllers
- `/src/database/migrations/` - Database migrations
- `/src/database/sqls/init_necessary.sql` - Initial database schema
- `/src/routes/api.php` - API route definitions

## Application URL

The application runs on **http://localhost:8001** when using Docker.