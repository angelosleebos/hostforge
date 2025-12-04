# Contributing to HostForge

Thank you for considering contributing to HostForge! This document provides guidelines and instructions for contributing.

## Code of Conduct

Please be respectful and constructive in all interactions.

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/ddd.git`
3. Create a branch: `git checkout -b feature/your-feature-name`
4. Follow the setup instructions in README.md
5. Make your changes
6. Test your changes
7. Commit with clear messages
8. Push to your fork
9. Open a Pull Request

## Coding Standards

### PHP / Laravel

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting: `./vendor/bin/pint`
- Write meaningful comments
- Use type hints
- Follow SOLID principles

### JavaScript / Vue.js

- Use ES6+ features
- Follow Vue.js style guide
- Use meaningful variable names
- Comment complex logic

### Security

- Never commit sensitive data (API keys, passwords)
- Validate all user inputs
- Use parameterized queries
- Sanitize output
- Follow OWASP guidelines

## Testing

- Write tests for new features
- Run tests before submitting PR: `php artisan test`
- Ensure all tests pass
- Maintain or improve code coverage

## Pull Request Process

1. Update README.md with any new features or changes
2. Update CHANGELOG.md (if applicable)
3. Ensure all tests pass
4. Ensure code follows coding standards
5. Request review from maintainers
6. Address review feedback
7. Wait for approval and merge

## Commit Messages

Use clear, descriptive commit messages:

```
feat: Add domain availability check API endpoint
fix: Resolve Plesk API authentication issue
docs: Update installation instructions
refactor: Improve OpenProvider service error handling
test: Add tests for Customer model
```

Prefixes:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

## Questions?

Feel free to open an issue for questions or discussions.

Thank you for contributing! 🎉
