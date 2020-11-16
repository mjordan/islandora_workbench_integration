# Islandora Workbench Integration

## Introduction

Drupal 8 Module required by [Islandora Workbench](https://github.com/mjordan/islandora_workbench). Enables the "Terms in Vocabulary" View, and also enables the Field, Field Storage, and Entity Form Display REST resources.

## Requirements

* [Islandora 8](https://github.com/Islandora-CLAW/islandora)

## Installation

1. Clone this Github repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_workbench_integration`.

## Configuration

By default, all vocabularies are registered in the view. To prevent vocabularies from being updated by Workbench, remove them using the View's "Taxonomy term: Vocabulary" filter.

## Permissions

By default, only users with the "Administer vocabularies and terms" permission can access the View. You should not relax the permission on this View since it returns large amounts of data, which can have an impact on your site's performance if the View is queried by anonymous users.

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
