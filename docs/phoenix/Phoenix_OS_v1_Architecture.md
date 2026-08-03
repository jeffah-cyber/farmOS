# Phoenix OS v1 Architecture

Version: 0.1  
Status: Draft  
Last updated: 2026-08-03

## 1. Purpose

This document defines the target architecture for Phoenix OS version 1.

Phoenix OS is an agricultural enterprise operating system built on farmOS and Drupal. Version 1 will focus on managing commercial plantation operations from estate registration through field production and harvest delivery.

The architecture should support:

- one or many estates
- multiple crops
- plantation blocks
- field operations
- workers and machinery
- inventory
- harvesting
- reporting
- future mobile, GIS and AI integrations

## 2. Architectural Principles

### 2.1 Extend farmOS

Phoenix OS should reuse farmOS entities, logs, quantities, locations and mapping capabilities where practical.

### 2.2 Avoid farmOS core modifications

Phoenix functionality should be delivered through custom modules and configuration.

### 2.3 Clear module responsibilities

Each module should represent one business domain and should not become a general dumping ground.

### 2.4 Configuration as code

Fields, vocabularies, displays, permissions and views required by Phoenix OS should be stored in Git.

### 2.5 Dependency injection

Services should receive their dependencies through Drupal’s service container.

### 2.6 Separate responsibilities

- Controllers handle web requests.
- Services apply business logic.
- Repositories retrieve data where specialised queries are needed.
- Twig templates handle presentation.
- CSS libraries handle styling.
- Installation and update hooks manage deployment changes.

### 2.7 Mobile and low-bandwidth readiness

Operational workflows should remain usable on mobile devices and unreliable connections.

### 2.8 Security by design

Permissions, audit trails, input validation and least privilege should be considered in every module.

## 3. Platform Layers

Phoenix OS will use the following layers:

1. User interface
2. Controllers and routes
3. Business services
4. Drupal and farmOS entity APIs
5. PostgreSQL database
6. Docker and Linux infrastructure

Future clients may include:

- mobile applications
- external APIs
- IoT devices
- GIS platforms
- AI and analytics services

## 4. Proposed Module Structure

```text
modules/phoenix/
├── core/
├── estate/
├── plantation/
├── operations/
├── nursery/
├── harvest/
├── inventory/
├── machinery/
├── mill/
├── dashboard/
└── ai/
