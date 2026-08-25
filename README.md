# MDNS

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

**This library is originally forked from https://github.com/clue/reactphp-mdns**

This library provides a Multicast DNS (mDNS) resolver for local network service discovery in WebRTC and other real-time peer-to-peer applications.

## About this fork

This is the `danog/php-rtc-mdns` fork used by MadelineProto. It targets PHP 8.2+ and has been ported from ReactPHP promises and sockets to Amp v3 fibers and sockets.

All internal Composer dependencies use their `danog/php-rtc-*` package names directly, so installing a component selects the maintained danog packages throughout the dependency graph.

## Requirements

- PHP ≥ 8.2

## Documentation

This package is part of the PHP WebRTC library. For complete documentation, examples, and API reference, visit:

[PHP WebRTC Documentation](https://github.com/danog/php-rtc-mdns)

## Credits

### Authors

- **Amin Yazdanpanah**  
  - Website: [aminyazdanpanah.com](https://www.aminyazdanpanah.com)
  - Email: [github@aminyazdanpanah.com](mailto:github@aminyazdanpanah.com)

- **Sana Moniri**  
  - [GitHub](https://github.com/sanamoniri)

## Reporting Issues

Found a bug? Please report it on our [issues](https://github.com/php-webrtc/mdns/issues).

## License

BSD 3-Clause License. See [LICENSE](LICENSE) for details.
