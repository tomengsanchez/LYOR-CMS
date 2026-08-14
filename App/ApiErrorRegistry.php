<?php
namespace App;

/**
 * Metadata for API error codes (docs + GET /api/meta/error-codes).
 *
 * @return list<array{code:string,http_status:int,message:string,retry:bool,mobile_action:string}>
 */
final class ApiErrorRegistry
{
    public static function all(): array
    {
        return [
            [
                'code' => ApiErrorCode::UNAUTHORIZED,
                'http_status' => 401,
                'message' => 'Authentication required or invalid token.',
                'retry' => false,
                'mobile_action' => 'Clear stored token and show login.',
            ],
            [
                'code' => ApiErrorCode::UNAUTHORIZED_CLIENT,
                'http_status' => 401,
                'message' => 'Missing or invalid API client credentials (X-App-Id / X-App-Secret).',
                'retry' => false,
                'mobile_action' => 'Attach official app id/secret headers; if misconfigured, contact admin (not a user login failure).',
            ],
            [
                'code' => ApiErrorCode::FORBIDDEN,
                'http_status' => 403,
                'message' => 'You do not have permission for this action.',
                'retry' => false,
                'mobile_action' => 'Hide the feature or show a permission message.',
            ],
            [
                'code' => ApiErrorCode::FORBIDDEN_PROJECT,
                'http_status' => 403,
                'message' => 'Project not allowed for the current user.',
                'retry' => false,
                'mobile_action' => 'Refresh project list from GET /api/projects.',
            ],
            [
                'code' => ApiErrorCode::BAD_REQUEST,
                'http_status' => 400,
                'message' => 'The request body or parameters are invalid.',
                'retry' => false,
                'mobile_action' => 'Fix input and resubmit.',
            ],
            [
                'code' => ApiErrorCode::VALIDATION_ERROR,
                'http_status' => 400,
                'message' => 'Business validation failed.',
                'retry' => false,
                'mobile_action' => 'Show error.message; use error.details.field when present.',
            ],
            [
                'code' => ApiErrorCode::NOT_FOUND,
                'http_status' => 404,
                'message' => 'The requested resource was not found.',
                'retry' => false,
                'mobile_action' => 'Navigate back or refresh the list.',
            ],
            [
                'code' => ApiErrorCode::CONFLICT,
                'http_status' => 409,
                'message' => 'The request conflicts with existing data.',
                'retry' => false,
                'mobile_action' => 'Show message; let user change unique fields.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_REQUIRED,
                'http_status' => 403,
                'message' => 'Email 2FA is enabled but could not be initiated for this account.',
                'retry' => false,
                'mobile_action' => 'Show admin contact; do not retry. Normal 2FA flow returns success with data.pending_2fa instead.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_NO_EMAIL,
                'http_status' => 403,
                'message' => '2FA is enabled but this account has no email address.',
                'retry' => false,
                'mobile_action' => 'Show "contact administrator to set email" message.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_SEND_FAILED,
                'http_status' => 502,
                'message' => 'Failed to send the verification email.',
                'retry' => true,
                'mobile_action' => 'Allow user to retry login or resend code.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_INVALID_CODE,
                'http_status' => 401,
                'message' => 'The verification code is incorrect.',
                'retry' => true,
                'mobile_action' => 'Let user re-enter the 6-digit code.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_CHALLENGE_EXPIRED,
                'http_status' => 410,
                'message' => 'The verification challenge has expired.',
                'retry' => false,
                'mobile_action' => 'Restart login to get a new code.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                'http_status' => 404,
                'message' => 'No active verification challenge for the provided id.',
                'retry' => false,
                'mobile_action' => 'Restart login flow.',
            ],
            [
                'code' => ApiErrorCode::TWO_FACTOR_LOCKED,
                'http_status' => 429,
                'message' => 'Too many incorrect codes. The challenge is locked.',
                'retry' => false,
                'mobile_action' => 'Restart login flow to get a new challenge.',
            ],
            [
                'code' => ApiErrorCode::PASSWORD_EXPIRED,
                'http_status' => 403,
                'message' => 'Password has expired. Contact your administrator.',
                'retry' => false,
                'mobile_action' => 'Show contact-admin message.',
            ],
            [
                'code' => ApiErrorCode::RATE_LIMITED,
                'http_status' => 429,
                'message' => 'Too many requests. Try again later.',
                'retry' => true,
                'mobile_action' => 'Backoff and retry login.',
            ],
            [
                'code' => ApiErrorCode::INTERNAL_ERROR,
                'http_status' => 500,
                'message' => 'An unexpected server error occurred.',
                'retry' => true,
                'mobile_action' => 'Show generic error; optional retry.',
            ],
        ];
    }

    /** Map legacy controller error strings to stable codes. */
    public static function codeFromLegacyErrorString(string $legacy): string
    {
        $key = strtolower(trim($legacy));
        return match ($key) {
            'unauthorized' => ApiErrorCode::UNAUTHORIZED,
            'forbidden' => ApiErrorCode::FORBIDDEN,
            'not found' => ApiErrorCode::NOT_FOUND,
            'bad request' => ApiErrorCode::BAD_REQUEST,
            'validationerror' => ApiErrorCode::VALIDATION_ERROR,
            'too many requests' => ApiErrorCode::RATE_LIMITED,
            'internal server error' => ApiErrorCode::INTERNAL_ERROR,
            'admin only' => ApiErrorCode::FORBIDDEN,
            default => ApiErrorCode::BAD_REQUEST,
        };
    }
}
