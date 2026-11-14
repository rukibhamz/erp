# Business ERP Training Manual

This manual is for trainers and staff learning the Business ERP. It explains what each role is for, and how to use each module in day-to-day work.

Module names in the left sidebar can be renamed by a Super Admin. If a name on screen differs from this manual, follow the name in the sidebar. The tasks inside the module stay the same.

---

## How to use this manual

- **New staff:** read Part A, then the role chapter that matches the account you were given, then the module chapters listed in that role’s training path.
- **Trainers:** use Part E for class plans. Each module chapter ends with a short practice task.
- **Supervisors:** use the role matrix in Part B when deciding who should be given which account.

Practice on a training company or with clearly labelled test records. Do not practise delete, period lock, or payroll posting on live data.

---

## Part A — Everyday use

### A1. Signing in and signing out

1. Open the system address given by your administrator.
2. Enter your username and password.
3. After sign-in you land on the **Dashboard**.
4. To leave, open your name in the top bar and choose **Logout**.

Staff accounts and customer accounts are separate.

| Who | Where to sign in |
| --- | --- |
| Employees (Super Admin, Admin, Manager, Accountant, Staff, User) | Main login page |
| Customers who book spaces | `customer-portal` |

If you cannot sign in, ask an administrator to check that your account status is **Active**. Inactive and suspended accounts cannot work in the system.

Passwords must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.

### A2. The screen

- **Left sidebar:** modules you are allowed to open. If a module is missing, either it is switched off or your role does not include it.
- **Top bar:** search, the bell (notifications), and your profile.
- **Inside a module:** a row of tabs (Dashboard, lists, reports, and so on).
- **Green and red messages** at the top of a page tell you whether the last action succeeded.

On a phone, open the menu with the list icon in the top bar.

### A3. Search, notifications, and profile

**Search.** Type in the search box at the top and press Enter. Search looks across modules. Results are limited to records you are allowed to see.

**Notifications.** The bell shows unread items. Open an item to go to the related record, or choose **View all notifications**. Use **Mark all read** when you have reviewed them. New bookings notify Admin, Super Admin, and Manager users.

**Profile.** Open your name, then **My Profile**. You can update your name and contact details, change your password, and upload a photo. You cannot change your own role.

### A4. Working with lists

Most lists share the same habits:

- Use the filter bar to search, narrow by status, or change how many rows appear.
- Click a column heading where it is offered to sort.
- Open a row’s view or edit action to work on one record.
- **Create** buttons appear only when your role includes create permission.
- **Delete** and bulk delete appear only for roles that are allowed to delete. Manager and Accountant accounts are not given delete on financial and operational records, so the history stays intact.

### A5. When a page is refused

Two different messages matter:

- **You do not have permission to perform this action.** Your role can open the area, but not this specific action (for example, delete).
- **This module is currently inactive.** A Super Admin has switched that module off for the whole system.

Admin and Super Admin accounts pass permission checks. Everyone else is limited to the permissions stored on their user.

---

## Part B — Roles

There are six staff roles and one customer role. Privilege runs from User and Staff at the bottom to Super Admin at the top. A person can edit or assign only roles below their own. Nobody can promote someone to a role higher than their own.

| Role | Main job | Can configure the system | Can delete financial records | Typical modules |
| --- | --- | --- | --- | --- |
| Super Admin | Owns the installation | Yes, including module names, activity log, and entities | Yes | All, even inactive modules |
| Admin | Runs the business day to day and manages users | Yes, except Super Admin-only tools | Yes | All active modules |
| Manager | Runs operations: bookings, property, stock, utilities, sales | No | No | All active modules except Tax, Users, Settings, Companies, and Modules |
| Accountant | Keeps the books and tax returns | No | No | Accounting, cash, receivables, payables, ledger, tax, reports. Read-only on dashboard, notifications, employees, and customers |
| Staff | Does the tasks an admin ticks for them | Only if granted | Only if granted | Whatever is ticked on the user |
| User | Limited internal access | Only if granted | Only if granted | Whatever is ticked on the user |
| Customer | Books and pays for their own spaces | No | No | Customer portal only |

