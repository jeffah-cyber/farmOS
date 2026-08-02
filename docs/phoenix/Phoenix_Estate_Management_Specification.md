# Phoenix Estate Management Specification

Version: 0.1  
Status: Draft

## 1. Purpose

The Estate Management feature provides a central record for each agricultural estate operated by Phoenix Holdings.

An estate acts as the parent operational unit for:

- plantation blocks
- nurseries
- buildings
- warehouses
- machinery
- employees
- field activities
- inventory
- harvest records
- financial reporting

## 2. farmOS Implementation

An estate will be represented as:

- Entity type: Asset
- Asset bundle: Land
- Land type: Estate

This allows Phoenix OS to reuse farmOS location, mapping, ownership, parent-child relationships, revision history and API support.

## 3. Estate Fields

### 3.1 Identity

| Field | Machine name | Type | Required |
|---|---|---|---|
| Estate Name | name | Existing farmOS field | Yes |
| Phoenix Code | field_phoenix_code | Plain text | Yes |
| Operational Status | field_operational_status | List | Yes |
| Date Established | field_date_established | Date | No |

Example Phoenix Code:

`EST-BEN-001`

### 3.2 Administrative Location

| Field | Machine name | Type | Required |
|---|---|---|---|
| Country | field_country | List or taxonomy | Yes |
| Region | field_region | Taxonomy reference | Yes |
| District | field_district | Taxonomy reference | Yes |
| Community | field_community | Plain text | No |
| Office Address | field_office_address | Address or long text | No |

### 3.3 Land Information

| Field | Machine name | Type | Required |
|---|---|---|---|
| Total Area | field_total_area | Measurement | Yes |
| Productive Area | field_productive_area | Measurement | No |
| Immature Area | field_immature_area | Measurement | No |
| Conservation Area | field_conservation_area | Measurement | No |
| GPS Boundary | geometry | Existing farmOS field | Recommended |

Preferred area unit: hectares.

Acres may be accepted at data entry and converted where necessary.

### 3.4 Management

| Field | Machine name | Type | Required |
|---|---|---|---|
| Estate Manager | field_estate_manager | User reference | No |
| Assistant Manager | field_assistant_manager | User reference | No |
| Contact Number | field_contact_number | Telephone | No |
| Contact Email | field_contact_email | Email | No |

### 3.5 Production Profile

| Field | Machine name | Type | Required |
|---|---|---|---|
| Primary Crop | field_primary_crop | Taxonomy reference | No |
| Other Crops | field_other_crops | Taxonomy reference, multiple | No |
| Production Start Date | field_production_start | Date | No |
| Notes | notes | Existing farmOS field | No |

## 4. Operational Status Values

The initial values will be:

- Planning
- Land preparation
- In development
- Active
- Partially operational
- Suspended
- Decommissioned

## 5. Business Rules

1. Every estate must have a unique Phoenix Code.
2. Phoenix Codes should begin with `EST-`.
3. Total area must be greater than zero.
4. Productive, immature and conservation areas must not be negative.
5. The combined classified areas should not exceed the total estate area.
6. Plantation Blocks must reference an Estate as their parent.
7. Archived estates should remain available in historical reports.
8. Deleting estates with linked operational records should be restricted.
9. Important changes should create a new revision.
10. Only authorised users should edit estate records.

## 6. Permissions

### System Administrator

- create estates
- edit all estates
- archive estates
- manage estate fields and configuration

### Executive

- view all estates
- view reports and performance indicators
- cannot change technical configuration

### Operations Director

- create and edit estates
- view all operational records
- assign estate managers

### Estate Manager

- view and edit assigned estate
- manage child plantation blocks
- view estate reports

### Field Supervisor

- view assigned estate and blocks
- create operational logs
- cannot edit estate identity or boundaries

## 7. User Interface

The Estate form should be organised into sections:

1. General Information
2. Location
3. Land and Area
4. Management
5. Production Profile
6. Documents
7. Notes

The Estate view page should show:

- estate name and code
- status
- location
- area summary
- estate manager
- mapped boundary
- child plantation blocks
- recent operational activity
- production summary
- alerts

## 8. Estate List

The Estate list should support:

- search by name or Phoenix Code
- filter by region
- filter by district
- filter by operational status
- filter by estate manager
- sorting by name, area or date established

Suggested columns:

- Estate Name
- Phoenix Code
- Region
- District
- Total Area
- Manager
- Status

## 9. Dashboard Indicators

The first Estate dashboard should eventually show:

- total estate area
- productive area
- immature area
- number of plantation blocks
- expected harvest today
- actual harvest today
- worker attendance
- machinery unavailable
- overdue activities
- pest and disease alerts
- inventory alerts

## 10. Initial Implementation Order

### Milestone 1

- Phoenix Code
- Operational Status
- Date Established
- Region
- District
- Total Area
- Estate Manager

### Milestone 2

- productive area
- immature area
- conservation area
- crop profile
- contact information

### Milestone 3

- estate listing and filters
- dashboard summary
- validation rules
- permissions
- automated tests

## 11. Acceptance Criteria

Estate Management Version 0.1 is complete when:

- an authorised user can create an Estate
- every Estate can store a unique Phoenix Code
- the Estate can be mapped
- the Estate can be assigned a manager
- the Estate can store location and area details
- a Plantation Block can reference the Estate as its parent
- Estate records can be searched and filtered
- unauthorised users cannot edit protected information
