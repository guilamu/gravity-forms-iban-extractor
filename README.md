# Gravity Forms IBAN Extractor

Adds an IBAN extractor field type to Gravity Forms with real-time validation and data extraction.

## Features
- **Validate IBANs** - Real-time validation of IBAN structure and checksums
- **Extract Data** - Automatically extract Bank Name, BIC/SWIFT, and Bank Code
- **Document Scanning** - Upload RIB/bank statements to auto-fill IBAN details using AI
- **Multi-Provider AI** - Choose between POE and Google Gemini for document extraction

## Key Features
- **Real-time Validation:** Instant feedback on IBAN validity as users type
- **AI Extraction:** Uses POE or Google Gemini API to read IBANs from uploaded documents
- **Customizable Display:** Choose which extracted banking details to show
- **Secure:** Validated inputs and secure API communication
- **GitHub Updates:** Automatic updates from GitHub releases

## Supported API Providers

### POE
- Dynamic model list fetched from API
- Requires a POE API key from [poe.com](https://poe.com)
- Models are filtered to only show image-capable ones

### Google Gemini
- Static curated model list (Gemini 2.0 Flash Lite, 2.0 Flash, 2.5 Flash Preview, 2.5 Pro Preview)
- Requires an API key from [Google AI Studio](https://aistudio.google.com/apikey)
- Uses the OpenAI-compatible endpoint

## Requirements
- POE API Key or Google Gemini API Key (for document extraction features)
- Gravity Forms 2.5 or higher
- WordPress 5.8 or higher
- PHP 7.4 or higher

## Installation
1. Upload the `gravity-forms-iban-extractor` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to any Form -> Add Field -> **Advanced Fields** -> **IBAN Extractor**
4. Configure display options in the field settings
5. (Optional) Enter your POE or Gemini API Key in the field settings to enable document scanning

## FAQ
### How do I enable document scanning?
In the form editor, click on the IBAN field, go to Field Settings, and check "Enable Document Scanning". Select your preferred API provider (POE or Google Gemini) and enter the corresponding API key.

### Can I use different providers per field?
Yes. Each IBAN Extractor field has its own provider settings, so you can use POE on one field and Gemini on another within the same form.

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
│   ├── class-gf-field-iban-extractor.php  # Field class
│   ├── class-iban-extractor.php      # IBAN parsing wrapper
│   ├── class-poe-api-service.php     # POE AI document extraction
│   ├── class-gemini-api-service.php  # Google Gemini AI extraction
│   ├── class-github-updater.php      # Auto-updater
│   └── admin-settings.php            # Form editor settings
├── lib
│   ├── php-iban.php                  # Bundled php-iban library
│   ├── registry.txt                  # IBAN country registry
│   ├── mistranscriptions.txt         # Common typo mappings
│   └── LICENSE-php-iban              # LGPL-2.1 license
└── languages
    └── gravity-forms-iban-extractor.pot # Translation template
```

## Changelog

### 1.4.0
- **New:** Google Gemini API support for document extraction
- **New:** Provider selection dropdown (POE / Gemini) per field
- **Improved:** Reusable prompt logic shared between API providers

### 1.3.2
- **Improved:** Completed French translations for Document Extraction settings

### 1.3.1
- **Changed:** Removed Composer dependency - php-iban library now bundled in `lib/`
- **Improved:** Simpler installation (no need to run `composer install`)

### 1.3.0
- **New:** Integrated Guilamu Bug Reporter support
- **New:** Added "Report a Bug" link in plugins list

### 1.2.0
- **Security:** Enforced SSL verification for POE API requests
- **Improved:** Updated to use GitHub auto-updates
- **Fixed:** Minor validation issues

### 1.0.0
- Initial release
- IBAN validation field
- POE API integration for document scanning

## Third-Party Libraries

This plugin includes the following third-party library:

### php-iban
- **Source:** [github.com/globalcitizen/php-iban](https://github.com/globalcitizen/php-iban)
- **License:** LGPL-2.1-or-later (see `lib/LICENSE-php-iban`)
- **Purpose:** IBAN validation, parsing, and country data extraction

The php-iban library provides comprehensive IBAN support including validation, checksum verification, and extraction of country, bank, branch, and account information.

## License
This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.

The bundled php-iban library is licensed under LGPL-2.1-or-later, which is compatible with AGPL-3.0.
