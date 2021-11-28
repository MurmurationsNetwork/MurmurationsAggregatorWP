# Murmurations Aggregator Wordpress Plugin

## What this plugin does
The aggregator plugin is designed to collect data from the Murmurations network, store it locally (while allowing manual approval/rejection of collected nodes), and display it in a variety of ways, including via a built in Leaflet map interface, a built-in directory interface, or through WP REST API endpoints that provide data to local or remote client-side interfaces or other services.

Filters give control over what nodes are collected from the network.

The plugin includes many hooks that can be used to effectively "white label" the aggregator, giving network organizations the possibility to create a bespoke UX for some aspects of the admin.

Display templates and styles can be overridden by themes or other plugins to customize the front-end UI.

### Schemas

The model for data collected from the network is determined by one or more JSON-Schemas. These are specified in the admin (either as local files or remote URLs), and cached by the plugin.

## Installation

To install, download a zip of this repo and upload into your plugins directory. Activate through the WP admin.

## Set up

Configure the aggregator by setting at least:
 - The filters that limit nodes collected from the network.
 - One or more data sources (indices) that the aggregator should connect to. By default, the main Mururations index is used.
 - One or more schemas that match the data collected.

## More help

For support installing or using the aggregator plugin, please post to the [Murmurations forum](https://murmurations.flarum.cloud/).
