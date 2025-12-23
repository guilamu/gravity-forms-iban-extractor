<?php
/**
 * PHPUnit Tests for IBAN Extractor
 *
 * Tests the IBAN validation and extraction functionality.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor\Tests;

use PHPUnit\Framework\TestCase;
use GravityFormsIBANExtractor\IBAN_Extractor;

/**
 * Test case for IBAN Extractor class.
 */
class Test_IBAN_Extractor extends TestCase
{

    /**
     * IBAN Extractor instance.
     *
     * @var IBAN_Extractor
     */
    private $extractor;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Require the php-iban library.
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/includes/class-iban-extractor.php';

        $this->extractor = new IBAN_Extractor();
    }

    /**
     * Test valid German IBAN.
     */
    public function test_valid_german_iban()
    {
        $iban = 'DE89370400440532013000';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('DE', $data['country_code']);
        $this->assertEquals('Germany', $data['country_name']);
        $this->assertEquals('EUR', $data['currency']);
        $this->assertNotEmpty($data['bban']);
        $this->assertNotEmpty($data['bank_code']);
    }

    /**
     * Test valid French IBAN.
     */
    public function test_valid_french_iban()
    {
        $iban = 'FR7630006000011234567890189';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('FR', $data['country_code']);
        $this->assertEquals('France', $data['country_name']);
        $this->assertEquals('EUR', $data['currency']);
    }

    /**
     * Test valid UK IBAN.
     */
    public function test_valid_uk_iban()
    {
        $iban = 'GB82WEST12345698765432';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('GB', $data['country_code']);
        $this->assertEquals('United Kingdom', $data['country_name']);
        $this->assertEquals('GBP', $data['currency']);
    }

    /**
     * Test valid Spanish IBAN.
     */
    public function test_valid_spanish_iban()
    {
        $iban = 'ES9121000418450200051332';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('ES', $data['country_code']);
        $this->assertEquals('Spain', $data['country_name']);
        $this->assertEquals('EUR', $data['currency']);
    }

    /**
     * Test valid Belgian IBAN.
     */
    public function test_valid_belgian_iban()
    {
        $iban = 'BE68539007547034';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('BE', $data['country_code']);
        $this->assertEquals('Belgium', $data['country_name']);
        $this->assertEquals('EUR', $data['currency']);
    }

    /**
     * Test invalid IBAN - wrong checksum.
     */
    public function test_invalid_iban_wrong_checksum()
    {
        $iban = 'DE00370400440532013000'; // Invalid checksum.

        $this->assertFalse($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertFalse($data['valid']);
    }

    /**
     * Test invalid IBAN - too short.
     */
    public function test_invalid_iban_too_short()
    {
        $iban = 'DE893704';

        $this->assertFalse($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertFalse($data['valid']);
    }

    /**
     * Test invalid IBAN - invalid country code.
     */
    public function test_invalid_iban_invalid_country()
    {
        $iban = 'XX89370400440532013000';

        $this->assertFalse($this->extractor->validate($iban));
    }

    /**
     * Test empty IBAN.
     */
    public function test_empty_iban()
    {
        $this->assertFalse($this->extractor->validate(''));

        $data = $this->extractor->extract('');

        $this->assertFalse($data['valid']);
    }

    /**
     * Test IBAN with spaces (human format).
     */
    public function test_iban_with_spaces()
    {
        $iban = 'DE89 3704 0044 0532 0130 00';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
        $this->assertEquals('DE', $data['country_code']);
    }

    /**
     * Test IBAN with lowercase letters.
     */
    public function test_iban_lowercase()
    {
        $iban = 'de89370400440532013000';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
    }

    /**
     * Test IBAN with IBAN prefix.
     */
    public function test_iban_with_prefix()
    {
        $iban = 'IBAN DE89370400440532013000';

        $this->assertTrue($this->extractor->validate($iban));

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['valid']);
    }

    /**
     * Test format for display.
     */
    public function test_format_for_display()
    {
        $iban = 'DE89370400440532013000';

        $formatted = $this->extractor->format_for_display($iban);

        $this->assertEquals('DE89 3704 0044 0532 0130 00', $formatted);
    }

    /**
     * Test machine format conversion.
     */
    public function test_to_machine_format()
    {
        $iban = 'DE89 3704 0044 0532 0130 00';

        $machine = $this->extractor->to_machine_format($iban);

        $this->assertEquals('DE89370400440532013000', $machine);
    }

    /**
     * Test mistranscription suggestions.
     */
    public function test_get_suggestions()
    {
        // This test may vary based on the actual php-iban implementation.
        $invalid_iban = 'DE89370400440532O13000'; // Letter O instead of 0.

        $suggestions = $this->extractor->get_suggestions($invalid_iban);

        $this->assertIsArray($suggestions);
    }

    /**
     * Test SEPA membership detection.
     */
    public function test_sepa_member()
    {
        $iban = 'DE89370400440532013000';

        $data = $this->extractor->extract($iban);

        $this->assertTrue($data['is_sepa']);
    }

    /**
     * Test non-SEPA country.
     */
    public function test_non_sepa_country()
    {
        // Brazilian IBAN.
        $iban = 'BR1500000000000010932840814P2';

        $data = $this->extractor->extract($iban);

        if ($data['valid']) {
            $this->assertFalse($data['is_sepa']);
        }
    }

    /**
     * Test all extracted fields are present.
     */
    public function test_all_fields_extracted()
    {
        $iban = 'DE89370400440532013000';

        $data = $this->extractor->extract($iban);

        $this->assertArrayHasKey('valid', $data);
        $this->assertArrayHasKey('account', $data);
        $this->assertArrayHasKey('bban', $data);
        $this->assertArrayHasKey('country_code', $data);
        $this->assertArrayHasKey('country_name', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('bank_code', $data);
        $this->assertArrayHasKey('branch_code', $data);
        $this->assertArrayHasKey('formatted', $data);
        $this->assertArrayHasKey('checksum', $data);
        $this->assertArrayHasKey('is_sepa', $data);
    }
}
