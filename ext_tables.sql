CREATE TABLE pages
(
    critical_css_disabled tinyint(1) unsigned DEFAULT '0' NOT NULL,
    critical_css_status   tinyint(1) unsigned DEFAULT '0' NOT NULL,
    critical_css_inline   text,
    critical_css_linked   text
);
