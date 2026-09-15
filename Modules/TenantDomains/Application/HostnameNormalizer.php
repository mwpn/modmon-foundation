<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Application;

use InvalidArgumentException;

/**
 * Hostname normalization for store and resolve (proposal §4).
 */
final class HostnameNormalizer
{
    public function normalize(string $hostname): string
    {
        $hostname = trim($hostname);

        if ($hostname === '') {
            throw new InvalidArgumentException('Hostname must be a non-empty string.');
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $hostname) === 1) {
            throw new InvalidArgumentException('Hostname must not include a URL scheme.');
        }

        if (str_contains($hostname, '/') || str_contains($hostname, '?') || str_contains($hostname, '#')) {
            throw new InvalidArgumentException('Hostname must not include a path or query.');
        }

        $hostname = strtolower($hostname);
        $hostname = rtrim($hostname, '.');

        if (preg_match('/^(.+):(\d+)$/', $hostname, $matches) === 1
            && ! str_starts_with($hostname, '[')) {
            $hostname = $matches[1];
        }

        if ($hostname === '' || $hostname === 'localhost') {
            throw new InvalidArgumentException('Hostname is not allowed.');
        }

        $forIpCheck = $hostname;
        if (str_starts_with($forIpCheck, '[') && str_ends_with($forIpCheck, ']')) {
            $forIpCheck = substr($forIpCheck, 1, -1);
        }
        if (filter_var($forIpCheck, FILTER_VALIDATE_IP) !== false) {
            throw new InvalidArgumentException('IP addresses are not allowed as tenant domains.');
        }

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($hostname, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                throw new InvalidArgumentException('Hostname could not be converted to ASCII/punycode.');
            }
            $hostname = strtolower($ascii);
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/', $hostname)
            && ! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $hostname)) {
            throw new InvalidArgumentException('Hostname format is invalid.');
        }

        return $hostname;
    }

    public function tryNormalize(string $hostname): ?string
    {
        try {
            return $this->normalize($hostname);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
