<?php

namespace Xsolla\SDK\Invoicing;

use Xsolla\SDK\Exception\InternalServerException;
use Xsolla\SDK\Exception\InvalidArgumentException;
use Xsolla\SDK\Exception\SecurityException;
use Xsolla\SDK\Invoice;
use Xsolla\SDK\User;
use Guzzle\Http\Client;
use Xsolla\SDK\Storage\ProjectInterface;
use Xsolla\SDK\Validator\Xsd;

class MobilePayment
{
    protected $xsd_path_calculate = '/../../resources/schema/mobilepayment/calculate.xsd';
    protected $xsd_path_invoice = '/../../resources/schema/mobilepayment/invoice.xsd';

    protected $client;
    protected $project;
    protected $url = 'mobile/payment/index.php';

    public function __construct(Client $client, ProjectInterface $project)
    {
        $this->client = $client;
        $this->project = $project;
    }

    public function createInvoice(User $user, Invoice $invoice)
    {
        $email = $user->getEmail();
        $queryParams = array(
            'command' => 'invoice',
            'project' => $this->project->getProjectId(),
            'v1' => $user->getV1(),
            'v2' => $user->getV2(),
            'v3' => $user->getV3(),
            'sum' => $invoice->getSum(),
            'out' => $invoice->getOut(),
            'phone' => $user->getPhone(),
            'userip' => $user->getUserIP()
        );

        if (!empty($email)) {
            $queryParams['email'] = $email;
        }

        $result = $this->send($queryParams, __DIR__ . $this->xsd_path_invoice);

        $this->checkCodeResult($result);

        return new Invoice(null, null, null, (string) $result->invoice);

    }

    public function calculate(User $user, Invoice $invoice)
    {
        $queryParams = array(
            'command' => 'calculate',
            'project' => $this->project->getProjectId(),
            'phone' => $user->getPhone()
        );

        $userSum = $invoice->getSum();
        if (!empty($userSum)) {
            $queryParams['sum'] = $userSum;
        } else {
            $queryParams['out'] = $invoice->getOut();
        }

        $result = $this->send($queryParams, __DIR__ . $this->xsd_path_calculate);

        $this->checkCodeResult($result);

        return new Invoice((string) $result->out, (string) $result->sum);
    }

    protected function createSignString(array $params)
    {
        $signString = '';
        foreach ($params as $value) {
            $signString .= $value;
        }

        return $signString;
    }

    protected function send(array $queryParams, $schemaFilename)
    {
        $signString = $this->createSignString($queryParams);
        $queryParams['md5'] = md5($signString . $this->project->getSecretKey());
        $request = $this->client->get($this->url, array(), array('query' => $queryParams));

        $xsollaResponse = $request->send()->getBody();
        (new Xsd())->check($xsollaResponse, $schemaFilename);
        $result = new \SimpleXMLElement($xsollaResponse);

        return $result;
    }

    protected function checkCodeResult($result)
    {
        if ($result->result == 3) {
            throw new SecurityException((string) $result->comment, (int) $result->result);
        } elseif ($result->result == 1) {
            throw new InternalServerException((string) $result->comment, (int) $result->result);
        } elseif ($result->result != 0) {
            throw new InvalidArgumentException((string) $result->comment, (int) $result->result);
        }
    }
}
