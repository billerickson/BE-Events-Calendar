# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.3.2] - 2017-01-16
### Added
- Filter for recurring post type args, `be_events_manager_recurring_post_type_args`

### Fixed
- Debug error if no date is specified for an event, see #23
- Javascript bug in event calendar widget, see #25
- Display all events on calendar widget, see #25

## [1.3.1] - 2016-09-05
### Changed
- Specify date format since WordPress changed the defaults, see #21

## [1.3.0] - 2016-04-26
### Added
- Calendar widget to view upcoming events, see #17
- Filter for post type arguments: be_events_manager_post_type_args, see #12
- Admin column for event categories

### Changed
- Inline documentation contained an incorrect URL, see #16
- Correctly update permalinks on plugin activation, see #20

## [1.2.0] - 2015-09-17
### Added
- All day event option

### Changed
- Updated widget to use PHP5 Constructor

## [1.1.0] - 2015-02-19
### Added
- AJAX Calendar View

## [1.0.4] - 2015-02-18
### Added
- CHANGELOG.md to easily track updates

### Changed
- Docblocks to be more descriptive and accurate
- File structure to better accomodate future expansion
