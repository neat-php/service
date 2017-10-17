<?php namespace Phrodo\Application\Test;

use PHPUnit\Framework\TestCase;
use Phrodo\Application\Configuration;

class ConfigurationTest extends TestCase
{
    /**
     * Test empty configuration
     */
    public function testEmptyConfiguration()
    {
        $config = new Configuration;

        $this->assertFalse($config->has('unknown'));
        $this->assertNull($config->get('unknown'));
        $this->assertSame('whatever', $config->get('unknown', 'whatever'));
    }

    /**
     * Test preset configuration
     */
    public function testPresetConfiguration()
    {
        $config = new Configuration(['theme' => 'dark']);

        $this->assertFalse($config->has('unknown'));
        $this->assertNull($config->get('unknown'));
        $this->assertSame('whatever', $config->get('unknown', 'whatever'));

        $this->assertTrue($config->has('theme'));
        $this->assertSame('dark', $config->get('theme'));
        $this->assertSame('dark', $config->get('theme', 'whatever'));
    }

    /**
     * Test setting configuration
     */
    public function testSettingConfiguration()
    {
        $config = new Configuration;
        $config->set('timezone', 'utc');

        $this->assertTrue($config->has('timezone'));
        $this->assertSame('utc', $config->get('timezone'));
    }
}
