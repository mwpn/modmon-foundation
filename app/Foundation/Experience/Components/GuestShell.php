<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use Illuminate\View\Component;

/**
 * Generic guest/auth shell for unauthenticated Experience surfaces.
 *
 * Modules (e.g. Identity) keep their forms and routes; this shell only
 * provides the visual composition.
 */
class GuestShell extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $heading = null,
        public ?string $subheading = null,
    ) {}

    public function render()
    {
        return view('foundation::layouts.guest');
    }
}
