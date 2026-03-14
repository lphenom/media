# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 0.1.x   | ✅         |

## Reporting a Vulnerability

If you discover a security vulnerability in `lphenom/media`, please report it
**privately** to avoid public disclosure before a fix is available.

**Contact:** Open a [GitHub Security Advisory](https://github.com/lphenom/media/security/advisories/new)
on this repository, or email the maintainer directly.

Please include:
- A description of the vulnerability and its potential impact.
- Steps to reproduce the issue.
- Any suggested mitigations or patches.

We will acknowledge receipt within **48 hours** and aim to release a fix within
**14 days** for critical issues.

## Scope

This package handles image and video file processing. Security-relevant areas include:

- **Path traversal** — always validate that paths point to expected directories before
  passing them to `GdImageProcessor` or `StubVideoProcessor`.
- **File size limits** — use `VideoProcessorInterface::validateSize()` before processing
  user-uploaded files to prevent DoS via oversized uploads.
- **Denial of Service via malformed images** — GD may be slow or crash on adversarially
  crafted image files. Run processing in a resource-limited environment.

