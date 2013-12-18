#!/bin/sh
php ../tordf.php -s ./marc/test_hld_10.mrc -o jsonld -t ../template/zdb-holding.jsonld > ./output/output_test_zdb_hld_10.jsonld
php ../tordf.php -s ./marc/test_hld_10.mrc -o rdfxml -t ../template/zdb-holding.jsonld > ./output/output_test_zdb_hld_10.xml
php ../tordf.php -s ./marc/test_hld_10.mrc -o turtle -t ../template/zdb-holding.jsonld > ./output/output_test_zdb_hld_10.ttl