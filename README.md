# Job Board API

A Laravel-based RESTful API for managing job listings with advanced filtering capabilities, built as part of a practical assessment for a Laravel Backend Developer role. The application supports job listings with standard fields, many-to-many relationships (languages, locations, categories), an Entity-Attribute-Value (EAV) system for dynamic attributes, and a powerful filtering API.

## Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Setup Instructions](#setup-instructions)
- [API Endpoints](#api-endpoints)
  - [GET /api/jobs](#get-apijobs)
  - [Filter Syntax](#filter-syntax)
  - [Example Queries](#example-queries)
  - [Response Format](#response-format)
- [Postman Collection](#postman-collection)
- [Assumptions and Design Decisions](#assumptions-and-design-decisions)
- [Trade-offs](#trade-offs)

## Project Overview

This project implements a job board API with advanced filtering capabilities, similar to Airtable's filtering system. It uses Laravel 11.x and follows best practices for code quality, database design, query efficiency, and documentation. The application supports:

- A `Job` model with standard fields (title, description, salary, etc.).
- Many-to-many relationships with `Languages`, `Locations`, and `Categories`.
- An EAV system for dynamic attributes based on job types.
- A RESTful API endpoint (`GET /api/jobs`) with complex filtering, logical operators, and grouping.

## Features

- **Core Job Model**: Includes fields like `title`, `description`, `company_name`, `salary_min`, `salary_max`, `is_remote`, `job_type`, `status`, and `published_at`.
- **Many-to-Many Relationships**:
  - `Languages`: Programming languages required for the job.
  - `Locations`: Possible job locations.
  - `Categories`: Job categories/departments.
- **EAV System**: Dynamic attributes with support for `text`, `number`, `boolean`, `date`, and `select` types.
- **Advanced Filtering API**:
  - Basic field filtering (e.g., `job_type=full-time`).
  - Relationship filtering (e.g., `languages HAS_ANY (PHP,JavaScript)`).
  - EAV attribute filtering (e.g., `attribute:years_experience>=3`).
  - Logical operators (`AND`, `OR`) and grouping with parentheses.
- **Sorting**: Supports sorting by fields (e.g., `sort=salary_min:asc`).
- **Pagination**: Returns paginated results with metadata.
- **Query Efficiency**: Uses joins for relationship and EAV filtering to minimize N+1 problems.
- **Documentation**: Comprehensive API documentation with examples.

## Setup Instructions

Follow these steps to set up the project locally:

1. **Clone the Repository**:
   ```bash
   git clone <[repository-url](https://github.com/Abdelrahman20180315/job-board-assessment-task.git)>
   cd job-board

Install Dependencies:
Ensure you have Composer installed, then run:

bash

composer install
Configure Environment:
Copy the .env.example file to .env and update the database settings:

bash

cp .env.example .env
Edit the .env file to match your database configuration:

env


DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=job_board
DB_USERNAME=root
DB_PASSWORD=
Generate Application Key:


php artisan key:generate
Run Migrations and Seed the Database:
Create the database tables and populate them with sample data:


php artisan migrate:fresh --seed
This will create the following tables:

jobs
languages
locations
categories
attributes
job_attribute_values
Pivot tables: job_language, job_location, category_job
The seeder will populate the database with 50 sample jobs, along with related languages, locations, categories, and EAV attributes.

Serve the Application:
Start the Laravel development server:


php artisan serve
The API will be available at http://localhost:8000.

API Endpoints
GET /api/jobs
Returns a paginated list of jobs with filtering and sorting capabilities.

Query Parameters
filter (optional): A string defining the filter conditions (see ).
sort (optional): A string defining the sort order (e.g., salary_min:asc or created_at:desc).
page (optional): The page number for pagination (default: 1).
per_page (optional): The number of items per page (default: 20).
Filter Syntax
The filter parameter supports complex conditions with the following syntax:

Basic Format: field:operator:value or field operator value
Logical Operators: AND, OR
Grouping: (condition AND condition)
Supported Operators:
Equality: =, !=
Comparison: >, >=, <, <=
Contains: LIKE
Multiple Values: IN(value1,value2)
Relationship: HAS_ANY, IS_ANY, EXISTS
EAV Attributes: attribute:name:operator:value
Supported Fields and Operators
Basic Fields:
Fields: title, description, company_name, salary_min, salary_max, is_remote, job_type, status, published_at, created_at, updated_at
Operators:
Text/String: =, !=, LIKE
Numeric (e.g., salary_min): =, !=, >, >=, <, <=
Boolean (e.g., is_remote): =, !=
Enum (e.g., job_type): =, !=, IN
Date (e.g., published_at): =, !=, >, >=, <, <=
Relationship Fields:
Fields: languages, locations, categories
Operators:
HAS_ANY: Job has any of the specified values (e.g., languages HAS_ANY (PHP,JavaScript)).
IS_ANY: Relationship matches any of the values (similar to HAS_ANY).
EXISTS: Relationship exists (e.g., languages EXISTS).
EAV Attributes:
Format: attribute:name:operator:value
Operators (depend on attribute type):
Text: =, !=, LIKE
Number: =, !=, >, >=, <, <=
Boolean: =, !=
Select: =, !=, IN
Date: =, !=, >, >=, <, <=
Example Queries
Filter by Job Type and Salary:
text

/api/jobs?filter=job_type=full-time AND salary_min>=50000
Returns full-time jobs with a minimum salary of at least 50,000.
Filter by Languages and Locations:


/api/jobs?filter=languages HAS_ANY (PHP,JavaScript) AND locations IS_ANY (New York,Remote)
Returns jobs that require PHP or JavaScript and are located in New York or are remote.
Filter by EAV Attribute:



/api/jobs?filter=attribute:years_experience>=3
Returns jobs requiring at least 3 years of experience.
Complex Filter with Grouping:


/api/jobs?filter=(job_type=full-time AND (languages HAS_ANY (PHP,JavaScript))) AND (locations IS_ANY (New York,Remote)) AND attribute:years_experience>=3
Returns full-time jobs that require PHP or JavaScript, are located in New York or are remote, and require at least 3 years of experience.
Sort by Salary:

/api/jobs?filter=job_type=full-time&sort=salary_min:desc
Returns full-time jobs sorted by minimum salary in descending order.
Response Format
The response is a JSON object containing the paginated list of jobs and metadata.

json


{
    "data": [
        {
            "id": 1,
            "title": "Senior Developer",
            "description": "Job description",
            "company_name": "Tech Corp",
            "salary_min": 80000.00,
            "salary_max": 120000.00,
            "is_remote": true,
            "job_type": "full-time",
            "status": "published",
            "published_at": "2025-03-01T00:00:00.000000Z",
            "created_at": "2025-03-01T00:00:00.000000Z",
            "updated_at": "2025-03-01T00:00:00.000000Z",
            "languages": [
                {"id": 1, "name": "PHP"},
                {"id": 2, "name": "JavaScript"}
            ],
            "locations": [
                {"id": 1, "city": "New York", "state": "NY", "country": "USA"}
            ],
            "categories": [
                {"id": 1, "name": "Engineering"}
            ],
            "attribute_values": [
                {
                    "id": 1,
                    "attribute_id": 1,
                    "value": "5",
                    "attribute": {
                        "id": 1,
                        "name": "years_experience",
                        "type": "number",
                        "options": null
                    }
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 50,
        "per_page": 20
    }
}
Error Responses
400 Bad Request: Invalid filter or sort parameter.
json


{
    "error": "Invalid filter or sort parameter",
    "message": "Invalid field: invalid_field"
}
500 Internal Server Error: Unexpected server error.
json

{
    "error": "Internal server error",
    "message": "Detailed error message"
}
Postman Collection
A Postman collection is included in the repository at postman_collection.json. Import it into Postman to test the API endpoints. The collection includes example requests for various filter scenarios, such as:

Basic filtering by job type and salary.
Relationship filtering by languages and locations.
EAV attribute filtering.
Complex filtering with grouping and sorting.
To use the Postman collection:

Open Postman.
Click "Import" and select the postman_collection.json file.
Set the base_url variable in the collection to http://localhost:8000 (or your server URL).
Run the requests to test the API.
Assumptions and Design Decisions
EAV Implementation:
Used a single value column in the job_attribute_values table as a string, with type casting handled in the application layer. This simplifies the schema but may not be optimal for performance with large datasets.
Cached attribute lookups using Laravel’s caching system to reduce database queries for EAV filtering.
Query Efficiency:
Used joins instead of subqueries for relationship and EAV filtering to improve performance with large datasets.
Added distinct() to handle duplicate rows that may result from joins.
Included indexes on frequently filtered columns (e.g., job_type, status, value in job_attribute_values).
Filter Parser:
Designed a custom filter parser to handle complex conditions, logical operators, and grouping.
The parser is extensible, allowing for additional operators or filter types to be added easily.
Error Handling:
Added comprehensive error handling in the controller to return meaningful error messages for invalid filter or sort parameters.
Used Laravel’s exception handling to catch unexpected errors and return a 500 response.
Sorting:
Added support for sorting on standard fields (e.g., salary_min:asc).
Sorting on EAV attributes is not implemented due to complexity but can be added by joining the job_attribute_values table.
Database Seeding:
Seeded the database with 50 sample jobs, each with random relationships and EAV attributes, to facilitate testing.
Included a variety of data to test different filter scenarios (e.g., different job types, locations, and experience levels).
Trade-offs
Joins vs. Subqueries:
Decision: Used joins for relationship and EAV filtering to improve query performance.
Trade-off: Joins can lead to duplicate rows if not handled properly, requiring distinct() to be used. Subqueries (via whereHas) are simpler but less efficient for large datasets.
EAV Performance:
Decision: Used a single value column for EAV attributes to keep the schema simple and flexible.
Trade-off: This requires type casting in the application layer and may not be as performant as using separate columns for different types (e.g., value_text, value_number). For a production system, denormalizing frequently accessed attributes into the jobs table could improve performance.
Filter Complexity:
Decision: Implemented a custom filter parser to support complex conditions, logical operators, and grouping.
Trade-off: The parser adds complexity to the codebase but provides a powerful and flexible filtering system. A simpler approach (e.g., using query parameters like job_type=full-time&salary_min=50000) would be easier to implement but less expressive.
Sorting on EAV Attributes:
Decision: Did not implement sorting on EAV attributes to keep the initial implementation focused on filtering.
Trade-off: Users cannot sort by dynamic attributes (e.g., attribute:years_experience:desc). This could be added by joining the job_attribute_values table and sorting on the value column, but it would increase query complexity.
Caching:
Decision: Cached attribute lookups for EAV filtering but did not cache query results.
Trade-off: Caching query results could further improve performance for frequently accessed filters, but it would add complexity and require cache invalidation logic.
text

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
