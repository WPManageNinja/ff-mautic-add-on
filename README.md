# Mautic Integration For Fluent Forms

## Overview

Mautic Integration For Fluent Forms is a WordPress plugin that connects your Fluent Forms with Mautic, allowing you to automatically send form data to Mautic CRM. This integration helps you segment your form data and automate your marketing processes seamlessly.

## Features

- **Secure Connection**: Establish a secure connection with Fluent Forms.
- **Easy Integration**: Integrate effortlessly with your Mautic API.
- **Custom Fields Mapping**: Map custom fields between Fluent Forms and Mautic.

## Why Choose Mautic?

Mautic automates lead generation and nurturing, segments contacts, manages workflows, and integrates with various technologies. By connecting Mautic with WordPress forms, you streamline your automation processes.

## Installation

### From WordPress Admin Panel

1. Log in to your WordPress Admin Area.
2. Navigate to `Plugins` -> `Add New`.
3. Search for "Mautic for FluentForms" and hit Enter.
4. Locate the plugin and click "Install Now".
5. Activate the plugin.
6. Access Mautic from the FluentForms module dashboard.

### Manual Installation

1. Download the "Mautic for FluentForms" plugin from the [WordPress.org repository](https://wordpress.org/plugins/mautic-for-fluentforms/).
2. In your WordPress admin dashboard, go to `Plugins` -> `Add New` -> `Upload Plugin`.
3. Upload the downloaded plugin file (`mautic-for-fluentforms.zip`) and click Install Now.
4. Ensure your FluentForms plugin is already activated.
5. Activate "Mautic for FluentForms" from your Plugins page.
6. Access Mautic from the FluentForms module dashboard.

## Setup

1. Enable your Mautic API in your Mautic account dashboard.
2. Create new oAuth2 credentials with a redirect URL available on your global settings page.
3. Map your form fields with Mautic from the single form integration settings.

## Support

For dedicated support, visit our [support page](https://wpmanageninja.com/support-tickets/).

## Changelog

### 1.0.3
- Adds support for custom fields.
- Fixes IP & last active issue.

### 1.0.2
- Fixes name fields sync.
- Fixes a few labels.

### 1.0.0
- Initial release.

## License

This project is licensed under the GPLv2 or later - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for details.

---

This plugin is an add-on for the [Fluent Forms](https://fluentforms.com/) WordPress plugin, providing enhanced integration capabilities with Mautic CRM.

## Description
This addon for Fluent Forms enables automatic syncing of your WordPress form submissions to Mautic CRM. It provides seamless integration for lead generation and contact management with powerful segmentation capabilities.

### Key Features
- 🔒 Secure connection with Fluent Forms
- 🔌 Easy integration with Mautic API
- 🗺️ Custom fields mapping
- 📱 Real-time data synchronization

## Requirements
- WordPress 5.0 or later
- PHP 5.6 or later
- Fluent Forms plugin installed and activated
- Mautic instance with API access

## Setup Guide

### Mautic API Configuration
1. Login to your Mautic dashboard
2. Click the gear icon next to your username
3. Navigate to Configuration settings >> Api settings
4. Enable the API
5. Go to "Api Credentials"
6. Create new OAuth2 credentials
7. Use the redirect URL from your Fluent Forms global settings page (Your-Site-URL/?ff_mautic_auth=1)

### Form Integration
1. Go to Fluent Forms dashboard
2. Configure Mautic authentication
3. Map your form fields with Mautic fields
4. Start collecting leads!

## Contributing
We welcome contributions! Please feel free to submit a Pull Request.

## Credits
Developed by [WPManageNinja](https://wpmanageninja.com/) 
