BE-Events-Calendar
==================

This is a simple, clean base plugin for developing event calendars. 

## Features

- Creates events post type and customizes columns
- Creates simple metabox for collecting event date/time. This can be replaced with your own metabox (see Dev Notes below)
- Customizes the query to sort by event start date and only show upcoming events
- Creates an Upcoming Events widget
- If you're using [Genesis](http://www.billerickson.net/go/genesis), it adds appropriate event schema markup.

## Optional Features

These can be added with add_theme_support( 'be-events-calendar', array() ). Add these keys to the array:
- 'event-category'. Creates an event_category taxonomy. 
- 'recurring-events' . Allows for recurring events

## Dev Notes

The metabox can be disabled using the 'be_events_manager_metabox_override' filter. [See wiki for more information.](https://github.com/billerickson/BE-Events-Calendar/wiki)

Here's the code for an [event calendar widget](http://www.billerickson.net/code/event-calendar-widget/). It's not included in the plugin since it often requires modification. 
