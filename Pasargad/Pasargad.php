<?php


namespace App\Helpers\Pasargad;


use App\Models\Payment;

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


    public function newTransaction($price, $invoiceId)
    {
        $this->transactionId = Payment::insertGetId([
            'port' => 'PASARGAD',
            'price' => $price,
            'status' => 'INIT',
            'ip' => \Request::ip(),
            'user_id' => Auth::id() ?? null,
            'invoice_id' => $invoiceId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $this->transactionId;
    }


    protected function transactionFailed($resultObj)
    {
        Payment::whereId($this->transactionId)->update([
            'status' => 'FAILED',
            'tracking_code' => $resultObj['transactionReferenceID'],
            'updated_at' => Carbon::now(),
        ]);

    }


    protected function transactionSucceed($resultObj)
    {
        Payment::whereId($this->transactionId)->update([
            'status' => 'SUCCEED',
            'tracking_code' => $resultObj['transactionReferenceID'],
            'ref_id' => $resultObj['referenceNumber'],
            'card_number' => $resultObj['cardNumber'] ?? null,
            'payment_date' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }


    protected function newLog($statusCode, $statusMessage)
    {
        return DB::table('gateway_transactions_logs')->insert([
            'transaction_id' => $this->transactionId,
            'result_code' => $statusCode,
            'result_message' => $statusMessage,
            'log_date' => Carbon::now(),
        ]);
    }





}