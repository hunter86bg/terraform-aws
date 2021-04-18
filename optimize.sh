#!/bin/bash
mysql -vv -u USER -pPASS -h DBADDR -e 'SELECT table_name AS `Table`, round(((data_length + index_length) / 1024 / 1024), 2) `Size in MB` FROM information_schema.TABLES WHERE table_schema = "DATABASE" ;'

mysqlcheck -o DATABASE -vv -u USER -pPASS -h DBADDR

mysql -vv -u USER -pPASS -h DBADDR -e 'SELECT table_name AS `Table`, round(((data_length + index_length) / 1024 / 1024), 2) `Size in MB` FROM information_schema.TABLES WHERE table_schema = "DATABASE" ;'

