# Prometheus Exporter

This plugin integrates **Prometheus** monitoring capabilities with your Craft CMS installation. It exposes health and performance metrics to Prometheus, enabling easy integration into existing monitoring setups.

## Features

- **Metrics Exposure**: Exposes key system metrics.
- **Customizable Endpoints**: Configurable endpoint path for `/metrics` (default `/metrics`).
- **Basic HTTP Auth**: Supports HTTP Basic Authentication.
- **Scalability**: Designed to work with high-traffic sites without significant overhead.
- **CraftCommerce Suppport**: Exports key metrics from CraftCommerce such as revenue, number of baskets (planned).

## Requirements

This plugin requires Craft CMS 5.10.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Prometheus Exporter”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require five-agency-ag/craft-prometheus-exporter

# tell Craft to install the plugin
./craft plugin/install prometheus-exporter
```
