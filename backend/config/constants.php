<?php

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// User States
define('USER_STATE_ACTIVE', 1);
define('USER_STATE_INACTIVE', 0);

// Token States
define('TOKEN_STATE_UNUSED', 0);
define('TOKEN_STATE_USED', 1);

// Token Types
define('TOKEN_TYPE_MAGIC_LINK', 'MAGIC_LINK');
define('TOKEN_TYPE_PASSWORD_RESET', 'PASSWORD_RESET');

// Response Keys
define('RESPONSE_KEY_SUCCESS', 'success');
define('RESPONSE_KEY_DATA', 'data');
define('RESPONSE_KEY_MESSAGE', 'message');
define('RESPONSE_KEY_ERRORS', 'errors');
