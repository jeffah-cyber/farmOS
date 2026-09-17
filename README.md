# Phoenix OS

**Agricultural Operations & Intelligence Platform**

Phoenix OS is an agricultural enterprise management platform being developed for plantation, processing, and integrated agribusiness operations.

The project is designed to provide a unified operational system for managing agricultural estates from land acquisition and plantation establishment through field operations, harvesting, processing, inventory, finance, and enterprise intelligence.

> **Development status:** Phoenix OS v1 — Active Development

---

## Current Capabilities

Phoenix OS currently includes:

- Estate Management
- Estate Operational Intelligence
- Plantation Block Management
- Plantation Block Profiles
- Agricultural Operations
- Estate and Plantation Workspaces
- Operational dashboards
- Phoenix-specific navigation and interface

Current operational data is built on real farm records rather than static dashboard data.

---

## Current Architecture

```text
Phoenix OS
│
├── Phoenix Core
│   └── Branding, navigation and shared platform services
│
├── Phoenix Estate
│   └── Estate management and operational intelligence
│
├── Phoenix Plantation
│   └── Plantation blocks and block intelligence
│
└── Phoenix Operations
    └── Agricultural operation records
```

Phoenix OS extends the farmOS and Drupal architecture rather than recreating mature agricultural data-management capabilities unnecessarily.

Core farmOS concepts such as assets, logs, locations, geometry, people, and API infrastructure can therefore be extended into Phoenix-specific enterprise workflows.

---

## Development Roadmap

| Milestone | Status |
|---|---|
| Estate Management v2 | Complete |
| Estate Operational Intelligence | Complete |
| Plantation Management v2 | Complete |
| Tasks & Scheduling | Next |
| Workforce & Equipment | Planned |
| Inventory & Procurement | Planned |
| Harvest Management | Planned |
| Mill & Manufacturing | Planned |
| Finance | Planned |
| Livestock & Greenhouse | Planned |
| Phoenix Root | Planned |
| Mobile, GIS & IoT | Planned |
| AI & Decision Support | Planned |
| Integration & Release | Planned |

---

## Development Philosophy

Phoenix OS follows a business-first development process:

```text
Business Problem
        ↓
Business Analysis
        ↓
Domain Model
        ↓
Software Design
        ↓
Implementation
        ↓
Testing
        ↓
Documentation
        ↓
Git Commit
        ↓
GitHub
```

The objective is to build agricultural software around real operational requirements rather than adding features without a defined business purpose.

---

## Technology

Phoenix OS currently uses:

- farmOS 4.x
- Drupal
- PHP
- PostgreSQL
- Docker
- Drush
- Twig
- CSS
- Git and GitHub

Development is currently performed using Docker in a WSL2-based development environment.

---

## Open-Source Foundation

Phoenix OS is built on and extends the open-source **farmOS** project.

farmOS provides the underlying agricultural record-management architecture on which parts of Phoenix OS are being developed.

farmOS is a registered trademark of its respective owner. Phoenix OS is an independent project and is not presented as the official farmOS distribution.

Upstream project:

- https://farmOS.org
- https://github.com/farmOS/farmOS

Phoenix OS retains the applicable open-source copyright and licensing notices from the upstream project.

See:

- `LICENSE.txt`
- `COPYRIGHT.txt`

for licensing and copyright information.

---

## Project Vision

Phoenix OS is intended to evolve into an integrated agricultural enterprise platform connecting:

```text
Land
  ↓
Plantations
  ↓
Operations
  ↓
Workforce
  ↓
Inputs
  ↓
Harvest
  ↓
Processing
  ↓
Inventory
  ↓
Finance
  ↓
Management Intelligence
```

Future capabilities are planned to include GIS, mobile field operations, IoT integration, analytics, and AI-assisted agricultural decision support.

---

## Repository Structure

Phoenix-specific modules are maintained under:

```text
modules/phoenix/
```

Phoenix technical documentation is maintained under:

```text
docs/phoenix/
```

Active development is currently taking place on the:

```text
phoenix-os
```

branch.

---

## License and Attribution

This repository is derived from and extends GPL-licensed farmOS software.

Refer to `LICENSE.txt` and `COPYRIGHT.txt` for the applicable licensing terms and upstream copyright notices.
