<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Services;

use PHPUnit\Framework\TestCase;

/**
 * @covers Format
 */
class FormatTest extends TestCase
{
    public function setUp(): void
    {
        $settings = [
            'code'                           => 'en_GB',
            'name'                           => 'English - United Kingdom',
            'dateFormat'                     => 'dd/mm/yyyy',
            'dateFormatRegEx'                => '/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i',
            'dateFormatPHP'                  => 'd/m/Y',
            'rtl'                            => 'N',
            'currency'                       => 'HKD $',
            'currencySymbol'                 => '$',
            'currencyName'                   => 'HKD',
            'dateFormatGenerate'             => false,
        ];

        // Set the locale for the tests.
        setlocale(
            LC_ALL,
            $settings['code'].'.utf8',
            $settings['code'].'.UTF8',
            $settings['code'].'.utf-8',
            $settings['code'].'.UTF-8',
            $settings['code']
        );

        Format::setup($settings);

        // Verify test environment has correct locale setup.
       $this->assertStringStartsWith($settings['code'], setlocale(LC_TIME, 0), 'Test environment has correct locale setup');
    }

    public function testFormatsDates()
    {
        $this->assertEquals('18/05/2018', Format::date('2018-05-18'));
    }

    public function testFormatsDatesFromTimestamp()
    {
        $this->assertEquals('18/05/2018', Format::date('2018-05-18 09:00:15'));
    }

    public function testFormatsDatesWithOptionalFormat()
    {
        $this->assertEquals('Sunday April 1st 2018', Format::date('2018-04-01', 'l F jS Y'));
    }

    public function testFormatsAmericanDates()
    {
        Format::setup(['dateFormatPHP' => 'm/d/Y']);
        $this->assertEquals('05/18/2018', Format::date('2018-05-18'));
    }

    public function testFormatsDateTime()
    {
        $this->assertEquals('18/05/2018 17:16', Format::dateTime('2018-05-18 17:16:18'));
    }

    public function testFormatsDateTimeWithFormat()
    {
        $this->assertEquals('May 18, 2018 5:16 pm', Format::dateTime('2018-05-18 17:16:18', 'F j, Y g:i a'));
    }

