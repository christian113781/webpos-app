<p align="center">
  <img src="docs/preview.png" alt="TalipapaPOS — point of sale and inventory for small shops" width="100%">
</p>

<h1 align="center"><img src="assets/img/logo.png" alt="TalipapaPOS" height="56"></h1>

<p align="center">
  A point-of-sale and inventory system for small shops in the Philippines — sari-sari stores, groceries, cafés, pharmacies and more.<br>
  Built with plain HTML, Tailwind CSS and Alpine.js — no build step, no backend required.
</p>

<p align="center">
  <img alt="HTML5" src="https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white">
  <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Alpine.js" src="https://img.shields.io/badge/Alpine.js-8BC0D0?logo=alpinedotjs&logoColor=black">
  <img alt="Status" src="https://img.shields.io/badge/status-front--end%20prototype-orange">
</p>

---

## Contents

- [Features](#features)
- [Pages](#pages)
- [Getting started](#getting-started)
- [Trying it out](#trying-it-out)
- [Project structure](#project-structure)
- [How data is stored](#how-data-is-stored)
- [Known limitations](#known-limitations)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

## Features

**Selling**
- Fast point of sale with product search, barcode/SKU scanning (press Enter), and a switchable large / grid / list product view (your choice is remembered).
- Philippine tax rules built in: prices include 12% VAT, and **Senior Citizen / PWD** sales are VAT-exempt with a 20% discount (ID number required).
- Cash, GCash and card payments, with quick-cash buttons, change calculation, an order summary step, and a printable receipt.
- **Thermal receipt printing** straight from the browser: Bluetooth (BLE) or USB ESC/POS printers, plus the normal system print dialog as a fallback. Receipt footer and options are configurable.
- Pick a saved customer at the counter: their discount type and ID fill in automatically, and any recorded notes such as **allergies** are flagged.
- Sales history with search, filters, CSV export and voiding (a reason is required).

**Inventory**
- Product catalogue with name, variant (size / flavour), brand, category, SKU / barcode, unit, stock, reorder level, cost and price. Ships with a sample catalogue of everyday store goods (drinks, snacks, canned goods, pantry, household, personal care, fresh & frozen, school & office).
- Expiry tracker for perishable items that groups batches into 30 / 60 / 90-day buckets, and lets you dispose of or return expired and near-expiry stock with a log. Non-perishable items are simply left without an expiry date.
- Suppliers and purchase orders (draft → ordered → received / cancelled).

**Management**
- Customers with type (Regular / Senior / PWD), ID number, notes and purchase history.
- Returns and refunds with reasons, item condition, restock-or-dispose handling, approval flow, and refund slips.
- Expense tracking with categories, payment methods and recurring bills.
- Reports: sales trend, payment mix, discounts given, top products, category breakdown, profit, and reorder / expiry lists, with CSV export.
- Profile (including optional professional and business licence details), settings, backup export, and login / register / 404 pages.
- A public-facing **welcome / landing page** (`index.html`) with features, pricing plans and FAQ.

**Interface**
- Responsive layout with a collapsible sidebar and a mobile bottom-sheet cart.
- Keyboard-accessible dialogs with focus trapping and screen-reader labels.

## Pages

| Page | File | What it does |
| --- | --- | --- |
| Dashboard | `dashboard.html` | Today's sales, 7-day chart, payment mix, low stock, expiring items, open orders |
| Point of Sale | `pos.html` | Cart, customer picker, discounts, checkout and receipt |
| Sales History | `sales.html` | Browse, filter, export and void sales |
| Products | `product.html` | Add, edit, view and delete products |
| Expiry Tracker | `expiry.html` | Track expiring perishables and log disposals |
| Suppliers | `suppliers.html` | Supplier directory |
| Purchase Orders | `purchase-orders.html` | Create and manage orders to suppliers |
| Customers | `customers.html` | Customer records, discount type and purchase history |
| Returns & Refunds | `returns.html` | Process and approve returns |
| Expenses | `expenses.html` | Record store expenses |
| Reports | `reports.html` | Sales, profit and inventory reports |
| My Profile | `profile.html` | Personal, licence and store details |
| Settings | `settings.html` | Preferences, backup and reset |
| Landing page | `index.html` | Welcome page with features, plans and FAQ |
| Login / Register / 404 | `login.html`, `register.html`, `404.html` | Account and error pages |

## Getting started

There is nothing to install or build.

**Option 1 — open the file**

Open `index.html` (the landing page) in your browser, then use **Explore live demo** to reach the dashboard.

**Option 2 — run a local server** (recommended)

```bash
# from the project folder
python3 -m http.server 8000
# then visit http://localhost:8000
```

> **Internet connection needed.** Tailwind CSS, Alpine.js and the Poppins font are loaded from CDNs, so the pages need to be online the first time they load. To work fully offline, download those files and point the `<script>` / `<link>` tags to your local copies.

## Trying it out

The app starts with a sample product catalogue, suppliers, customers, purchase orders, expenses and returns so every page has something to show.

1. On the **Dashboard**, **Sales History** or **Returns** page, click **Load sample sales** to fill in demo sales.
2. Open **Point of Sale**, add a product, and choose a saved customer (try *Remedios Cruz*, a Senior Citizen) to see the VAT exemption and 20% discount applied.
3. Complete the sale, then check **Sales History** and the customer's profile on **Customers**.
4. Try a refund on **Returns & Refunds**, or add a product on **Products**.

Sample expiry dates are relative to today, so every status (expired, expiring soon, OK) always appears in the demo.

**Printing receipts:** on the POS page, open the printer settings to pair a Bluetooth or USB thermal printer. Web Bluetooth and WebUSB need Chrome or Edge over HTTPS (or `localhost`); they are not available in Safari or on iPhone.

## Project structure

```
talipapapos/
├── index.html              # Landing page (welcome)
├── dashboard.html          # Dashboard
├── pos.html                # Point of sale
├── sales.html              # Sales history
├── product.html            # Product catalogue
├── expiry.html             # Expiry tracker
├── suppliers.html          # Suppliers
├── purchase-orders.html    # Purchase orders
├── customers.html          # Customers
├── returns.html            # Returns & refunds
├── expenses.html           # Expenses
├── reports.html            # Reports
├── profile.html            # My profile
├── settings.html           # Settings
├── login.html              # Sign in
├── register.html           # Create account
├── 404.html                # Not found
├── docs/
│   └── preview.png         # README banner
├── logo/
│   └── talipapa-logo.png   # Brand logo
└── assets/
    ├── css/style.css       # Shared styles
    ├── js/
    │   ├── main.js         # Storage helpers, navigation, shared page logic
    │   └── data.js         # Sample product catalogue and sample sales
    └── img/                # Logo, favicon and product icons
```

Each page is a self-contained HTML file with its own Alpine.js component at the bottom. Shared behaviour (sidebar, toasts, dialogs, notifications) lives in `assets/js/main.js` and is spread into every page through `sharedChrome()`.

## How data is stored

TalipapaPOS has no backend. Records are saved in your browser's `localStorage`, so they stay on that browser and device.

| Data | Storage key |
| --- | --- |
| Sales | `pp-sales` |
| Customers | `pp-customers` |
| Suppliers | `pp-suppliers` |
| Purchase orders | `pp-purchase-orders` |
| Returns | `pp-returns` |
| Expenses | `pp-expenses` |
| Profile | `pp-profile` |
| Settings | `pp-settings` |
| Expiry disposal log | `pp-expiry-log` |
| Receipt options | `pp_receiptCfg` |
| Printer choice | `pp_printerMode`, `pp_printerName`, `pp_usbName` |
| UI preferences | `pp-pos-view`, `pp-sidebar-collapsed`, `pp-nav-open` |

Backup export and "Reset all data" in Settings cover every key that starts with `pp-`. Each store is a small object in `main.js` (for example `ppSales`, `ppCustomers`) with `load()` and `save()` methods. To connect a real database later, replace those methods with API calls.

## Known limitations

This is a front-end prototype, and some parts are not connected yet:

- **Stock levels are not shared between pages.** Products are loaded from `data.js` on each page (the Products page keeps its own sample list), so selling an item, receiving a purchase order, restocking a return, or editing a product does not carry over to other pages or survive a refresh.
- **Settings are saved but not applied.** The VAT rate, discount rate and low-stock and expiry thresholds are currently fixed values (12%, 20%, 90 days).
- **Refunds are not subtracted from reports.**
- **Notifications are sample text,** not generated from real stock or expiry data.
- **Some pharmacy wording remains.** Parts of Profile, Settings and Customers still mention medicines, PRC and FDA LTO fields from the earlier drugstore version.
- **Login and register are placeholders.** There is no real authentication, so do not use this to protect real data.
- Data lives in one browser only; clearing site data erases it.

## Roadmap

- [ ] Finish the move to a general-store wording (Profile, Settings, Customers) and make licence fields optional
- [ ] Shared inventory store so sales, purchase orders, returns and disposals update stock everywhere
- [ ] Apply Settings (VAT, discount rate, thresholds, receipt footer) across the app
- [ ] Subtract refunds from sales and profit reports
- [ ] Real notifications from low-stock and expiry data
- [ ] Backend API and database, with real authentication and user roles
- [ ] Offline support (PWA) and bundled local copies of Tailwind and Alpine

## Contributing

Issues and pull requests are welcome. For larger changes, please open an issue first to discuss what you would like to change.

When adding a page, follow the existing pattern: spread `sharedChrome('your-page.html')` into the Alpine component, and add the page to `getNavSections()` in `assets/js/main.js`.

## License

No license has been chosen yet. Add a `LICENSE` file (for example [MIT](https://choosealicense.com/licenses/mit/)) before sharing the project publicly, and update this section to match.
