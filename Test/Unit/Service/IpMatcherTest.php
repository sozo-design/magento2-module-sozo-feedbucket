<?php
/*
 * SOZO Design Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    SOZO Design Ltd
 * @package     Sozo_Feedbucket
 * @copyright   Copyright (c) SOZO Design Ltd (https://sozodesign.co.uk)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Sozo\Feedbucket\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Sozo\Feedbucket\Service\IpMatcher;

class IpMatcherTest extends TestCase
{
    /**
     * @var IpMatcher
     */
    private $matcher;

    protected function setUp(): void
    {
        $this->matcher = new IpMatcher();
    }

    public function testParseListHandlesCrlfBlankLinesAndWhitespace(): void
    {
        $list = "1.2.3.4\r\n\r\n  5.6.7.8  \r\n10.0.0.0/8\n\r9.9.9.9\r";

        $this->assertSame(['1.2.3.4', '5.6.7.8', '10.0.0.0/8', '9.9.9.9'], $this->matcher->parseList($list));
    }

    public function testParseListHandlesNull(): void
    {
        $this->assertSame([], $this->matcher->parseList(null));
    }

    public function testEveryLineOfABrowserSubmittedListMatches(): void
    {
        $entries = $this->matcher->parseList("1.2.3.4\r\n5.6.7.8\r\n9.9.9.9");

        $this->assertTrue($this->matcher->isAllowed('1.2.3.4', $entries));
        $this->assertTrue($this->matcher->isAllowed('5.6.7.8', $entries));
        $this->assertTrue($this->matcher->isAllowed('9.9.9.9', $entries));
        $this->assertFalse($this->matcher->isAllowed('1.2.3.5', $entries));
    }

    public function testCidrAfterAPlainAddressIsStillChecked(): void
    {
        $entries = ['1.2.3.4', '10.0.0.0/8', '192.168.1.0/255.255.255.0'];

        $this->assertTrue($this->matcher->isAllowed('10.20.30.40', $entries));
        $this->assertTrue($this->matcher->isAllowed('192.168.1.200', $entries));
        $this->assertFalse($this->matcher->isAllowed('192.168.2.1', $entries));
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatches(string $ip, string $entry, bool $expected): void
    {
        $this->assertSame($expected, $this->matcher->matches($ip, $entry));
    }

    public static function matchProvider(): array
    {
        return [
            'exact' => ['203.0.113.7', '203.0.113.7', true],
            'exact different' => ['203.0.113.8', '203.0.113.7', false],
            'cidr inside' => ['192.168.5.9', '192.168.0.0/16', true],
            'cidr outside' => ['192.169.0.1', '192.168.0.0/16', false],
            'cidr /32' => ['203.0.113.7', '203.0.113.7/32', true],
            'cidr /0' => ['8.8.8.8', '0.0.0.0/0', true],
            'cidr odd prefix inside' => ['10.0.0.200', '10.0.0.128/25', true],
            'cidr odd prefix outside' => ['10.0.0.100', '10.0.0.128/25', false],
            'short cidr' => ['10.1.2.3', '10/8', true],
            'short cidr two parts' => ['10.1.2.3', '10.1/16', true],
            'cidr prefix too big' => ['10.1.2.3', '10.0.0.0/33', false],
            'cidr junk prefix' => ['10.1.2.3', '10.0.0.0/abc', false],
            'dotted mask inside' => ['172.16.4.4', '172.16.0.0/255.255.0.0', true],
            'dotted mask outside' => ['172.17.4.4', '172.16.0.0/255.255.0.0', false],
            'dotted mask wildcard' => ['172.16.4.4', '172.16.0.0/255.255.*.*', true],
            'invalid dotted mask' => ['172.16.4.4', '172.16.0.0/255.0.255.0', false],
            'wildcard inside' => ['192.168.44.1', '192.168.*.*', true],
            'wildcard outside' => ['192.169.44.1', '192.168.*.*', false],
            'range inside' => ['10.0.0.15', '10.0.0.10-10.0.0.20', true],
            'range with spaces' => ['10.0.0.15', '10.0.0.10 - 10.0.0.20', true],
            'range outside' => ['10.0.0.21', '10.0.0.10-10.0.0.20', false],
            'range high addresses' => ['250.1.1.1', '200.0.0.0-255.255.255.255', true],
            'ipv6 exact' => ['2001:db8::1', '2001:0db8:0000::1', true],
            'ipv6 cidr inside' => ['2001:db8:abcd::1', '2001:db8::/32', true],
            'ipv6 cidr outside' => ['2001:db9::1', '2001:db8::/32', false],
            'ipv4 against ipv6 cidr' => ['10.0.0.1', '2001:db8::/32', false],
            'garbage entry' => ['10.0.0.1', 'not an ip', false],
            'empty entry' => ['10.0.0.1', '', false],
        ];
    }

    public function testInvalidOrEmptyAddressIsNeverAllowed(): void
    {
        $this->assertFalse($this->matcher->isAllowed('', ['0.0.0.0/0']));
        $this->assertFalse($this->matcher->isAllowed('unknown', ['0.0.0.0/0', 'unknown']));
    }
}