    public function testFormatsReadableDates()
    {
        

        $dateString = '2018-02-03 13:24';

        // Verify fidelity of formatting output using default en_GB locale

        $this->assertEquals('18 May 2018', Format::dateReadable('2018-05-18'));
        $this->assertEquals('18 May 2018, 13:24', Format::dateTimeReadable('2018-05-18 13:24'));
        $this->assertEquals('Saturday, 3 February 2018', Format::dateReadable($dateString, Format::FULL));
        $this->assertEquals('Saturday, 3 February', Format::dateReadable($dateString, Format::FULL_NO_YEAR));
        $this->assertEquals('3 February 2018', Format::dateReadable($dateString, Format::LONG));
        $this->assertEquals('3 Feb 2018', Format::dateReadable($dateString, Format::MEDIUM));
        $this->assertEquals('3 Feb', Format::dateReadable($dateString, Format::MEDIUM_NO_YEAR));
        $this->assertEquals('03', Format::date($dateString, 'd'));
        $this->assertEquals('Saturday', Format::dayOfWeekName($dateString));
        $this->assertEquals('Sat', Format::dayOfWeekName($dateString, true));
        $this->assertEquals('February', Format::monthName($dateString));
        $this->assertEquals('Feb', Format::monthName($dateString, true));
        $this->assertEquals('3 Feb 2018, 13:24', Format::dateTimeReadable($dateString));
        $this->assertEquals('3 February 2018 At 13:24', Format::dateReadable($dateString, Format::LONG, Format::SHORT));
        $this->assertEquals('13:24', Format::dateReadable($dateString, Format::NONE, Format::SHORT));

        // Verify fidelity of formatting output using en_US locale

        Format::setup(['code' => 'en_US']);

        $this->assertEquals('May 18, 2018', Format::dateReadable('2018-05-18'));
        $this->assertEquals('May 18, 2018, 1:24 Pm', Format::dateTimeReadable('2018-05-18 13:24'));
        $this->assertEquals('Saturday, February 3, 2018', Format::dateReadable($dateString, Format::FULL));
        $this->assertEquals('Saturday, February 3', Format::dateReadable($dateString, Format::FULL_NO_YEAR));
        $this->assertEquals('February 3, 2018', Format::dateReadable($dateString, Format::LONG));
        $this->assertEquals('Feb 3, 2018', Format::dateReadable($dateString, Format::MEDIUM));
        $this->assertEquals('Feb 3', Format::dateReadable($dateString, Format::MEDIUM_NO_YEAR));
        $this->assertEquals('03', Format::date($dateString, 'd'));
        $this->assertEquals('Saturday', Format::dayOfWeekName($dateString));
        $this->assertEquals('Sat', Format::dayOfWeekName($dateString, true));
        $this->assertEquals('February', Format::monthName($dateString));
        $this->assertEquals('Feb', Format::monthName($dateString, true));
        $this->assertEquals('Feb 3, 2018, 1:24 Pm', Format::dateTimeReadable($dateString));
        $this->assertEquals('February 3, 2018 At 1:24 Pm', Format::dateReadable($dateString, Format::LONG, Format::SHORT));
        $this->assertEquals('1:24 Pm', Format::dateReadable($dateString, Format::NONE, Format::SHORT));

        // Verify fidelity of formatting output using generic fallbacks 

        Format::$intlFormatterAvailable = false;

        $this->assertEquals('May 18 2018', Format::dateReadable('2018-05-18'));
        $this->assertEquals('May 18 2018, 13:24', Format::dateTimeReadable('2018-05-18 13:24'));
        $this->assertEquals('Saturday, February 3 2018', Format::dateReadable($dateString, Format::FULL));
        $this->assertEquals('Saturday, February 3', Format::dateReadable($dateString, Format::FULL_NO_YEAR));
        $this->assertEquals('February 3 2018', Format::dateReadable($dateString, Format::LONG));
        $this->assertEquals('Feb 3 2018', Format::dateReadable($dateString, Format::MEDIUM));
        $this->assertEquals('Feb 3', Format::dateReadable($dateString, Format::MEDIUM_NO_YEAR));
        $this->assertEquals('03', Format::date($dateString, 'd'));
        $this->assertEquals('Saturday', Format::dayOfWeekName($dateString));
        $this->assertEquals('Sat', Format::dayOfWeekName($dateString, true));
        $this->assertEquals('February', Format::monthName($dateString));
        $this->assertEquals('Feb', Format::monthName($dateString, true));
        $this->assertEquals('Feb 3 2018, 13:24', Format::dateTimeReadable($dateString));
        $this->assertEquals('February 3 2018, 13:24', Format::dateReadable($dateString, Format::LONG, Format::SHORT));
        $this->assertEquals('13:24', Format::dateReadable($dateString, Format::NONE, Format::SHORT));
        
    }

    public function testFormatsDateRanges()
    {
        $this->assertEquals('18/05/2018 - 18/06/2018', Format::dateRange('2018-05-18', '2018-06-18'));
    }

    public function testFormatsDateFromTimestamps()
    {
        $this->assertEquals('18/05/2018', Format::dateFromTimestamp('1526615872'));
    }

    public function testFormatsUnixTimestamps()
    {
        $this->assertEquals(1526601600, Format::timestamp('2018-05-18', new \DateTimeZone('UTC')));
    }

    public function testFormatsUnixTimestampsFromMysqlTimestamps()
    {
        $this->assertEquals(1526635815, Format::timestamp('2018-05-18 09:30:15', new \DateTimeZone('UTC')));
    }

    public function testFormatsTimes()
    {
        $this->assertEquals('09:30', Format::time('09:30:15'));
    }

    public function testFormatsTimesFromTimestamp()
    {
        $this->assertEquals('09:30', Format::time('2018-05-18 09:30:15'));
    }

    public function testFormatsTimesWithFormat()
    {
        $this->assertEquals('9:30 am', Format::time('09:30:15', 'g:i a'));
    }

    public function testFormatsTimeRanges()
    {
        $this->assertEquals('9:30 am - 1:45 pm', Format::timeRange('09:30:15', '13:45:42', 'g:i a'));
    }

    public function testFormatsNumbers()
    {
        $this->assertEquals('123', Format::number(123));
        $this->assertEquals('123.00', Format::number(123, 2));
    }

    public function testFormatsCurrency()
    {
        $this->assertEquals('$321.00', Format::currency(321));
    }

    public function testFormatsCurrencyWithName()
    {
        $this->assertEquals('$321.00 (HKD)', Format::currency(321, true));
    }

    public function testFormatsAlternateCurrency()
    {
        Format::setup(['currencySymbol' => '£']);
        $this->assertEquals('£321.00', Format::currency(321));
    }

