# Changelog
## [Unreleased] - yyyy-mm-dd

### Added
- attribute escaping

### Changed
- sanitize post on original function
- plugin tested up to 7.0
- replaced in_array with isset

### Fixed
- non-prefixd post meta's

### Updated

## [10.3.7] - 2026-06-25


## [10.3.6] - 2026-06-24


## [10.3.5] - 2026-06-23


### Fixed
- hook names

## [10.3.4] - 2026-06-23


### Fixed
- phonenumbers bug

## [10.3.3] - 2026-06-23


## [10.3.2] - 2026-06-23


### Changed
- implemented db caching
- implemented db caching
- replaced wpdb->update with updateDbFunction

## [10.3.0] - 2026-06-21


## [10.2.9] - 2026-06-20


## [10.2.8] - 2026-06-19


## [10.2.7] - 2026-06-18


### Changed
- retrieve single value for family picture
- hook and filter name update
- hook and filter name update
- prefix all hooks with plugin name

### Fixed
- signal groups

## [10.2.6] - 2026-06-15


## [10.2.5] - 2026-06-15


## [10.2.3] - 2026-06-15


## [10.2.2] - 2026-06-13


### Changed
- prefix meta key in get_users

### Fixed
- shared code loader
- activation hook

## [10.2.1] - 2026-06-11


### Added
- user, post and rest_meta prefixing

### Changed
- prefixed post metas and shortcodes

### Fixed
- prefix meta_query

## [10.2.0] - 2026-06-09


### Added
- shared functionality loader

### Changed
- comply to coding standards
- code layout
- _ to -
- namespaced all constants
- sanitize all posts and get vars
- moved inline style to scss file

### Fixed
- spacing problem
- space before dot bug
- use pluginversion

## [10.1.9] - 2026-06-03


### Added
- echo escaping

### Changed
- use of gmdate and not date

### Fixed
- do not use wp_strip_all_tags

## [10.1.8] - 2026-06-01


### Changed
- merged hooks.md into readme.md

## [10.1.7] - 2026-06-01


### Changed
- show group name in functions

## [10.1.6] - 2026-06-01


### Changed
- js update

## [10.1.5] - 2026-05-30


### Changed
- do not store get_plugin_data in global variable

## [10.1.4] - 2026-05-29


### Added
- wp_unslash

## [10.1.3] - 2026-05-28


### Added
- js dependency

### Fixed
- empty username error

## [10.1.2] - 2026-05-23


### Changed
- raw html to dom element implementation

## [10.1.1] - 2026-05-17


### Added
- deactivation hook

## [10.1.0] - 2026-05-16


### Changed
- force update of schedule after change

### Fixed
- after update

## [10.0.9] - 2026-05-16


### Added
- send prayer form functions

### Fixed
- small bug

## [10.0.8] - 2026-05-15


### Changed
- store prayer times in db
- prevent duplicates

### Fixed
- get prayer on cron

## [10.0.7] - 2026-05-14


### Fixed
- do not add groups with empty names

## [10.0.6] - 2026-05-13


### Changed
- date( to gmdate(

### Fixed
- saving signal group when empty name

## [10.0.5] - 2026-05-12


### Changed
- removed admin login for cron

## [10.0.2] - 2026-05-08


### Changed
- js update

## [10.0.1] - 2026-05-03


### Changed
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

## [10.0.0] - 2026-05-01


### Added
- redirection to settings page on plugin activation

### Changed
- main plugin name from sim-base to tsjippy-shared-functionality
- module to plugin
- exclude .vscode from releases
- updated github workflow versions

## [8.3.6] - 2026-04-06


### Fixed
- parse prayer requests without br for a new line

## [8.3.5] - 2025-12-01


### Changed
- implemented new userpagelinks
- implemented new userpagelink class

## [8.3.4] - 2025-11-03


### Fixed
- bug in regex
- getting family picture
- there should be an empty line in between prayer requests

## [8.3.2] - 2025-10-30


### Changed
- use new family class

## [8.3.1] - 2025-10-27


### Changed
- new format for frontendcontent

### Fixed
- casing in new prayer messages
- prayer reminder
- prayer reminder message

## [8.3.0] - 2025-10-25


### Added
- prayer-request post type

### Fixed
- bugs
- prayer message updating via signal
- bug in retrieving pictures
- show prayer categories above praer request

## [8.2.1] - 2025-10-13


### Changed
- classnames
- data attribute names

### Fixed
- bugs

## [8.2.0] - 2025-09-26


### Changed
- cleaner admin js
- classnames replace _ with -

## [8.1.9] - 2025-07-25


### Added
- 'sim-prayer-send-message' action hook

### Changed
- better message parsing

## [8.1.8] - 2025-05-15


### Fixed
- updating of prayer

## [8.1.7] - 2025-05-08


### Added
- error checking

### Changed
- also clean empty entries

## [8.1.6] - 2025-04-03


### Changed
- removed signal messaging

### Fixed
- replacing prayer requests

## [8.1.5] - 2025-03-21


### Changed
- use getSignalInstance

## [8.1.4] - 2025-03-19


### Added
- 	= apply_filters('sim-prayer-params', , , , , );
- signal daemon responses

### Fixed
- prayer message parsing

## [8.1.3] - 2025-03-05


### Fixed
- bold heading

## [8.1.2] - 2025-02-13


### Changed
- module hooks now include module slug

## [8.1.1] - 2025-02-11


### Changed
- sim_module_updated filter to new format
- use site date and time format

## [8.1.0] - 2025-02-06


### Fixed
- prayer request update request

## [8.0.9] - 2025-02-06


### Fixed
- bold in international message

## [8.0.8] - 2025-01-22


### Fixed
- issue with <br />

## [8.0.7] - 2025-01-21


### Added
- module_path constant

## [8.0.6] - 2025-01-10


### Fixed
- prayer request update message

## [8.0.5] - 2024-12-01


### Changed
- moved signal layout to signal module

### Fixed
- user urls

## [8.0.4] - 2024-11-29


### Added
- sim international

## [8.0.3] - 2024-11-20


### Changed
- redering of asset urls
- removed css
- removed anonymous functions

## [8.0.1] - 2024-10-07


### Changed
- readme

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
