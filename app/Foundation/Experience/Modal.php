<?php

declare(strict_types=1);

namespace App\Foundation\Experience;

/**
 * Old input and failure state of `<x-foundation::modal>` forms (Contract
 * 1.3.0, ADR-0011). Modal forms post a hidden `_modal` id; after a failed
 * validation only the modal with that id reopens and gets the old input,
 * so several edit modals on one page do not share each other's values.
 */
final class Modal
{
    public const FIELD = '_modal';

    /** Session flash: open this modal after a successful redirect ("save and add another"). */
    public const REOPEN = '_modal_reopen';

    /**
     * Whether the last submit came from this modal and failed validation.
     */
    public static function failed(string $id): bool
    {
        $errors = session('errors');

        return old(self::FIELD) === $id && $errors !== null && $errors->any();
    }

    /**
     * Whether this modal opens on page load: it failed validation, or the
     * last redirect asked to reopen it (`->with(Modal::REOPEN, $id)`).
     */
    public static function opensOnLoad(string $id): bool
    {
        return self::failed($id) || session(self::REOPEN) === $id;
    }

    /**
     * `old($field, $default)` for this modal only.
     */
    public static function old(string $id, string $field, mixed $default = null): mixed
    {
        return old(self::FIELD) === $id ? old($field, $default) : $default;
    }

    /**
     * Checkbox state for this modal: after its failed submit the box is
     * checked only if it was sent (unchecked boxes are not submitted).
     */
    public static function checked(string $id, string $field, bool $default = false): bool
    {
        return old(self::FIELD) === $id ? (bool) old($field) : $default;
    }
}
