# Contributing to KWUPO Website

Thank you for your interest in supporting the KWUPO digital platform! As a civic project, we welcome contributions from qualified volunteers who share our values of unity, transparency, and service.

## 📌 Guidelines

1. **All contributors must be KWUPO members in good standing**  
   Please coordinate with the National Secretariat before submitting code.

2. **Focus areas**:
   - Bug fixes
   - Performance improvements
   - Accessibility (WCAG compliance)
   - Documentation

3. **Do NOT contribute**:
   - Payment gateway modifications (Paystack integration is locked)
   - Admin privilege logic changes
   - Branding/logo alterations

## 🔄 Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-idea`
3. Commit changes: `git commit -m "Add feature"`
4. Push to branch: `git push origin feature/your-idea`
5. Open a Pull Request with:
   - Clear description
   - Screenshots (if UI change)
   - Test steps

## ✅ Code Standards

- Use **prepared statements** for all database queries
- Sanitize all user inputs with `htmlspecialchars()` and validation
- Follow existing CSS/JS patterns
- Comment complex logic

All PRs require review by the project lead before merging.

---
*“Alone we can do so little; together we can do so much.” – Helen Keller*