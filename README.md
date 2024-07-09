# EcoSpold Explorer

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

This app provides ecospold extraction features, from **.spold** sources to **.xslsx**. It can handle large requests based on multiple criteria. This source code does not contain any dataset: you must have a valid license (e.g. [ecoinvent](https://ecoinvent.org/)) to specify the sources to parse with this app.

## Features

- EcoSpold files to Excel extraction
- Multiple sources declaration
- Search based on keywords contained in activity names
- Filtering features base on keywords contained in reference flows and/or contained intermediate exchanges
- Impacts extraction
- Elementary flows extraction
- Impacts contribution calculation and extraction

## Requirements

- EcoSpold files with a valid license to read them
- PHP 8.3 or higher
- MySQL 5.7 or higher
- Composer

## Installation

1. **Clone the repository:**

    ```bash
    git clone https://github.com/Florian-Altermaker/ecospold-explorer
    cd ecospold-explorer
    ```

2. **Install dependencies:**

    ```bash
    composer install
    ```

3. **Set up your environment variables:**

    Copy `.env.example` to `.env` and update the **DB_DSN** varialbe to declare your MySQL database credentials.

    ```bash
    cp .env.example .env
    ```

4. **Open a MySQL database service and execute the following request to create the structure**

    ```sql
    DROP TABLE IF EXISTS `characterization_factor`;
    CREATE TABLE IF NOT EXISTS `characterization_factor` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `source_id` int(11) NOT NULL,
    `method` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column A "Method"',
    `category` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column B "Category"',
    `indicator` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column C "Indicator"',
    `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column D "Name"',
    `compartment` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column E "Compartment"',
    `subcompartment` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Column F "Subcompartment"',
    `characterization_factor` double NOT NULL COMMENT 'Column G "CF"',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table is based on file "LCIA implementation" available on Ecoquery platform';

    DROP TABLE IF EXISTS `source`;
    CREATE TABLE IF NOT EXISTS `source` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Database name and version (e.g. "ecoinvent 3.10 - cutoff")',
    `path_to_unit_datasets_repository` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to folder containing UNIT datasets (e.g. "/path/to/somewhere/ei10/cutoff")',
    `path_to_system_datasets_repository` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to folder containing SYSTEM datasets (e.g. "/path/to/somewhere/ei10/cutoff_lci")',
    `path_to_impact_datasets_repository` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to folder containing LCIA results datasets (e.g. "/path/to/somewhere/ei10/cutoff_lcia")',
    `path_to_geographies_file` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to file containing geographies (e.g. "/path/to/somewhere/ei10/MasterData/geopraphies.xml")',
    PRIMARY KEY (`id`),
    UNIQUE KEY `source_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Available sources of EcoSpold datasets';
    ```

5. **Declare a first data source**

    First, you need a valid license to use EcoSpold data source. Four path sources must be declared for a source (table `source` in database):
    - `path_to_unit_datasets_repository`: the directory where the "Unit" files are (the main files with intermediate exchanges and elementary flows) - E.g. __"C:ecopspold/path/to/somewhere/ei10/cutoff"__
    - `path_to_system_datasets_repository`: the directory where the "System" files are (also called LCI files) - E.g. __"C:ecopspold/path/to/somewhere/ei10/cutoff_lci"__
    - `path_to_impact_datasets_repository`: the directory where calculated impacts files are (also called LCIA files) - E.g. __"C:ecopspold/path/to/somewhere/ei10/cutoff_lcia"__
    - `path_to_geographies_file`: the file referencing available geographies - E.g. __"C:ecopspold/path/to/somewhere/ei10/MasterData/geopraphies.xml"__

    Then, to allow calculation (if you need to extract impacts contributions), the table `characterization_factor` has to be filled with editor's LCIA implementation: you can find it on the editor's platform if he published it (if not, the calculation feature may not be available).

    If you are using ecoinvent, you can download all the source on the [Ecoquery](https://ecoquery.ecoinvent.org) platform, under "Files". LCIA Implementation is available in the supporting documents of the selected version.

6. **Let's open the app!**

    Once everything is ready, open the file __public/index.html__ in your favorite browser (hoping you chose the right one).


## Usage

Define your search parameters, configure your output and click on **Extract**. That's it.

## Support

In case you encounter any trouble using this app, please contact the author.

## Author

Florian Bratec, PhD <florian.bratec@gmail.com>