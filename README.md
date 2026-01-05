🎣 TackleBox Pro

![TackleBox Main Screen](./screenshots/Screenshot1.png)




TackleBox Pro is a lightweight, web-based inventory system designed specifically for anglers to efficiently manage their lures, rods, reels, and accessories. The app provides a fast overview of your inventory, the total value of your gear, and detailed statistics for individual categories.

✨ Key Features

Visual Inventory
A modern grid view displays all tackle items with image previews.

Category Management
Automatic filtering for hardbaits, soft plastics, rods, reels, and more.

Real-Time Statistics
Instant calculation of total item count and monetary value — globally and per category.

Smart Search
Quickly find equipment using live search by brand, model, or target fish.

Target Fish Tracking
Assign specific target fish (e.g. pike, zander, perch) to each lure.

Lightbox Detail View
Click on any image to view it in full-screen mode.

Full Data Control
Built-in export and import functionality (ZIP/SQLite) to create full database backups, including all images.

🛠 Technical Highlights

Backend
PHP & SQLite (no complex database setup required)

Frontend
Modern, responsive design with Dark Mode support

Security
CSRF protection, secure session handling, and password hashing for the admin area

Performance
Lazy loading (infinite scroll) keeps the app extremely fast, even with hundreds of entries

🚀 Installation

On your Proxmox system, paste the following command into the console and press Enter:

bash -c "$(curl -sL https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/setup.sh | tr -d '\r')"

📸 Screenshots
![TackleBox Main Screen](./screenshots/Screenshot2.png)
![TackleBox Main Screen](./screenshots/Screenshot3.png)
![TackleBox Main Screen](./screenshots/Screenshot4.png)