**Admin** and **Manager** receive a standard permission set when the account is created and no extra boxes are ticked. **Accountant** receives financial access the same way. **Staff** and **User** receive only the boxes an administrator ticks. If those boxes are ticked on an Admin, Manager, or Accountant, the ticked list replaces the standard set. Leave the boxes empty when you want the standard set.

**Super Admin** always has full access, whether or not boxes are ticked.

### B1. Super Admin

**You are responsible for** the installation: which modules are on, what they are called, who the other administrators are, and whether the system is healthy.

**You can see extra sidebar items:**

- Activity Log
- Entities (companies)
- Customize Modules
- Promo Codes (shared with Admin)
- Debug Log
- Settings

**Day-to-day tasks**

1. Confirm the business profile in **Settings → System Settings** (company name, email, SMS, preferences).
2. Turn modules on or off and rename them so the sidebar matches how the business talks (for example, “Locations” instead of “Properties”).
3. Create the first Admin, and only as many Super Admin accounts as the business truly needs.
4. Review **Activity Log** when something looks wrong: who did it, which module, and when.
5. Use **Debug Log** only to investigate a fault. It is not a business report.
6. Take backups from **Settings → Backup & Restore** before month-end, payroll posting, or a financial-year close.

**Do not** use this account for routine sales, bookings, or data entry. A Super Admin action is hard to separate from an ordinary clerk’s action in an audit.

**Training checklist**

- [ ] Sign in and identify every sidebar item that other roles do not have.
- [ ] Open Activity Log and explain one row.
- [ ] Rename nothing on the live system during class. Describe Customize Modules instead.
- [ ] Show where backups are started.
- [ ] Create a Manager on a training company and confirm Tax and Users are absent from that Manager’s sidebar.

### B2. Admin

**You are responsible for** people, settings the business uses every week, and stepping in on any module.

**You can**

- Create and edit users up to Admin. You cannot create or edit a Super Admin, and you cannot raise someone to Super Admin.
- Open **Settings**: system settings, payment gateways, module settings, backup, and roles.
- Manage promo codes and POS terminals.
- Work in every active module, including Tax.
- Delete records where the screen offers delete. Use that sparingly.

**You cannot** (Super Admin only)

- Open Activity Log, Entities, Customize Modules, or Debug Log.
- Use a module that a Super Admin has switched off.

**Day-to-day tasks**

1. Create each new hire with the lowest role that still lets them do the job.
2. Set the account to **Active**. Use **Inactive** for someone on long leave and **Suspended** when access must stop immediately.
3. Confirm payment gateways before the business takes online booking payments.
4. Create POS terminals before cashiers start selling.
5. Hand routine bookings, stock, and billing to Managers. Hand the books to the Accountant.

**Training checklist**

- [ ] Create a Staff user with only Inventory read and update.
- [ ] Sign in as that Staff user and confirm other modules are gone.
- [ ] Open Roles and describe the difference between Manager and Accountant.
- [ ] Open Payment Gateways and point out which ones are enabled. Do not save changes in class unless the trainer says so.

### B3. Manager

**You are responsible for** operations: spaces and leases, bookings, stock, utilities, staff records, and the till. You keep work moving. You do not redesign the system or file tax.

**You can** create, view, and update records in the modules you see. You can read reports.

**You cannot**

- Open Users, Settings, Companies, Modules, or Tax.
- Delete accounting, cash, receivables, payables, ledger, POS, property, inventory, or utility records.
- Change system configuration.

The Users link is hidden. If you open Settings from the sidebar, the system refuses the page.

**Day-to-day tasks**

1. Start on the module dashboard that matches today’s work (Bookings, Inventory, Utilities, or Locations).
2. Clear overdue or pending items before creating new ones.
3. Hand completed sales, bookings, and bills to the Accountant by making sure payments are recorded in the system. Do not keep a side spreadsheet.
4. Ask an Admin when a person needs access, a terminal, a promo code, or a tax question.

**Training checklist**

- [ ] Confirm Tax, Users, and Settings are not usable.
- [ ] Create a booking and record a payment.
- [ ] Receive stock and confirm the item quantity changed.
- [ ] Try to delete a financial record and confirm the action is unavailable.

### B4. Accountant

