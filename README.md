# Islandora Workbench Integration

## Introduction

Drupal 8 Module that provides a View used by [Islandora Workbench](https://github.com/mjordan/islandora_workbench). This view provides a REST endpoint listing all the terms in a vocabulary.

Note that this module is not currently required to use Islandora Workbench, but if you want to use Islandora Workbench to validate the existence of taxonomy term IDs/names used in your input CSV, you do need to install this module.

## Requirements

* [Islandora 8](https://github.com/Islandora-CLAW/islandora)

## Installation

1. Clone this Github repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_workbench_integration`.

## Configuration

By default, all vocabularies are registered in the view. To prevent vocabularies from being updated by Workbench, remove them using the View's "Taxonomy term: Vocabulary" filter.

## Permissions

By default, only users with the "Administer vocabularies and terms" permission can access the View. You should not relax the permission on this View since it returns large amounts of data, which can have an impact on your site's performance if the View is queried by anonymous users.


## Usage

`curl -v  "http://localhost:8000/vocabulary?_format=json&vid=islandora_models"` will return a list of all the terms in the 'islandora_models' vocabulary.

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
