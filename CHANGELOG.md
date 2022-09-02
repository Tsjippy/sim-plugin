# Changelog

## [Unreleased] - yyyy-mm-dd

### Added

### Changed
- link pictures to full srceen picture
mediagallery

### Fixed
- form validation
- tinymce over ajax
- warnig for prayer when not including
month or year in title

## [2.12.3] - 2022-09-01


### Changed
- only show post edit
form when fully loaded

### Fixed
- issue with categories in mediagallery
- custom post types can have parents

## [2.12.2] - 2022-08-31


## [2.12.1] - 2022-08-31


### Changed
- do not send to the same
mailchimp group again

### Fixed
- bug in fullscreen pdf

## [2.12.0] - 2022-08-31


### Added
- media gallery block

### Fixed
- better description when media is missing
- bug whith page edit button on media gallery
- issue with loading more media button
- issue with mailchimp on frontend editor
- media gallery info box layout

## [2.11.4] - 2022-08-29


### Fixed
- better update error handling

## [2.11.3] - 2022-08-29


### Fixed
- reload page when response is not an url

## [2.11.2] - 2022-08-29


### Fixed
- make not sending e-mails from staing
site optional

## [2.11.1] - 2022-08-29


## [2.11.0] - 2022-08-29


### Added
- statistics overview

## [2.10.4] - 2022-08-26


### Fixed
- do not print recipe details if not a recipe

## [2.10.4] - 2022-08-26


### Changed
- update mu-plugin on update

## [2.10.2] - 2022-08-26


### Fixed
- mu-pluigin problem

## [2.10.1] - 2022-08-26


### Fixed
- bug when creating repeating events
- issue with upcoming events block
date layout

## [2.10.0] - 2022-08-26


### Fixed
- issue with edit page over AJAX

## [2.9.0] - 2022-08-25


### Added
- edit page over AJAX instead of
reloading the entire page

### Changed
- edit post now over AJAX

## [2.8.4] - 2022-08-24


### Fixed
- bug in calendar when no times set
- issue with single events with small
content body

## [2.8.3] - 2022-08-23


### Added
- static content for locations

### Fixed
- upcoming events block layout
- tinyMce fields in formbuilder


## [2.8.0] - 2022-08-23


### Added
- recipe metadata block
- Signal block
- mailchimp block
- expiry date and update warnings
block settings

### Changed
- embed page content to embed page module

### Fixed
- better error handling while upload
- location lookup by google
- issue with creating events
- location pages not showing a map
- when update featured image
update icon image to
- issue with  metadata blocks
- &amp; in signal messages
- lists are shown again
- issue in widget blocks page
- do not show categories if there are none
- upcoming events layout & all day events
- warning when someone submits a
post for review
- do not send pendngpost warning when
publishing it

## [2.7.0] - 2022-08-19


### Added
- events metadata block

## [2.6.0] - 2022-08-17


### Added
- missing form fields block
- pending pages block
- pening pages, your posts, displayname, login count,
welcome message, mandatory pages blocks
- user description block

### Changed
- table buttons result in ajax refresh
- removed simnigeria/v1/notifications
 from rest api

### Fixed
- mail issue

## [2.5.0] - 2022-08-16


### Added
- bulk change module

### Changed
- switch form ver ajax

### Fixed
- first login on staging site

## [2.4.0] - 2022-08-12


### Added
- check for title for prayerpost
- block filters
- category block
- schedules block
- form selector block

### Changed
- better block filters

### Fixed
- bug when inserting a non-existing file
- bug with wp_localize_script
- issue with urls on events
- removing featured image
- account statement download links
- bug in checkboxes formresults

## [2.3.2] - 2022-08-02


### Changed
- main file structure
- remove dublicate tags during posting post

### Fixed
- mail problem in comments module
- better name finding in content
- errors in post comparisson
- userpage links on frontpage
- issue with wedding anniversaries

## [2.3.1] - 2022-07-30


### Fixed
- multiple good mornings in prayer
- editing location type
- issue when resubmitting a image
- backend profile image
- issue with wrong page urls in e-mail
- issues with repeating events

## [2.2.19] - 2022-07-29


### Changed
- remove images fileupload plugin

### Fixed
- wrong query on events page
- prayerrequest filter is not applied
-  2 celebrations on 1 day for 1 person
- issue when multiple same forms on page

## [2.2.18] - 2022-07-29


### Fixed
- retieve events with a category
- anniversary messages

## [2.2.17] - 2022-07-25


### Fixed
- issue with upcoming events

## [2.2.16] - 2022-07-23


### Fixed
- issue when no events

## [2.2.15] - 2022-07-23


### Added
- upcoming events widget
- remove empty widgets

### Fixed
- forms wrong order when adding element
- frontpage layout when arriving users

## [2.2.14] - 2022-07-21


### Fixed
- personal prayer schedule

## [2.2.13] - 2022-07-21


### Fixed
- wrong usage of getDefaultPageLink
- scheduled prayer

## [2.2.12] - 2022-07-21


### Changed
- family function layout
- convert file upload to default module

### Fixed
- login on non-home page
- exclude family from family dropdown
- banking module

## [2.2.11] - 2022-07-18


### Fixed
- updating with version larger than 9

## [2.2.10] - 2022-07-18


### Fixed
- default page redirection and css enqueue

## [2.2.9] - 2022-07-18


### Fixed
- home page redirect

## [2.2.8] - 2022-07-16


### Fixed
- problems

## [2.2.7] - 2022-07-15


### Fixed
- page removal action

## [2.2.6] - 2022-07-15


### Added
- manual update possibility

## [2.2.5] - 2022-07-15


### Fixed
- default pages for login module

## [2.2.4] - 2022-07-15


### Fixed
- message no permission to delete accounts
- better auto page creation

## [2.2.3] - 2022-07-15


### Added
- select attachment cat in backend

### Changed
- do not store empty values

### Fixed
- sending e-mail containing 2fa code on linux
- issue with uploads organised in date folder
- rest api response when errors on screen
- attachment cats

## [2.2.2] - 2022-07-14


### Fixed
- module activation actions on Linux systems
- e-mail content settings field
- module settings not saved properly

## [2.2.1] - 2022-07-14


### Fixed
- update routine

## [2.1.1] - 2022-07-14


### Added
- Signal Prayer Time now defined on website

### Changed
- Libraries are now on a per module basis
- Javascript optimalization

### Fixed
- better error handling
- changelog script for releases
- class loader on Linux systems

## [2.0.0] - 2022-06-21

First public release on github

### Changed

- Lots of code changed for better readability
- Split code into modules for better maintability

## [1.0.0] - 2020-06-15

Initial release
