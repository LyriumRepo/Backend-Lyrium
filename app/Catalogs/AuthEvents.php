<?php

declare(strict_types=1);

namespace App\Catalogs;

final class AuthEvents
{
    const LOGIN_SUCCESS = 'auth.login.success';
    const LOGIN_FAILED = 'auth.login.failed';
    const LOGOUT = 'auth.logout';
    const REGISTER = 'auth.register';
    const REGISTER_CUSTOMER = 'auth.register.customer';
    const EMAIL_VERIFIED = 'auth.email.verified';
    const EMAIL_CHANGED = 'auth.email.changed';
    const PASSWORD_CHANGED = 'auth.password.changed';
    const PASSWORD_RESET_REQUESTED = 'auth.password.reset.requested';
    const PASSWORD_RESET_COMPLETED = 'auth.password.reset.completed';
    const OAUTH_LOGIN = 'auth.oauth.login';
    const TOKEN_REFRESHED = 'auth.token.refreshed';
    const TOKEN_REVOKED = 'auth.token.revoked';
}
