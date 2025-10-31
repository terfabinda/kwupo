# KWUPO Official Website

> **Kwande United Peoples Organization (KWUPO)** – Uniting the Kwande people at home and in the diaspora through digital empowerment.

This repository contains the full source code for the official KWUPO website and administrative dashboard, built to manage membership, incident reporting, financial dues, and community communications.

🔗 **Live Site (Staging)**: [https://kwupo.org.ng](https://kwupo.org.ng)  
*(Public launch pending final content and verification)*

---

## ✨ Features

### Public-Facing Website
- Responsive homepage with history, leadership, and cultural highlights  
- News, press releases, and event announcements  
- Contact information and community resources  

### Admin Dashboard (Role-Based Access)
- **Member Management**: Register, view, and filter members by LGA, ward, and status  
- **Incident Reporting System**: Submit, verify, and track community incidents with media uploads  
- **Financial Dashboard**:  
  - Track membership dues and payment history  
  - Integrated **Paystack payment gateway** (currently in test mode; live mode requires CAC documentation)  
- **Bulk Messaging**: WhatsApp message generator for outreach to verified members  
- **Export & Print**: CSV exports and print-ready reports for transparency  

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3 (custom), JavaScript (vanilla)  
- **Backend**: PHP 8+, MySQL  
- **Payment**: Paystack API (test mode active)  
- **Hosting**: Linux-based LAMP stack  
- **Fonts**: Google Fonts (`Vollkorn`, `Poppins`, `Open Sans`)  
- **Icons**: Font Awesome  

---

## 📁 Project Structure

```
kwupo-website/
├── admin/                  # Admin dashboard (role_id = 1)
├── member/                 # Member portal (role_id = 3)
├── assets/
│   ├── css/                # Custom styles
│   ├── js/                 # JavaScript files
│   └── images/             # Static images (logos, banners)
├── includes/               # Shared PHP includes
│   ├── init.php            # DB connection & session start
│   ├── helpers.php         # Utility functions
│   ├── header.php          # Global header
│   ├── footer.php          # Global footer
│   ├── sidebar.php         # Admin sidebar
│   └── config.example.php  # Template for DB config
├── uploads/
│   ├── incident_media/     # Uploaded incident photos/videos
│   └── profiles/           # Member profile pictures
├── index.php               # Public homepage
├── signin.php / signout.php
├── ...
├── README.md
├── .gitignore
├── LICENSE
├── SECURITY.md
└── CONTRIBUTING.md
```

---

## ⚙️ Local Development Setup

> **Prerequisites**: XAMPP/WAMP/MAMP or LAMP stack with **PHP ≥ 8.0**, **MySQL**, and **Apache**

### Step-by-Step

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/kwupo-website.git
   cd kwupo-website
   ```

2. **Create a MySQL database**
   ```sql
   CREATE DATABASE kwupo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import the database schema**  
   > ⚠️ The SQL dump (`kwupo_db.sql`) is **not included in this repo** for security.  
   > Contact the project lead to obtain it via secure channel.

4. **Configure database credentials**
   ```bash
   cp includes/config.example.php includes/config.php
   ```
   Edit `includes/config.php`:
   ```php
   $db_host = 'localhost';
   $db_user = 'your_db_user';
   $db_pass = 'your_db_pass';
   $db_name = 'kwupo_db';
   ```

5. **Set upload directory permissions**
   ```bash
   chmod -R 755 uploads/
   ```

6. **Serve the application**  
   Place the project in your web server root (e.g., `htdocs/kwupo`) and visit:  
   → `http://localhost/kwupo`

---

## 🔐 Security & Compliance

### Sensitive Data Handling
- **Never commit** `config.php`, `.env`, or SQL dumps to version control  
- All user inputs are sanitized using `htmlspecialchars()` and validated  
- Database queries use **prepared statements** to prevent SQL injection  
- File uploads are restricted by type and stored outside web root where possible  

### Paystack Live Activation Requirements
To enable **live payments**, KWUPO must provide the following to Paystack (during production launch only):
1. CAC Form 2 – Share Capital  
2. CAC Form 7 – List of Directors  
3. Memorandum of Association  
4. Certificate of Incorporation  
5. RC Number  
6. Tax Identification Number (TIN)  
7. Proof of Business Address  
8. Valid ID & Address Proof for Directors  
9. Valid ID & Address Proof for Shareholders  

> 🔒 These documents **must not** be shared during development or beta testing.

### Reporting Vulnerabilities
See [`SECURITY.md`](#security-policy) below.

---

## 🤝 Contributing

We welcome contributions from **verified KWUPO members** who wish to support this civic initiative.

### Guidelines
- All contributors must coordinate with the **National Secretariat** before submitting code  
- Focus on: bug fixes, accessibility (WCAG), performance, and documentation  
- **Do not modify**:  
  - Payment logic (Paystack integration)  
  - Role-based access controls  
  - Branding or official content  

### Workflow
1. Fork the repo  
2. Create a feature branch: `git checkout -b feature/your-idea`  
3. Commit and push your changes  
4. Open a Pull Request with:  
   - Clear description  
   - Test steps  
   - Screenshots (if UI-related)  

### Code Standards
- Use prepared statements for all DB queries  
- Sanitize output with `htmlspecialchars()`  
- Follow existing CSS/JS patterns  
- Comment complex logic  

All PRs require review by the project lead.

---

## 📄 License

This project is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2025 Kwande United Peoples Organization (KWUPO)

Permission is hereby granted... [full text in LICENSE file]
```

> **Note**:  
> - The **software code** is open-source under MIT  
> - All **content** (text, images, logos, data) remains the exclusive property of **KWUPO** and may not be reused without written permission  

---

## 📬 Contact & Support

For technical issues, feature requests, or access to secure assets (e.g., DB schema), please contact:

**Project Lead**  
📧 [eternexsys@gmail.com]  
📱 [+234 807 085 0317]  

*Developed with pride for the Kwande/Ushongo nation.*

---

## Appendix

### Security Policy
If you discover a security vulnerability, **do not disclose it publicly**.  
Report it immediately via email to the project lead. We will acknowledge within 48 hours and work swiftly to resolve it.

### Required Launch Assets (Pending from KWUPO Leadership)
To complete the public homepage, we still need:
- History of KWUPO  
- Photos of all current Exco members  
- Images of **Tor Kwande**, **Ter Kwande**, and **Ter Ushongo**  
- Official KWUPO phone number  
- Photos from KWUPO events and community activities  

---

*“Alone we can do so little; together we can do so much.”*  
— Helen Keller
``` 