**You are responsible for** the books and tax compliance: chart of accounts, cash, customer invoices, supplier bills, journals, financial reports, VAT, withholding tax, company income tax, PAYE, education tax, and tax payments.

**You can** create and update financial and tax records. You can read the dashboard, notifications, employees, and customers.

**You cannot**

- Delete financial records.
- Open Users, Settings, Companies, Modules, or POS.
- Change inventory, property, bookings, or utilities. Those stay with Managers. You see the accounting result (invoice, bill, payment, journal), not the operational screen.

Journal entries are produced by the system from payments and postings. Treat the ledger as something you review.

**Day-to-day tasks**

1. Open **Accounting**. Check cash, receivables, payables, overdue invoices, and overdue bills.
2. Record customer receipts and supplier payments that were not captured at the till or in bookings.
3. Review the trial balance before you rely on profit and loss or the balance sheet.
4. During the tax period, open **Tax**, clear overdue items, prepare each return, and record the payment.
5. At year-end, work with an Admin on **Financial Years** (`financial-years`): close the year only after reports are agreed, then lock periods that must not change.

**Training checklist**

- [ ] Read the accounting dashboard and name the four headline figures.
- [ ] Open an invoice, record a part payment, and show the new balance.
- [ ] Open Trial Balance and say what you are checking.
- [ ] Open Tax and list VAT, WHT, CIT, PAYE, and Education Tax without changing live returns.

### B5. Staff

**You are responsible for** the tasks written on your user account. Two Staff users can see different modules. If you need a screen you do not have, ask an Admin to add that permission. Do not share someone else’s login.

**Training checklist**

- [ ] Read your own profile and confirm the role says Staff.
- [ ] List the sidebar modules you can see.
- [ ] Complete one real task in each of those modules using the matching chapter in Part C.
- [ ] Sign out at the end of the shift.

### B6. User

**You are responsible for** limited internal lookup or data entry, again only where boxes were ticked. This role is for people who should not run a department. If your job is broader than that, ask for Staff, Manager, or Accountant instead of collecting extra permissions on a User account.

### B7. Customer

**You are responsible for** your own bookings. You do not see stock, accounts, other customers, or staff tools.

**Tasks**

1. Open `customer-portal` and choose **Register** if you have no account.
2. Confirm the email using the verification link.
3. Sign in and open **Dashboard**.
4. Open **Bookings** to see your reservations. Filter by status when the list is long.
5. Open one booking to view details, pay an outstanding balance, or request a reschedule when the business allows it.
6. Use **Profile** to keep your contact details current.
7. Use **Forgot password** on the portal login if you are locked out. Reset links belong to the portal, not the staff login.

Customers only receive dashboard read and bookings read inside the staff permission model. Payment and reschedule happen through the portal screens, not through the staff Bookings module.

---

## Part C — Modules

### C1. Dashboard

**Purpose.** A home page after sign-in.

**Who.** Every staff role.

**What you see.** User count, company count, and a recent-activity table (person, action, module, time).

**How to use it.** Treat it as a starting point, then open the module for today’s work. The activity list is a short feed. Super Admins who need a full history use **Activity Log**.

**Practice.** Sign in, read the latest three activity rows aloud, and open the module named in one of them.

### C2. Users

**Purpose.** Create the people who work in the system and decide what they can do.

**Who.** Admin and Super Admin. Hidden from Managers.

**Screens.** User list, create, edit, permissions.

**Create a user**

1. Open **Users** and choose **Create**.
2. Enter username, email, and a temporary password that meets the password rule.
3. Add first name, last name, and phone.
4. Choose the role from Part B. Pick the lowest role that fits the job.
5. Leave status as **Active**.
6. Leave the permission boxes empty for Admin, Manager, and Accountant so they receive the standard set. Tick boxes only for Staff and User, or when you intentionally want a custom set.
7. Save, then ask the person to sign in and change the password from **My Profile**.

**Edit access later**

1. Open the user.
2. Change role or status if their job changed.
3. Use the permissions screen when one person needs an exception.
4. Set status to **Suspended** the same day someone leaves.

**Practice.** Create a Staff user who can read Inventory only. Sign in as them and confirm Accounting is absent.

### C3. Entities

