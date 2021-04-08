# Islandora Workbench Integration

## Introduction

Drupal 8/9 Module required by [Islandora Workbench](https://github.com/mjordan/islandora_workbench). Enables the following Views:

* Terms in vocabulary
* Term from URI

Also enables the following REST resources:

* Field
* Field Storage
* Entity Form Display
* User
* URL alias

It also enables JSON:API with create, read, update, and delete operations enabled.

Also provides endpoints for exposing:

* the maximum file size that can be uploaded
* Drupal's version number

Access to both of these endpoints requires the 'administer site configuration' permission.

## Usage

There is no user interface to this module. It only installs configuration that is required by Islandora Workbench.

## Requirements

* [Islandora 8 or 9](https://github.com/Islandora/islandora)

## Installation

1. Clone this Github repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_workbench_integration`.

## Configuration

By default, all vocabularies are registered in the views. To prevent vocabularies from being updated by Workbench, remove them from the "Terms in vocabulary" View using its "Taxonomy term: Vocabulary" filter.

## Permissions

By default, only users with the "Administer vocabularies and terms" permission can access the "Terms in vocabulary" and "Term from URI" Views. You should not relax the permission on this Views since they return large amounts of data, which can have an impact on your site's performance if the Views are queried by anonymous users.

All other REST endpoints, including those provided by JSON:API, require the use of Basic Authentication. 

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
