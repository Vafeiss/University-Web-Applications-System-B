
# System B – Content Search & Filtering

This branch implements the **Content Search & Filtering** feature of System B.

The feature allows users to search posts and filter results using different criteria such as keywords, category, and date range.

---

# Database Configuration

The project uses a shared database connection file:

backend/config/db.php

Default local configuration:

- Host: 127.0.0.1
- Port: 3306
- Database: university_web
- User: root
- Password: (empty)

Optional environment variables can be used:

SYSTEM_B_DB_HOST  
SYSTEM_B_DB_PORT  
SYSTEM_B_DB_NAME  
SYSTEM_B_DB_USER  
SYSTEM_B_DB_PASS  

---

# Database Schema

The schema used by this feature is included in:

database/university_web_main.sql

Import this file into phpMyAdmin if you want to replicate the exact database structure locally.

---

# Feature Structure

Frontend files:

frontend/index.php  
frontend/search.php  
frontend/js/search.js  

Backend files:

backend/controllers/search_controllers.php  
backend/modules/search.php  

---

# Supported Search Filters

The search system currently supports:

• Keyword search in post **title** and **content**  
• **Category** filtering  
• **Date range** filtering  
• **Sorting results by date**  
• Filtering posts from **followed users** using the followers table  

The search queries operate on the existing **posts** table and related tables defined in the main schema.

---

# How to Run the Feature

1. Start **Apache** and **MySQL** in XAMPP.

2. Import the database schema:

database/university_web_main.sql

3. Ensure the database name is:

university_web

4. Open the application in your browser:

Main page  
http://localhost/University-Web-Applications-System-B/frontend/index.php

Search page  
http://localhost/University-Web-Applications-System-B/frontend/search.php

---

# Notes

This branch contains only the implementation of the **Content Search & Filtering** feature and does not modify other system components.