**Purpose.** Hold the companies this installation manages.

**Who.** Super Admin only. The sidebar label follows the Entities module name.

**How to use it.** Create a company before you load that company’s accounts, locations, and stock. Staff then work inside the company context the business has set up. Admins do not manage this list.

**Practice.** Open the list and identify which company is the training company. Do not create a live company during class.

### C4. Activity Log

**Purpose.** A full history of who changed what.

**Who.** Super Admin.

**How to use it.** Filter or scan when a record disagrees with what someone remembers. Note the user, the action, the module, and the time before you change data to “fix” it.

**Practice.** Find today’s sign-in or a record you just saved and read the log line back.

### C5. Accounting

**Purpose.** The financial heart of the system: what the business owns, is owed, and owes.

**Who.** Accountant (daily). Admin and Super Admin (oversight). Managers do not use Tax, but they do feed this module by recording bookings, rent, utilities, and purchases.

**Tabs**

| Tab | Use it to |
| --- | --- |
| Dashboard | See cash, receivables, payables, profit and loss, recent payments, recent ledger activity, overdue invoices, and overdue bills |
| Chart of Accounts | Maintain the account list used by every posting |
| Transactions | Review and maintain financial transactions |
| Journal Entries | Review ledger postings |
| Reports | Trial balance, general ledger, profit and loss, balance sheet, cash flow, statement of changes in equity |
| Cash Accounts | Bank and cash balances |
| Receivables | Customers, invoices, receipts, aging |
| Payables | Vendors, bills, payments, aging |

#### Chart of Accounts

1. Open **Chart of Accounts**.
2. Add an account only when the business does not already have one for that purpose. Duplicate accounts make reports hard to read.
3. Set the account type and currency to match how the business reports.

#### Cash accounts

1. Open **Cash Accounts**.
2. Create one account per real bank account or cash box.
3. Record movements against the cash account that actually received or paid the money.
4. Compare the on-screen balance with the bank statement before month-end. Admin and Super Admin can delete a cash account; everyone else should correct with an adjusting entry instead.

#### Receivables (money customers owe you)

1. Open **Receivables**. The page opens on **Customers**.
2. Create the customer if they are not already there. You can filter customers by source: invoice, booking, or tenant.
3. Open **Customer Types** only when you need a new category.
4. Create an invoice from the invoices list. Check currency, dates, and lines before you save.
5. Send the invoice by email from the invoice screen, or download the PDF.
6. When money arrives, open the invoice and **record a payment**. A part payment leaves a balance. A full payment marks it paid.
7. Use **Aging** to see who is late. Export the aging report when you need a copy for a meeting.
8. Open a customer’s history before you chase them, so you quote the right invoices.

#### Payables (money you owe suppliers)

1. Open **Payables**, then vendors.
2. Create the vendor before the first bill.
3. Enter the bill from the supplier’s document. Match the amounts to the purchase order or goods receipt when those exist.
4. Record a payment against one bill, or use **batch payment** when you pay several bills together.
5. Use vendor history and **Aging** the same way as receivables.
6. Download or email a bill when someone outside the system needs a copy.

#### Reports

Open **Reports** and run them in this order when you are checking a period:

1. **Trial Balance** — debits must equal credits.
2. **General Ledger** — one account, when a balance looks wrong.
3. **Profit and Loss** — performance for the period.
4. **Balance Sheet** — position on a date.
5. **Cash Flow** — where cash came from and went.
6. **Statement of Changes in Equity** — how equity moved, including profit.

#### Financial years and currencies

These screens sit beside accounting rather than in the tab row.

- **Currencies** (`currencies`): add a currency and maintain rates. Invoices, bills, vendors, and accounts can then use it.
- **Financial years** (`financial-years`): create the year, review its periods, **lock** a period after it is agreed, and **close** the year when reporting is finished. Unlock a period only to correct an agreed error, then lock it again.

**Practice.** Create a training customer, raise a small invoice, record a part payment, and find that customer on the aging report.

**Common mistakes**

- Paying a bill from the wrong cash account.
- Editing an invoice after it has been sent instead of recording the real payment or a correction.
- Closing a financial year before the trial balance is agreed.

### C6. Staff and payroll

