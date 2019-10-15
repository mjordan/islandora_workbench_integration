# Islandora Workbench Integration

## Introduction

Drupal 8 Module that provides a View used by [Islandora Workbench](https://github.com/mjordan/islandora_workbench). This view provides a REST endpoint listing all the terms in a vocabulary.

## Requirements

* [Islandora 8](https://github.com/Islandora-CLAW/islandora)

## Installation

1. Clone this Github repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_workbench_integration`.

## Configuration

By default, the following vocabularies are registered in the view:

* islandora_display
* islandora_media_use
* islandora_models
* tags

To add new vocabularies, add them to the filter in the View.

## Usage

`curl -v  "http://localhost:8000/vocabulary?_format=json&vid=islandora_models"` will return a list of all the terms in the 'islandora_models' vocabulary.

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
