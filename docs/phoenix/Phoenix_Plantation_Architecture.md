# Phoenix Plantation Architecture

## Version

0.1

## 1. Purpose

Phoenix Plantation is the oil palm plantation management component of Phoenix OS.

It will extend farmOS to manage:

- estates
- plantation blocks
- nurseries
- planting
- field operations
- harvesting
- labour
- machinery
- inventory
- pest and disease incidents
- operational reports

## 2. Architectural Principle

Phoenix Plantation will extend existing farmOS entities wherever practical.

It will not duplicate functionality already available in farmOS.

Phoenix OS will mainly use:

- Assets for physical and operational resources
- Logs for activities and events
- Quantities for measurable values
- Taxonomy terms for classifications
- People and users for staff
- Locations and geometry for mapping
- Custom fields for Phoenix-specific information

## 3. Plantation Domain Model

Phoenix Palm Industries
    |
    +-- Estate
          |
          +-- Plantation Block
          |     |
          |     +-- Palm Plants
          |     +-- Harvest Logs
          |     +-- Fertiliser Logs
          |     +-- Spraying Logs
          |     +-- Pruning Logs
          |     +-- Pest and Disease Observations
          |     +-- Labour Records
          |
          +-- Nursery
          |     |
          |     +-- Seed Batches
          |     +-- Seedlings
          |     +-- Nursery Operations
          |
          +-- Structures
          |     |
          |     +-- Warehouse
          |     +-- Workshop
          |     +-- Office
          |     +-- Fuel Depot
          |
          +-- Equipment
          |     |
          |     +-- Tractors
          |     +-- Vehicles
          |     +-- Harvesting Tools
          |     +-- Sprayers
          |
          +-- Workers
          +-- Contractors
          +-- Inventory

## 4. Core Asset Types

### Estate

Implementation:

- farmOS Land asset
- land type: Estate

Proposed fields:

- estate code
- region
- district
- total area
- established date
- operational status
- estate manager
- GPS boundary

### Plantation Block

Implementation:

- farmOS Land asset
- land type: Plantation Block

Proposed fields:

- block code
- parent estate
- area
- planting date
- palm variety
- planting density
- number of palms
- productive palms
- immature palms
- soil type
- terrain
- block status

### Nursery

Implementation:

- farmOS Land asset or Structure asset

Proposed fields:

- nursery code
- parent estate
- nursery capacity
- opening date
- supervisor
- nursery status

### Palm Plant

Implementation:

- farmOS Plant asset

Individual palm-level tracking will be optional.

Phoenix OS should support both:

- individual palm records
- grouped palm populations by plantation block

### Machinery

Implementation:

- farmOS Equipment asset

Proposed fields:

- asset number
- equipment category
- assigned estate
- operating condition
- purchase date
- next service date
- hours operated
- fuel usage

### Warehouse

Implementation:

- farmOS Structure asset

Proposed fields:

- warehouse code
- parent estate
- storage capacity
- storekeeper
- operational status

## 5. Core Logs

### Planting Log

Records palms planted within a plantation block.

Data:

- estate
- plantation block
- date
- palm variety
- number planted
- planting material source
- spacing
- workers
- supervisor

### Fertiliser Application Log

Records fertiliser applied to a plantation block.

Data:

- plantation block
- fertiliser type
- quantity
- application rate
- date
- workers
- weather conditions
- cost

### Harvest Log

Records Fresh Fruit Bunch production.

Data:

- plantation block
- harvest date
- harvesting team
- bunch count
- total weight
- rejected bunches
- loose fruit weight
- collection time
- delivery destination

### Maintenance Log

Records maintenance work.

Examples:

- road maintenance
- drain clearing
- pruning
- circle weeding
- machinery servicing

### Observation Log

Records:

- pest sightings
- disease symptoms
- flooding
- fire risks
- nutrient deficiencies
- damaged palms

## 6. Relationships

One estate can contain many plantation blocks.

One estate can contain many nurseries.

One estate can contain many structures.

One estate can have many workers and machines.

One plantation block belongs to one estate.

One plantation block can contain many palms.

One plantation block can have many activity logs.

One harvest log belongs to one plantation block.

One machinery asset can participate in many activity logs.

One worker can participate in many activity logs.

## 7. User Roles

### Executive

Can view:

- total area
- yield
- revenue
- operating costs
- risks
- strategic reports

### Estate Manager

Can:

- manage estate operations
- approve work plans
- review production
- assign supervisors
- review machinery and stock

### Field Supervisor

Can:

- create field activity records
- assign workers
- record completed work
- report incidents

### Harvest Supervisor

Can:

- record harvests
- manage harvest teams
- record rejected fruit
- confirm deliveries

### Storekeeper

Can:

- receive stock
- issue materials
- monitor balances
- report shortages

### Machinery Supervisor

Can:

- assign equipment
- record fuel use
- report breakdowns
- schedule maintenance

### Field Worker

Can:

- view assigned work
- confirm completion
- submit photographs
- report field observations

### System Administrator

Can:

- manage users
- manage permissions
- configure the platform
- maintain integrations
- review audit logs

## 8. Executive Dashboard

The first dashboard should show:

- total estate area
- total planted area
- number of estates
- number of plantation blocks
- expected harvest today
- actual harvest today
- harvest variance
- workers present
- unavailable machinery
- fertiliser stock alerts
- pest and disease alerts
- overdue activities
- rainfall
- monthly operating cost

## 9. Development Phases

### Version 0.1

- Phoenix Plantation information page
- Estate land classification
- Plantation Block land classification
- basic documentation

### Version 0.2

- estate-specific fields
- plantation block fields
- estate and block relationships
- estate list and filters

### Version 0.3

- nursery management
- planting records
- fertiliser application records
- field operation records

### Version 0.4

- harvest recording
- Fresh Fruit Bunch quantities
- harvest team assignments
- delivery tracking

### Version 0.5

- machinery
- maintenance
- labour
- inventory

### Version 1.0

- executive dashboard
- reports
- GIS views
- mobile-friendly workflows
- user roles and permissions
- audit history
- production-ready deployment

## 10. Design Decisions

### Decision 1

Estates and plantation blocks will use farmOS Land assets.

### Decision 2

Operational work will use farmOS Logs.

### Decision 3

Weights, areas, costs and other measurements will use farmOS Quantities where appropriate.

### Decision 4

Phoenix OS will avoid direct changes to farmOS core.

### Decision 5

Phoenix Plantation will remain the main oil palm business module.

Supporting modules may provide optional configuration and specialised features.

## 11. Immediate Next Feature

Add Plantation Block as a new Land classification.

The next hierarchy will be:

Estate
    |
    +-- Plantation Block
