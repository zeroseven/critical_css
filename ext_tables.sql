CREATE TABLE pages
(
    critical_css_disabled tinyint(1) unsigned DEFAULT '0' NOT NULL,
    critical_css_status   int(1) unsigned DEFAULT '0' NOT NULL,
    critical_css          text
);
