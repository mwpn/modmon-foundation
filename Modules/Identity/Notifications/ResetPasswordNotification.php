<?php

declare(strict_types=1);

namespace Modules\Identity\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

/**
 * Identity-owned password reset notification.
 *
 * Overrides the default reset URL so the link targets Identity's own
 * `identity.password.reset` route — never the host-global
 * `password.reset` name that a clean Foundation host does not define.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return url(route('identity.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
