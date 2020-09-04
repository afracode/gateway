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


    public function callback(Request $request)
    {
        $iD = $request->get('iD');
        $tref = $request->get('tref');
        $this->InvoiceNumber = $request->get('iN');


        $this->transactionId = $request->get('transactionId');
        $result = $this->getPaymentResult($tref);


        if ($result) {
            $verify = $this->verifyPaymentResult($this->InvoiceNumber, $iD);
            if ($verify['actionResult']['result'] == "True") {
                $this->transactionSucceed($result['resultObj']);
                echo "پرداخت شما با موفقیت انجام شد, کد پیگیری : {$tref}";
            } else {
                echo "خطا : " . $verify['actionResult']['resultMessage'];
            }
        } else {
            echo "پرداخت ناموفق بود.";
        }


    }


    public function getPaymentResult($tref = null)
    {

        $fields = ['invoiceUID' => $tref];

        $result = Parser::post2https($fields, 'https://pep.shaparak.ir/CheckTransactionResult.aspx');


        $array = Parser::makeXMLTree($result);


        if (isset($array["resultObj"]) && $array["resultObj"]["result"] == "True")
            return $array;

        $this->transactionFailed($array["resultObj"]);
        return false;

    }


    public function verifyPaymentResult($InvoiceNumber, $iD)
    {
        $fields = [
            'MerchantCode' => $this->merchantCode,
            'TerminalCode' => $this->terminalCode,
            'InvoiceNumber' => $InvoiceNumber,
            'InvoiceDate' => $iD,
            'amount' => $this->price * 10,
            'TimeStamp' => date("Y/m/d H:i:s"),
            'sign' => ''
        ];

        $processor = new RSAProcessor(public_path('certificate.xml'), RSAKeyType::XMLFile);
        $data = "#" . $fields['MerchantCode'] . "#" . $fields['TerminalCode'] . "#" . $fields['InvoiceNumber'] . "#" . $fields['InvoiceDate'] . "#" . $fields['amount'] . "#" . $fields['TimeStamp'] . "#";
        $data = sha1($data, true);
        $data = $processor->sign($data);
        $fields['sign'] = base64_encode($data);

        $verifyresult = Parser::post2https($fields, 'https://pep.shaparak.ir/VerifyPayment.aspx');
        $array = Parser::makeXMLTree($verifyresult);

        return $array;
    }


}