    public function testFormatsYesNo()
    {
        $this->assertEquals('Yes', Format::yesNo('Y'));
        $this->assertEquals('No', Format::yesNo('N'));
        $this->assertEquals('No', Format::yesNo('Invalid'));
    }

    public function testFormatsAge()
    {
        $date = date('Y-m-d', strtotime('-12 years -6 months -15 day'));
        $this->assertEquals('12 years, 6 months', Format::age($date));
    }

    public function testFormatsShortAge()
    {
        $date = date('Y-m-d', strtotime('-24 years -0 months -15 day'));
        $this->assertEquals('24y, 0m', Format::age($date, true));
    }

    public function testFormatsUnknownAge()
    {
        $this->assertEquals('Unknown', Format::age('foo bar'));
    }

    public function testFormatsPhoneNumbers()
    {
        $this->assertEquals('Work: +852 1234 5678', Format::phone('12345678', '852', 'Work'));
    }

    public function testFormatsSevenDigitPhoneNumbers()
    {
        $this->assertEquals('123 4567', Format::phone('1234567'));
    }

    public function testFormatsEightDigitPhoneNumbers()
    {
        $this->assertEquals('1234 5678', Format::phone('12345678'));
    }

    public function testFormatsNineDigitPhoneNumbers()
    {
        $this->assertEquals('123 - 45 67 89', Format::phone('123456789'));
    }

    public function testFormatsTenDigitPhoneNumbers()
    {
        $this->assertEquals('(123) 456 7890', Format::phone('1234567890'));
    }

    public function testFormatsPhoneNumbersNumerically()
    {
        $this->assertEquals('1234 5678', Format::phone('+1 (234) 5678 Foo Bar'));
    }

    public function testFormatsCourseClassNames()
    {
        $this->assertEquals('Foo.1-2', Format::courseClassName('Foo', '1-2'));
    }

    public function testFormatsStudentNames()
    {
        $this->assertEquals('Test McTest', Format::name('', 'Test', 'McTest', 'Student'));
    }

    public function testFormatsStudentNamesReversed()
    {
        $this->assertEquals('McTest, Test', Format::name('', 'Test', 'McTest', 'Student', true));
    }

    public function testFormatsParentNames()
    {
        $this->assertEquals('Ms. Test McTest', Format::name('Ms.', 'Test', 'McTest', 'Parent'));
    }

    public function testFormatsParentNamesReversed()
    {
        $this->assertEquals('Ms. McTest, Test', Format::name('Ms.', 'Test', 'McTest', 'Parent', true));
    }

    public function testFormatsParentNamesInformal()
    {
        $this->assertEquals('Test McTest', Format::name('Ms.', 'Test', 'McTest', 'Parent', false, true));
    }

    public function testFormatsParentNamesReversedInformal()
    {
        $this->assertEquals('McTest, Test', Format::name('Ms.', 'Test', 'McTest', 'Parent', true, true));
    }

    public function testFormatsStaffNamesBySetting()
    {
        Format::setup(['nameFormatStaffFormal' => '[title] [preferredName:1]. [surname]']);
        $this->assertEquals('Mr. T. McTest', Format::name('Mr.', 'Test', 'McTest', 'Staff'));

        Format::setup(['nameFormatStaffFormal' => '[title] [surname]']);
        $this->assertEquals('Mr. McTest', Format::name('Mr.', 'Test', 'McTest', 'Staff'));
    }

    public function testFormatsStaffNamesReversedBySetting()
    {
        Format::setup(['nameFormatStaffFormalReversed' => '[title] [surname], [preferredName:1].']);
        $this->assertEquals('Mr. McTest, T.', Format::name('Mr.', 'Test', 'McTest', 'Staff', true));

        Format::setup(['nameFormatStaffFormalReversed' => '[title] [surname], [preferredName]']);
        $this->assertEquals('Ms. McTest, Test', Format::name('Ms.', 'Test', 'McTest', 'Staff', true));
    }

    public function testFormatsStaffNamesInformalBySetting()
    {
        Format::setup(['nameFormatStaffInformal' => '[preferredName] [surname]']);
        $this->assertEquals('Test McTest', Format::name('Mr.', 'Test', 'McTest', 'Staff', false, true));

        Format::setup(['nameFormatStaffInformalReversed' => '[surname], [preferredName]']);
        $this->assertEquals('McTest, Test', Format::name('Mr.', 'Test', 'McTest', 'Staff', true, true));
    }
}
