<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 27/01/2015
 * Time: 12:33
 */
namespace KCore\InstitutionAPIBundle\Entity;

use KCore\CoreBundle\Entity\InstitutionDescriptor;

class InstitutionObjectForVoter
{

    protected $institution;

    public function __construct($institution)
    {
        $this->institution = $institution;
    }

    /**
     * @return InstitutionDescriptor|null
     */
    public function getInstitution()
    {
        return $this->institution;
    }
}