**Purpose.** Employee records and pay runs.

**Who.** Managers for employee upkeep and preparing payroll. Accountants for reviewing posted payroll and PAYE. Admins can do both.

**Tabs.** Employees, Payroll.

**Add an employee**

1. Open **Staff Management → Employees**.
2. Create the employee with the legal name, contact details, and pay information the payroll screen expects.
3. Keep the employee record up to date when pay or status changes. The employee record is not the same thing as a login. Create a user in **Users** only if this person must sign in.

**Run payroll**

1. Open **Payroll**.
2. Choose the period (year and month).
3. Choose **Process Payroll** and review the run: period, people, and total.
4. Open the run and check each payslip.
5. Post the run when the figures are approved. Posting sends the cost into accounting. Do this once per period after review.
6. Status moves from processed to **posted**. A posted run is the one finance should rely on.

**Practice.** Open an existing posted run (or a training run) and open one payslip. Say which figure is gross pay and which is the amount going to accounting.

### C7. Bookings

**Purpose.** Reserve spaces and resources, take payment, and keep the calendar honest.

**Who.** Managers and anyone granted the Bookings permission. Customers use the portal in C14 instead of this screen. Accountants see the money through receivables and the ledger, not this module.

**Main actions on the booking list**

- **Reports** — utilisation and booking analysis.
- **Calendar View** — see clashes by day.
- **New Booking** — create a reservation as staff.
- **Reconcile Finances** — align booking payments with accounting when something was recorded in the wrong place.
- **Sync Gateway Payments** — re-check pending online payments and repair facility names. Use this when a customer paid online and the booking still looks unpaid.
- **Manage Add-ons** — extras sold with a booking.

**Create a booking as staff**

1. Choose **New Booking**.
2. Pick the location and the space.
3. Pick a date and a free time slot. If the slot is missing, the space is closed, blocked, or already taken.
4. Add any extras.
5. Attach the customer. You can create a customer inline if they are new.
6. Save, then open the booking and record a payment if they pay now.
7. Send the invoice from the booking when they need a document.

**After the booking exists**

- Change status as the visit progresses.
- Reschedule from the booking. Check the quote for the new slot before you confirm.
- Cancel when the customer will not come. Record any refund according to house rules.
- Add or remove resources on the booking when the setup changes.
- For rentals that leave the building, use checkout and return so the item is not booked twice.

**Set up a bookable resource** (before the first customer)

1. Create the facility or space (see Locations).
2. Open resource availability and set each weekday’s open hours and breaks.
3. Add block-out dates for maintenance and holidays.
4. Add pricing rules.
5. Add optional add-ons.
6. Test by opening the calendar and the booking wizard as a customer would.

**Booking wizard** (the guided path)

1. Select the resource.
2. Select date and time.
3. Choose add-ons.
4. Enter customer details.
5. Review and pay.
6. Confirm. Online payments can post into accounting automatically.

**Practice.** Block out tomorrow morning on a training space, then try to book that slot and confirm the system refuses it.

### C8. Locations (property and leases)

**Purpose.** Buildings, the spaces inside them, the people who occupy them, and the rent they owe.

**Who.** Managers, plus Admins. The sidebar may say Locations or Properties. Both open the same area.

**Tabs.** Locations, Spaces, Leases, Tenants, Rent Invoices.

**Set up a site**

1. **Add Location** — the building or site.
2. **Spaces** — rooms, desks, halls, or units inside that location. Mark a space bookable when short stays are sold as bookings.
3. **Tenants** — the occupier. Keep contact details current because invoices and notices use them.
4. **Leases** — link a tenant to a space, with start and end dates and the rent terms.
5. **Rent Invoices** — generate an invoice for one lease and period, or use auto-generate for the due cycle. Record a payment when rent arrives.

Space bookings and the space calendar are the short-stay view of the same spaces. Use a lease for a sitting tenant and a booking for an hourly or daily hire.

**Practice.** Create a training location, one space, one tenant, and one lease. Generate a single rent invoice and stop before recording a real payment.

### C9. Utilities

**Purpose.** Meters, readings, tenant bills, and the supplier bills the business pays.

