Western Australian BMD index scraper
====================================

A small data-quality checking tool to confirm the contents of the [Pioneers Index](http://www.bdm.dotag.wa.gov.au/_apps/pioneersindex/).

Usage:

1. Get the data with `php scrape-all.php`
2. Sort it: `./data-sort.sh`

Then `git diff` will show you any differences from previous runs (there shouldn't be).
