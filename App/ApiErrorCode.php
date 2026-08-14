<?php
namespace App;

/**
 * Stable machine-readable API error codes for /api/* clients (mobile, integrations).
 * Documented in docs/API_ERROR_CODES.md and docs/api-error-codes.json.
 */
final class ApiErrorCode
{
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const UNAUTHORIZED_CLIENT = 'UNAUTHORIZED_CLIENT';
    public const FORBIDDEN = 'FORBIDDEN';
    public const FORBIDDEN_PROJECT = 'FORBIDDEN_PROJECT';
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const NOT_FOUND = 'NOT_FOUND';
    public const CONFLICT = 'CONFLICT';
    public const TWO_FACTOR_REQUIRED = 'TWO_FACTOR_REQUIRED';
    public const TWO_FACTOR_NO_EMAIL = 'TWO_FACTOR_NO_EMAIL';
    public const TWO_FACTOR_SEND_FAILED = 'TWO_FACTOR_SEND_FAILED';
    public const TWO_FACTOR_INVALID_CODE = 'TWO_FACTOR_INVALID_CODE';
    public const TWO_FACTOR_CHALLENGE_EXPIRED = 'TWO_FACTOR_CHALLENGE_EXPIRED';
    public const TWO_FACTOR_CHALLENGE_NOT_FOUND = 'TWO_FACTOR_CHALLENGE_NOT_FOUND';
    public const TWO_FACTOR_LOCKED = 'TWO_FACTOR_LOCKED';
    public const PASSWORD_EXPIRED = 'PASSWORD_EXPIRED';
    public const RATE_LIMITED = 'RATE_LIMITED';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';
}