**Who.** Managers who walk the meters and bill occupiers. Accountants pick up the cost through payables and the ledger.

**Tabs.** Dashboard, Meters, Readings, Bills, Providers, Tariffs, Payments, Reports, Vendor Bills, Alerts.

**Monthly cycle**

1. **Providers** — the utility company, once.
2. **Tariffs** — the rate used to turn units into money. Update the tariff when the provider changes price, before you bill.
3. **Meters** — one meter per measured point, tied to the right space.
4. **Readings** — enter the new reading. The system bills the difference from the last reading, so a wrong reading becomes a wrong bill.
5. **Bills** — produce the occupier bill from readings and the tariff.
6. **Payments** — record what the occupier paid.
7. **Vendor Bills** — enter what the provider charged the business. This is not the same document as the occupier’s bill.
8. **Alerts** — work the list (overdue, unusual use, missing readings) before you close the month.
9. **Reports** — consumption and cost when someone asks where the money went.

**Practice.** Enter a training meter reading that is higher than the last one, open the draft bill, and check that the units equal the difference between the two readings.

### C10. Inventory

**Purpose.** What you stock, where it sits, what you bought, and what it is worth.

**Who.** Managers and inventory Staff. Accountants use the valuation and the bills, not the warehouse screens. POS sells items that are marked sellable.

**Tabs.** Dashboard, Items, Movements, Adjustments, Stock Takes, Purchasing, Suppliers, Assets, Reports.

**Dashboard.** Total items, stock value, low-stock list, and out-of-stock list. Start here each morning.

**Items**

1. Open **Items** and create the product.
2. Set a selling price if it is sold at the till.
3. Set a reorder level so the dashboard can warn you.
4. On the item, open wholesale pricing when a quantity discount applies. POS switches to the wholesale price once the cart quantity reaches the minimum.

**Movements**

- **Receive** — goods arriving outside a formal goods receipt, or to correct an intake.
- **Issue** — goods leaving for use or sale that was not rung through POS.
- **Transfer** — move quantity from one stock location to another.

Always pick the location. A transfer that names the same place twice does not move stock.

**Purchasing**

1. Create the **supplier**.
2. Raise a **purchase order**.
3. When the delivery arrives, create a **goods receipt** against that order.
4. The supplier’s invoice is then a bill in Payables (or a utility vendor bill, if it is a utility).

**Adjustments and stock takes**

1. Print or open a **stock take** and count the shelves.
2. Enter counted quantities.
3. Post an **adjustment** for the difference, with a reason. Adjustments change both quantity and value, so a casual edit is visible in the movement report.

**Assets.** Register equipment the business keeps, as distinct from items you sell.

**Reports**

- Stock on hand
- Stock movements
- Stock valuation
- Reorder level
- Fast and slow movers
- Purchase analysis

**Practice.** Receive 5 units of a training item into a location, then transfer 2 to a second location, and confirm both balances.

### C11. Tax

**Purpose.** Nigerian tax records kept inside the system: VAT, withholding tax (WHT), company income tax (CIT), PAYE, education tax, payments, and reports.

**Who.** Accountants, Admins, and Super Admins. Managers have no Tax access. This manual teaches the screens. Filing rules and rates come from your finance lead or tax adviser, then they are stored in **Configuration**.

**Tabs.** Dashboard, VAT, WHT, CIT, PAYE, Education Tax, Payments, Reports, Configuration.

**Dashboard.** Compliance score, total tax payable, upcoming deadlines, and overdue items. Clear overdue items first.

**Work a tax type**

1. Open **Configuration** and confirm rates and settings before you prepare a return. Admins maintain this screen.
2. Open the tax (VAT, WHT, CIT, PAYE, or Education Tax).
3. Create the return or calculation for the period.
4. Open it and agree the figures to the accounting reports for the same dates.
5. Record the payment under **Payments** when the money is sent.
6. Use **Reports** for the pack you keep with the filing.

PAYE should be checked against the posted payroll for the same month. Do not file from a payroll run that is still unposted.

**Practice.** Open Configuration and read the current VAT setting aloud. Open the VAT list and identify the latest return’s period. Do not create a return in class unless the trainer has a training period ready.

### C12. Point of Sale

