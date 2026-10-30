# NADA - Data Catalog

NADA is an open source microdata cataloging system, compliant with the Data Documentation Initiative (DDI) and Dublin Core’s RDF metadata standards. It serves as a portal for researchers to browse, search, compare, apply for access, and download relevant census or survey datasets, questionnaires, reports and other information.

## Documentation

- [NADA documentation](https://ihsn.github.io/nada-documentation/) - user and administrator guide, installation and upgrading
- [Metadata schemas](https://worldbank.github.io/metadata-schemas/) - schema and API reference
- [Guide to development data metadata standards](https://worldbank.github.io/guide-metadata-standards/)
- [Demo catalog](https://nada-demo.ihsn.org/index.php/catalog/) - showcases the supported data types

## Supported data types

`Microdata` (DDI Codebook 2.5), `Document`, `Table`, `Geospatial`, `Timeseries`, `Image`, `Video` and `Script`. See the documentation above for details on each type.

## Requirements

* PHP 7.4 or later
* MySQL 8 or MariaDB, or Microsoft SQL Server (with Full-Text Search installed)
* Apache, IIS or NGINX

## Installation and upgrading

* New installation: [installation guide](https://ihsn.github.io/nada-documentation/installation-guide/)
* Upgrading: [upgrade guide](install/UPGRADING.md) ([all versions](https://ihsn.github.io/nada-documentation/installation-guide/upgrade/)). Back up the database first, then run `php index.php cli/migrate latest`.

## API client tools

* R package - https://github.com/mah0001/nadar
* Python package - https://pypi.org/project/pynada/

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md). To report a security vulnerability, see [SECURITY.md](SECURITY.md).

## Versioning

For the versions available, see the [tags on this repository](https://github.com/ihsn/nada/tags).


## License

This project is licensed under the MIT License - see the [license.txt](license.txt) file for details.

## AI Use

This project uses AI coding assistants to accelerate development, support code generation, and improve documentation. All AI-generated code and documentation is reviewed and tested by the project maintainers, who remain responsible for its quality, security, and reliability.
