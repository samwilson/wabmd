Western Australian BMD index scraper
====================================

A small data-quality checking tool to confirm the contents of the [Pioneers Index](http://www.bdm.dotag.wa.gov.au/_apps/pioneersindex/).

Usage:

1. Get the data with `php scrape-all.php`
2. Sort it: `./data-sort.sh`

Then `git diff` will show you any differences from previous runs (there shouldn't be).

## Scraping

Birth, e.g. https://justice.wa.gov.au/_apps/DoJWebsite/onlineIndex/birthRecords?yearFrom=1900&yearTo=1900

```
surname	"A'hern"
givenNames	"Juanita"
father	"Alexander A'HERN"
mother	"Catherine Jane BRUCE"
birthPlace	"S Fremantle"
registrationYear	"1900"
registrationNumber	"1950"
recordID	1125
recordTypeID	null
registrationDistrict	"-"
gender	"F"
yearOfBirth	"1900"
yearOfBirthFrom	null
yearOfBirthTo	null
```

Result fields:

Surname
Given Names
Sex
Father
Mother
Place of Birth
Year of Birth
Registration District
Registration Number
Registration Year

And death, e.g. https://justice.wa.gov.au/_apps/DoJWebsite/onlineIndex/deathRecords?yearFrom=1900&yearTo=1900

```
surname	"Abbey"
givenNames	"David"
father	"Thomas ABBEY"
mother	"Mary Ann MINION"
deathPlace	"Lennox River"
age	"57"
registrationYear	"1900"
registrationNumber	"2128"
recordId	84
recordTypeId	null
registrationDistrict	"-"
gender	"M"
yearOfDeath	"1900"
yearOfDeathFrom	null
yearOfDeathTo	null
```

Result fields:

Surname
Given Names
Sex
Age
Father
Mother
Place of Death
Year of Death
Registration District
Registration Number
Registration Year