**Purpose.** Sell items in person and print a receipt.

**Who.** Cashiers with POS access, usually Staff or Manager. Admins set up terminals. Accountants are not given POS; they see the sales through accounting.

**Sell**

1. Open **POS**.
2. Choose the terminal if you have more than one.
3. Search or tap items to add them to the cart.
4. If an item shows a wholesale badge, the price changes when the quantity reaches the minimum on that item.
5. Take payment with the method the customer uses.
6. Complete the sale and open the receipt if they want a copy.

**Terminals.** Admins open **Manage Terminals**, create one terminal per till, and tell each cashier which name to pick.

**Reports.** Use POS reports for till totals. Use Accounting reports for the official profit and loss.

**Practice.** Ring a one-item training sale, open the receipt, and find the same sale in the accounting payments list.

### C13. Settings and administration

**Purpose.** How the system behaves for everyone.

**Who.** Admin and Super Admin. Managers are refused.

| Screen | Who | What you do |
| --- | --- | --- |
| System Settings | Admin, Super Admin | Company profile, email, SMS, preferences. Test email only as Admin or Super Admin |
| Payment Gateways | Admin, Super Admin | Turn gateways on and enter the business’s credentials. Flutterwave subaccounts and split rules live here when online booking payments are split |
| Module Settings | People who can update settings | Control module visibility |
| Backup & Restore | People who can update settings | Back up before closing a year, posting payroll, or importing a large file. Restore only when you mean to replace current data |
| Customize Modules | Super Admin | Change sidebar names, icons, order, and visibility. Reset a label if a rename caused confusion |
| Roles & Permissions | Admin, Super Admin | Review what each role contains. Change a role when the whole job changes; change one user when only one person is an exception |
| Promo Codes | Admin, Super Admin | Create a code, set the discount, switch it off when a campaign ends. The booking wizard checks the code at payment |
| Debug Log | Super Admin | Read technical errors. Do not treat it as a user report |

**Practice.** Open Settings and, without saving, point to the card you would use to turn a payment gateway off.

### C14. Customer portal

**Purpose.** Let a customer register, see their bookings, pay, and reschedule without a staff login.

**Who.** Customers. Staff still create walk-in bookings inside **Bookings**.

**Staff should tell customers**

1. Go to `customer-portal`.
2. Register and open the verification email.
3. Sign in, pay outstanding bookings, and use reschedule rather than calling to ask staff to guess a new time.
4. Use the portal’s own forgot-password link.

**Staff follow-up.** A new booking notifies Admins, Super Admins, and Managers. If an online payment stays pending, a Manager or Admin runs **Sync Gateway Payments** on the booking list.

### C15. Notifications

**Purpose.** The full list behind the bell.

**Who.** All staff. Accountants have read access.

**How to use it.** Open **Notifications**, read anything tied to a booking or payment, and mark items read so the badge stays meaningful. Unread badges are not a substitute for the accounting overdue lists.

### C16. Supporting tools

These are real screens. They are not all in the sidebar.

| Tool | Address | Use |
| --- | --- | --- |
| Import / Export | `import-export` | Download a template, fill it, and import. Export when you need a file. Always back up first |
| Report builder | `report-builder` | Build a saved report, run it, and export it when the standard reports are not enough |
| Currencies | `currencies` | Codes and rates |
| Financial years | `financial-years` | Open, lock, and close periods |
| Search | Search box, or `search` | Find a record when you do not know which module holds it |

---

## Part D — Processes that cross modules

Use these as the final exercise in a course. Each one is done by more than one role.

### D1. Open a new operation

| Step | Role | Where |
| --- | --- | --- |
| 1. Create the company | Super Admin | Entities |
| 2. Set the company profile, email, and backup | Super Admin or Admin | Settings |
| 3. Create Admin, Manager, and Accountant | Super Admin or Admin | Users |
| 4. Add currencies and the financial year | Accountant, with Admin | Currencies, Financial years |
| 5. Build the chart of accounts and cash accounts | Accountant | Accounting |
| 6. Add locations, spaces, and tariffs | Manager | Locations, Utilities |
| 7. Add suppliers, items, and opening stock | Manager | Inventory |
| 8. Add facilities, hours, prices, and add-ons | Manager | Bookings / resource management |
| 9. Create a POS terminal and a test promo code | Admin | POS, Promo Codes |
| 10. Ring one test sale and one test booking | Manager | POS, Bookings |
| 11. Confirm both appear in accounting | Accountant | Accounting dashboard and reports |

