<?php

namespace KCore\CoreBundle\Tests\Entity;

use KCore\CoreBundle\Entity\InstitutionDescriptor;

class Test extends \PHPUnit_Framework_TestCase
{
    protected $faker;


    public function setUp()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @return array
     */
    public function addressFieldDataProvider()
    {
        $base = [
            'country' => NULL,
            'street' => NULL,
            'zip' => NULL,
            'locality' => NULL,
        ];

        return [
            [NULL, $base],
            ['street1', array_merge($base, ['street' => 'street1'])],
            ['address', array_merge($base, ['address' => 'address'])],
            ['', array_merge($base, ['address' => ''])],
            [NULL, array_merge($base, ['street' => '  '])],
            ['address', array_merge($base, ['street' => 'street1', 'address' => 'address'])],

            ["street1\nzip1 locality1\ncountry1", array_merge($base, [
                'street' => 'street1',
                'country' => 'country1',
                'locality' => 'locality1',
                'zip' => 'zip1',
            ])],
        ];
    }

    /**
     * @dataProvider addressFieldDataProvider
     */
    public function testAddressField($expected, $data)
    {
        $i = new InstitutionDescriptor($this->faker->uuid);

        $i->setAddressCountry($data['country']);
        $i->setAddressLocality($data['locality']);
        $i->setAddressZip($data['zip']);
        $i->setAddressStreet($data['street']);
        if (array_key_exists('address', $data)) {
            $i->setAddress($data['address']);
        }

        $this->assertSame($expected, $i->getAddress());
    }
}
