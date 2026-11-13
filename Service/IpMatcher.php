<?php
/*
 * SOZO Design Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    SOZO Design Ltd
 * @package     Sozo_Feedbucket
 * @copyright   Copyright (c) SOZO Design Ltd (https://sozodesign.co.uk)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Sozo\Feedbucket\Service;

/**
 * Matches an IP address against IP whitelist entries.
 *
 * Supported entry formats, one per line:
 * - a single address:        192.168.1.10 or 2001:db8::1
 * - CIDR:                    192.168.1.0/24, 10/8 or 2001:db8::/32
 * - address and netmask:     192.168.1.0/255.255.255.0
 * - wildcards (IPv4):        192.168.*.*
 * - a range (IPv4):          192.168.1.10-192.168.1.20
 */
class IpMatcher
{
    /**
     * Split the configured whitelist into entries, ignoring blank lines and surrounding whitespace
     *
     * Browsers submit textarea lines separated by CRLF, so "\r" must not end up in the entries.
     *
     * @param string|null $whitelist
     * @return string[]
     */
    public function parseList(?string $whitelist): array
    {
        $lines = preg_split('/\R/', (string)$whitelist) ?: [];
        $lines = array_map('trim', $lines);

        return array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
    }

    /**
     * Does the IP address match any of the entries
     *
     * @param string $ipAddress
     * @param string[] $entries
     * @return bool
     */
    public function isAllowed(string $ipAddress, array $entries): bool
    {
        $ipAddress = trim($ipAddress);
        if ($ipAddress === '' || @inet_pton($ipAddress) === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($this->matches($ipAddress, trim((string)$entry))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the entry in a format the matcher understands
     *
     * @param string $entry
     * @return bool
     */
    public function isValidEntry(string $entry): bool
    {
        $entry = trim($entry);
        if ($entry === '') {
            return false;
        }

        if (strpos($entry, '/') !== false) {
            [$network, $mask] = array_map('trim', explode('/', $entry, 2));
            if (strpos($mask, '.') !== false) {
                return strpos($network, ':') === false
                    && @inet_pton($this->padIpv4($network)) !== false
                    && $this->netmaskToPrefix(str_replace('*', '0', $mask)) !== null;
            }

            if (!ctype_digit($mask)) {
                return false;
            }

            if (strpos($network, ':') !== false) {
                return @inet_pton($network) !== false && (int)$mask <= 128;
            }

            return @inet_pton($this->padIpv4($network)) !== false && (int)$mask <= 32;
        }

        if (strpos($entry, '*') !== false) {
            return ip2long(str_replace('*', '0', $entry)) !== false;
        }

        if (strpos($entry, '-') !== false) {
            [$lower, $upper] = array_map('trim', explode('-', $entry, 2));

            return ip2long($lower) !== false && ip2long($upper) !== false;
        }

        return @inet_pton($entry) !== false;
    }

    /**
     * Does the IP address match one entry
     *
     * @param string $ipAddress
     * @param string $entry
     * @return bool
     */
    public function matches(string $ipAddress, string $entry): bool
    {
        if ($entry === '') {
            return false;
        }

        if (strpos($entry, '/') !== false) {
            return $this->matchesNetwork($ipAddress, $entry);
        }

        if (strpos($entry, '*') !== false) {
            $entry = str_replace('*', '0', $entry) . '-' . str_replace('*', '255', $entry);
        }

        if (strpos($entry, '-') !== false) {
            return $this->matchesRange($ipAddress, $entry);
        }

        $ip = @inet_pton($ipAddress);
        $candidate = @inet_pton($entry);

        return $ip !== false && $candidate !== false && $ip === $candidate;
    }

    /**
     * Match "network/prefix" or "network/netmask"
     *
     * @param string $ipAddress
     * @param string $entry
     * @return bool
     */
    private function matchesNetwork(string $ipAddress, string $entry): bool
    {
        [$network, $mask] = explode('/', $entry, 2);
        $network = trim($network);
        $mask = trim($mask);

        if (strpos($mask, '.') !== false) {
            // Dotted netmask, e.g. 255.255.0.0 (IPv4 only)
            $mask = $this->netmaskToPrefix(str_replace('*', '0', $mask));
            if ($mask === null) {
                return false;
            }
        } elseif (!ctype_digit($mask)) {
            return false;
        }

        if (strpos($network, ':') === false) {
            $network = $this->padIpv4($network);
        }

        $ip = @inet_pton($ipAddress);
        $net = @inet_pton($network);
        if ($ip === false || $net === false || strlen($ip) !== strlen($net)) {
            return false;
        }

        $prefix = (int)$mask;
        if ($prefix > strlen($ip) * 8) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        if (substr($ip, 0, $fullBytes) !== substr($net, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $byteMask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ip[$fullBytes]) & $byteMask) === (ord($net[$fullBytes]) & $byteMask);
    }

    /**
     * Match an IPv4 range "A-B"
     *
     * @param string $ipAddress
     * @param string $entry
     * @return bool
     */
    private function matchesRange(string $ipAddress, string $entry): bool
    {
        [$lower, $upper] = array_map('trim', explode('-', $entry, 2));

        $ip = ip2long($ipAddress);
        $low = ip2long($lower);
        $high = ip2long($upper);
        if ($ip === false || $low === false || $high === false) {
            return false;
        }

        return $ip >= $low && $ip <= $high;
    }

    /**
     * Turn a dotted netmask into a prefix length, or null when it is not a valid netmask
     *
     * @param string $netmask
     * @return int|null
     */
    private function netmaskToPrefix(string $netmask): ?int
    {
        $long = ip2long($netmask);
        if ($long === false) {
            return null;
        }

        $binary = str_pad(decbin($long), 32, '0', STR_PAD_LEFT);
        if (preg_match('/^1*0*$/', $binary) !== 1) {
            return null;
        }

        return substr_count($binary, '1');
    }

    /**
     * Expand a short IPv4 network such as "10" or "10.1" to four parts
     *
     * @param string $network
     * @return string
     */
    private function padIpv4(string $network): string
    {
        $parts = explode('.', $network);
        while (count($parts) < 4) {
            $parts[] = '0';
        }

        return implode('.', array_map(static fn(string $part): string => $part === '' ? '0' : $part, $parts));
    }
}
