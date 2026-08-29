<?php

/**
 * This file is part of the PHP WebRTC package.
 *
 * (c) Amin Yazdanpanah <https://www.aminyazdanpanah.com/#contact>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webrtc\MDNS;

/**
 * Factory for creating mDNS (Multicast DNS) resolvers.
 *
 * This class initializes a multicast DNS resolver suitable for use with
 * WebRTC local peer discovery and service resolution scenarios.
 */
final class Factory
{
    /**
     * Default mDNS address used for multicast DNS queries (RFC 6762).
     */
    const DNS = '224.0.0.251:5353';

    /**
     * The executor responsible for sending queries over multicast.
     */
    private MulticastExecutor $executor;

    /**
     * Constructs the Factory with an optional custom executor.
     *
     * @param ?MulticastExecutor $executor Optional executor for query resolution.
     */
    public function __construct(?MulticastExecutor $executor = null)
    {
        $this->executor = $executor ?: new MulticastExecutor(self::DNS);
    }

    /**
     * Returns a resolver capable of performing mDNS queries.
     *
     * @return MulticastExecutor A resolver whose resolve() blocks until an answer arrives.
     */
    public function createResolver(): MulticastExecutor
    {
        return $this->executor;
    }
}
