# Phoenix OS Software Requirements Specification

## Version

0.1

## 1. Purpose

Phoenix OS is an agricultural management platform built on farmOS for managing oil palm plantations and, later, livestock, greenhouse production, processing, warehouses, finance, logistics and artificial intelligence.

## 2. Initial Scope

The first version will focus only on oil palm plantation management.

The initial system will support:

- estates
- plantation blocks
- nurseries
- palm varieties
- field operations
- fertiliser applications
- harvesting
- pest and disease observations
- machinery
- workers
- reports

## 3. Main Users

### System Administrator

Manages users, permissions, configuration and system security.

### Estate Manager

Reviews overall estate performance, approves work and monitors production.

### Field Supervisor

Assigns work, records field activities and monitors plantation blocks.

### Storekeeper

Records fertiliser, chemicals, tools and other inventory.

### Harvest Supervisor

Records harvested Fresh Fruit Bunches and harvesting teams.

### Field Worker

Records assigned activities using a simple mobile-friendly interface.

### Executive

Views performance indicators, costs, yield and operational risks.

## 4. Core Data

### Estate

Represents a complete plantation or operating site.

Important information:

- estate name
- location
- total area
- manager
- GPS boundary
- operational status

### Plantation Block

Represents a defined section of an estate.

Important information:

- block name
- estate
- area in hectares
- GPS boundary
- planting year
- palm variety
- number of palms
- soil type
- block status

### Nursery

Represents seedlings being prepared for field planting.

Important information:

- nursery name
- seed source
- palm variety
- sowing date
- number of seedlings
- expected transplanting date
- mortality rate

### Field Operation

Represents work performed within a plantation block.

Examples:

- weeding
- pruning
- fertiliser application
- spraying
- harvesting
- road maintenance
- drainage maintenance

Important information:

- operation type
- date
- plantation block
- workers
- supervisor
- materials used
- machinery used
- cost
- notes

### Harvest

Represents Fresh Fruit Bunch production from a plantation block.

Important information:

- harvest date
- plantation block
- harvesting team
- number of bunches
- total weight
- rejected bunches
- collection time
- delivery destination

### Machinery

Represents tractors, vehicles and plantation equipment.

Important information:

- equipment name
- registration or asset number
- operating status
- assigned estate
- service date
- fuel usage
- maintenance history

## 5. Initial Dashboard Requirements

The dashboard should eventually show:

1. total estate area
2. total planted area
3. total number of palms
4. blocks scheduled for harvesting
5. expected Fresh Fruit Bunch production
6. actual harvest today
7. worker attendance
8. machinery unavailable
9. fertiliser and chemical stock alerts
10. pest and disease alerts

## 6. Functional Requirements

The system must allow authorised users to:

- create and update estates
- create and map plantation blocks
- register nurseries
- record field operations
- record fertiliser applications
- record harvest quantities
- assign workers and machinery
- attach photographs and documents
- search and filter operational records
- generate reports by date, estate and plantation block

## 7. Non-Functional Requirements

Phoenix OS should be:

- secure
- mobile friendly
- usable on low-bandwidth connections
- easy for non-technical field workers
- capable of supporting multiple estates
- auditable
- backed up regularly
- extendable without modifying farmOS core unnecessarily

## 8. Development Principles

- Build Phoenix OS through custom modules.
- Avoid unnecessary changes to farmOS core.
- Make small changes and test them.
- Commit each meaningful change to Git.
- Document important technical decisions.
- Design for future mobile and API integrations.

## 9. First Development Milestone

The first milestone is to create a custom Drupal module named:

Phoenix Plantation

The module will initially:

- appear in the module administration page
- provide a Phoenix Plantation information page
- create the foundation for future plantation features
