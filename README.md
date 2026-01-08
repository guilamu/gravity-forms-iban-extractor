# Gravity Forms IBAN Extractor

Adds an IBAN extractor field type to Gravity Forms with real-time validation and data extraction.

## Features
- **Validate IBANs** - Real-time validation of IBAN structure and checksums
- **Extract Data** - Automatically extract Bank Name, BIC/SWIFT, and Bank Code
- **Document Scanning** - Upload RIB/bank statements to auto-fill IBAN details using AI

## Key Features
- **Real-time Validation:** Instant feedback on IBAN validity as users type
- **AI Extraction:** Uses POE API to read IBANs from uploaded documents
- **Customizable Display:** Choose which extracted banking details to show
- **Secure:** Validated inputs and secure API communication
- **GitHub Updates:** Automatic updates from GitHub releases

## Requirements
- POE API Key (for document extraction features)
- Gravity Forms 2.5 or higher
- WordPress 5.8 or higher
- PHP 7.4 or higher

## Installation
1. Upload the `gravity-forms-iban-extractor` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to any Form -> Add Field -> **Advanced Fields** -> **IBAN Extractor**
4. Configure display options in the field settings
5. (Optional) Enter your POE API Key in the field settings to enable document scanning

## FAQ
### How do I enable document scanning?
In the form editor, click on the IBAN field, go to Field Settings, and check "Enable Document Scanning". You will need to provide a valid POE API Key.

### Which countries are supported?
The plugin supports all SEPA countries and most international IBAN formats supported by the php-iban library.

### Can I customize the extraction prompt?
Currently the prompt is optimized for standard bank documents. Customization may be added in future versions.

## Project Structure
```
.
├── gravity-forms-iban-extractor.php  # Main plugin file
├── README.md
├── assets
│   ├── css
│   │   └── iban-extractor.css        # Frontend styles
│   └── js
│       └── iban-extractor.js         # Validation & extraction logic
├── includes
│   ├── class-gravity-extract.php     # Main class
│   ├── class-poe-api-service.php     # API communication
│   ├── class-github-updater.php      # Auto-updater
│   └── admin-settings.php            # Form editor settings
└── languages
    └── gravity-forms-iban-extractor.pot # Translation template
```

## Changelog

### 1.2.0
- **Security:** Enforced SSL verification for POE API requests
- **Improved:** Updated to use GitHub auto-updates
- **Fixed:** Minor validation issues

### 1.0.0
- Initial release
- IBAN validation field
- POE API integration for document scanning

## License
This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.
