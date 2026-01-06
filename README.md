🎣 TackleBox Pro

![TackleBox Main Screen](./screenshots/Screenshot5.png)



TackleBox Pro is a lightweight, web-based inventory system for anglers. It helps manage lures, rods, reels, and accessories in a clear and structured way, providing a fast overview of your inventory, total gear value, and detailed statistics at all times.

🚀 Features

📦 Visual Inventory
Modern grid-based layout with image previews of all tackle items

🗂 Category Management
Automatic filtering for hardbaits, soft plastics, rods, reels, and more

📊 Real-Time Statistics
Display of total item count and total value — globally and per category

🔍 Smart Search
Live search by brand, model, or target fish

🐟 Target Fish Tracking
Lures can be assigned to specific target fish (e.g. pike, zander, perch)

🖼 Lightbox Detail View
Images can be viewed in full-screen mode with a single click

💾 Data Export & Import
Backup functionality (ZIP/SQLite) including all images

📱 QR Codes

Each box has its own QR code.
The QR code contains a direct link to the box in the app.
______________________________________________________________________________________________________

🛠 Technical Overview

Area | Technology
Backend | PHP, SQLite
Frontend | HTML, CSS, JavaScript
Design | Responsive, Dark Mode
Security | CSRF protection, sessions, password hashing
Performance | Lazy loading (infinite scroll)

No external database required — ideal for self-hosting.
______________________________________________________________________________________________________
⚙️ Installation (Proxmox)

Open the Proxmox console

Paste the following command and press Enter:

bash -c "$(curl -sL https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/setup.sh | tr -d '\r')"

______________________________________________________________________________________________________

The setup will handle:

Installation of all dependencies

Setup of the SQLite database

Deployment of the web application

______________________________________________________________________________________________________

🔐 Security

Protected admin area

CSRF tokens for all forms

Secure password hashing mechanisms

Clean and reliable session handling

______________________________________________________________________________________________________

🎯 Target Audience

Hobby and professional anglers

Tackle collectors

Anglers with large lure and gear inventories

Self-hosting enthusiasts
📸 Screenshots
![TackleBox Main Screen](./screenshots/Screenshot2.png)
![TackleBox Main Screen](./screenshots/Screenshot3.png)
![TackleBox Main Screen](./screenshots/Screenshot4.png)
