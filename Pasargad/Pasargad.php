<?php


namespace App\Helpers\Pasargad;


class Pasargad
{
    public $terminalCode = '***';
    public $merchantCode = '***';
    public $price = 0;
    public $invoiceNumber = 1001;
    public $transactionId;
    public $InvoiceNumber;


    public function pay()
    {
        $amount = $this->price * 10;
        $invoiceNumber = $this->invoiceNumber;
        $this->newTransaction($this->price, $invoiceNumber);

        $processor = new RSAProcessor(public_path('/pasargad/certificate.xml'), RSAKeyType::XMLFile);

        $url = 'https://pep.shaparak.ir/gateway.aspx';
        $redirectUrl = '/pasargad/callback?transactionId=' . $this->transactionId;


        $terminalCode = $this->terminalCode;
        $merchantCode = $this->merchantCode;
        $timeStamp = date("Y/m/d H:i:s");
        $invoiceDate = date("Y/m/d H:i:s");
        $action = 1003;
        $data = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $redirectUrl . "#" . $action . "#" . $timeStamp . "#";
        $data = sha1($data, true);
        $data = $processor->sign($data);
        $sign = base64_encode($data);


        return \View::make('callback.pasargad-redirector')->with(compact('url', 'redirectUrl', 'invoiceNumber', 'invoiceDate', 'amount', 'terminalCode', 'merchantCode', 'timeStamp', 'action', 'sign'));
    }



}