### D2. A paid booking

1. Customer registers on the portal, or staff create the customer.
2. Customer or staff completes the booking wizard or **New Booking**.
3. Payment is taken online or recorded on the booking.
4. If the online payment stays pending, staff run **Sync Gateway Payments**.
5. Accountant sees the receipt under recent payments and in receivables.
6. Manager marks the booking complete, or checks the rental back in.

### D3. A customer invoice

1. Accountant creates or confirms the customer.
2. Accountant raises the invoice and sends the PDF.
3. When money arrives, Accountant records the payment against that invoice and the right cash account.
4. Aging drops the balance.
5. The cash account and the general ledger agree.

### D4. Buying stock

1. Manager creates the supplier and the purchase order.
2. Manager records the goods receipt when the delivery is counted.
3. Accountant enters the supplier bill in Payables and pays it from the correct cash account.
4. Inventory valuation and the trial balance are reviewed at month-end.

### D5. Payroll and PAYE

1. Manager keeps employee records current.
2. Manager or Accountant processes the payroll for the month.
3. A reviewer opens payslips.
4. An authorised person posts the run.
5. Accountant prepares PAYE for that same month and records the tax payment.

### D6. Month-end

1. Manager finishes stock counts, meter readings, rent generation, and open bookings.
2. Accountant clears overdue invoices and bills, then runs the trial balance.
3. Accountant reviews profit and loss, balance sheet, and cash flow.
4. Accountant prepares the tax returns that fall in the month.
5. Admin takes a backup.
6. Accountant locks the period after the reports are accepted.

---

## Part E — Class plans

Each session is about two hours. Use a training company. Every student signs in as the role they are learning.

### Session 1 — Everyone

Part A, then the student’s own role in Part B. Exercise: profile, search, notifications, and a refused page.

### Session 2 — Admin and Super Admin

Users, Settings, promo codes, POS terminals. Super Admins add Activity Log, Entities, Customize Modules, and backup. Exercise: the Staff-user checklist in B2.

### Session 3 — Managers, operations

Locations, then Bookings, including one blocked date. Exercise: D2 without the accounting check.

### Session 4 — Managers, stock and utilities

Inventory receive, transfer, and reorder report. Then one meter reading and a bill. Exercise: D4 steps 1 and 2.

### Session 5 — Accountants

Accounting dashboard, invoice, part payment, bill, trial balance. Exercise: D3.

### Session 6 — Accountants, tax and close

Tax dashboard and configuration, one return on a training period, financial-year lock. Exercise: D6 on paper if the training company has no closed period yet.

### Session 7 — Cashiers and portal

POS sale and receipt. Then a customer registers on the portal and pays a booking. Exercise: D2 from the customer side.

### Session 8 — Combined

Run D1 or D6 with one student in each role. The goal is to see a single event appear in operations and in the books.

---

## Glossary

| Term | Meaning |
| --- | --- |
| Role | The job type on the user: Super Admin, Admin, Manager, Accountant, Staff, User, or Customer |
| Permission | A single allowance, such as “create invoices” or “read inventory” |
| Module | A sidebar area, such as Accounting or Inventory. Super Admin can rename it |
| Chart of accounts | The list of financial accounts |
| Receivables | Money customers still owe |
| Payables | Money the business still owes suppliers |
| Journal / ledger | The accounting record created when money moves |
| Cash account | A bank account or cash box inside the system |
| Aging | A report of unpaid invoices or bills grouped by how late they are |
| Goods receipt | The record that ordered stock has arrived |
| Stock take | A physical count compared with the system |
| Tariff | The price per unit used to bill a meter |
| Vendor bill | The supplier’s invoice, including a utility provider’s invoice |
| Terminal | One point-of-sale till |
| Financial year | The reporting year, divided into periods that can be locked |
| Posted payroll | A pay run that has been sent to the accounts |
| Portal | The customer site at `customer-portal` |
