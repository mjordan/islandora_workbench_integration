# Islandora Workbench Integration

## Introduction

Drupal 8/9 Module required by [Islandora Workbench](https://github.com/mjordan/islandora_workbench). Enables the following Views:

* Terms in vocabulary
* Term from URI
* Term from term name

Also enables the following REST resources:

* Field
* Field Storage
* Entity Form Display
* User
* URL alias
* File upload
* Media type
* Taxonomy Vocabulary

Also provides endpoints for exposing:

* Drupal core's version number
* the maximum file size that can be uploaded
* this module's version number
* a given file's checksum

## Usage

There is no user interface to this module. It only installs configuration that is required by Islandora Workbench.

## Requirements

* [Islandora 8 or 9](https://github.com/Islandora/islandora)

## Installation

You can install this module using Composer. Within your Drupal root directory, run the following:

1. `composer require mjordan/islandora_workbench_integration "dev-main"`
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_workbench_integration`.

If you're deploying Islandora via ISLE, install and enable this module using these two commands from within your isle-dc directory:

1. `docker-compose exec -T drupal with-contenv bash -lc "composer require mjordan/islandora_workbench_integration"`
2. `docker-compose exec -T drupal with-contenv bash -lc "drush en -y islandora_workbench_integration"`

## Configuration

By default, all vocabularies are registered in the views. To prevent vocabularies from being updated by Workbench, remove them from the "Terms in vocabulary" View using its "Taxonomy term: Vocabulary" filter.

## Updates

Since this module enables a number of REST endpoints, you may need to reimport the configuration if a new endpoint is added. For example, after pulling in updates from Github, you should run the following `drush` commands from within the `/var/www/html/drupal/web` directory:

1. `drush cim -y --partial --source=modules/contrib/islandora_workbench_integration/config/optional`
1. `drush cr`

Or, if you are using ISLE:

1. `docker-compose exec -T drupal with-contenv bash -lc "drush cim -y --partial --source=modules/contrib/islandora_workbench_integration/config/optional"`
1. `docker-compose exec -T drupal with-contenv bash -lc "drush cr"`

## Permissions

All REST endpoints added or endabled by this module require the use of Basic Authentication. The username/password combination used in your Islandora Workbench configuration files should be a member of the "Administrator" role.

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
