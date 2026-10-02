<?php
/**
 * Webhook request authenticity helpers.
 *
 * Kept free of WordPress/WooCommerce dependencies so they can be unit tested in isolation.
 *
 * @category Plugin
 * @package  Paymaya
 */

if (!defined('ABSPATH') && !defined('CYNDER_PAYMAYA_TESTING')) {
    exit;
}

class Cynder_Paymaya_Webhook_Guard {
    /**
     * Cloudflare edge ranges, from https://www.cloudflare.com/ips-v4 and https://www.cloudflare.com/ips-v6.
     * CF-Connecting-IP is only honoured when the TCP peer is inside one of these.
     * Review against those lists periodically.
     */
    const CLOUDFLARE_RANGES = array(
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    );

    /** Headers a merchant may choose to trust, keyed by the value stored in the gateway settings. */
    const PROXY_HEADERS = array(
        'x-forwarded-for' => 'HTTP_X_FORWARDED_FOR',
        'x-real-ip' => 'HTTP_X_REAL_IP',
    );

    /**
     * Resolve the webhook sender's IP from $_SERVER-style data.
     *
     * The TCP peer (REMOTE_ADDR) is the only trusted value, except:
     * - behind Cloudflare (peer is a Cloudflare edge IP), CF-Connecting-IP is used;
     * - if the merchant configured a proxy header AND the peer is inside their trusted proxy ranges,
     *   that header is used. With no trusted ranges the header is never honoured.
     * Other forwarding headers are client-controlled and never used.
     *
     * @param array  $server        $_SERVER-style data.
     * @param string $proxyHeader   Key of self::PROXY_HEADERS, or '' for none.
     * @param array  $trustedRanges CIDR ranges of the merchant's proxies (see parse_ranges()).
     */
    public static function resolve_source_ip(array $server, $proxyHeader = '', array $trustedRanges = array()) {
        $remote = isset($server['REMOTE_ADDR']) ? (string) $server['REMOTE_ADDR'] : '';

        if ($remote === '') {
            return '';
        }

        $cf = isset($server['HTTP_CF_CONNECTING_IP']) ? trim((string) $server['HTTP_CF_CONNECTING_IP']) : '';

        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) !== false && self::is_ip_in_ranges($remote, self::CLOUDFLARE_RANGES)) {
            return $cf;
        }

        if (isset(self::PROXY_HEADERS[$proxyHeader]) && self::is_ip_in_ranges($remote, $trustedRanges)) {
            $serverKey = self::PROXY_HEADERS[$proxyHeader];
            $value = isset($server[$serverKey]) ? (string) $server[$serverKey] : '';

            // Walk right to left: the right-most entry not added by a trusted proxy is the client.
            $hops = array_reverse(array_map('trim', explode(',', $value)));

            foreach ($hops as $hop) {
                if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                    break; // Garbage in the chain: stop trusting it.
                }

                if (!self::is_ip_in_ranges($hop, $trustedRanges)) {
                    return $hop;
                }
            }
        }

        return $remote;
    }

    /**
     * Parse a merchant-entered list of IPs/CIDRs (one per line or comma separated) into CIDR ranges.
     * Invalid entries are dropped.
     */
    public static function parse_ranges($text) {
        $ranges = array();

        foreach (preg_split('/[\s,]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            $parts = explode('/', $entry, 2);
            $packed = @inet_pton($parts[0]);

            if ($packed === false) {
                continue;
            }

            $maxBits = strlen($packed) * 8;

            if (count($parts) === 1) {
                $ranges[] = $parts[0] . '/' . $maxBits;
            } elseif (ctype_digit($parts[1]) && (int) $parts[1] <= $maxBits) {
                $ranges[] = $parts[0] . '/' . (int) $parts[1];
            }
        }

        return $ranges;
    }

    public static function is_ip_in_ranges($ip, array $ranges) {
        foreach ($ranges as $range) {
            if (self::is_ip_in_cidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    public static function is_ip_in_cidr($ip, $cidr) {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        list($subnet, $bits) = explode('/', $cidr, 2);

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        // Different families (or invalid input) never match.
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bits;
        $maxBits = strlen($ipBin) * 8;

        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }

    /**
     * True only if the hex signature verifies against at least one public key.
     * openssl_verify() returns 1 (valid), 0 (invalid) or -1 (error); only 1 counts.
     */
    public static function is_valid_signature($verifyString, $hexSignature, array $publicKeys) {
        if (!is_string($hexSignature) || $hexSignature === '' || strlen($hexSignature) % 2 !== 0 || !ctype_xdigit($hexSignature)) {
            return false;
        }

        $binarySignature = hex2bin($hexSignature);

        if ($binarySignature === false) {
            return false;
        }

        foreach ($publicKeys as $publicKey) {
            // Warnings (e.g. unusable key) are suppressed: the result is checked strictly below.
            if (@openssl_verify($verifyString, $binarySignature, $publicKey, 'sha256WithRSAEncryption') === 1) {
                return true;
            }
        }

        return false;
    }
}
