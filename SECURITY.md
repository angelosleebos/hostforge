# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take the security of HostForge seriously. If you believe you have found a security vulnerability, please report it to us as described below.

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: security@hostforge.nl

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

Please include the following information:

- Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

## Security Measures

HostForge implements the following security measures:

### Application Security
- ✅ CSRF Protection
- ✅ XSS Protection
- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ Rate Limiting
- ✅ Input Validation & Sanitization
- ✅ Secure Session Handling
- ✅ Password Hashing (Bcrypt)

### Infrastructure Security
- ✅ HTTPS Only
- ✅ Security Headers (CSP, HSTS, X-Frame-Options)
- ✅ Non-root Container User
- ✅ Read-only Root Filesystem
- ✅ Secrets Management
- ✅ Regular Security Updates

### API Security
- ✅ Authentication Required
- ✅ API Rate Limiting
- ✅ Request Validation
- ✅ Encrypted Communication

## Disclosure Policy

When we receive a security vulnerability report, we will:

1. Confirm the receipt of your vulnerability report
2. Investigate and validate the issue
3. Develop and test a fix
4. Release a security update
5. Publicly disclose the vulnerability (after a fix is available)

## Comments on this Policy

If you have suggestions on how this process could be improved, please submit a pull request or open an issue.
