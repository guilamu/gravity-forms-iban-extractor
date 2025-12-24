# Gravity Forms IBAN Extractor

A WordPress plugin that adds an IBAN (International Bank Account Number) extractor field type to Gravity Forms. Validates IBANs locally using the [php-iban](https://github.com/globalcitizen/php-iban) library and extracts detailed bank information in real-time. Optionally extract IBANs from scanned documents using AI.

## Features

- **Real-time IBAN Validation**: Validates IBANs as users type with debounced input (300ms)
- **Data Extraction**: Extracts and displays account number, BBAN, country, currency, bank code, and more
- **Document Scanning**: Extract IBAN from PDF, PNG, JPG, or WEBP documents using AI (POE API)
- **Zero External APIs for Validation**: All IBAN validation happens locally using the php-iban library
- **Configurable Display**: Toggle which extracted fields to show in the form editor
- **Error Suggestions**: Provides mistranscription suggestions for invalid IBANs
- **Auto-formatting**: Formats IBAN with spaces on blur for human readability
- **Multilingual**: Full French and English translation support
- **Accessible**: ARIA labels, keyboard navigation, and high contrast support
- **Responsive**: Mobile-friendly styling with dark mode support

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Gravity Forms 2.5 or higher
- Composer (for installation)
- POE API key (optional, for document extraction)

## Installation

1. **Download the plugin** and extract to `wp-content/plugins/gravity-forms-iban-extractor/`

2. **Install PHP dependencies**:
   ```bash
   cd wp-content/plugins/gravity-forms-iban-extractor
   composer install
   ```

3. **Activate the plugin** in WordPress Admin → Plugins

## Usage

### Adding the Field

1. Edit any Gravity Form
2. In the form editor, locate "IBAN Extractor" under **Advanced Fields**
3. Drag the field into your form
4. Configure display options in the field settings

### Field Settings

| Setting | Description |
|---------|-------------|
| Show Account No. | Display the account number portion |
| Show BBAN | Display the Basic Bank Account Number |
| Show Country Currency | Display the ISO 4217 currency code |
| Show Country Name | Display the full country name |
| Show BIC/Bank Code | Display the bank identifier code |
| Show Bank Info | Display additional bank information |
| Enable Real-time Preview | Validate and show data as user types |
| Enable Document Extraction | Allow users to upload documents to extract IBAN |
| POE API Key | Your POE API key for document extraction |
| AI Model | Select the AI model for document analysis |

### Document Extraction

When enabled, users can upload documents (bank statements, RIBs, etc.) to automatically extract IBAN information:

1. Click "Browse" or drag a file onto the upload area
2. Supported formats: PDF, PNG, JPG, WEBP (max 10MB)
3. The AI analyzes the document and extracts:
   - IBAN
   - BIC/SWIFT code
   - Bank name
   - Account holder name
4. The IBAN field is auto-populated with the extracted value
5. Additional extracted data is displayed in a table below

**Note**: Document extraction requires a valid [POE API](https://poe.com/) key and an AI model with image input capability.

### Example Output

When a user enters `DE89370400440532013000`:

```
Country: Germany
Currency: EUR
Bank Code: 37040044
Account No.: 0532013000
BBAN: 370400440532013000
```

## Merge Tags

Use these modifiers with the field merge tag `{IBAN Extractor:1}`:

| Modifier | Output |
|----------|--------|
| `:formatted` | DE89 3704 0044 0532 0130 00 |
| `:country` | Germany |
| `:currency` | EUR |
| `:bank` | 37040044 |
| `:account` | 0532013000 |
| `:bban` | 370400440532013000 |

## Entry Meta

Extracted data is automatically stored in entry meta with these keys:

- `iban_{field_id}_country`
- `iban_{field_id}_currency`
- `iban_{field_id}_bank_code`
- `iban_{field_id}_branch_code`
- `iban_{field_id}_account`
- `iban_{field_id}_bban`
- `iban_{field_id}_formatted`
- `iban_{field_id}_is_sepa`

Document extraction data is stored in a separate database table (`{prefix}gf_iban_entry_extraction`) and displayed in the entry detail view.

## Supported Countries

The plugin supports 100+ countries including all SEPA member countries. See the [php-iban documentation](https://github.com/globalcitizen/php-iban#countries-supported) for the complete list.

## Translation

Translation files are located in the `languages/` directory:

- `gravity-forms-iban-extractor.pot` - Template file
- `gravity-forms-iban-extractor-fr_FR.po` - French
- `gravity-forms-iban-extractor-en_US.po` - English

To generate .mo files from .po files:

```bash
cd languages/
msgfmt gravity-forms-iban-extractor-fr_FR.po -o gravity-forms-iban-extractor-fr_FR.mo
msgfmt gravity-forms-iban-extractor-en_US.po -o gravity-forms-iban-extractor-en_US.mo
```

Or use [WP-CLI](https://developer.wordpress.org/cli/commands/i18n/):

```bash
wp i18n make-mo languages/
```

## Running Tests

```bash
cd gravity-forms-iban-extractor
composer install
./vendor/bin/phpunit tests/
```

## File Structure

```
gravity-forms-iban-extractor/
├── composer.json
├── gravity-forms-iban-extractor.php
├── README.md
├── includes/
│   ├── class-gf-field-iban-extractor.php
│   ├── class-iban-extractor.php
│   ├── class-poe-api-service.php
│   └── admin-settings.php
├── assets/
│   ├── js/
│   │   └── iban-extractor.js
│   └── css/
│       └── iban-extractor.css
├── languages/
│   ├── gravity-forms-iban-extractor.pot
│   ├── gravity-forms-iban-extractor-fr_FR.po
│   └── gravity-forms-iban-extractor-en_US.po
└── tests/
    ├── bootstrap.php
    ├── phpunit.xml
    └── test-iban-extractor.php
```

## Hooks & Filters

### Actions

None currently exposed.

### Filters

The plugin uses standard Gravity Forms filters:

- `gform_field_validation` - Server-side IBAN validation
- `gform_tooltips` - Custom tooltip content

## Security

- All inputs are sanitized with `sanitize_text_field()`
- AJAX endpoints use nonce verification
- Output is escaped with appropriate WordPress functions
- POE API key is stored securely in field settings

## License

AGPL-3.0-or-later

## Credits

- [php-iban](https://github.com/globalcitizen/php-iban) by Global Citizen
- [Gravity Forms](https://www.gravityforms.com/)
- [POE API](https://poe.com/) for document extraction

## Changelog

### 1.1.0
- Added document extraction feature using POE AI API
- Extract IBAN from PDF, PNG, JPG, WEBP documents
- Side-by-side layout for IBAN input and document upload
- Entry detail view shows extracted bank information
- New field settings for POE API configuration

### 1.0.0
- Initial release
- IBAN validation and extraction
- Real-time preview
- French/English translations
- PHPUnit tests

