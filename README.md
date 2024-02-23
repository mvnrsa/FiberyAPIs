# Fibery APIs

An implementation of the Fibery APIs for Laravel.

(c) 2024 - Marnus van Niekerk - fibery@mjvn.net

## APIs Included

- Integrations API - For automatically including your models as entities inside Fibery.
- Webhooks - For updating your models from Fibery webhooks whenever fields are changed in Fibery.
- REST API - For querying the schema, retrieving, creating and updating entities and creating fields in Fibery.

## Two-Way Sync

A *minimalist* form of Two-Way sync can be achieved by combining the Integrations API with Webhooks:

1. Use the Integrations API to include your model as an entity inside Fibery - the fields from Laravel will be **read only**.
2. Add some (shadow) field(s) to that entity in Fibery - these fields will **not** be read only.
3. Set up a webhook to publish changes to the entity back to your Laravel.
4. Configure the webhook handler to update your model's real field(s) from changes to the shadow field(s).

This is **not** ideal or perfect, but it's the best that the Fibery APIs can do at the moment.  
Unfortunately the Fibery Integrations API does not support any updates and it probably won't any time soon.  
More details are included in the Webhooks section below.

### Caution!!

Watch out for creating infinte loops.  **Do not** include fields that depend on each other in your webhook config in any way.

## Installation

## Integrations API

## Webhooks

## REST API (Fibery Client)
