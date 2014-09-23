mt
==

Data model translator

**mt** is a command line tool written in PHP to convert relational data models between formats.

It works by using importing and exporting modules, which turn an initial representation of the model into an abstract representation, and then into a final representation as output.

Although it has room to accomodate multiple input and output formats and make it a general purpose tool, it's limited by the scope of my daily work, which is to generate PHP classes in a home-grown framework from MySQL Workbench model files (.mwb). However, it also has SQL output support for PostgreSQL and MS SQL Server, so it's not that useless, if you ever find yourself switching DBMS amidst project development, or if you really like Workbench as a modelling tool but don't use MySQL as DBMS.

Sadly, for now, it does very specific things, and there's no documentation for its internal API. If you ever needed a new feature, or support a new input/output format, let me know in the [issues](https://github.com/roeltz/mt/issues).

## Usage ##
	php mt {importer}/{exporter} --in="input path" --out="output path"
	
## Examples ##
	php mt mwb/pipa  --in="model.mwb" --out="src/model"
	php mt mwb/pgsql --in="model.mwb" --out="db.sql"
	php mt mwb/mssql --in="model.mwb" --out="db.sql"
