<?php

use Symfony\Component\HttpFoundation\Request;
use Xsolla\SDK\Protocol\Command\Factory as CommandFactory;
use Xsolla\SDK\Protocol\Standard;
use Xsolla\SDK\Response\Xml;
use Xsolla\SDK\Security;
use Xsolla\SDK\Storage\PaymentsStandard;
use Xsolla\SDK\Storage\Project;
use Xsolla\SDK\Storage\Users;

require_once __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();

$protocol = new Standard(new Security(), new CommandFactory(), new Project(), new Users(), new PaymentsStandard());

$response = (new Xml())->get($protocol->getResponse($request));
$response->send();
