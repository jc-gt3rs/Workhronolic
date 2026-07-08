# 

# 

# 

# 

# 

# 

# 

# 

# 

# Workhronolic

## Proposed by group **Aa** from TA22

Members:  
Ancheta, Dathan Raniel  
Antor, John Cris

# 

# 

# 

# 

# 

# 

# 

# Workhronolic

## Automated Time-Tracking for Startup Micro-Teams

Workhronolic is a lightweight, web-based time-tracking, productivity verification platform specifically designed for startup organizations employing part-time workers on fixed monthly rates. In fast-paced startup environments, fixed-rate compensation models frequently suffer from a lack of visibility, leading to disputes over accountability and a complete lack of justification for actual hours contributed. This system bridges that gap by providing intuitive clock-in/clock-out mechanisms and structured daily activity logs, transforming ambiguous monthly arrangements into transparent, data-driven partnerships.

The system directly addresses the "black box" problem of remote, part-time contract work where hours spent do not always match output quality. By requiring structured proof of activity alongside a rigorous time log, it ensures founders get what they pay for while giving honest workers a platform to justify their value. The primary target users are startup founders, project managers, and decentralized part-time freelancers or contractors who need an uncomplicated, low-friction tool to prove and track operational hours without the bloat of enterprise-grade HR software.

## Core Features

### Secure User Account & Role Management System

Allows users to register an account and log in securely. The system implements basic Role-Based Access Control (RBAC) separating Administrators/Founders (who view all team dashboards, approve timesheets, and manage users) from Part-Time Workers (who track time, submit justifications, and edit their personal profiles).

### Dynamic Timesheet CRUD Operations

The core data-handling engine of the application. Workers can Create new time-logs and daily accomplishment notes, Read their historical tracking logs, Update pending entries to fix accidental mistakes, and Delete incorrect submissions before final managerial review.

### Relational Database Integration

Powered by a structured database (such as MySQL), the application dynamically stores, organizes, and retrieves relational data. Tables are highly optimized to link user profiles, time-stamps, role permissions, and text-based justification logs seamlessly.

### Stateful Session Management & Access Control

Utilizes secure server-side sessions to maintain a user's logged-in state across pages. It strictly enforces authentication guardrails, preventing unauthenticated users or standard workers from forcefully accessing administrative dashboards or other workers' sensitive logs via URL manipulation.

### Robust Input Validation & Error Handling

The system mandates strict backend validation parameters. It ensures time formats are accurate (e.g., end times cannot occur before start times), fields like "Daily Accomplishments" meet a minimum character requirement to prevent empty justifications, and email formats are stringently verified.

### Data Sanitization & Injection Defense

Built with security-first coding practices, the backend employs parameterized queries and prepared statements to eliminate SQL Injection vulnerabilities. It also utilizes HTML entity encoding to sanitize user-submitted activity logs, effectively mitigating Cross-Site Scripting (XSS) risks.

### Structured Activity & Justification Logger

When clocking out or logging hours, workers are prompted with a mandatory justification form. Instead of just tracking raw numbers, workers must detail tasks completed, matching the logged time directly against specific project milestones or deliverables.

### Automated Monthly Hours Auditing & Export

Generates an automated summary at the end of each monthly billing cycle. Administrators can view a clean breakdown comparing agreed-upon expected hours against the actual verified hours worked, with an option to export the data to a CSV or PDF file for payroll alignment.

