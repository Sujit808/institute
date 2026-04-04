# MEERAHR - Institute Management System
## Complete User Guide | संपूर्ण उपयोगकर्ता गाइड

---

## 📋 TABLE OF CONTENTS / विषय-सूची

### English Version
1. [Project Overview](#project-overview-en)
2. [Getting Started](#getting-started-en)
3. [Login & Authentication](#login-authentication-en)
4. [Main Features](#main-features-en)
5. [User Roles](#user-roles-en)
6. [Step-by-Step Guides](#step-by-step-guides-en)
7. [Market Positioning & Services](#market-positioning-services-en)

### Hindi Version
1. [प्रोजेक्ट अवलोकन](#project-overview-hi)
2. [शुरुआत करें](#getting-started-hi)
3. [लॉगिन और प्रमाणीकरण](#login-authentication-hi)
4. [मुख्य विशेषताएं](#main-features-hi)
5. [उपयोगकर्ता भूमिकाएं](#user-roles-hi)
6. [चरण-दर-चरण गाइड](#step-by-step-guides-hi)
7. [मार्केट पोजिशनिंग और सर्विस](#market-positioning-services-hi)

---

# 🌐 ENGLISH VERSION

## Project Overview {#project-overview-en}

**MEERAHR** is a comprehensive **Institute Management System** designed for schools, colleges, and educational institutions to manage:

- ✅ Student & Staff Management
- ✅ Fee Collection & Payment Processing
- ✅ Receipt Generation & Tracking
- ✅ Multi-Branch Organization Support
- ✅ Dynamic Organization Branding
- ✅ Attendance & Academic Records
- ✅ License-Based Access Control

**Tech Stack:** Laravel 11 | Bootstrap 5 | MySQL | PDF Generation (DomPDF)

---

## Getting Started {#getting-started-en}

### System Requirements
- PHP 8.2 or higher
- MySQL 8.0+ or compatible database
- 100MB disk space minimum
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Installation

```bash
# 1. Clone the repository
git clone <repo-url>
cd meerahr

# 2. Install dependencies
composer install

# 3. Environment setup
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=meerahr
DB_USERNAME=root
DB_PASSWORD=

# 6. Run migrations
php artisan migrate

# 7. Start development server
php artisan serve
```

**Access Application:** http://localhost:8000

---

## Login & Authentication {#login-authentication-en}

### First Login

1. Open http://localhost:8000 in your browser
2. You should see the **Login Page**
3. Default credentials (if seeded):
   - **Email:** admin@meerahr.test
   - **Password:** password

> ⚠️ **IMPORTANT:** Change default password immediately after first login!

### User Types (Roles)

- **Super Admin** - Full system access, institute setup
- **Admin** - Day-to-day operations
- **HR** - Human resources management
- **Teacher** - Class & student management
- **Accountant** - Fee & payment processing

---

## Main Features {#main-features-en}

### 1️⃣ Institute Settings (Super Admin Only)

**Location:** Admin Menu → Institute Setup

**What You Can Do:**
- Set organization type (School/College/Institute)
- Upload organization logo
- Add organization contact details
- Create and manage multiple branches
- Map teachers/users to branches
- Set primary branch for each user

**Benefits:**
- Dynamic header branding with organization logo
- Multi-branch data isolation
- Centralized user-branch mapping
- Professional institute identity

---

### 2️⃣ Fee Management

**Location:** Admin Menu → Fees Module

**What You Can Do:**
- Create student fee structures
- Track fee payments
- Generate automated receipt numbers
- Download professional receipts with:
  - Organization logo
  - Watermark branding
  - Payment details
  - Automatic receipt numbering

**Receipt Number Format:** `SCHOOL-RCPT-YYYYMM-SERIALNO`
- Example: `MEER-RCPT-202603-000045`

**Auto-Generated Features:**
- Receipt numbers cannot be manually changed (system-locked)
- Receipt# automatically generated from payment serial
- PDFs include school name watermark + logo

---

### 3️⃣ Multi-Branch Organization

**Supported for:**
- Schools with multiple campuses
- Colleges with different departments
- Institutions with branches in different cities

**How It Works:**
1. Super Admin creates branches in Institute Settings
2. Super Admin maps users to multiple branches
3. Users select active branch via header dropdown
4. All data (fees, students, attendance) filtered by active branch
5. Reports generated branch-wise

---

### 4️⃣ License Management (Backend)

**Location:** Settings → License Settings (Admin Only)

**Features:**
- Automatic unique license key generation
- License validation
- Backend API endpoint for key generation
- Database-level uniqueness enforcement

---

## User Roles {#user-roles-en}

### 👤 Super Admin
**Permissions:**
- Create/edit organization profile
- Manage all branches
- Assign users to branches
- Access all reports
- Modify system settings
- Override any data

**Primary Tasks:**
- Initial setup and configuration
- Branch creation
- User role assignment
- Organization branding

---

### 👤 Admin
**Permissions:**
- Manage day-to-day operations
- Process fee payments
- Generate receipts
- View reports for active branch
- Add students & staff within branch

**Primary Tasks:**
- Fee collection
- Receipt generation
- Student admission
- Staff management

---

### 👤 HR
**Permissions:**
- Manage staff records
- Track attendance (HR level)
- Manage payroll
- Staff leave management

**Primary Tasks:**
- Employee management
- Attendance processing
- Leave approvals

---

### 👤 Teacher
**Permissions:**
- Mark attendance
- Manage class records
- View student performance
- Submit academic reports

**Primary Tasks:**
- Daily class attendance
- Grade entry
- Academic reporting

---

### 👤 Accountant
**Permissions:**
- Access fee module
- Generate receipts
- View payment reports
- Financial statements

**Primary Tasks:**
- Fee receipt generation
- Payment collection tracking
- Financial reporting

---

## Step-by-Step Guides {#step-by-step-guides-en}

### ⭐ GUIDE 1: Initial Organization Setup

**Step 1: Login as Super Admin**
```
1. Open application home page
2. Enter credentials
3. Click "Login"
```

**Step 2: Navigate to Institute Settings**
```
1. Click on Admin menu (top-right)
2. Select "Institute Setup"
```

**Step 3: Fill Institute Profile**
```
Fields to complete:
- Institute Type: Select "School", "College", or "Institute"
- Institute Name: Enter your institution name
- Short Name: 3-4 letter code (e.g., "MEER")
- Logo: Upload your institution logo (PNG/JPG)
- Address: Complete institution address
- Phone: Contact number
- Email: Official email
- City: Location
- Is Active: Check the checkbox
```

**Step 4: Save Profile**
```
Click "Save Profile" button
```

**Step 5: Create First Branch**
```
Go to "Add Branch" section:
- Branch Name: Main Campus / Head Office
- Branch Code: HEAD01
- City: Your city
- Address: Branch details
- Is Active: Check
Click "Add Branch"
```

**Step 6: Map Users to Branches**
```
Scroll to "Branch User Mapping":
- Select user from dropdown
- Check branches you want to assign
- Select "Primary Branch"
- Click "Update Mapping"
```

✅ **Setup Complete!** Organization is now configured with branding.

---

### ⭐ GUIDE 2: Generating & Downloading Fee Receipt

**Step 1: Login as Admin/Accountant**
```
1. Use your credentials
2. Click Login
```

**Step 2: Navigate to Fees Module**
```
1. Click Admin menu
2. Select "Fees"
```

**Step 3: Find Student Fee Record**
```
1. Locate the student in fees table
2. Click student name or fee ID
```

**Step 4: Process Payment (If New Payment)**
```
1. Click "Add Payment" button
2. Enter payment amount
3. Select payment method (Cash/Cheque/Online)
4. Click "Save Payment"
5. Receipt number AUTOMATICALLY GENERATED
```

**Step 5: Download Receipt**
```
1. Click "Download Receipt" button
2. PDF opens with:
   - Organization logo and watermark
   - Receipt number (auto-generated)
   - Student details
   - Payment information
   - Institution branding
3. Save or print PDF
```

📄 **Receipt Format Example:**
```
=====================================
MEERAHR INSTITUTE
Receipt #: MEER-RCPT-202603-000045
=====================================
Student: Ahmed Khan
Fee Due: 5,000 PKR
Amount Paid: 5,000 PKR
Date: 22-03-2026
Status: PAID
=====================================
```

✅ **Receipt Ready!** Can be printed or emailed to student.

---

### ⭐ GUIDE 3: Switch Between Branches (Multi-Branch Users)

**Step 1: Login with Multi-Branch Access**
```
Your user must be mapped to 2+ branches
```

**Step 2: Look at Header**
```
In top navigation bar, you'll see:
[Logo] MEERAHR | Active Branch: Main Campus
```

**Step 3: Click Branch Dropdown**
```
Click on branch name or dropdown arrow
Shows list of branches you can access
```

**Step 4: Select New Branch**
```
Choose branch from dropdown:
- Main Campus
- Branch 2
- Branch 3
etc.
```

**Step 5: Confirm Switch**
```
New branch becomes active
All data now filtered by selected branch
Page reloads automatically
```

✅ **Branch Switched!** All subsequent operations for new branch.

---

### ⭐ GUIDE 4: Adding a New Branch

**Step 1: Super Admin → Institute Settings**
```
1. Login as Super Admin
2. Click Admin menu → Institute Setup
```

**Step 2: Scroll to "Add Branch" Section**
```
Fill the form:
- Branch Name: "Lahore Campus" (required)
- Branch Code: "LHR01" (optional but recommended)
- City: "Lahore"
- Address: Full address
- Is Active: Checked
```

**Step 3: Submit Form**
```
Click "Add Branch" button
```

**Step 4: Confirm Addition**
```
Success message appears
New branch visible in Branch Mapping section
```

**Step 5: Map Users to New Branch**
```
Scroll to Branch User Mapping
Select users who should access this branch
Add them to the new branch
```

✅ **New Branch Created & Configured!**

---

### ⭐ GUIDE 5: Mapping Teachers to Branches

**Step 1: Super Admin → Institute Settings**
```
1. Login as Super Admin
2. Go to Institute Setup
3. Scroll to "Branch User Mapping" section
```

**Step 2: Select User from Dropdown**
```
Find teacher name in dropdown
Click to select
```

**Step 3: Select Branches**
```
Checkboxes appear for all branches
Check branches where teacher should work
Example: Check "Main Campus" and "Lahore Campus"
```

**Step 4: Set Primary Branch**
```
Select PRIMARY branch from dropdown
This is where user logs in by default
```

**Step 5: Update Mapping**
```
Click "Update Mapping" button
```

**Step 6: Confirm Changes**
```
User now appears in the mapping table
Refresh page to verify
```

✅ **Teacher Mapped to Branches!**
Teacher can now:
- Login and see all assigned branches
- Switch branches from top menu
- Access data only for their active branch

---

### ⭐ GUIDE 6: View Reports by Branch

**Step 1: Select Active Branch**
```
If multi-branch user, switch to desired branch
(See Guide 3: Switch Between Branches)
```

**Step 2: Navigate to Reports**
```
1. Click Menu
2. Select "Reports"
3. Choose report type (Fees, Attendance, etc.)
```

**Step 3: View Branch-Filtered Data**
```
All report data shown ONLY for active branch
Reports automatically filtered
No manual filtering needed
```

**Step 4: Export/Print**
```
Click "Export" or "Print" buttons
Data includes branch information
Professional formatting
```

✅ **Report Generated for Active Branch!**

---

### ⭐ GUIDE 7: Troubleshooting Common Issues

**Issue 1: Login fails with correct password**
```
Solution:
1. Clear browser cookies
2. Try another browser
3. Check CAPS LOCK is off
4. Verify email format
5. Contact admin to reset password
```

**Issue 2: Receipt shows wrong branch**
```
Solution:
1. Switch to correct branch in header
2. Download receipt again
3. Process payment again
4. Ensure branch is set as PRIMARY
```

**Issue 3: Branch dropdown not showing**
```
Solution:
1. You likely have only 1 branch assigned
2. Ask Super Admin to add more branches
3. Ask Super Admin to map you to more branches
4. Logout and login to refresh
```

**Issue 4: Cannot see certain data**
```
Solution:
1. Check active branch in header
2. Verify your user is mapped to this branch
3. Check your user role has permission
4. Contact super admin for access
```

**Issue 5: Receipt PDF not downloading**
```
Solution:
1. Check browser popup blocker
2. Try different browser
3. Ensure payment is saved
4. Check internet connection
5. Ensure logo file exists
```

---

## 💾 Data Backup & Security {#data-backup-en}

**Regular Backups:**
```bash
# Manual database backup
php artisan backup:run

# Backup location: storage/backups/
```

**Security Tips:**
- Change password every 3 months
- Don't share login credentials
- Use strong passwords (min 12 chars)
- Enable HTTPS in production
- Restrict database access
- Monitor user activity logs

---

## Market Positioning & Services {#market-positioning-services-en}

### Ideal Customers
- Schools with one or multiple campuses
- Colleges managing different departments or city branches
- Coaching centers and academies handling fee collection manually
- Educational groups needing a branded ERP for multiple branches
- Institutes that want receipt automation and role-based access

### Market Problems This Software Solves
- Manual fee receipt books cause errors and duplicate records
- Branch-wise data is difficult to control in Excel or paper systems
- Staff access is often unmanaged and insecure
- Management cannot get consistent reporting across branches
- School branding is missing from receipts and internal operations

### Unique Selling Points
- Multi-branch setup in a single system
- Auto-generated system-controlled receipt numbers
- Branded PDF receipts with logo and watermark
- User-to-branch mapping with primary branch selection
- Super admin control with branch-wise visibility
- Suitable for School, College, and Institute models

### Service Model You Can Offer to Clients

**1. Setup Service**
- Installation on client server or local hosting
- Organization profile setup
- Branch creation and user mapping
- Logo upload and branding configuration

**2. Training Service**
- Super Admin training
- Accountant/Admin fee workflow training
- Teacher and HR onboarding training
- Branch switching and report usage guidance

**3. Customization Service**
- Add client-specific modules
- Change receipt format or branding layout
- Add role permissions or approval flows
- Build custom reports for management

**4. Support Service**
- Bug fixing and maintenance
- Backup and restore help
- Performance monitoring
- Monthly or yearly support contracts

### Suggested Service Packages

**Basic Package**
- Single branch setup
- Logo branding
- Fee receipt module
- 1 admin training session

**Standard Package**
- Multi-branch setup
- User and teacher mapping
- Receipt and reporting setup
- 3 training sessions
- 30 days support

**Premium Package**
- Full institute deployment
- Custom workflows and reports
- Priority support
- Data import assistance
- 3 to 12 months maintenance plan

### Demo Flow for Sales Meetings
1. Start with institute profile setup and branding.
2. Show how branches are created and mapped to users.
3. Show fee entry and payment processing.
4. Download a receipt to demonstrate watermark and auto receipt number.
5. Switch branch from the header to prove multi-branch support.
6. Close with reporting, security, and support options.

### Ready Marketing Pitch
"MEERAHR is a modern institute management solution for schools, colleges, and academies. It helps you manage branches, staff, fees, and receipts from one system. With auto-generated receipt numbers, organization branding, branch-wise access control, and professional reporting, it replaces manual registers and scattered Excel files with a secure and scalable platform."

### Client Benefits in Simple Words
- Save admin time
- Reduce fee entry mistakes
- Improve reporting speed
- Give each branch controlled access
- Present a more professional image to parents and students
- Prepare the institute for future digital growth

### Proposal / Quotation Format for Clients

Use the following structure when sending a formal offer to a school, college, or institute:

```text
PROPOSAL / QUOTATION

Date:
Quotation No:
Client Name:
Institute Name:
City:
Contact Person:
Mobile / Email:

Subject: Proposal for Institute Management Software Implementation

Dear Sir/Madam,

Thank you for your interest in MEERAHR Institute Management System.
We are pleased to submit our proposal for software deployment, configuration, training, and support.

Project Scope:
- Institute profile setup
- Branch setup and mapping
- User role management
- Fee management and receipt generation
- Branding with logo and watermark
- Training and after-sales support

Modules Included:
- Organization Setup
- Branch Management
- User and Teacher Mapping
- Fee Management
- Receipt PDF Download
- Role-Based Access

Commercial Offer:
Package Name:
Implementation Cost:
Training Cost:
Support Cost:
Customization Cost (if any):
Total Project Cost:

Delivery Timeline:
- Setup Time:
- Training Time:
- Go-Live Date:

Payment Terms:
- 50% advance before project start
- 30% after setup completion
- 20% after training and handover

Support Terms:
- Free support period:
- Paid AMC / maintenance after warranty:

Validity:
This quotation is valid for 15 to 30 days from the date of issue.

Regards,
Your Name
Company Name
Phone / WhatsApp
Email
```

### Pricing Table in PKR / INR

| Package | Best For | Features | Price PKR | Price INR |
|---|---|---|---:|---:|
| Basic | Single branch school or academy | Institute setup, logo branding, fee receipts, 1 training session | 45,000 | 13,500 |
| Standard | Growing school or college | Multi-branch setup, user mapping, receipts, reports, 3 training sessions, 30 days support | 95,000 | 28,500 |
| Premium | Large institute group | Full deployment, customization, priority support, data import, extended maintenance | 180,000 | 54,000 |

### Optional Add-On Pricing

| Add-On Service | Price PKR | Price INR |
|---|---:|---:|
| Additional branch setup | 8,000 | 2,400 |
| Data import from Excel | 15,000 | 4,500 |
| Custom report | 12,000 | 3,600 |
| WhatsApp/SMS integration | 25,000 | 7,500 |
| Extra training session | 7,500 | 2,250 |
| Monthly support plan | 10,000 | 3,000 |

### Pricing Notes
- PKR and INR values are sample commercial rates and can be adjusted by your market, city, and client size.
- Hosting, domain, SMS gateway, WhatsApp API, and third-party charges should be quoted separately if applicable.
- Annual maintenance can be offered at 15% to 25% of total project cost.

---

<hr/>

# 🇮🇳 HINDI VERSION / हिंदी संस्करण

## प्रोजेक्ट अवलोकन {#project-overview-hi}

**MEERAHR** एक व्यापक **संस्थान प्रबंधन प्रणाली** है जो स्कूल, कॉलेज और शैक्षणिक संस्थानों को निम्नलिखित प्रबंधित करने में मदद करती है:

- ✅ छात्र और कर्मचारी प्रबंधन
- ✅ फीस संग्रह और भुगतान प्रसंस्करण
- ✅ रसीद जनन और ट्रैकिंग
- ✅ बहु-शाखा संगठन समर्थन
- ✅ गतिशील संगठन ब्रांडिंग
- ✅ उपस्थिति और शैक्षणिक रिकॉर्ड
- ✅ लाइसेंस-आधारित पहुंच नियंत्रण

**तकनीकी स्टैक:** Laravel 11 | Bootstrap 5 | MySQL | PDF जनन (DomPDF)

---

## शुरुआत करें {#getting-started-hi}

### सिस्टम आवश्यकताएं
- PHP 8.2 या उससे अधिक
- MySQL 8.0+ या संगत डेटाबेस
- न्यूनतम 100MB डिस्क स्पेस
- आधुनिक वेब ब्राउज़र (Chrome, Firefox, Safari, Edge)

### स्थापना

```bash
# 1. रिपोजिटरी क्लोन करें
git clone <repo-url>
cd meerahr

# 2. निर्भरताएं इंस्टॉल करें
composer install

# 3. पर्यावरण सेटअप
cp .env.example .env

# 4. एप्लिकेशन कुंजी जेनरेट करें
php artisan key:generate

# 5. .env में डेटाबेस कॉन्फ़िगर करें
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=meerahr
DB_USERNAME=root
DB_PASSWORD=

# 6. माइग्रेशन चलाएं
php artisan migrate

# 7. विकास सर्वर शुरू करें
php artisan serve
```

**एप्लिकेशन तक पहुंचें:** http://localhost:8000

---

## लॉगिन और प्रमाणीकरण {#login-authentication-hi}

### पहला लॉगिन

1. अपने ब्राउज़र में http://localhost:8000 खोलें
2. आपको **लॉगिन पृष्ठ** दिखेगा
3. डिफ़ॉल्ट साख (अगर बीज लगाई गई हो):
   - **ईमेल:** admin@meerahr.test
   - **पासवर्ड:** password

> ⚠️ **महत्वपूर्ण:** पहले लॉगिन के बाद तुरंत डिफॉल्ट पासवर्ड बदलें!

### उपयोगकर्ता प्रकार (भूमिकाएं)

- **सुपर एडमिन** - पूर्ण सिस्टम पहुंच, संस्थान सेटअप
- **एडमिन** - दिन-प्रतिदिन की कार्रवाई
- **एचआर** - मानव संसाधन प्रबंधन
- **शिक्षक** - कक्षा और छात्र प्रबंधन
- **लेखाकार** - फीस और भुगतान प्रसंस्करण

---

## मुख्य विशेषताएं {#main-features-hi}

### 1️⃣ संस्थान सेटिंग्स (केवल सुपर एडमिन)

**स्थान:** एडमिन मेनू → संस्थान सेटअप

**आप क्या कर सकते हैं:**
- संगठन प्रकार सेट करें (स्कूल/कॉलेज/संस्थान)
- संगठन लोगो अपलोड करें
- संगठन संपर्क विवरण जोड़ें
- कई शाखाएं बनाएं और प्रबंधित करें
- शिक्षकों/उपयोगकर्ताओं को शाखाओं से मैप करें
- प्रत्येक उपयोगकर्ता के लिए प्राथमिक शाखा सेट करें

**लाभ:**
- संगठन लोगो के साथ गतिशील हेडर ब्रांडिंग
- बहु-शाखा डेटा पृथक्करण
- केंद्रीकृत उपयोगकर्ता-शाखा मैपिंग
- व्यावहारिक संस्थान पहचान

---

### 2️⃣ फीस प्रबंधन

**स्थान:** एडमिन मेनू → फीस मॉड्यूल

**आप क्या कर सकते हैं:**
- छात्र फीस संरचना बनाएं
- फीस भुगतान ट्रैक करें
- स्वचालित रसीद नंबर जेनरेट करें
- पेशेवर रसीदें डाउनलोड करें जिनमें शामिल है:
  - संगठन लोगो
  - वाटरमार्क ब्रांडिंग
  - भुगतान विवरण
  - स्वचालित रसीद नंबरिंग

**रसीद नंबर प्रारूप:** `SCHOOL-RCPT-YYYYMM-SERIALNO`
- उदाहरण: `MEER-RCPT-202603-000045`

**स्वचालित विशेषताएं:**
- रसीद नंबर को मैन्युअल रूप से नहीं बदला जा सकता (सिस्टम-लॉक)
- रसीद# भुगतान क्रम से स्वचालित रूप से जेनरेट होता है
- PDF में स्कूल का नाम वाटरमार्क + लोगो शामिल है

---

### 3️⃣ बहु-शाखा संगठन

**समर्थित:**
- कई परिसरों वाले स्कूल
- विभिन्न विभागों वाले कॉलेज
- विभिन्न शहरों में शाखाएं वाली संस्थाएं

**यह कैसे काम करता है:**
1. सुपर एडमिन संस्थान सेटिंग्स में शाखाएं बनाता है
2. सुपर एडमिन उपयोगकर्ताओं को कई शाखाओं से मैप करता है
3. उपयोगकर्ता हेडर ड्रॉपडाउन से सक्रिय शाखा चुनते हैं
4. सभी डेटा (फीस, छात्र, उपस्थिति) सक्रिय शाखा द्वारा फ़िल्टर किया जाता है
5. रिपोर्ट शाखा-वार उत्पन्न होती हैं

---

### 4️⃣ लाइसेंस प्रबंधन (बैकेंड)

**स्थान:** सेटिंग्स → लाइसेंस सेटिंग्स (केवल एडमिन)

**विशेषताएं:**
- स्वचालित अद्वितीय लाइसेंस कुंजी जनन
- लाइसेंस सत्यापन
- कुंजी जनन के लिए बैकेंड API एंडपॉइंट
- डेटाबेस-स्तर की अद्वितीयता प्रवर्तन

---

## उपयोगकर्ता भूमिकाएं {#user-roles-hi}

### 👤 सुपर एडमिन
**अनुमतियां:**
- संगठन प्रोफ़ाइल बनाएं/संपादित करें
- सभी शाखाओं का प्रबंधन करें
- उपयोगकर्ताओं को शाखाओं को असाइन करें
- सभी रिपोर्ट तक पहुंच
- सिस्टम सेटिंग्स संशोधित करें
- किसी भी डेटा को ओवरराइड करें

**प्राथमिक कार्य:**
- प्रारंभिक सेटअप और कॉन्फ़िगरेशन
- शाखा निर्माण
- उपयोगकर्ता भूमिका असाइनमेंट
- संगठन ब्रांडिंग

---

### 👤 एडमिन
**अनुमतियां:**
- दिन-प्रतिदिन की कार्रवाई का प्रबंधन करें
- फीस भुगतान प्रक्रिया
- रसीद उत्पन्न करें
- सक्रिय शाखा के लिए रिपोर्ट देखें
- शाखा के भीतर छात्र और कर्मचारी जोड़ें

**प्राथमिक कार्य:**
- फीस संग्रह
- रसीद जनन
- छात्र प्रवेश
- कर्मचारी प्रबंधन

---

### 👤 एचआर
**अनुमतियां:**
- कर्मचारी रिकॉर्ड का प्रबंधन करें
- उपस्थिति ट्रैक करें (एचआर स्तर)
- पेरोल का प्रबंधन करें
- कर्मचारी छुट्टी प्रबंधन

**प्राथमिक कार्य:**
- कर्मचारी प्रबंधन
- उपस्थिति प्रसंस्करण
- छुट्टी अनुमोदन

---

### 👤 शिक्षक
**अनुमतियां:**
- उपस्थिति चिह्नित करें
- कक्षा रिकॉर्ड का प्रबंधन करें
- छात्र प्रदर्शन देखें
- शैक्षणिक रिपोर्ट सबमिट करें

**प्राथमिक कार्य:**
- दैनिक कक्षा उपस्थिति
- ग्रेड प्रविष्टि
- शैक्षणिक रिपोर्टिंग

---

### 👤 लेखाकार
**अनुमतियां:**
- फीस मॉड्यूल तक पहुंच
- रसीद उत्पन्न करें
- भुगतान रिपोर्ट देखें
- वित्तीय विवरण

**प्राथमिक कार्य:**
- फीस रसीद जनन
- भुगतान संग्रह ट्रैकिंग
- वित्तीय रिपोर्टिंग

---

## चरण-दर-चरण गाइड {#step-by-step-guides-hi}

### ⭐ गाइड 1: प्रारंभिक संगठन सेटअप

**चरण 1: सुपर एडमिन के रूप में लॉगिन करें**
```
1. एप्लिकेशन होम पेज खोलें
2. साख दर्ज करें
3. "लॉगिन" पर क्लिक करें
```

**चरण 2: संस्थान सेटिंग्स पर नेविगेट करें**
```
1. एडमिन मेनू पर क्लिक करें (ऊपर-दाएं)
2. "संस्थान सेटअप" चुनें
```

**चरण 3: संस्थान प्रोफ़ाइल भरें**
```
पूरी करने के लिए फ़ील्ड:
- संस्थान प्रकार: "स्कूल", "कॉलेज", या "संस्थान" चुनें
- संस्थान का नाम: अपने संस्थान का नाम दर्ज करें
- लघु नाम: 3-4 अक्षर कोड (जैसे, "MEER")
- लोगो: अपने संस्थान का लोगो अपलोड करें (PNG/JPG)
- पता: संपूर्ण संस्थान पता
- फोन: संपर्क संख्या
- ईमेल: आधिकारिक ईमेल
- शहर: स्थान
- सक्रिय है: चेकबॉक्स चेक करें
```

**चरण 4: प्रोफ़ाइल सेव करें**
```
"प्रोफ़ाइल सेव करें" बटन पर क्लिक करें
```

**चरण 5: पहली शाखा बनाएं**
```
"शाखा जोड़ें" सेक्शन पर जाएं:
- शाखा का नाम: मुख्य परिसर / मुख्य कार्यालय
- शाखा कोड: HEAD01
- शहर: आपका शहर
- पता: शाखा विवरण
- सक्रिय है: चेक करें
"शाखा जोड़ें" पर क्लिक करें
```

**चरण 6: उपयोगकर्ताओं को शाखाओं से मैप करें**
```
"ब्रांच यूजर मैपिंग" तक स्क्रॉल करें:
- ड्रॉपडाउन से उपयोगकर्ता चुनें
- शाखाएं चेक करें जिन्हें असाइन करना है
- "प्राथमिक शाखा" चुनें
- "मैपिंग अपडेट करें" पर क्लिक करें
```

✅ **सेटअप पूर्ण!** संगठन अब ब्रांडिंग के साथ कॉन्फ़िगर किया गया है।

---

### ⭐ गाइड 2: फीस रसीद जेनरेट और डाउनलोड करें

**चरण 1: एडमिन/लेखाकार के रूप में लॉगिन करें**
```
1. अपनी साख का उपयोग करें
2. लॉगिन पर क्लिक करें
```

**चरण 2: फीस मॉड्यूल पर नेविगेट करें**
```
1. एडमिन मेनू पर क्लिक करें
2. "फीस" चुनें
```

**चरण 3: छात्र फीस रिकॉर्ड खोजें**
```
1. फीस तालिका में छात्र पता लगाएं
2. छात्र का नाम या फीस आईडी पर क्लिक करें
```

**चरण 4: भुगतान प्रसंस्कृत करें (यदि नया भुगतान हो)**
```
1. "भुगतान जोड़ें" बटन पर क्लिक करें
2. भुगतान राशि दर्ज करें
3. भुगतान विधि चुनें (नकद/चेक/ऑनलाइन)
4. "भुगतान सेव करें" पर क्लिक करें
5. रसीद नंबर स्वचालित रूप से उत्पन्न होता है
```

**चरण 5: रसीद डाउनलोड करें**
```
1. "रसीद डाउनलोड करें" बटन पर क्लिक करें
2. PDF खुलता है:
   - संगठन लोगो और वाटरमार्क
   - रसीद नंबर (स्वचालित-उत्पन्न)
   - छात्र विवरण
   - भुगतान जानकारी
   - संस्थान ब्रांडिंग
3. PDF सेव या प्रिंट करें
```

📄 **रसीद प्रारूप उदाहरण:**
```
=====================================
MEERAHR संस्थान
रसीद #: MEER-RCPT-202603-000045
=====================================
छात्र: अहमद खान
बकाया फीस: 5,000 रुपये
भुगतान की गई राशि: 5,000 रुपये
तारीख: 22-03-2026
स्थिति: भुगतान किया गया
=====================================
```

✅ **रसीद तैयार!** प्रिंट या ईमेल किए जा सकते हैं।

---

### ⭐ गाइड 3: शाखाओं के बीच स्विच करें (बहु-शाखा उपयोगकर्ता)

**चरण 1: बहु-शाखा पहुंच के साथ लॉगिन करें**
```
आपके उपयोगकर्ता को 2+ शाखाओं से मैप किया जाना चाहिए
```

**चरण 2: हेडर को देखें**
```
शीर्ष नेविगेशन बार में, आप देखेंगे:
[लोगो] MEERAHR | सक्रिय शाखा: मुख्य परिसर
```

**चरण 3: शाखा ड्रॉपडाउन पर क्लिक करें**
```
शाखा नाम या ड्रॉपडाउन तीर पर क्लिक करें
उन शाखाओं की सूची दिखाता है जिन तक आप पहुंच सकते हैं
```

**चरण 4: नई शाखा चुनें**
```
ड्रॉपडाउन से शाखा चुनें:
- मुख्य परिसर
- शाखा 2
- शाखा 3
आदि।
```

**चरण 5: स्विच की पुष्टि करें**
```
नई शाखा सक्रिय हो जाती है
सभी डेटा अब चयनित शाखा द्वारा फ़िल्टर किया जाता है
पृष्ठ स्वचालित रूप से पुनः लोड होता है
```

✅ **शाखा स्विच हो गई!** सभी बाद की कार्रवाई नई शाखा के लिए।

---

### ⭐ गाइड 4: नई शाखा जोड़ें

**चरण 1: सुपर एडमिन → संस्थान सेटिंग्स**
```
1. सुपर एडमिन के रूप में लॉगिन करें
2. एडमिन मेनू → संस्थान सेटअप पर क्लिक करें
```

**चरण 2: "शाखा जोड़ें" सेक्शन तक स्क्रॉल करें**
```
फॉर्म भरें:
- शाखा का नाम: "लाहौर परिसर" (आवश्यक)
- शाखा कोड: "LHR01" (वैकल्पिक लेकिन अनुशंसित)
- शहर: "लाहौर"
- पता: पूर्ण पता
- सक्रिय है: चेक किया गया
```

**चरण 3: फॉर्म सबमिट करें**
```
"शाखा जोड़ें" बटन पर क्लिक करें
```

**चरण 4: जोड़ने की पुष्टि करें**
```
सफलता संदेश दिखाई देता है
नई शाखा शाखा मैपिंग सेक्शन में दिखाई देती है
```

**चरण 5: शाखा के लिए उपयोगकर्ता मैप करें**
```
ब्रांच यूजर मैपिंग तक स्क्रॉल करें
उन उपयोगकर्ताओं को चुनें जिन्हें इस शाखा तक पहुंच होनी चाहिए
उन्हें नई शाखा में जोड़ें
```

✅ **नई शाखा बनाई गई और कॉन्फ़िगर की गई!**

---

### ⭐ गाइड 5: शिक्षकों को शाखाओं से मैप करें

**चरण 1: सुपर एडमिन → संस्थान सेटिंग्स**
```
1. सुपर एडमिन के रूप में लॉगिन करें
2. संस्थान सेटअप पर जाएं
3. "शाखा उपयोगकर्ता मैपिंग" सेक्शन तक स्क्रॉल करें
```

**चरण 2: ड्रॉपडाउन से उपयोगकर्ता चुनें**
```
ड्रॉपडाउन में शिक्षक का नाम खोजें
चुनने के लिए क्लिक करें
```

**चरण 3: शाखाएं चुनें**
```
सभी शाखाओं के लिए चेकबॉक्स दिखाई देते हैं
उन शाखाओं को चेक करें जहां शिक्षक को काम करना चाहिए
उदाहरण: "मुख्य परिसर" और "लाहौर परिसर" चेक करें
```

**चरण 4: प्राथमिक शाखा सेट करें**
```
ड्रॉपडाउन से प्राथमिक शाखा चुनें
यह वह जगह है जहां उपयोगकर्ता डिफॉल्ट रूप से लॉगिन करता है
```

**चरण 5: मैपिंग अपडेट करें**
```
"मैपिंग अपडेट करें" बटन पर क्लिक करें
```

**चरण 6: परिवर्तन की पुष्टि करें**
```
उपयोगकर्ता अब मैपिंग तालिका में दिखाई देता है
सत्यापित करने के लिए पृष्ठ को ताज़ा करें
```

✅ **शिक्षक शाखाओं से मैप हो गया!**
शिक्षक अब कर सकता है:
- लॉगिन करें और सभी असाइन की गई शाखाओं को देखें
- शीर्ष मेनू से शाखाएं स्विच करें
- केवल अपनी सक्रिय शाखा के लिए डेटा तक पहुंच

---

### ⭐ गाइड 6: शाखा द्वारा रिपोर्ट देखें

**चरण 1: सक्रिय शाखा चुनें**
```
यदि बहु-शाखा उपयोगकर्ता हैं, तो वांछित शाखा पर स्विच करें
(गाइड 3 देखें: शाखाओं के बीच स्विच करें)
```

**चरण 2: रिपोर्ट पर नेविगेट करें**
```
1. मेनू पर क्लिक करें
2. "रिपोर्ट" चुनें
3. रिपोर्ट प्रकार चुनें (फीस, उपस्थिति, आदि।)
```

**चरण 3: शाखा-फ़िल्टर किए गए डेटा देखें**
```
सभी रिपोर्ट डेटा केवल सक्रिय शाखा के लिए दिखाया जाता है
रिपोर्ट स्वचालित रूप से फ़िल्टर की जाती हैं
कोई मैन्युअल फ़िल्टरिंग की आवश्यकता नहीं है
```

**चरण 4: निर्यात/प्रिंट करें**
```
"निर्यात" या "प्रिंट" बटन पर क्लिक करें
डेटा में शाखा जानकारी शामिल है
व्यावहारिक स्वरूपण
```

✅ **सक्रिय शाखा के लिए रिपोर्ट उत्पन्न की गई!**

---

### ⭐ गाइड 7: सामान्य समस्याओं को ठीक करना

**समस्या 1: सही पासवर्ड के साथ लॉगिन विफल**
```
समाधान:
1. ब्राउज़र कुकीज़ को साफ़ करें
2. दूसरे ब्राउज़र का प्रयास करें
3. जांचें कि CAPS LOCK बंद है
4. ईमेल प्रारूप सत्यापित करें
5. पासवर्ड रीसेट करने के लिए एडमिन से संपर्क करें
```

**समस्या 2: रसीद गलत शाखा दिखाती है**
```
समाधान:
1. हेडर में सही शाखा पर स्विच करें
2. रसीद फिर से डाउनलोड करें
3. भुगतान फिर से प्रसंस्कृत करें
4. सुनिश्चित करें कि शाखा प्राथमिक के रूप में सेट है
```

**समस्या 3: शाखा ड्रॉपडाउन नहीं दिखा रहा**
```
समाधान:
1. आपके पास संभवतः केवल 1 शाखा असाइन की गई है
2. अधिक शाखाएं जोड़ने के लिए सुपर एडमिन से पूछें
3. अधिक शाखाओं के लिए आपको मैप करने के लिए सुपर एडमिन से पूछें
4. ताज़ा करने के लिए लॉगआउट करें और फिर से लॉगिन करें
```

**समस्या 4: कुछ डेटा नहीं देख सकते**
```
समाधान:
1. हेडर में सक्रिय शाखा जांचें
2. सत्यापित करें कि आपका उपयोगकर्ता इस शाखा से मैप है
3. आपकी उपयोगकर्ता भूमिका में अनुमति है
4. पहुंच के लिए सुपर एडमिन से संपर्क करें
```

**समस्या 5: रसीद PDF डाउनलोड नहीं हो रहा**
```
समाधान:
1. ब्राउज़र पॉपअप ब्लॉकर चेक करें
2. दूसरे ब्राउज़र का प्रयास करें
3. सुनिश्चित करें कि भुगतान सेव है
4. इंटरनेट कनेक्शन जांचें
5. सुनिश्चित करें कि लोगो फ़ाइल मौजूद है
```

---

## 💾 डेटा बैकअप और सुरक्षा {#data-backup-hi}

**नियमित बैकअप:**
```bash
# मैनुअल डेटाबेस बैकअप
php artisan backup:run

# बैकअप स्थान: storage/backups/
```

**सुरक्षा सुझाव:**
- हर 3 महीने में पासवर्ड बदलें
- लॉगिन साख साझा न करें
- मजबूत पासवर्ड का उपयोग करें (न्यूनतम 12 वर्ण)
- उत्पादन में HTTPS सक्षम करें
- डेटाबेस पहुंच प्रतिबंधित करें
- उपयोगकर्ता गतिविधि लॉग की निगरानी करें

---

## मार्केट पोजिशनिंग और सर्विस {#market-positioning-services-hi}

### किन संस्थानों के लिए यह सॉफ्टवेयर सबसे उपयुक्त है
- एक या कई शाखाओं वाले स्कूल
- अलग-अलग विभागों या शहर शाखाओं वाले कॉलेज
- कोचिंग सेंटर और अकादमी जहां फीस अभी भी मैन्युअल चल रही है
- एजुकेशन ग्रुप जिन्हें एक branded ERP चाहिए
- ऐसे संस्थान जिन्हें receipt automation और role-based access चाहिए

### यह सॉफ्टवेयर मार्केट की कौन सी समस्याएं हल करता है
- मैन्युअल फीस रसीदों में गलती और डुप्लिकेट रिकॉर्ड
- Excel या कागजी सिस्टम में branch-wise data control मुश्किल होना
- स्टाफ access का proper control न होना
- सभी branches का एक जैसा reporting system न होना
- रसीद और internal operations में institute branding का न होना

### इस प्रोजेक्ट के मुख्य selling points
- एक ही system में multi-branch setup
- पूरी तरह system-controlled auto receipt number
- logo और watermark के साथ branded PDF receipt
- user-to-branch mapping और primary branch option
- super admin control और branch-wise visibility
- school, college, institute तीनों model के लिए suitable

### Client ko dene wali services

**1. Setup Service**
- Client server ya local hosting par installation
- Organization profile setup
- Branch creation aur user mapping
- Logo upload aur branding configuration

**2. Training Service**
- Super Admin training
- Accountant/Admin ko fee workflow training
- Teacher aur HR onboarding training
- Branch switching aur reports ka practical use

**3. Customization Service**
- Client-specific modules add karna
- Receipt format ya branding layout change karna
- Role permissions ya approval flow banana
- Management ke liye custom reports banana

**4. Support Service**
- Bug fixing aur maintenance
- Backup aur restore support
- Performance monitoring
- Monthly ya yearly support contract

### Suggested Service Packages

**Basic Package**
- Single branch setup
- Logo branding
- Fee receipt module
- 1 admin training session

**Standard Package**
- Multi-branch setup
- User aur teacher mapping
- Receipt aur reporting setup
- 3 training sessions
- 30 days support

**Premium Package**
- Full institute deployment
- Custom workflows aur reports
- Priority support
- Data import assistance
- 3 se 12 months maintenance plan

### Sales Demo ka Best Flow
1. Sabse pehle institute profile aur branding dikhayein.
2. Uske baad branches create karke user mapping dikhayein.
3. Fee entry aur payment process demo karein.
4. Receipt download karke watermark aur auto receipt number dikhayein.
5. Header se branch switch karke multi-branch support prove karein.
6. End me reporting, security, aur support options explain karein.

### Ready Marketing Pitch
"MEERAHR ek modern institute management solution hai jo school, college, aur academy ke liye design kiya gaya hai. Isme aap branches, staff, fees, aur receipts ko ek hi system se manage kar sakte hain. Auto-generated receipt numbers, organization branding, branch-wise access control, aur professional reporting ke sath yeh manual register aur scattered Excel files ko replace karke ek secure aur scalable platform provide karta hai."

### Client ko simple language me fayde
- Admin ka time bachta hai
- Fee entry mistakes kam hoti hain
- Reporting fast hoti hai
- Har branch ko controlled access milta hai
- Parents aur students ke samne professional image banti hai
- Institute future digital growth ke liye ready hota hai

### Client ke liye Proposal / Quotation Format

School, college, ya institute ko formal offer bhejte waqt aap yeh format use kar sakte hain:

```text
PROPOSAL / QUOTATION

Date:
Quotation No:
Client Name:
Institute Name:
City:
Contact Person:
Mobile / Email:

Subject: Institute Management Software Implementation Proposal

Dear Sir/Madam,

MEERAHR Institute Management System me interest dikhane ke liye shukriya.
Humein khushi hai ke hum aap ko software deployment, configuration, training, aur support ke liye yeh proposal submit kar rahe hain.

Project Scope:
- Institute profile setup
- Branch setup aur mapping
- User role management
- Fee management aur receipt generation
- Logo aur watermark branding
- Training aur after-sales support

Included Modules:
- Organization Setup
- Branch Management
- User aur Teacher Mapping
- Fee Management
- Receipt PDF Download
- Role-Based Access

Commercial Offer:
Package Name:
Implementation Cost:
Training Cost:
Support Cost:
Customization Cost (if any):
Total Project Cost:

Delivery Timeline:
- Setup Time:
- Training Time:
- Go-Live Date:

Payment Terms:
- 50% advance before project start
- 30% after setup completion
- 20% after training and handover

Support Terms:
- Free support period:
- Paid AMC / maintenance after warranty:

Validity:
Yeh quotation issue date se 15 se 30 din tak valid rahega.

Regards,
Your Name
Company Name
Phone / WhatsApp
Email
```

### PKR / INR Pricing Table

| Package | Kis ke liye best hai | Features | Price PKR | Price INR |
|---|---|---|---:|---:|
| Basic | Single branch school ya academy | Institute setup, logo branding, fee receipts, 1 training session | 45,000 | 13,500 |
| Standard | Growing school ya college | Multi-branch setup, user mapping, receipts, reports, 3 training sessions, 30 days support | 95,000 | 28,500 |
| Premium | Large institute group | Full deployment, customization, priority support, data import, extended maintenance | 180,000 | 54,000 |

### Optional Add-On Pricing

| Add-On Service | Price PKR | Price INR |
|---|---:|---:|
| Additional branch setup | 8,000 | 2,400 |
| Data import from Excel | 15,000 | 4,500 |
| Custom report | 12,000 | 3,600 |
| WhatsApp/SMS integration | 25,000 | 7,500 |
| Extra training session | 7,500 | 2,250 |
| Monthly support plan | 10,000 | 3,000 |

### Pricing Notes
- PKR aur INR values sample rates hain; aap apne city, client size, aur market ke hisab se adjust kar sakte hain.
- Hosting, domain, SMS gateway, WhatsApp API, aur third-party charges alag quote karne chahiye.
- Annual maintenance contract total project cost ka 15% se 25% rakha ja sakta hai.

---

## 📞 समर्थन और संपर्क {#support-hi}

**समस्या होने पर:**
1. यूजर गाइड की जांच करें
2. एडमिन से संपर्क करें
3. सिस्टम लॉग में त्रुटि संदेश देखें
4. तकनीकी समर्थन से संपर्क करें

**तकनीकी सहायता:**
- ईमेल: support@meerahr.test
- फोन: +92-300-XXXX-XXX
- दस्तावेज: https://meerahr.test/docs

---

## ✅ सारांश

यह गाइड MEERAHR प्रणाली का संपूर्ण अवलोकन प्रदान करती है। आप अब कर सकते हैं:

✔️ संस्थान प्रोफ़ाइल सेट करें  
✔️ शाखाएं बनाएं और प्रबंधित करें  
✔️ उपयोगकर्ताओं को शाखाओं से मैप करें  
✔️ फीस और भुगतान प्रबंधित करें  
✔️ पेशेवर रसीदें जेनरेट करें  
✔️ शाखाओं के बीच स्विच करें  
✔️ शाखा-अनुसार रिपोर्ट देखें  

**संपूर्ण सेटअप के लिए 100% गाइड पूरी हुई!**

