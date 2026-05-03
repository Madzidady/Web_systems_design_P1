# Session 6 – URL Shortener Module

## Purpose of the module

This module allows users to create short URLs from long URLs and redirect users from a short code to the original URL.

## Functional requirements

The system accepts a long URL and returns a short URL.
The system redirects users from a short code to the original URL.
The system validates the original URL.
The system stores URL mappings in SQLite.
The system counts redirects using click_count.

## Non-functional requirements

The redirect should be fast.
The short code should be unique.
The API should return consistent JSON responses.
The system should use correct HTTP status codes.
The list endpoint should use file-based cache.

## Base62 encoding

Base62 converts numeric IDs into short text codes using digits, lowercase letters, and uppercase letters. This is useful because database IDs can be represented as short readable strings.

## Endpoints

POST /api/78688/v1/short-links
GET /api/78688/v1/short-links
GET /api/78688/v1/short-links/{id}
GET /r/{code}

## Testing evidence

Include screenshots of successful POST, GET list, validation error 422, redirect 302, missing code 404, and file cache verification.
