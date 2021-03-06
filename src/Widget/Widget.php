<?php

namespace Xsolla\SDK\Widget;

use Xsolla\SDK\Storage\ProjectInterface;
use Xsolla\SDK\User;
use Xsolla\SDK\Invoice;
use Xsolla\SDK\Exception\InvalidArgumentException;

abstract class Widget implements WidgetInterface
{

    protected $project;
    protected $baseUrl = 'https://secure.xsolla.com/paystation2/?';

    public function __construct(ProjectInterface $project)
    {
        $this->project = $project;
    }

    public function getLink(User $user, Invoice $invoice, array $params)
    {
        $params = array_merge($params, $this->getDefaultParams());
        $params['marketplace'] = $this->getMarketplace();
        $params['project'] = $this->project->getProjectId();
        $params['v1'] = $user->getV1();
        $params['v2'] = $user->getV2();;
        $params['v3'] = $user->getV3();
        $params['email'] = $user->getEmail();
        $params['userip'] = $user->getUserIP();
        $params['phone'] = $user->getPhone();
        $params['out'] = $invoice->getOut();
        $params['currency'] = $invoice->getCurrency();

        foreach ($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }
        $this->checkRequiredParams($params);
        $params['sign'] = $this->generateSign($params);

        return $this->baseUrl.http_build_query($params);
    }

    private function signParamList()
    {
        return array('theme', 'project', 'signparams', 'v0', 'v1', 'v2', 'v3', 'out', 'email', 'currency', 'userip',
            'allowSubscription', 'fastcheckout'
        );
    }

    private function generateSign($params = array())
    {
        $keys = $this->signParamList();
        sort($keys);

        $sign = '';
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $sign .= $key . '=' . $params[$key];
            }
        }

        $key = $this->project->getSecretKey();

        return md5($sign . $key);
    }

    private function checkRequiredParams($params = array())
    {
        $requiredParams = $this->getRequiredParams();

        foreach ($requiredParams as $key) {
            if (!isset($params[$key])) {
                throw new InvalidArgumentException(sprintf('Parameter %s is not defined',$key));
            }
        }

        return true;
    }

    abstract public function getMarketplace();

    abstract public function getRequiredParams();

    protected function getDefaultParams()
    {
        return array();
    }
}
