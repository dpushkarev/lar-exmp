<?php


namespace App\Services\PlatformRule;


use App\Models\FrontendDomainRule;

/**
 * Class AbstractHandler
 * @package App\Services\PlatformRule
 */
abstract class AbstractHandler
{
    /**
     * @var AbstractHandler
     */
    private $next;

    /**
     * @var FrontendDomainRule
     */
    protected $rule;

    /**
     * AbstractHandler constructor.
     * @param FrontendDomainRule $rule
     */
    public function __construct(FrontendDomainRule $rule)
    {
        $this->rule = $rule;
    }

    public function with(AbstractHandler $next): AbstractHandler
    {
        $this->next = $next;

        return $next;
    }

    public function check($collection)
    {
        if (!$this->next) {
            return true;
        }

        return $this->next->check($collection);
    }
}