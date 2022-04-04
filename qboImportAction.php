<?php

class BankReconciliationController {
	 public function importBankStatementQbo() {
		// Accept Request Input File
        $input = \Illuminate\Support\Facades\Input::all();
        try{
		// Get file after upload
        $file = public_path() . '/path/' . $input['qbo'];
		// Open file and get its content
        $cont = file_get_contents(public_path() . '/ImportTemplates/uploads/' . $input['qbo']);
		
		// Finding repeated tag, remove everything before and after the tag <STMTTRN> 
        $cc = strstr($cont, '<STMTTRN>');
        $result = substr($cc, 0, strrpos($cc, '</BANKTRANLIST>'));
		
		// Creating the transactions array by exploding the content by the repeated tag
        $qbo_array = explode("<STMTTRN>", $result);
      
        for ($i = 1; $i < count($qbo_array); $i++) {
            $qbo_array[$i] = str_replace("\r\n", "", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<TRNTYPE>", "", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<DTPOSTED>", ",", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<TRNAMT>", ",", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<FITID>", ",", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<NAME>", ",", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("<MEMO>", ",", $qbo_array[$i]);
            $qbo_array[$i] = str_replace("</STMTTRN>", "", $qbo_array[$i]);
            $qbo_array[$i] = explode(",", utf8_encode($qbo_array[$i]));
        }
		// Specify each value and insert it into database	
        for ($j = 1; $j < count($qbo_array); $j++) {
            $tr_date_year = substr($qbo_array[$j][1], 0, 4);
            $tr_date_month = substr($qbo_array[$j][1], 4, 2);
            $tr_date_day = substr($qbo_array[$j][1], 6, 2);
            $tr_date = $tr_date_year . '-' . $tr_date_month . '-' . $tr_date_day;
            if ($qbo_array[$j][0] == "DEBIT") {
                $deposit = 0;
                $withdraw = $qbo_array[$j][2];
            } else {
                $deposit = $qbo_array[$j][2];
                $withdraw = 0;
            }
            \App\DBTable::insert(
                    [   'tr_date' => $tr_date,
                        'tr_value_date' => $tr_date,
                        'tr_description' => $qbo_array[$j][4],
                        'deposit' => abs($deposit),
                        'withdraw' => abs($withdraw),
                        'balance' => abs($qbo_array[$j][2]),
                        'company_id' => \Session::get('company_id'),
                        'status' => 0,
                        'ref_id' => $ref_id + 1]);
        }
        }catch(\Exception $e){
            unlink($file);
            return 0;
        }
        unlink($file);
        return 1;
    }
	
}