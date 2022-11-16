# NADA - Data Catalog

NADA is an open source microdata cataloging system, compliant with the Data Documentation Initiative (DDI) and Dublin Core’s RDF metadata standards. It serves as a portal for researchers to browse, search, compare, apply for access, and download relevant census or survey datasets, questionnaires, reports and other information.

## Getting Started

- [NADA documentation ](https://ihsn.github.io/nada-documentation)
- [API/Schemas documentation](https://ihsn.github.io/nada-api-redoc/catalog-admin/)

### Server Requirements

* PHP version 7.3 or later
* MySQL or Microsoft SQL Server database
* Apache, IIS or NGINX

### Documentation

User and admin guide - https://ihsn.github.io/nada-documentation/


### Installation

System requirements and steps for installation - [NADA installation guide](https://ihsn.github.io/nada-documentation/installation-guide/).


### Upgrading from older versions of NADA

Documentation for upgrading from various versions - [NADA upgrade guide](https://ihsn.github.io/nada-documentation/installation-guide/upgrade/).

### Schema guide

NADA supports multiple data types that include `Microdata` (DDI CodeBook 2.5), `Document`, `Table`, `Geospatial`, `Timeseries`, `Visualization` and `Image`. For all data types, documentation is available in the draft guide (https://mah0001.github.io/schema-guide/). See our demo catalog show casing all support data types - https://nada-demo.ihsn.org/index.php/catalog/

### API documentation

The API documentation is available in OpenAPI/Swagger format here -  https://ihsn.github.io/nada-api-redoc/catalog-admin/

For Getting started with the API, read the `Administrator Guide` section which covers both using the web interface and the API - https://ihsn.github.io/nada-documentation/getting-started/#publishing-a-document

### API Client tools

* R package - https://github.com/mah0001/nadar
* Python package - https://pypi.org/project/pynada/


## Versioning

For the versions available, see the [tags on this repository](https://github.com/ihsn/nada/tags). 


## Authors

* **IHSN** - [International Household Survey Network](http://ihsn.org)


## License

This project is licensed under the MIT License - see the [license.txt](license.txt) file for details

## Acknowledgments

* CodeIgniter
* Bootstrap
* todo - other libraries used
